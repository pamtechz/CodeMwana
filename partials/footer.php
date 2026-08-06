</main>
<?php if ($user): ?>
    <footer class="app-footer"><span>&copy; <?= date('Y') ?> <?= e(setting('site_name', 'CodeMwana')) ?></span><a href="<?= e(url('help.php')) ?>">Help and guidance</a></footer>
    </div>
</div>
<?php else: ?>
<?php $publicFooterPages = PublicPages::navigation('footer'); ?>
<footer class="public-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark">CM</span><span><strong><?= e(setting('site_name', 'CodeMwana')) ?></strong><small><?= e(setting('site_tagline', 'Learn. Build. Shine.')) ?></small></span></a>
            <p><?= e(setting('site_description', 'A guided programming learning platform for young creators.')) ?></p>
        </div>
        <div><h2>Learning</h2><a href="<?= e(url('index.php#learning')) ?>">Learning paths</a><a href="<?= e(url('login.php')) ?>">Learner sign in</a><?php if ((string) setting('registration_open', '1') === '1'): ?><a href="<?= e(url('register.php')) ?>">Create account</a><?php endif; ?></div>
        <div><h2>Information</h2><?php foreach ($publicFooterPages as $publicPage): ?><a href="<?= e(PublicPages::urlFor((string) $publicPage['slug'])) ?>"><?= e((string) $publicPage['navigation_label']) ?></a><?php endforeach; ?></div>
    </div>
    <div class="container footer-bottom"><span>&copy; <?= date('Y') ?> <?= e(setting('organisation_name', setting('site_name', 'CodeMwana'))) ?></span><span>Designed and developed in Zambia</span></div>
</footer>
<?php endif; ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/ui-v4.js')) ?>" defer></script>
<?php
$pageScripts = is_array($pageScripts ?? null) ? $pageScripts : [];
if (!empty($pageScript)) $pageScripts[] = $pageScript;
if (in_array('remote-runner.js', $pageScripts, true)) array_unshift($pageScripts, 'managed-frame-compat.js');
foreach (array_unique(array_filter($pageScripts)) as $script):
?>
<script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>