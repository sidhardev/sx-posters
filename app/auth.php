<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_user_id(): ?int
{
    $id = $_SESSION['user_id'] ?? null;
    return is_int($id) ? $id : null;
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

function login_user(int $userId): void
{
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
}

function is_admin_logged_in(): bool
{
    return (bool)($_SESSION['is_admin'] ?? false);
}

function admin_credential_matches(string $identifier, string $password): bool
{
    $adminPhone = env_value('ADMIN_LOGIN_PHONE', '');
    $adminEmail = env_value('ADMIN_LOGIN_EMAIL', '');
    $adminPassword = env_value('ADMIN_PASSWORD', '');

    if ($adminPassword === '' || ($adminPhone === '' && $adminEmail === '')) {
        return false;
    }

    $idMatch = ($adminPhone !== '' && hash_equals($adminPhone, $identifier)) ||
        ($adminEmail !== '' && hash_equals(strtolower($adminEmail), strtolower($identifier)));

    return $idMatch && hash_equals($adminPassword, $password);
}

function login_admin(): void
{
    $_SESSION['is_admin'] = true;
}

function logout_admin(): void
{
    unset($_SESSION['is_admin']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect_to('/?page=admin-login');
    }
}
