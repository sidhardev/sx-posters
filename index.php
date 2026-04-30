<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/db.php';
require_once __DIR__ . '/app/controllers/actions.php';
require_once __DIR__ . '/app/controllers/context.php';

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

switch ($action) {
    case 'logout':
        handle_logout();
        break;
    case 'admin-logout':
        handle_admin_logout();
        break;
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handle_register($pdo);
        }
        break;
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handle_login($pdo);
        }
        break;
    case 'admin-login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handle_admin_login($pdo);
        }
        break;
    case 'save-profile':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handle_save_profile($pdo);
        }
        break;
    case 'add-template':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handle_add_template($pdo);
        }
        break;
    case 'generate-poster':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handle_generate_poster($pdo);
        }
        break;
}

$success = get_flash('success');
$error = get_flash('error');
$isUser = is_user_logged_in();
$isAdmin = is_admin_logged_in();

$profile = null;
$templates = [];
$posters = [];
$adminTemplates = [];

if ($isUser) {
    $userId = current_user_id();
    if ($userId !== null) {
        $context = load_user_context($pdo, $userId);
        $profile = $context['profile'] ?? null;
        $templates = $context['templates'] ?? [];
        $posters = $context['posters'] ?? [];
    }
}

if ($isAdmin) {
    $context = load_admin_context($pdo);
    $adminTemplates = $context['adminTemplates'] ?? [];
}

$latestPoster = $_SESSION['latest_poster'] ?? null;
$pageTitle = ($page === 'admin' || $page === 'admin-login') ? 'Admin - Poster SaaS' : 'Poster SaaS';
$view = ($page === 'admin' || $page === 'admin-login') ? 'pages/admin' : 'pages/home';

render_view($view, [
    'pageTitle' => $pageTitle,
    'success' => $success,
    'error' => $error,
    'isUser' => $isUser,
    'isAdmin' => $isAdmin,
    'profile' => $profile,
    'templates' => $templates,
    'posters' => $posters,
    'adminTemplates' => $adminTemplates,
    'latestPoster' => $latestPoster,
]);
