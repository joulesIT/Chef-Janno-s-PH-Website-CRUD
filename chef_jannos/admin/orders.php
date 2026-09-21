<?php
require '../config.php';
need_role('cashier');
if (isset($_GET['cancel'])) {
    set_status($_GET['cancel'], 'cancelled');
    flash('Order cancelled.');
    go('orders.php');
}
$d = $_GET['d'] ?? date('Y-m-d');
head('Manage Orders', true);
$sql = "SELECT o.*,"
    . "COALESCE(o.customer_name,u.name) who,(SELECT GROUP_CONCAT(CONCAT(qty,'× ',"
    . "name) SEPARATOR ', ') FROM order_items WHERE order_id=o.id) items"
    . " FROM orders o LEFT JOIN users u ON u.id=o.user_id ";
$os = $d === 'all' ? q($sql . "ORDER BY o.id DESC LIMIT 200")
    : q($sql . "WHERE o.order_date=? ORDER BY o.id DESC", [$d]);
?>
<form class="row g-2 mb-3">
    <div class="col-auto">
        <input type="date" name="d" value="<?=$d==='all'?date('Y-m-d'):e($d)?>"
                class="form-control">
    </div>
    <div class="col-auto">
        <button class="btn btn-o">Show</button> <a href="?d=all"
                class="btn btn-outline-dark">All orders</a>
    </div>
</form>
<table class="table">
    <tr>
        <th>Order</th>
        <th>Customer</th>
        <th>Items &amp; notes</th>
        <th>Status</th>
        <th>Total</th>
        <th></th>
    </tr>
    <?php foreach ($os as $o): ?>
        <tr>
            <td><b><?= olabel($o) ?></b><br><small class="text-muted"><?= otime($o) ?> ·
            <?= e($o['source']) ?></small></td>
            <td><?= e($o['who']) ?><br><small class="text-muted"><?= e($o['order_type']) ?> <?= e($o['table_ref']) ?></small></td>
            <td><?= e($o['items']) ?><?= $o['special_requests'] ? '<br><small>📝 '
                . e($o['special_requests']) . '</small>' : '' ?><?= $o['allergies']
                ? '<br><small class="text-danger fw-bold">⚠ '
                . e($o['allergies']) . '</small>' : '' ?></td>
            <td><?= badge($o['status']) ?><br><?= paybadge($o['payment_status']) ?></td>
            <td><?= money($o['total']) ?></td>
            <td>
                <?php
                if ($o['payment_status'] === 'paid')
                    echo '<a class="btn btn-sm btn-outline-dark" href="receipt.php?id=' . $o['id']
                        . '">Receipt</a>';
                elseif ($o['status'] !== 'cancelled')
                    echo '<a class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Cancel this order?\')" href="?cancel='
                        . $o['id'] . '">Cancel</a>';
                ?>
            </td>
        </tr>
        <?php
    endforeach;
    if (!$os)
        echo '<tr><td colspan="6" class="text-muted">No orders.</td></tr>';
        ?>
</table>
<?php
foot();
