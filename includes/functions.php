<?php
declare(strict_types=1);

/**
 * Sanitize output to prevent XSS
 */
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Simple redirect helper
 */
if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header("Location: $url");
        exit;
    }
}

/**
 * Get initials from a full name
 */
if (!function_exists('getInitials')) {
    function getInitials(string $name): string {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $w) {
            $initials .= $w[0] ?? '';
        }
        return strtoupper(substr($initials, 0, 2));
    }
}
