<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($pageTitle ?? 'Poster SaaS') ?></title>
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

    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded bg-green-100 text-green-800 p-3 text-sm"><?= e((string)$success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded bg-red-100 text-red-800 p-3 text-sm"><?= e((string)$error) ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
</div>
</body>
</html>
