<?php
require '../config.php';
need_role('cashier');
$o = q("SELECT o.*,p.method,p.tendered,p.reference,p.paid_at FROM orders o"
    . " JOIN payments p ON p.order_id=o.id WHERE o.id=?", [$_GET['id'] ?? 0])[0] ?? null;
if (!$o)
    go('pos.php');
head('Receipt', true);
$its = q("SELECT * FROM order_items WHERE order_id=?", [$o['id']]);
?>
<div class="card card-body mx-auto" style="max-width:380px">
    <h4 class="text-center">Chef Janno's Chill &amp; Grill</h4>
    <p class="text-center small text-muted mb-2">Official Receipt</p>
    <div class="ordno text-center" style="font-size:2.4rem">
        Order <?= olabel($o) ?>
    </div>
    <p class="text-center small"><?= date('M j, Y', strtotime($o['paid_at'])) ?> ·
    <?= date('g:i A', strtotime($o['paid_at'])) ?></p>
    <hr>
    <?php
    foreach ($its as $i)
        echo '<div class="d-flex justify-content-between"><span>' . $i['qty'] . '× '
            . e($i['name']) . '</span><span>' . money($i['qty'] * $i['price']) . '</span></div>';
    ?>
    <hr>
    <div class="d-flex justify-content-between">
        <b>Total</b><b><?= money($o['total']) ?></b>
    </div>
    <div class="d-flex justify-content-between small">
        <span><?= e($o['method']) ?> tendered</span><span><?= money($o['tendered']) ?></span>
    </div>
    <?php
    if ($o['method'] === 'Cash')
        echo '<div class="d-flex justify-content-between small"><span>Change</span><span>'
            . money($o['tendered'] - $o['total']) . '</span></div>';
    ?>
    <p class="text-center small text-muted mt-3 mb-0">Thank you! Please come again.</p>
</div>
<div class="text-center mt-3 no-print">
    <button class="btn btn-o" onclick="print()">Print</button> <a href="pos.php"
            class="btn btn-outline-dark">Back to POS</a>
</div>
<?php
foot();
