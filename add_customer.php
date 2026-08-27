<?php
/**
 * TailorMate - Add Customer
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$customer_id = generateUniqueID('CUST', $db, 'customers', 'customer_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirect('add_customer.php', 'error', 'Invalid request. Please try again.');
    }

    $customer_id = trim($_POST['customer_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate
    $errors = [];
    if (empty($name)) $errors[] = 'Customer name is required.';
    if (!validatePhone($phone)) $errors[] = 'Enter a valid 10-digit Indian phone number.';
    if (!empty($email) && !validateEmail($email)) $errors[] = 'Enter a valid email address.';

    if (empty($errors)) {
        try {
            $stmt = $db->prepare('INSERT INTO customers (customer_id, name, phone, email, address) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$customer_id, $name, $phone, $email ?: null, $address ?: null]);
            redirect('view_customers.php', 'success', 'Customer ' . e($name) . ' added successfully!');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                redirect('add_customer.php', 'error', 'Duplicate customer ID or phone number detected.');
            }
            redirect('add_customer.php', 'error', 'Error adding customer. Please try again.');
        }
    } else {
        setFlash('error', implode('<br>', $errors));
    }
}

$pageTitle = 'Add Customer';
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>Add New Customer</h2>
    <a href="view_customers.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>

<div class="card p-4" style="max-width: 520px;">
    <form method="POST" action="" novalidate>
        <?php csrfField(); ?>
        <div class="mb-3">
            <label class="form-label">Customer ID</label>
            <input type="text" class="form-control bg-light" value="<?= e($customer_id) ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name" name="name"
                   value="<?= e($_POST['name'] ?? '') ?>" placeholder="Enter full name" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" id="phone" name="phone"
                   value="<?= e($_POST['phone'] ?? '') ?>" placeholder="10-digit mobile number" required
                   pattern="[6-9][0-9]{9}" maxlength="10">
            <div class="form-text">Indian 10-digit mobile number (starts with 6-9).</div>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email <span class="text-muted fw-normal">(optional)</span></label>
            <input type="email" class="form-control" id="email" name="email"
                   value="<?= e($_POST['email'] ?? '') ?>" placeholder="email@example.com">
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address <span class="text-muted fw-normal">(optional)</span></label>
            <textarea class="form-control" id="address" name="address" rows="3"
                      placeholder="Street, City, State..."><?= e($_POST['address'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus me-1"></i>Add Customer</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
