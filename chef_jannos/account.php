<?php
require 'config.php';
need_login();
$uid = user()['id'];
if (isset($_POST['review'])) {
    $o = q("SELECT id FROM orders WHERE id=? AND user_id=? AND status='completed'",
        [$_POST['order_id'], $uid]);
    if ($o && !q("SELECT id FROM reviews WHERE order_id=?", [$_POST['order_id']])) {
        q("INSERT INTO reviews(order_id,user_id,rating,comment) VALUES(?,?,?,?)",
            [$_POST['order_id'], $uid, $_POST['rating'], $_POST['comment']]);
        flash('Thanks for your review! It appears once approved.');
    }
    go('account.php');
}
head('My Orders');
$os = q("SELECT o.*,(SELECT GROUP_CONCAT(CONCAT(qty,'× ',name) SEPARATOR ', ')"
    . " FROM order_items WHERE order_id=o.id) items,(SELECT id FROM reviews"
    . " WHERE order_id=o.id) rv FROM orders o WHERE user_id=?"
    . " ORDER BY id DESC", [$uid]);
$K = [];
foreach ($os as $o)
    if ($o['id'] == ($_GET['new'] ?? 0))
        echo '<div class="alert alert-success text-center"><div class="ordno">' . olabel($o)
            . '</div><b>Your order number</b> · placed ' . otime($o)
            . '<br>We\'ll update the status below automatically. Pay at the counter once it\'s completed.</div>';
?>
<h2>My Orders</h2>
<?php
foreach ($os as $o):
    $paid = $o['payment_status'] === 'paid';
    if (!$paid && $o['status'] !== 'cancelled')
        $K[$o['id']] = $o['status'] . '|' . $o['payment_status'];
    $idx = $paid ? 4
        : (['pending' => 0, 'preparing' => 1, 'served' => 2, 'completed' => 3][$o['status']] ?? -1);
?>
    <div class="card card-body mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <h5 class="mb-0">Order <?= olabel($o) ?> <small class="text-muted fs-6">·
            <?= date('M j', strtotime($o['created'])) ?>, <?= otime($o) ?></small></h5>
            <?= $paid ? '<span class="badge bg-success">Paid</span>' : badge($o['status']) ?>
        </div>
        <div class="my-2">
            <?= e($o['items']) ?>
        </div>
        <?php if ($o['status'] !== 'cancelled'): ?>
            <div class="d-flex gap-1 flex-wrap mb-2">
                <?php
                foreach (['Pending', 'Preparing', 'Served', 'Completed', 'Paid'] as $k => $l)
                    echo '<span class="stp' . ($k <= $idx ? ' on' : '') . '">' . $l . '</span>';
                ?>
            </div>
        <?php endif; ?>
        <?php
        if ($o['special_requests'] || $o['allergies'])
            echo '<small class="text-muted">Notes: ' . e($o['special_requests'])
                . ($o['allergies'] ? ' · Allergy: ' . e($o['allergies']) : '') . '</small>';
        ?>
        <div>
            <b><?= money($o['total']) ?></b>
            <?php
            if ($o['status'] === 'completed' && !$paid)
                echo ' <small class="text-success">— ready! Please pay at the counter.</small>';
            ?>
        </div>
        <?php if ($o['status'] === 'completed' && !$o['rv']): ?>
            <form method="post" class="row g-2 mt-1">
                <input type="hidden" name="order_id" value="<?=$o['id']?>">
                <div class="col-auto">
                    <select name="rating" class="form-select">
                        <?php
                        for ($i = 5; $i >= 1; $i--)
                            echo "<option>$i</option>";
                        ?>
                    </select>
                </div>
                <div class="col">
                    <input name="comment" class="form-control"
                            placeholder="How was your order?" required>
                </div>
                <div class="col-auto">
                    <button name="review" value="1" class="btn btn-o">Review</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php
endforeach;
if (!$os)
    echo '<p class="text-muted">No orders yet. <a href="menu.php">Order now</a></p>';
    ?>
<script>
    const K = <?=json_encode((object)$K)?>;
    setInterval(async () => {
        for (const id in K) {
            const d = await (await fetch('api/index.php?r=track&id=' + id)).json();
            if (d.status + '|' + d.payment !== K[id]) {
                location.reload();
                return;
            }
        }
    }, 5000);
</script>
<hr class="my-5">
<?php
profile_form();
foot();
