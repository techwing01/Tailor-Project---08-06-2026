<?php
/**
 * TailorMate - Logout
 */
require_once __DIR__ . '/includes/auth.php';
sessionDestroy();
header('Location: login.php?loggedout=1');
exit;
