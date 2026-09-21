<?php
require 'config.php';
if (isset($_POST['add'])) {
    $_SESSION['cart'][$_POST['add']] = ($_SESSION['cart'][$_POST['add']] ?? 0) + 1;
    flash('Added to cart.');
    go('menu.php' . (isset($_GET['c']) ? '?c=' . urlencode($_GET['c']) : ''));
}
head('Menu');
$cats = array_column(q("SELECT DISTINCT category FROM items"), 'category');
$c = $_GET['c'] ?? '';
$items = $c ? q("SELECT * FROM items WHERE category=?", [$c])
    : q("SELECT * FROM items ORDER BY category,id");
?>
<span class="tag">Fresh from the grill</span>
<h2>Our Menu</h2>
<div class="mb-3">
    <a href="menu.php" class="btn btn-sm <?=$c?'btn-outline-dark':'btn-dark'?>">All</a>
    <?php foreach ($cats as $x): ?>
        <a href="?c=<?=urlencode($x)?>"
                class="btn btn-sm <?=$c===$x?'btn-dark':'btn-outline-dark'?>"><?= e($x) ?></a>
    <?php endforeach; ?>
</div>
<div class="row g-4">
    <?php
    foreach ($items as $i):
        $ok = $i['available'] && $i['stock'] > 0;
    ?>
        <div class="col-md-4 col-lg-3">
            <div class="card h-100">
                <?= img($i['image']) ?>
                <div class="card-body d-flex flex-column">
                    <small class="text-muted"><?= e($i['category']) ?></small>
                    <h5><?= e($i['name']) ?></h5>
                    <p class="small text-muted"><?= e($i['description']) ?></p>
                    <div class="mt-auto d-flex justify-content-between align-items-center">
                        <b><?= money($i['price']) ?></b>
                        <?php if ($ok): ?>
                            <form method="post">
                                <button name="add" value="<?=$i['id']?>"
                                        class="btn btn-o btn-sm">Add</button>
                            </form>
                        <?php else: ?>
                            <span class="badge bg-secondary">Sold out</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php
foot();
