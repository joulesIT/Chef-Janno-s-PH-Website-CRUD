<?php
require '../config.php';
need_role();
if (isset($_POST['save'])) {
    $img = upload('image');
    $a = [$_POST['name'], $_POST['category'], $_POST['description'], $_POST['price'],
        $_POST['stock'], $_POST['low_stock'], isset($_POST['available']) ? 1 : 0];
    if ($_POST['id']) {
        q("UPDATE items"
            . " SET name=?,category=?,description=?,price=?,stock=?,low_stock=?,available=?"
            . " WHERE id=?", [...$a, $_POST['id']]);
        if ($img)
            q("UPDATE items SET image=? WHERE id=?", [$img, $_POST['id']]);
    } else
        q("INSERT INTO items(name,category,description,price,stock,low_stock,available,"
            . "image) VALUES(?,?,?,?,?,?,?,?)", [...$a, $img]);
    flash('Item saved.');
    go('index.php');
}
if (isset($_GET['del'])) {
    q("DELETE FROM items WHERE id=?", [$_GET['del']]);
    flash('Item deleted.');
    go('index.php');
}
head('Dashboard', true);
$e = isset($_GET['edit'])
    ? (q("SELECT * FROM items WHERE id=?", [$_GET['edit']])[0] ?? null) : null;
$st = q("SELECT (SELECT COUNT(*) FROM orders"
    . " WHERE status IN('pending','preparing','served')) open,(SELECT COUNT(*)"
    . " FROM orders"
    . " WHERE status='completed' AND payment_status='unpaid') unpaid,(SELECT COALESCE(SUM(amount),0)"
    . " FROM payments WHERE DATE(paid_at)=CURDATE()) sales,(SELECT COUNT(*)"
    . " FROM orders WHERE order_date=CURDATE()) today")[0];
$low = q("SELECT * FROM items WHERE stock<=low_stock");
$os = q("SELECT o.*,COALESCE(o.customer_name,u.name) who FROM orders o LEFT"
    . " JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 6");
$rv = q("SELECT r.*,u.name FROM reviews r JOIN users u ON u.id=r.user_id"
    . " ORDER BY r.id DESC LIMIT 3");
$items = q("SELECT * FROM items ORDER BY category,id");
?>
<div class="row g-3 mb-3">
    <?php
    foreach ([['Orders today', $st['today']], ['In kitchen', $st['open']], ['Awaiting payment',
        $st['unpaid']], ["Today's sales", money($st['sales'])]] as $c):
    ?>
        <div class="col-6 col-lg-3">
            <div class="card card-body">
                <small class="text-muted text-uppercase"><?= $c[0] ?></small>
                <div class="stat">
                    <?= $c[1] ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php foreach ($low as $l): ?>
    <div class="alert alert-danger py-2">
        ⚠ Low stock: <b><?= e($l['name']) ?></b> — <?= $l['stock'] ?> left (threshold
        <?= $l['low_stock'] ?>)
    </div>
<?php endforeach; ?>
<div class="row g-3">
    <div class="col-lg-7">
        <h5>Recent Orders</h5>
        <table class="table table-sm">
            <?php foreach ($os as $o): ?>
                <tr>
                    <td><b><?= olabel($o) ?></b> <small class="text-muted"><?= otime($o) ?></small></td>
                    <td><?= e($o['who']) ?></td>
                    <td><?= money($o['total']) ?></td>
                    <td><?= badge($o['status']) ?> <?= paybadge($o['payment_status']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <a href="orders.php">All orders →</a>
    </div>
    <div class="col-lg-5">
        <h5>Recent Reviews</h5>
        <?php foreach ($rv as $x): ?>
            <div class="card card-body mb-2 py-2">
                <b><?= e($x['name']) ?> · <?= str_repeat('⭐', $x['rating']) ?></b><small><?= e(mb_substr($x['comment'], 0, 90)) ?></small>
            </div>
            <?php
        endforeach;
        if (!$rv)
            echo '<p class="text-muted">No reviews yet.</p>';
            ?>
        <a href="reviews.php">All reviews →</a>
    </div>
</div>
<h5 class="mt-4"><?= $e ? 'Edit' : 'Add' ?> Item</h5>
<form method="post" enctype="multipart/form-data" class="card card-body mb-4">
    <input type="hidden" name="id" value="<?=$e['id']??''?>">
    <div class="row g-2">
        <div class="col-md-4">
            <input name="name" class="form-control" placeholder="Name" required
                    value="<?=e($e['name']??'')?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <?php
                foreach (['Burgers', 'Filipino Rice Meals', 'Sides', 'Drinks', 'Desserts'] as $c)
                    echo '<option' . (($e['category'] ?? '') === $c ? ' selected' : '') . '>' . $c . '</option>';
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <input name="price" type="number" step="0.01" class="form-control"
                    placeholder="Price" required value="<?=e($e['price']??'')?>">
        </div>
        <div class="col-md-1">
            <input name="stock" type="number" class="form-control" placeholder="Stock"
                    value="<?=e($e['stock']??50)?>">
        </div>
        <div class="col-md-2">
            <input name="low_stock" type="number" class="form-control"
                    placeholder="Low-stock at" value="<?=e($e['low_stock']??10)?>">
        </div>
        <div class="col-md-6">
            <textarea name="description" class="form-control" placeholder="Description"><?=e($e['description']??'')?></textarea>
        </div>
        <div class="col-md-4">
            <input type="file" name="image" class="form-control">
        </div>
        <div class="col-md-2">
            <label><input type="checkbox" name="available"
                    <?=($e['available']??1)?'checked':''?>> Available</label>
        </div>
    </div>
    <div class="mt-2">
        <button name="save" value="1" class="btn btn-o">Save item</button>
        <?php
        if ($e)
            echo '<a href="index.php" class="btn btn-outline-secondary">Cancel</a>';
        ?>
    </div>
</form>
<h5>Menu Items &amp; Inventory</h5>
<table class="table table-sm">
    <tr>
        <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Stock</th>
        <th></th>
    </tr>
    <?php foreach ($items as $i): ?>
        <tr>
            <td><?= e($i['name']) ?><?= $i['available'] ? ''
                : ' <span class="badge bg-secondary">hidden</span>' ?></td>
            <td><?= e($i['category']) ?></td>
            <td><?= money($i['price']) ?></td>
            <td><?= $i['stock'] ?></td>
            <td><a href="?edit=<?=$i['id']?>" class="btn btn-sm btn-outline-dark">Edit</a>
            <a href="?del=<?=$i['id']?>" onclick="return confirm('Delete this item?')"
                    class="btn btn-sm btn-outline-danger">Delete</a></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php
foot();
