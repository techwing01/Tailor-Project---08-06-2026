<?php
/**
 * TailorMate - Helper Functions
 * Shared utility functions used across the application.
 */

require_once __DIR__ . '/auth.php';

/**
 * Get status badge HTML with consistent styling
 */
function statusBadge($status) {
    $statusLower = strtolower(trim($status));
    $classes = [
        'delivered'  => 'bg-success',
        'ready'      => 'bg-info',
        'in progress' => 'bg-warning text-dark',
        'pending'    => 'bg-warning text-dark',
        'cancelled'  => 'bg-danger',
    ];
    $class = $classes[$statusLower] ?? 'bg-secondary';
    return '<span class="badge ' . $class . '">' . e(ucwords($status)) . '</span>';
}

/**
 * Format currency in INR
 */
function formatINR($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Format date consistently
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * Flash message - set and get
 */
function setFlash($type, $message) {
    initSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    initSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Output flash message HTML
 */
function flashHtml() {
    $flash = getFlash();
    if (!$flash) return;
    $alertClass = ($flash['type'] === 'error') ? 'alert-danger' : 
                   (($flash['type'] === 'warning') ? 'alert-warning' : 'alert-success');
    echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show">' . e($flash['message']) . 
         '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

/**
 * Redirect with flash message
 */
function redirect($url, $type = 'success', $message = '') {
    if ($message) {
        setFlash($type, $message);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Validate Indian phone number (10 digits)
 */
function validatePhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone) === 1;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get all order statuses
 */
function getOrderStatuses() {
    return ['pending', 'in progress', 'ready', 'delivered', 'cancelled'];
}

/**
 * Get garment types
 */
function getGarmentTypes() {
    return ['Shirt', 'Pant', 'Kurta', 'Blazer'];
}

/**
 * Simple pagination helper
 */
function paginate($totalItems, $currentPage, $perPage = 15) {
    $totalPages = max(1, ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return [
        'total'      => $totalItems,
        'per_page'   => $perPage,
        'current'    => $currentPage,
        'total_pages'=> $totalPages,
        'offset'     => $offset,
    ];
}

/**
 * Output pagination links HTML
 */
function paginationHtml($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return;
    
    echo '<nav><ul class="pagination justify-content-center">';
    
    // Previous
    $disabled = $pagination['current'] <= 1 ? ' disabled' : '';
    $prevUrl = $pagination['current'] > 1 ? $baseUrl . '&page=' . ($pagination['current'] - 1) : '#';
    echo '<li class="page-item' . $disabled . '"><a class="page-link" href="' . $prevUrl . '">&laquo;</a></li>';
    
    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = $i == $pagination['current'] ? ' active' : '';
        $url = $baseUrl . '&page=' . $i;
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }
    
    // Next
    $disabled = $pagination['current'] >= $pagination['total_pages'] ? ' disabled' : '';
    $nextUrl = $pagination['current'] < $pagination['total_pages'] ? $baseUrl . '&page=' . ($pagination['current'] + 1) : '#';
    echo '<li class="page-item' . $disabled . '"><a class="page-link" href="' . $nextUrl . '">&raquo;</a></li>';
    
    echo '</ul></nav>';
}
