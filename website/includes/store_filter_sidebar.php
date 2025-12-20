<?php
// ../includes/store_filter_sidebar.php
?>

<aside class="col-md-3 store-sidebar">
  <div class="filter-box"> 
    <h5 class="text-darling">Filter</h5>

    <form method="get">

        <!-- CATEGORY (Hierarchical) -->
        <div class="mb-4">
        <strong>Category</strong>
        <?php foreach ($categories as $parent): ?>
            <!-- Parent Category -->
            <div class="form-check">
            <input class="form-check-input"
                    type="checkbox"
                    name="category[]"
                    value="<?= htmlspecialchars($parent['name']) ?>"
                    <?= in_array($parent['name'], $filters['categories'], true) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold"><?= htmlspecialchars($parent['name']) ?></label>
            </div>
            
            <!-- Child Categories (nếu có) -->
            <?php if (!empty($parent['children'])): ?>
                <div class="ps-4">
                <?php foreach ($parent['children'] as $child): ?>
                    <div class="form-check">
                    <input class="form-check-input"
                            type="checkbox"
                            name="category[]"
                            value="<?= htmlspecialchars($child['name']) ?>"
                            <?= in_array($child['name'], $filters['categories'], true) ? 'checked' : '' ?>>
                    <label class="form-check-label"><?= htmlspecialchars($child['name']) ?></label>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>

        <!-- SKIN CONDITION (UI only) -->
        <div class="mb-4">
        <strong>Skin Condition</strong>
        <?php foreach ($skinConditions as $cond): ?>
            <div class="form-check">
            <input class="form-check-input"
                    type="checkbox"
                    name="condition[]"
                    value="<?= htmlspecialchars($cond) ?>"
                    <?= in_array($cond, $filters['conditions'], true) ? 'checked' : '' ?>>
            <label class="form-check-label"><?= htmlspecialchars($cond) ?></label>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- FEATURED (UI only) -->
        <div class="mb-4">
        <strong>Featured</strong>
        <?php foreach ($featuredOptions as $opt): ?>
            <div class="form-check">
            <input class="form-check-input"
                    type="checkbox"
                    name="featured[]"
                    value="<?= htmlspecialchars($opt) ?>"
                    <?= in_array($opt, $filters['featured'], true) ? 'checked' : '' ?>>
            <label class="form-check-label"><?= htmlspecialchars($opt) ?></label>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- PRICE -->
        <div class="mb-4">
        <strong>Price</strong>
        <?php foreach ($priceRanges as $range): ?>
            <div class="form-check">
            <input class="form-check-input"
                    type="checkbox"
                    name="price[]"
                    value="<?= htmlspecialchars($range) ?>"
                    <?= in_array($range, $filters['prices'], true) ? 'checked' : '' ?>>
            <label class="form-check-label"><?= htmlspecialchars($range) ?></label>
            </div>
        <?php endforeach; ?>
        </div>

        <button class="btn btn-outline-dark w-100">Apply Filter</button>
    </form>
  </div>
</aside>