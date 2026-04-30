<?php if (!$isUser): ?>
    <div class="grid md:grid-cols-2 gap-5">
        <section class="bg-white rounded-xl shadow p-4 sm:p-6">
            <h2 class="text-xl font-semibold">Login</h2>
            <form method="post" action="<?= e(url_for('?action=login')) ?>" class="mt-4 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <input name="phone" required placeholder="Phone number" class="w-full border rounded px-3 py-2" />
                <input type="password" name="password" required placeholder="Password" class="w-full border rounded px-3 py-2" />
                <button class="w-full sm:w-auto px-4 py-2 bg-slate-900 text-white rounded">Login</button>
            </form>
        </section>

        <section class="bg-white rounded-xl shadow p-4 sm:p-6">
            <h2 class="text-xl font-semibold">Register</h2>
            <form method="post" action="<?= e(url_for('?action=register')) ?>" class="mt-4 space-y-3">
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
            <a href="<?= e(url_for('?action=logout')) ?>" class="text-sm text-red-600">Logout</a>
        </div>

        <form method="post" action="<?= e(url_for('?action=save-profile')) ?>" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-3 mt-4">
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
                <img src="<?= e(url_for((string)$profile['logo_path'])) ?>" alt="logo" class="h-20 w-20 object-cover rounded border" />
            </div>
        <?php endif; ?>
    </section>

    <section class="bg-white rounded-xl shadow p-4 sm:p-6 mb-5">
        <h2 class="text-xl font-semibold mb-3">Select Template & Generate</h2>
            <form method="post" action="<?= e(url_for('?action=generate-poster')) ?>" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($templates as $tpl): ?>
                    <label class="block border rounded-lg p-2 cursor-pointer">
                        <input type="radio" name="template_id" value="<?= (int)$tpl['id'] ?>" class="mb-2" required />
                        <img src="<?= e(url_for((string)$tpl['preview_image'])) ?>" alt="preview" class="w-full h-36 object-cover rounded" />
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
            <img src="<?= e(url_for($latestPoster)) ?>" alt="Generated poster" class="w-full max-w-md rounded border" />
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="<?= e(url_for($latestPoster)) ?>" download class="px-4 py-2 bg-green-600 text-white rounded">Download Image</a>
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
                <a href="<?= e(url_for((string)$poster['image_url'])) ?>" target="_blank" class="border rounded p-1">
                    <img src="<?= e(url_for((string)$poster['image_url'])) ?>" alt="poster" class="w-full h-28 object-cover rounded" />
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (count($posters) === 0): ?>
            <p class="text-sm text-slate-500">No posters generated yet.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
