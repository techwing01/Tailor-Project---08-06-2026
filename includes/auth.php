<?php
/**
 * TailorMate - Authentication & Authorization
 * Handles session management, login verification, and password hashing.
 */

require_once __DIR__ . '/database.php';

/**
 * Start secure session
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Use strict mode for better security
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin() {
    initSession();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }

    // Check session expiry
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        sessionDestroy();
        header('Location: login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Attempt to log a user in
 */
function attemptLogin($username, $password) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, username, password, email FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        initSession();
        session_regenerate_id(true); // Prevent session fixation
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['loggedin'] = true;
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

/**
 * Destroy session safely
 */
function sessionDestroy() {
    initSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Hash a password using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Generate CSRF token
 */
function generateCSRF() {
    initSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from form submission
 */
function verifyCSRF($token) {
    initSession();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF hidden input field
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRF()) . '">';
}

/**
 * Sanitize output (shortcut for htmlspecialchars)
 */
function e($string, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
    return htmlspecialchars((string)$string, $flags, $encoding);
}

/**
 * Generate a unique ID with a given prefix
 */
function generateUniqueID($prefix, $db, $table, $column) {
    $maxAttempts = 100;
    for ($i = 0; $i < $maxAttempts; $i++) {
        $id = $prefix . random_int(1000, 9999);
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() == 0) {
            return $id;
        }
    }
    // Fallback: use timestamp-based ID
    return $prefix . time();
}
