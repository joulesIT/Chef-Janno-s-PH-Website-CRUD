<?php
require '../config.php';
need_role('cashier');
if (isset($_POST['pay'])) {
    $r = record_payment($_POST['id'], $_POST['method'], $_POST['reference'],
        $_POST['tendered'] !== '' ? $_POST['tendered'] : 0, user()['id']);
    if ($r === true) {
        flash('Payment recorded. Inventory updated.');
        go('receipt.php?id=' . (int)$_POST['id']);
    }
    flash($r, 'danger');
    go('pos.php');
}
head('POS Payments', true);
$os = q("SELECT o.*,"
    . "COALESCE(o.customer_name,u.name) who,(SELECT GROUP_CONCAT(CONCAT(qty,'× ',"
    . "name) SEPARATOR ', ') FROM order_items WHERE order_id=o.id) items"
    . " FROM orders o LEFT JOIN users u ON u.id=o.user_id"
    . " WHERE o.status='completed' AND o.payment_status='unpaid'"
    . " ORDER BY o.id");
$pays = q("SELECT p.*,o.order_no FROM payments p JOIN orders o ON o.id=p.order_id"
    . " ORDER BY p.id DESC LIMIT 15");
?>
<p class="text-muted">Only orders completed by the kitchen appear here (FR-06). Recording
payment automatically deducts inventory (FR-07) and flags low-stock items (FR-08).</p>
<h5>Awaiting payment</h5>
<?php foreach ($os as $o): ?>
    <form method="post" class="card card-body mb-2">
        <input type="hidden" name="id" value="<?=$o['id']?>">
        <div class="d-flex justify-content-between">
            <span><span class="ordno" style="font-size:2rem"><?= olabel($o) ?></span>
            <?= e($o['who']) ?> · <?= e($o['table_ref']) ?></span><b class="fs-4"><?= money($o['total']) ?></b>
        </div>
        <div class="small text-muted mb-2">
            <?= e($o['items']) ?>
        </div>
        <div class="row g-2">
            <div class="col-md-3">
                <select name="method" class="form-select">
                    <option>Cash</option>
                    <option>Card</option>
                    <option>GCash</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" name="tendered" class="form-control"
                        value="<?=$o['total']?>" placeholder="Cash tendered">
            </div>
            <div class="col-md-4">
                <input name="reference" class="form-control"
                        placeholder="Transaction reference (card/GCash)">
            </div>
            <div class="col-md-2">
                <button name="pay" value="1" class="btn btn-o w-100">Record payment</button>
            </div>
        </div>
    </form>
    <?php
endforeach;
if (!$os)
    echo '<p class="text-muted">Nothing awaiting payment.</p>';
    ?>
<h5 class="mt-4">Recent payments</h5>
<table class="table">
    <tr>
        <th>Order</th>
        <th>Method</th>
        <th>Reference</th>
        <th>Amount</th>
        <th>Paid at</th>
        <th></th>
    </tr>
    <?php foreach ($pays as $p): ?>
        <tr>
            <td><b><?= olabel($p) ?></b></td>
            <td><?= e($p['method']) ?></td>
            <td><?= e($p['reference']) ?></td>
            <td><?= money($p['amount']) ?></td>
            <td><?= date('M j, g:i A', strtotime($p['paid_at'])) ?></td>
            <td><a href="receipt.php?id=<?=$p['order_id']?>"
                    class="btn btn-sm btn-outline-dark">Receipt</a></td>
        </tr>
    <?php endforeach; ?>
</table>
<script>
    setInterval(() => {
        if ([...document.querySelectorAll('input[name=reference]')].every((i) => !i.value))
            location.reload();
    }, 15000);
</script>
<?php
foot();
