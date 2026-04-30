<?php if (!$isAdmin): ?>
    <section class="card fade-in">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Admin Panel</h2>
        </div>

        <p class="text-sm text-slate-600 mt-1">Login using the admin phone configured in your .env.</p>
        <form method="post" action="<?= e(url_for('?action=admin-login')) ?>" class="mt-5 space-y-3">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
            <div>
                <label class="block text-sm font-medium">Admin Phone</label>
                <input name="phone" required placeholder="e.g. 9999999999" class="input mt-1" />
            </div>
            <div>
                <label class="block text-sm font-medium">Password</label>
                <input type="password" name="password" required placeholder="Admin password" class="input mt-1" />
            </div>
            <button class="btn btn-dark w-full">Login</button>
        </form>
    </section>
<?php else: ?>
    <section class="card fade-in">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold">Admin Panel</h2>
            <a href="<?= e(url_for('?action=admin-logout')) ?>" class="text-sm text-red-600">Logout</a>
        </div>

        <div class="grid md:grid-cols-2 gap-5 mt-5">
            <form method="post" action="<?= e(url_for('?action=add-template')) ?>" enctype="multipart/form-data" class="space-y-3 border border-slate-200 rounded-xl p-4 bg-white/70">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>" />
                <h3 class="font-semibold">Add Template</h3>
                <input name="name" required placeholder="Template name" class="input" />
                <input name="category" required placeholder="Category (Festival/Sale/etc)" class="input" />
                <textarea name="prompt" required rows="4" placeholder="Prompt with {business_name}, {business_type}, {phone}" class="input"></textarea>
                <input type="file" name="preview_image" accept="image/png,image/jpeg,image/webp" required class="w-full text-sm" />
                <button class="btn btn-dark w-full sm:w-auto">Save Template</button>
            </form>

            <div class="border border-slate-200 rounded-xl p-4 bg-white/70">
                <h3 class="font-semibold mb-3">Template List</h3>
                <div class="space-y-3 max-h-[500px] overflow-auto pr-1">
                    <?php foreach ($adminTemplates as $tpl): ?>
                        <div class="border border-slate-200 rounded-xl p-2">
                            <img src="<?= e(url_for((string)$tpl['preview_image'])) ?>" alt="preview" class="w-full h-36 object-cover rounded" />
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
    </section>
<?php endif; ?>
