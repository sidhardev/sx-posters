<?php

declare(strict_types=1);

require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/gemini.php';

$pdo = db();
$action = $_GET['action'] ?? '';
$page = $_GET['page'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
    set_flash('error', 'CSRF validation failed.');
    $redirectTarget = '/';
    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $refererPath = parse_url($referer, PHP_URL_PATH);
    $refererQuery = parse_url($referer, PHP_URL_QUERY);
    if (is_string($refererPath) && str_starts_with($refererPath, '/')) {
        $redirectTarget = $refererPath . (is_string($refererQuery) && $refererQuery !== '' ? '?' . $refererQuery : '');
    }
    redirect_to($redirectTarget);
}

if ($action === 'logout') {
    logout_user();
    set_flash('success', 'Logged out successfully.');
    redirect_to('/');
}

if ($action === 'admin-logout') {
    logout_admin();
    set_flash('success', 'Admin logged out.');
    redirect_to('/');
}

if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($phone === '' || $password === '') {
        set_flash('error', 'Phone and password are required.');
        redirect_to('/');
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $phone]);
    if ($stmt->fetch()) {
        set_flash('error', 'Phone already registered. Please login.');
        redirect_to('/');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (phone, password) VALUES (:phone, :password)');
    $insert->execute([
        ':phone' => $phone,
        ':password' => $hash,
    ]);

    login_user((int)$pdo->lastInsertId());
    set_flash('success', 'Registration successful.');
    redirect_to('/');
}

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, password FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password'])) {
        set_flash('error', 'Invalid login credentials.');
        redirect_to('/');
    }

    login_user((int)$user['id']);
    set_flash('success', 'Welcome back!');
    redirect_to('/');
}

if ($action === 'admin-login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string)($_POST['identifier'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!admin_credential_matches($identifier, $password)) {
        set_flash('error', 'Invalid admin credentials or missing ADMIN_* env setup.');
        redirect_to('/?page=admin-login');
    }

    login_admin();
    set_flash('success', 'Admin login successful.');
    redirect_to('/?page=admin');
}

if ($action === 'save-profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_user();

    $userId = current_user_id();
    if ($userId === null) {
        redirect_to('/');
    }

    $businessName = trim((string)($_POST['business_name'] ?? ''));
    $businessType = trim((string)($_POST['business_type'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if ($businessName === '' || $businessType === '' || $phone === '') {
        set_flash('error', 'Business name, business type and phone are required.');
        redirect_to('/');
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

    redirect_to('/');
}

if ($action === 'add-template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();

    $name = trim((string)($_POST['name'] ?? ''));
    $prompt = trim((string)($_POST['prompt'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));

    if ($name === '' || $prompt === '' || $category === '') {
        set_flash('error', 'Template name, prompt and category are required.');
        redirect_to('/?page=admin');
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

    redirect_to('/?page=admin');
}

if ($action === 'generate-poster' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_user();
    $userId = current_user_id();

    if ($userId === null) {
        redirect_to('/');
    }

    $templateId = (int)($_POST['template_id'] ?? 0);
    if ($templateId <= 0) {
        set_flash('error', 'Please select a template.');
        redirect_to('/');
    }

    $tplStmt = $pdo->prepare('SELECT id, prompt FROM templates WHERE id = :id LIMIT 1');
    $tplStmt->execute([':id' => $templateId]);
    $template = $tplStmt->fetch();

    if (!$template) {
        set_flash('error', 'Selected template not found.');
        redirect_to('/');
    }

    $profileStmt = $pdo->prepare('SELECT business_name, business_type, phone FROM business_profiles WHERE user_id = :user_id LIMIT 1');
    $profileStmt->execute([':user_id' => $userId]);
    $profile = $profileStmt->fetch();

    if (!$profile) {
        set_flash('error', 'Please save business profile first.');
        redirect_to('/');
    }

    $prompt = strtr((string)$template['prompt'], [
        '{business_name}' => (string)$profile['business_name'],
        '{business_type}' => (string)$profile['business_type'],
        '{phone}' => (string)$profile['phone'],
    ]);

    $result = generate_poster_with_gemini($prompt);
    if (!(bool)($result['ok'] ?? false)) {
        set_flash('error', (string)($result['error'] ?? 'Poster generation failed.'));
        redirect_to('/');
    }

    $imageData = $result['data'] ?? null;
    if (!is_string($imageData) || $imageData === '') {
        set_flash('error', 'Invalid image data from Gemini.');
        redirect_to('/');
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
    redirect_to('/');
}

$success = get_flash('success');
$error = get_flash('error');
$isUser = is_user_logged_in();
$isAdmin = is_admin_logged_in();

$profile = null;
$templates = [];
$posters = [];

if ($isUser) {
    $userId = current_user_id();

    $stmt = $pdo->prepare('SELECT business_name, business_type, phone, logo_path FROM business_profiles WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $userId]);
    $profile = $stmt->fetch() ?: null;

    $templates = $pdo->query('SELECT id, name, preview_image, prompt, category, created_at FROM templates ORDER BY id DESC')->fetchAll();

    $pStmt = $pdo->prepare('SELECT image_url, created_at FROM posters WHERE user_id = :user_id ORDER BY id DESC LIMIT 8');
    $pStmt->execute([':user_id' => $userId]);
    $posters = $pStmt->fetchAll();
}

$adminTemplates = [];
if ($isAdmin) {
    $adminTemplates = $pdo->query('SELECT id, name, preview_image, prompt, category, created_at FROM templates ORDER BY id DESC')->fetchAll();
}

$latestPoster = $_SESSION['latest_poster'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Poster SaaS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
<div class="max-w-5xl mx-auto p-4 sm:p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-800">🚀 Poster SaaS</h1>
        <div class="space-x-2">
            <a href="/" class="px-3 py-2 bg-white rounded border text-sm">User Home</a>
            <a href="/?page=admin-login" class="px-3 py-2 bg-white rounded border text-sm">Admin</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="mb-4 rounded bg-green-100 text-green-800 p-3 text-sm"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-4 rounded bg-red-100 text-red-800 p-3 text-sm"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($page === 'admin-login' || $page === 'admin'): ?>
        <section class="bg-white rounded-xl shadow p-4 sm:p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Admin Panel</h2>
                <?php if ($isAdmin): ?>
                    <a href="/?action=admin-logout" class="text-sm text-red-600">Logout</a>
                <?php endif; ?>
            </div>

            <?php if (!$isAdmin): ?>
                <form method="post" action="/?action=admin-login" class="mt-4 space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                    <div>
                        <label class="block text-sm mb-1">Admin Phone or Email</label>
                        <input name="identifier" required class="w-full border rounded px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Password</label>
                        <input type="password" name="password" required class="w-full border rounded px-3 py-2" />
                    </div>
                    <button class="w-full sm:w-auto px-4 py-2 bg-slate-900 text-white rounded">Login</button>
                </form>
            <?php else: ?>
                <div class="grid md:grid-cols-2 gap-5 mt-5">
                    <form method="post" action="/?action=add-template" enctype="multipart/form-data" class="space-y-3 border rounded p-4">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                        <h3 class="font-semibold">Add Template</h3>
                        <input name="name" required placeholder="Template name" class="w-full border rounded px-3 py-2" />
                        <input name="category" required placeholder="Category (Festival/Sale/etc)" class="w-full border rounded px-3 py-2" />
                        <textarea name="prompt" required rows="4" placeholder="Prompt with {business_name}, {business_type}, {phone}" class="w-full border rounded px-3 py-2"></textarea>
                        <input type="file" name="preview_image" accept="image/png,image/jpeg,image/webp" required class="w-full text-sm" />
                        <button class="w-full sm:w-auto px-4 py-2 bg-slate-900 text-white rounded">Save Template</button>
                    </form>

                    <div class="border rounded p-4">
                        <h3 class="font-semibold mb-3">Template List</h3>
                        <div class="space-y-3 max-h-[500px] overflow-auto pr-1">
                            <?php foreach ($adminTemplates as $tpl): ?>
                                <div class="border rounded p-2">
                                    <img src="/<?= e((string)$tpl['preview_image']) ?>" alt="preview" class="w-full h-36 object-cover rounded" />
                                    <p class="font-medium mt-2"><?= e((string)$tpl['name']) ?></p>
                                    <p class="text-xs text-slate-500"><?= e((string)$tpl['category']) ?></p>
                                    <p class="text-xs mt-1"><?= e((string)$tpl['prompt']) ?></p>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($adminTemplates) === 0): ?>
                                <p class="text-sm text-slate-500">No templates yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

    <?php else: ?>
        <?php if (!$isUser): ?>
            <div class="grid md:grid-cols-2 gap-5">
                <section class="bg-white rounded-xl shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold">Login</h2>
                    <form method="post" action="/?action=login" class="mt-4 space-y-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                        <input name="phone" required placeholder="Phone number" class="w-full border rounded px-3 py-2" />
                        <input type="password" name="password" required placeholder="Password" class="w-full border rounded px-3 py-2" />
                        <button class="w-full sm:w-auto px-4 py-2 bg-slate-900 text-white rounded">Login</button>
                    </form>
                </section>

                <section class="bg-white rounded-xl shadow p-4 sm:p-6">
                    <h2 class="text-xl font-semibold">Register</h2>
                    <form method="post" action="/?action=register" class="mt-4 space-y-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                        <input name="phone" required placeholder="Phone number" class="w-full border rounded px-3 py-2" />
                        <input type="password" name="password" required placeholder="Password" class="w-full border rounded px-3 py-2" />
                        <button class="w-full sm:w-auto px-4 py-2 bg-slate-900 text-white rounded">Create Account</button>
                    </form>
                </section>
            </div>
        <?php else: ?>
            <section class="bg-white rounded-xl shadow p-4 sm:p-6 mb-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Business Details</h2>
                    <a href="/?action=logout" class="text-sm text-red-600">Logout</a>
                </div>

                <form method="post" action="/?action=save-profile" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-3 mt-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                    <input name="business_name" required placeholder="Business Name" value="<?= e($profile['business_name'] ?? '') ?>" class="border rounded px-3 py-2" />
                    <input name="business_type" required placeholder="Business Type" value="<?= e($profile['business_type'] ?? '') ?>" class="border rounded px-3 py-2" />
                    <input name="phone" required placeholder="Business Phone" value="<?= e($profile['phone'] ?? '') ?>" class="border rounded px-3 py-2" />
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="text-sm" />
                    <div class="md:col-span-2">
                        <button class="w-full sm:w-auto px-4 py-2 bg-slate-900 text-white rounded">Save Profile</button>
                    </div>
                </form>

                <?php if (!empty($profile['logo_path'])): ?>
                    <div class="mt-4">
                        <p class="text-sm mb-1 text-slate-600">Current Logo</p>
                        <img src="/<?= e((string)$profile['logo_path']) ?>" alt="logo" class="h-20 w-20 object-cover rounded border" />
                    </div>
                <?php endif; ?>
            </section>

            <section class="bg-white rounded-xl shadow p-4 sm:p-6 mb-5">
                <h2 class="text-xl font-semibold mb-3">Select Template & Generate</h2>
                <form method="post" action="/?action=generate-poster" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <?php foreach ($templates as $tpl): ?>
                            <label class="block border rounded-lg p-2 cursor-pointer">
                                <input type="radio" name="template_id" value="<?= (int)$tpl['id'] ?>" class="mb-2" required />
                                <img src="/<?= e((string)$tpl['preview_image']) ?>" alt="preview" class="w-full h-36 object-cover rounded" />
                                <p class="font-medium mt-2"><?= e((string)$tpl['name']) ?></p>
                                <p class="text-xs text-slate-500"><?= e((string)$tpl['category']) ?></p>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($templates) === 0): ?>
                        <p class="text-sm text-slate-500">No templates yet. Ask admin to add templates.</p>
                    <?php else: ?>
                        <button class="w-full sm:w-auto px-4 py-2 bg-indigo-600 text-white rounded">Generate Poster</button>
                    <?php endif; ?>
                </form>
            </section>

            <?php if (is_string($latestPoster) && $latestPoster !== '' && is_file(base_path($latestPoster))): ?>
                <?php $shareBaseUrl = rtrim((string)env_value('APP_URL', ''), '/'); ?>
                <?php $shareUrl = $shareBaseUrl !== '' ? $shareBaseUrl . '/' . ltrim($latestPoster, '/') : ''; ?>
                <section class="bg-white rounded-xl shadow p-4 sm:p-6 mb-5">
                    <h2 class="text-xl font-semibold mb-3">Latest Poster</h2>
                    <img src="/<?= e($latestPoster) ?>" alt="Generated poster" class="w-full max-w-md rounded border" />
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="/<?= e($latestPoster) ?>" download class="px-4 py-2 bg-green-600 text-white rounded">Download Image</a>
                        <?php if ($shareUrl !== ''): ?>
                            <a target="_blank" rel="noopener" href="https://wa.me/?text=<?= e(urlencode('Check this poster: ' . $shareUrl)) ?>" class="px-4 py-2 bg-emerald-500 text-white rounded">Share on WhatsApp</a>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="bg-white rounded-xl shadow p-4 sm:p-6">
                <h2 class="text-xl font-semibold mb-3">Recent Posters</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <?php foreach ($posters as $poster): ?>
                        <a href="/<?= e((string)$poster['image_url']) ?>" target="_blank" class="border rounded p-1">
                            <img src="/<?= e((string)$poster['image_url']) ?>" alt="poster" class="w-full h-28 object-cover rounded" />
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if (count($posters) === 0): ?>
                    <p class="text-sm text-slate-500">No posters generated yet.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
