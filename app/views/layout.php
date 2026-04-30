<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= e($pageTitle ?? 'Poster SaaS') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --accent: #0f766e;
            --accent-2: #f59e0b;
            --card: rgba(255, 255, 255, 0.92);
            --ring: rgba(15, 118, 110, 0.18);
        }

        body {
            font-family: 'Sora', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 600px at -10% -10%, rgba(245, 158, 11, 0.15), transparent 60%),
                radial-gradient(900px 500px at 110% -20%, rgba(14, 116, 144, 0.18), transparent 60%),
                linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        }

        h1, h2, h3 {
            font-family: 'Space Grotesk', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            letter-spacing: -0.02em;
        }

        .card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 1.1rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.1);
            padding: 1.25rem;
            backdrop-filter: blur(10px);
        }

        .input {
            width: 100%;
            border-radius: 0.8rem;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: #fff;
            padding: 0.65rem 0.8rem;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--ring);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border-radius: 0.85rem;
            padding: 0.65rem 1.1rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.12);
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-dark {
            background: #0f172a;
            color: #fff;
        }

        .btn-outline {
            background: #fff;
            border: 1px solid rgba(148, 163, 184, 0.5);
            color: var(--ink);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(245, 158, 11, 0.18);
            color: #92400e;
            border-radius: 999px;
            padding: 0.3rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .fade-in {
            animation: fadeIn 0.6s ease both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen">
<div class="max-w-5xl mx-auto p-4 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <span class="badge">Fast posters in 10-20 sec</span>
            <h1 class="text-3xl sm:text-4xl font-semibold mt-2">Poster SaaS</h1>
            <p class="text-sm sm:text-base text-slate-600 mt-1">Ready-made marketing posters for Indian small businesses.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= e(url_for('')) ?>" class="btn btn-outline">User Home</a>
            <a href="<?= e(url_for('?page=admin-login')) ?>" class="btn btn-dark">Admin</a>
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
