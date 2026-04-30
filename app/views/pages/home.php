<?php if (!$isUser): ?>
    <div class="grid lg:grid-cols-2 gap-5">
        <section class="card fade-in">
            <h2 class="text-2xl font-semibold">Welcome Back</h2>
            <p class="text-sm text-slate-600 mt-1">Login to auto-fill your business details and generate posters.</p>
            <form method="post" action="<?= e(url_for('?action=login')) ?>" class="mt-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <div>
                    <label class="text-sm font-medium">Phone number</label>
                    <input name="phone" required placeholder="e.g. 9876543210" class="input mt-1" />
                </div>
                <div>
                    <label class="text-sm font-medium">Password</label>
                    <input type="password" name="password" required placeholder="Your password" class="input mt-1" />
                </div>
                <button class="btn btn-dark w-full">Login</button>
            </form>
        </section>

        <section class="card fade-in" style="animation-delay:0.05s;">
            <h2 class="text-2xl font-semibold">Create Account</h2>
            <p class="text-sm text-slate-600 mt-1">One-time setup. Next time, everything is ready.</p>
            <form method="post" action="<?= e(url_for('?action=register')) ?>" class="mt-5 space-y-3">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <div>
                    <label class="text-sm font-medium">Phone number</label>
                    <input name="phone" required placeholder="e.g. 9876543210" class="input mt-1" />
                </div>
                <div>
                    <label class="text-sm font-medium">Password</label>
                    <input type="password" name="password" required placeholder="Choose a password" class="input mt-1" />
                </div>
                <button class="btn btn-primary w-full">Create Account</button>
            </form>
        </section>
    </div>
<?php else: ?>
    <section class="card fade-in mb-5">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Business Details</h2>
            <a href="<?= e(url_for('?action=logout')) ?>" class="text-sm text-red-600">Logout</a>
        </div>

        <form method="post" action="<?= e(url_for('?action=save-profile')) ?>" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4 mt-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
            <div>
                <label class="text-sm font-medium">Business name</label>
                <input name="business_name" required placeholder="Sharma Kirana" value="<?= e($profile['business_name'] ?? '') ?>" class="input mt-1" />
            </div>
            <div>
                <label class="text-sm font-medium">Business type</label>
                <input name="business_type" required placeholder="Grocery / Salon / Clothing" value="<?= e($profile['business_type'] ?? '') ?>" class="input mt-1" />
            </div>
            <div>
                <label class="text-sm font-medium">Business phone</label>
                <input name="phone" required placeholder="9876543210" value="<?= e($profile['phone'] ?? '') ?>" class="input mt-1" />
            </div>
            <div>
                <label class="text-sm font-medium">Logo (optional)</label>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-2 text-sm" />
            </div>
            <div class="md:col-span-2">
                <button class="btn btn-dark w-full sm:w-auto">Save Profile</button>
            </div>
        </form>

        <?php if (!empty($profile['logo_path'])): ?>
            <div class="mt-4">
                <p class="text-sm mb-1 text-slate-600">Current Logo</p>
                <img src="<?= e(url_for((string)$profile['logo_path'])) ?>" alt="logo" class="h-20 w-20 object-cover rounded border" />
            </div>
        <?php endif; ?>
    </section>

    <section class="card fade-in mb-5" style="animation-delay:0.04s;">
        <h2 class="text-xl font-semibold mb-3">Select Template & Generate</h2>
        <form method="post" action="<?= e(url_for('?action=generate-poster')) ?>" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($templates as $tpl): ?>
                    <label class="block border border-slate-200 rounded-xl p-2 cursor-pointer hover:border-slate-300 transition">
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
                <button class="btn btn-primary w-full sm:w-auto">Generate Poster</button>
            <?php endif; ?>
        </form>
    </section>

        <?php if (is_string($latestPoster) && $latestPoster !== '' && is_file(base_path($latestPoster))): ?>
        <?php $shareBaseUrl = rtrim((string)env_value('APP_URL', ''), '/'); ?>
        <?php $shareUrl = $shareBaseUrl !== '' ? $shareBaseUrl . '/' . ltrim($latestPoster, '/') : ''; ?>
        <section class="card fade-in mb-5" style="animation-delay:0.06s;">
            <h2 class="text-xl font-semibold mb-3">Latest Poster</h2>
            <img src="<?= e(url_for($latestPoster)) ?>" alt="Generated poster" class="w-full max-w-md rounded border" />
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="<?= e(url_for($latestPoster)) ?>" download class="btn btn-primary">Download Image</a>
                <?php if ($shareUrl !== ''): ?>
                    <a target="_blank" rel="noopener" href="https://wa.me/?text=<?= e(urlencode('Check this poster: ' . $shareUrl)) ?>" class="btn btn-outline">Share on WhatsApp</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="card fade-in" style="animation-delay:0.08s;">
        <h2 class="text-xl font-semibold mb-3">Recent Posters</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach ($posters as $poster): ?>
                <a href="<?= e(url_for((string)$poster['image_url'])) ?>" target="_blank" class="border border-slate-200 rounded-xl p-1">
                    <img src="<?= e(url_for((string)$poster['image_url'])) ?>" alt="poster" class="w-full h-28 object-cover rounded" />
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (count($posters) === 0): ?>
            <p class="text-sm text-slate-500">No posters generated yet.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
