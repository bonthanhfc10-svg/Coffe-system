<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!function_exists('verifyCsrfToken')) require_once __DIR__ . '/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
}
