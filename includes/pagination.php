<?php
/**
 * Pager. Expects: $result (from RecipeRepository::search), $listPage ('recipes.php'),
 * $listParams (current filters, without page).
 */
if ($result['pages'] <= 1) {
    return;
}
$current = $result['page'];
?>
<nav class="pagination" aria-label="Pages">
    <?php if ($current > 1): ?>
        <a href="<?= e(pageUrl($listPage, $listParams + ['page' => $current - 1])) ?>" rel="prev">&larr; Newer</a>
    <?php endif; ?>

    <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
        <?php if ($p === $current): ?>
            <span aria-current="page"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= e(pageUrl($listPage, $listParams + ['page' => $p])) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($current < $result['pages']): ?>
        <a href="<?= e(pageUrl($listPage, $listParams + ['page' => $current + 1])) ?>" rel="next">Older &rarr;</a>
    <?php endif; ?>
</nav>
