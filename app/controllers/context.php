<?php

declare(strict_types=1);

function load_user_context(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT business_name, business_type, phone, logo_path FROM business_profiles WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $userId]);
    $profile = $stmt->fetch() ?: null;

    $templates = $pdo->query('SELECT id, name, preview_image, prompt, category, created_at FROM templates ORDER BY id DESC')->fetchAll();

    $pStmt = $pdo->prepare('SELECT image_url, created_at FROM posters WHERE user_id = :user_id ORDER BY id DESC LIMIT 8');
    $pStmt->execute([':user_id' => $userId]);
    $posters = $pStmt->fetchAll();

    return [
        'profile' => $profile,
        'templates' => $templates,
        'posters' => $posters,
    ];
}

function load_admin_context(PDO $pdo): array
{
    $templates = $pdo->query('SELECT id, name, preview_image, prompt, category, created_at FROM templates ORDER BY id DESC')->fetchAll();

    return ['adminTemplates' => $templates];
}
