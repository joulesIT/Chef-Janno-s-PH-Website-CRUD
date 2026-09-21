<?php
require '../config.php';
need_role('cashier');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = create_order(null, trim($_POST['name']) ?: 'Walk-in', 'counter', $_POST['type'],
        $_POST['table_ref'], $_POST['special'], $_POST['allergies'], $_POST['qty'] ?? []);
    if ($oid) {
        $o = q("SELECT * FROM orders WHERE id=?", [$oid])[0];
        flash('Order ' . olabel($o) . ' sent to the kitchen queue at ' . otime($o) . '.');
    } else
        flash('Add at least one available item.', 'danger');
    go('encode.php');
}
head('Encode Order', true);
$items = q("SELECT * FROM items WHERE available=1 AND stock>0 ORDER BY category,id");
$cat = '';
?>
<p class="text-muted">Front-of-house order entry (FR-01, FR-02). The order goes straight to
the kitchen queue with its special requests and allergy alerts.</p>
<form method="post">
    <div class="row g-3">
        <div class="col-lg-7">
            <?php
            foreach ($items as $i):
                if ($i['category'] !== $cat) {
                    $cat = $i['category'];
                    echo '<h6 class="mt-3 text-uppercase text-muted">' . e($cat) . '</h6>';
                }
            ?>
                <div
                        class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <span><?= e($i['name']) ?> <small class="text-muted"><?= money($i['price']) ?></small></span><input
                            type="number" min="0" name="qty[<?=$i['id']?>]" value="0"
                            class="form-control form-control-sm" style="width:80px">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-lg-5">
            <div class="card card-body">
                <label>Customer name</label><input name="name" class="form-control mb-2"
                        placeholder="Walk-in"><label>Order type</label>
                <select name="type" class="form-select mb-2">
                    <option>Dine-in</option>
                    <option>Take-out</option>
                </select>
                <label>Table / reference</label><input name="table_ref"
                        class="form-control mb-2"><label>Special requests / meal changes</label><textarea
                        name="special" class="form-control mb-2"></textarea><label
                        class="text-danger">Allergy alerts</label><textarea name="allergies"
                        class="form-control mb-3"></textarea><button class="btn btn-o">Send
                to Kitchen</button>
            </div>
        </div>
    </div>
</form>
<?php
foot();
