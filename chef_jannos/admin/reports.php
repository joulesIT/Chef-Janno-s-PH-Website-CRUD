<?php
require '../config.php';
need_role();
$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? $from;
if ($to < $from)
    $to = $from;
$R = [$from, $to];
$sum = q("SELECT COUNT(*) n,COALESCE(SUM(amount),0) s FROM payments"
    . " WHERE DATE(paid_at) BETWEEN ? AND ?", $R)[0];
$meth = q("SELECT method,COUNT(*) n,SUM(amount) s FROM payments"
    . " WHERE DATE(paid_at) BETWEEN ? AND ? GROUP BY method", $R);
$day = q("SELECT DATE(paid_at) d,COUNT(*) n,SUM(amount) s FROM payments"
    . " WHERE DATE(paid_at) BETWEEN ? AND ? GROUP BY DATE(paid_at) ORDER BY d", $R);
$top = q("SELECT oi.name,SUM(oi.qty) q,SUM(oi.qty*oi.price) s"
    . " FROM order_items oi JOIN payments p ON p.order_id=oi.order_id"
    . " WHERE DATE(p.paid_at) BETWEEN ? AND ? GROUP BY oi.name"
    . " ORDER BY q DESC LIMIT 10", $R);
$inv = q("SELECT i.*,COALESCE((SELECT SUM(qty) FROM stock_movements m"
    . " WHERE m.item_id=i.id AND DATE(m.moved_at) BETWEEN ? AND ?),0) used"
    . " FROM items i ORDER BY category,name", $R);
$log = q("SELECT m.*,i.name,o.order_no FROM stock_movements m"
    . " JOIN items i ON i.id=m.item_id JOIN orders o ON o.id=m.order_id"
    . " WHERE DATE(m.moved_at) BETWEEN ? AND ? ORDER BY m.id DESC LIMIT 50", $R);
head('Reports', true);
$t = date('Y-m-d');
?>
<form class="row g-2 mb-3 no-print">
    <div class="col-auto">
        <input type="date" name="from" value="<?=$from?>" class="form-control">
    </div>
    <div class="col-auto">
        <input type="date" name="to" value="<?=$to?>" class="form-control">
    </div>
    <div class="col-auto">
        <button class="btn btn-o">Generate</button>
    </div>
    <div class="col-auto">
        <a class="btn btn-outline-dark" href="?from=<?=$t?>&to=<?=$t?>">Today</a> <a
                class="btn btn-outline-dark"
                href="?from=<?=date('Y-m-d',strtotime('monday this week'))?>&to=<?=$t?>">This
        week</a> <a class="btn btn-outline-dark" href="?from=<?=date('Y-m-01')?>&to=<?=$t?>">This
        month</a> <button type="button" class="btn btn-outline-dark" onclick="print()">🖨
        Print</button>
    </div>
</form>
<p class="text-muted">Report period: <b><?= $from ?></b> to <b><?= $to ?></b></p>
<h4>Sales report</h4>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card card-body">
            <small>Total sales</small>
            <h3><?= money($sum['s']) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-body">
            <small>Paid orders</small>
            <h3><?= $sum['n'] ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-body">
            <small>Average order</small>
            <h3><?= money($sum['n'] ? $sum['s'] / $sum['n'] : 0) ?></h3>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-6">
        <table class="table">
            <tr>
                <th>Date</th>
                <th>Orders</th>
                <th>Sales</th>
            </tr>
            <?php
            foreach ($day as $d)
                echo '<tr><td>' . $d['d'] . '</td><td>' . $d['n'] . '</td><td>' . money($d['s'])
                    . '</td></tr>';
            if (!$day)
                echo '<tr><td colspan="3" class="text-muted">No sales in this period.</td></tr>';
            ?>
        </table>
        <table class="table">
            <tr>
                <th>Payment method</th>
                <th>Orders</th>
                <th>Amount</th>
            </tr>
            <?php
            foreach ($meth as $m)
                echo '<tr><td>' . e($m['method']) . '</td><td>' . $m['n'] . '</td><td>'
                    . money($m['s']) . '</td></tr>';
            ?>
        </table>
    </div>
    <div class="col-lg-6">
        <table class="table">
            <tr>
                <th>Top items</th>
                <th>Qty sold</th>
                <th>Sales</th>
            </tr>
            <?php
            foreach ($top as $x)
                echo '<tr><td>' . e($x['name']) . '</td><td>' . $x['q'] . '</td><td>' . money($x['s'])
                    . '</td></tr>';
            ?>
        </table>
    </div>
</div>
<h4 class="mt-4">Inventory report</h4>
<table class="table">
    <tr>
        <th>Item</th>
        <th>Category</th>
        <th>Current stock</th>
        <th>Low-stock at</th>
        <th>Used in period</th>
        <th>Status</th>
    </tr>
    <?php
    foreach ($inv as $i) {
        $s = $i['stock'] <= 0 ? ['OUT', 'danger']
            : ($i['stock'] <= $i['low_stock'] ? ['LOW', 'warning text-dark'] : ['OK', 'success']);
        echo '<tr><td>' . e($i['name']) . '</td><td>' . e($i['category']) . '</td><td>'
            . $i['stock'] . '</td><td>' . $i['low_stock'] . '</td><td>' . $i['used']
            . '</td><td><span class="badge bg-' . $s[1] . '">' . $s[0] . '</span></td></tr>';
    }
    ?>
</table>
<h5>Stock movement</h5>
<table class="table">
    <tr>
        <th>Date / time</th>
        <th>Item</th>
        <th>Qty deducted</th>
        <th>Order</th>
    </tr>
    <?php
    foreach ($log as $l)
        echo '<tr><td>' . date('M j, g:i A', strtotime($l['moved_at'])) . '</td><td>'
            . e($l['name']) . '</td><td>−' . $l['qty'] . '</td><td>#'
            . str_pad($l['order_no'], 4, '0', STR_PAD_LEFT) . '</td></tr>';
    if (!$log)
        echo '<tr><td colspan="4" class="text-muted">No stock movement in this period.</td>'
            . '</tr>';
    ?>
</table>
<?php
foot();
