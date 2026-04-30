<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_user_id(): ?int
{
    $id = $_SESSION['user_id'] ?? null;
    return is_int($id) ? $id : null;
}

function current_user_role(): ?string
{
    $role = $_SESSION['user_role'] ?? null;
    return is_string($role) ? $role : null;
}

function is_user_logged_in(): bool
{
    return current_user_id() !== null;
}

function require_user(): void
{
    if (!is_user_logged_in()) {
        redirect_to('/');
    }
}

function login_user(int $userId, string $role): void
{
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
    unset($_SESSION['user_role']);
}

function is_admin_logged_in(): bool
{
    return is_user_logged_in() && current_user_role() === 'admin';
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect_to('/?page=admin-login');
    }
}
