<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dbPathValue = env_value('DB_PATH', 'database/app.sqlite');
    if ($dbPathValue === null || $dbPathValue === '') {
        throw new RuntimeException('DB_PATH is not configured.');
    }
    $dbPath = str_starts_with($dbPathValue, '/') ? $dbPathValue : base_path($dbPathValue);

    $dbDir = dirname($dbPath);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    migrate($pdo);

    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT "user",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS business_profiles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            business_name TEXT NOT NULL,
            business_type TEXT NOT NULL,
            phone TEXT NOT NULL,
            logo_path TEXT,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            preview_image TEXT NOT NULL,
            prompt TEXT NOT NULL,
            category TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS posters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            template_id INTEGER NOT NULL,
            image_url TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id),
            FOREIGN KEY(template_id) REFERENCES templates(id)
        )'
    );

    ensure_users_role_column($pdo);
    seed_admin_user($pdo);
}

function ensure_users_role_column(PDO $pdo): void
{
    $columns = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    foreach ($columns as $column) {
        if (($column['name'] ?? '') === 'role') {
            return;
        }
    }

    $pdo->exec('ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT "user"');
}

function seed_admin_user(PDO $pdo): void
{
    $adminPhone = env_value('ADMIN_SEED_PHONE', '');
    $adminPassword = env_value('ADMIN_SEED_PASSWORD', '');
    if ($adminPhone === '') {
        $adminPhone = env_value('ADMIN_LOGIN_PHONE', '');
    }
    if ($adminPassword === '') {
        $adminPassword = env_value('ADMIN_PASSWORD', '');
    }

    if ($adminPhone === '' || $adminPassword === '') {
        return;
    }

    $stmt = $pdo->prepare('SELECT id, role, password FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $adminPhone]);
    $existing = $stmt->fetch();

    if ($existing) {
        $needsUpdate = false;
        if (($existing['role'] ?? '') !== 'admin') {
            $needsUpdate = true;
        }

        $storedPassword = (string)($existing['password'] ?? '');
        $hashInfo = password_get_info($adminPassword);
        $envIsHash = ($hashInfo['algo'] ?? 0) !== 0;
        $passwordMatches = $envIsHash
            ? hash_equals($adminPassword, $storedPassword)
            : password_verify($adminPassword, $storedPassword);

        if (!$passwordMatches) {
            $needsUpdate = true;
            $storedPassword = $envIsHash ? $adminPassword : password_hash($adminPassword, PASSWORD_DEFAULT);
        }

        if ($needsUpdate) {
            $update = $pdo->prepare('UPDATE users SET role = :role, password = :password WHERE id = :id');
            $update->execute([
                ':role' => 'admin',
                ':password' => $storedPassword,
                ':id' => $existing['id'],
            ]);
        }
        return;
    }

    $hashInfo = password_get_info($adminPassword);
    $finalPassword = ($hashInfo['algo'] ?? 0) !== 0
        ? $adminPassword
        : password_hash($adminPassword, PASSWORD_DEFAULT);

    $insert = $pdo->prepare('INSERT INTO users (phone, password, role) VALUES (:phone, :password, :role)');
    $insert->execute([
        ':phone' => $adminPhone,
        ':password' => $finalPassword,
        ':role' => 'admin',
    ]);
}
