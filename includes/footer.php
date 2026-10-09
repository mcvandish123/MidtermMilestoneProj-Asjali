<?php
/** Page footer. Optional: $scripts = ['favorites.js', ...] (files in assets/js). */
$scripts = $scripts ?? [];
?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <p><strong><?= e(APP_NAME) ?></strong> · Lutong bahay, shared by neighbors.</p>
        <p class="muted">Members only · Be kind in the comments</p>
    </div>
</footer>

<div class="toast-region" aria-live="polite" aria-atomic="true"></div>

<script src="assets/js/ui.js" defer></script>
<?php foreach ($scripts as $script): ?>
<script src="assets/js/<?= e($script) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
