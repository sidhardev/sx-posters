<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../gemini.php';

function handle_logout(): void
{
    logout_user();
    set_flash('success', 'Logged out successfully.');
    redirect_to(url_for(''));
}

function handle_admin_logout(): void
{
    logout_user();
    set_flash('success', 'Admin logged out.');
    redirect_to(url_for('?page=admin-login'));
}

function handle_register(PDO $pdo): void
{
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($phone === '' || $password === '') {
        set_flash('error', 'Phone and password are required.');
        redirect_to(url_for(''));
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $phone]);
    if ($stmt->fetch()) {
        set_flash('error', 'Phone already registered. Please login.');
        redirect_to(url_for(''));
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (phone, password, role) VALUES (:phone, :password, :role)');
    $insert->execute([
        ':phone' => $phone,
        ':password' => $hash,
        ':role' => 'user',
    ]);

    login_user((int)$pdo->lastInsertId(), 'user');
    set_flash('success', 'Registration successful.');
    redirect_to(url_for(''));
}

function handle_login(PDO $pdo): void
{
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, password, role FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password'])) {
        set_flash('error', 'Invalid login credentials.');
        redirect_to(url_for(''));
    }

    $role = is_string($user['role'] ?? null) && $user['role'] !== '' ? (string)$user['role'] : 'user';
    login_user((int)$user['id'], $role);
    set_flash('success', 'Welcome back!');
    redirect_to(url_for(''));
}

function handle_admin_login(PDO $pdo): void
{
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($phone === '' || $password === '') {
        set_flash('error', 'Phone and password are required.');
        redirect_to(url_for('?page=admin-login'));
    }

    $stmt = $pdo->prepare('SELECT id, password, role FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    $validUser = $user && password_verify($password, (string)$user['password']);

    if (!$validUser && admin_env_matches($phone, $password)) {
        seed_admin_user($pdo);
        $stmt->execute([':phone' => $phone]);
        $user = $stmt->fetch();
        $validUser = $user && password_verify($password, (string)$user['password']);
    }

    if (!$validUser) {
        set_flash('error', 'Invalid admin credentials.');
        redirect_to(url_for('?page=admin-login'));
    }

    if (($user['role'] ?? '') !== 'admin') {
        set_flash('error', 'This account is not an admin.');
        redirect_to(url_for('?page=admin-login'));
    }

    login_user((int)$user['id'], 'admin');
    set_flash('success', 'Admin login successful.');
    redirect_to(url_for('?page=admin'));
}

function admin_env_matches(string $phone, string $password): bool
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
        return false;
    }

    if (!hash_equals($adminPhone, $phone)) {
        return false;
    }

    $hashInfo = password_get_info($adminPassword);
    if (($hashInfo['algo'] ?? 0) !== 0) {
        return password_verify($password, $adminPassword);
    }

    return hash_equals($adminPassword, $password);
}

function handle_save_profile(PDO $pdo): void
{
    require_user();

    $userId = current_user_id();
    if ($userId === null) {
        redirect_to(url_for(''));
    }

    $businessName = trim((string)($_POST['business_name'] ?? ''));
    $businessType = trim((string)($_POST['business_type'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if ($businessName === '' || $businessType === '' || $phone === '') {
        set_flash('error', 'Business name, business type and phone are required.');
        redirect_to(url_for(''));
    }

    try {
        $logoPath = store_upload($_FILES['logo'] ?? [], 'uploads/logos', ['image/png', 'image/jpeg', 'image/webp']);

        $existingStmt = $pdo->prepare('SELECT id, logo_path FROM business_profiles WHERE user_id = :user_id LIMIT 1');
        $existingStmt->execute([':user_id' => $userId]);
        $existing = $existingStmt->fetch();

        if ($existing) {
            $newLogo = $logoPath ?? $existing['logo_path'];
            $update = $pdo->prepare('UPDATE business_profiles SET business_name = :name, business_type = :type, phone = :phone, logo_path = :logo WHERE id = :id');
            $update->execute([
                ':name' => $businessName,
                ':type' => $businessType,
                ':phone' => $phone,
                ':logo' => $newLogo,
                ':id' => $existing['id'],
            ]);
        } else {
            $insert = $pdo->prepare('INSERT INTO business_profiles (user_id, business_name, business_type, phone, logo_path) VALUES (:user_id, :name, :type, :phone, :logo)');
            $insert->execute([
                ':user_id' => $userId,
                ':name' => $businessName,
                ':type' => $businessType,
                ':phone' => $phone,
                ':logo' => $logoPath,
            ]);
        }

        set_flash('success', 'Business profile saved successfully.');
    } catch (Throwable $t) {
        set_flash('error', $t->getMessage());
    }

    redirect_to(url_for(''));
}

function handle_add_template(PDO $pdo): void
{
    require_admin();

    $name = trim((string)($_POST['name'] ?? ''));
    $prompt = trim((string)($_POST['prompt'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));

    if ($name === '' || $prompt === '' || $category === '') {
        set_flash('error', 'Template name, prompt and category are required.');
        redirect_to(url_for('?page=admin'));
    }

    try {
        $preview = store_upload($_FILES['preview_image'] ?? [], 'uploads/previews', ['image/png', 'image/jpeg', 'image/webp']);
        if ($preview === null) {
            throw new RuntimeException('Preview image is required.');
        }

        $stmt = $pdo->prepare('INSERT INTO templates (name, preview_image, prompt, category) VALUES (:name, :preview, :prompt, :category)');
        $stmt->execute([
            ':name' => $name,
            ':preview' => $preview,
            ':prompt' => $prompt,
            ':category' => $category,
        ]);

        set_flash('success', 'Template added successfully.');
    } catch (Throwable $t) {
        set_flash('error', $t->getMessage());
    }

    redirect_to(url_for('?page=admin'));
}

function handle_generate_poster(PDO $pdo): void
{
    require_user();
    $userId = current_user_id();

    if ($userId === null) {
        redirect_to(url_for(''));
    }

    $templateId = (int)($_POST['template_id'] ?? 0);
    if ($templateId <= 0) {
        set_flash('error', 'Please select a template.');
        redirect_to(url_for(''));
    }

    $tplStmt = $pdo->prepare('SELECT id, prompt FROM templates WHERE id = :id LIMIT 1');
    $tplStmt->execute([':id' => $templateId]);
    $template = $tplStmt->fetch();

    if (!$template) {
        set_flash('error', 'Selected template not found.');
        redirect_to(url_for(''));
    }

    $profileStmt = $pdo->prepare('SELECT business_name, business_type, phone FROM business_profiles WHERE user_id = :user_id LIMIT 1');
    $profileStmt->execute([':user_id' => $userId]);
    $profile = $profileStmt->fetch();

    if (!$profile) {
        set_flash('error', 'Please save business profile first.');
        redirect_to(url_for(''));
    }

    $prompt = strtr((string)$template['prompt'], [
        '{business_name}' => (string)$profile['business_name'],
        '{business_type}' => (string)$profile['business_type'],
        '{phone}' => (string)$profile['phone'],
    ]);

    $result = generate_poster_with_gemini($prompt);
    if (!(bool)($result['ok'] ?? false)) {
        set_flash('error', (string)($result['error'] ?? 'Poster generation failed.'));
        redirect_to(url_for(''));
    }

    $imageData = $result['data'] ?? null;
    if (!is_string($imageData) || $imageData === '') {
        set_flash('error', 'Invalid image data from Gemini.');
        redirect_to(url_for(''));
    }

    $mime = (string)($result['mime'] ?? 'image/png');
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        default => 'png',
    };

    $filename = bin2hex(random_bytes(24)) . '.' . $ext;
    $relativePath = 'uploads/generated/' . $filename;
    $fullPath = base_path($relativePath);

    if (!is_dir(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0755, true);
    }

    file_put_contents($fullPath, $imageData);

    $insert = $pdo->prepare('INSERT INTO posters (user_id, template_id, image_url) VALUES (:user_id, :template_id, :image_url)');
    $insert->execute([
        ':user_id' => $userId,
        ':template_id' => $templateId,
        ':image_url' => $relativePath,
    ]);

    $_SESSION['latest_poster'] = $relativePath;
    set_flash('success', 'Poster generated successfully.');
    redirect_to(url_for(''));
}
