<?php
/**
 * TailorMate - Settings (Password Change)
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirect('settings.php', 'error', 'Invalid request.');
    }

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $errors = [];
    if (empty($current) || empty($new) || empty($confirm)) {
        $errors[] = 'All fields are required.';
    } elseif (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            $hashed = hashPassword($new);
            $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hashed, $_SESSION['user_id']]);
            redirect('settings.php', 'success', 'Password changed successfully.');
        }
    }

    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
}

$pageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';
?>

<?php flashHtml(); ?>

<div class="page-header">
    <h2>Settings</h2>
</div>

<div class="card p-4" style="max-width: 480px;">
    <h5 class="fw-bold mb-3"><i class="fas fa-key me-2"></i>Change Password</h5>
    <form method="POST" action="" novalidate>
        <?php csrfField(); ?>
        <div class="mb-3">
            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
        </div>
        <div class="mb-3">
            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
            <div class="form-text">Minimum 8 characters.</div>
        </div>
        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Password</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
