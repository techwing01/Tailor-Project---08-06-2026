<?php
/**
 * TailorMate - Edit Customer
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('view_customers.php', 'error', 'Invalid customer ID.');
}

$stmt = $db->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect('view_customers.php', 'error', 'Customer not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirect('edit_customer.php?id=' . $id, 'error', 'Invalid request.');
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $errors = [];
    if (empty($name)) $errors[] = 'Name is required.';
    if (!validatePhone($phone)) $errors[] = 'Enter a valid 10-digit phone number.';
    if (!empty($email) && !validateEmail($email)) $errors[] = 'Enter a valid email.';

    if (empty($errors)) {
        try {
            $stmt = $db->prepare('UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?');
            $stmt->execute([$name, $phone, $email ?: null, $address ?: null, $id]);
            redirect('view_customer.php?id=' . $id, 'success', 'Customer updated successfully.');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                setFlash('error', 'Phone number already exists for another customer.');
            } else {
                setFlash('error', 'Error updating customer.');
            }
        }
    } else {
        setFlash('error', implode('<br>', $errors));
    }
}

$pageTitle = 'Edit Customer';
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>Edit Customer: <?= e($customer['customer_id']) ?></h2>
    <a href="view_customer.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card p-4" style="max-width: 520px;">
    <form method="POST" action="" novalidate>
        <?php csrfField(); ?>
        <div class="mb-3">
            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name"
                   value="<?= e($_POST['name'] ?? $customer['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" id="phone" name="phone"
                   value="<?= e($_POST['phone'] ?? $customer['phone']) ?>"
                   pattern="[6-9][0-9]{9}" maxlength="10" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= e($_POST['email'] ?? $customer['email']) ?>">
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control" id="address" name="address" rows="3"><?= e($_POST['address'] ?? $customer['address']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Customer</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
