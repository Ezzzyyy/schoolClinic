<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Protects a page by redirecting to login if the user is not authenticated.
 * @param int $depth How many levels deep the current file is from the root.
 */
function protectPage(int $depth = 0): void {
    if (!isset($_SESSION['user_id'])) {
        $prefix = str_repeat('../', $depth);
        header("Location: {$prefix}login.php");
        exit;
    }
}

/**
 * Checks if the logged-in user has a specific role.
 */
function hasRole(string $role): bool {
    return ($_SESSION['role'] ?? '') === $role;
}

/**
 * Redirects to dashboard if the user is already logged in (used on login page).
 */
function redirectIfLoggedIn(): void {
    if (isset($_SESSION['user_id'])) {
        header("Location: modules/dashboard.php");
        exit;
    }
}
