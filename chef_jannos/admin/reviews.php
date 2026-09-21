<?php
require '../config.php';
need_role();
if (isset($_GET['toggle'])) {
    q("UPDATE reviews SET approved=1-approved WHERE id=?", [$_GET['toggle']]);
    go('reviews.php');
}
if (isset($_GET['del'])) {
    q("DELETE FROM reviews WHERE id=?", [$_GET['del']]);
    go('reviews.php');
}
head('Manage Reviews', true);
$r = q("SELECT r.*,u.name,o.order_no FROM reviews r"
    . " JOIN users u ON u.id=r.user_id JOIN orders o ON o.id=r.order_id"
    . " ORDER BY r.id DESC");
?>
<?php foreach ($r as $x): ?>
    <div class="card card-body mb-2">
        <div class="d-flex justify-content-between">
            <b><?= e($x['name']) ?> · Order #<?= str_pad($x['order_no'], 4, '0', STR_PAD_LEFT) ?> ·
            <?= str_repeat('⭐', $x['rating']) ?></b><?= $x['approved']
                ? '<span class="badge bg-success">Published</span>'
                : '<span class="badge bg-secondary">Hidden</span>' ?>
        </div>
        <p class="mb-1"><?= e($x['comment']) ?></p>
        <div>
            <a href="?toggle=<?=$x['id']?>" class="btn btn-sm btn-outline-dark"><?= $x['approved'] ? 'Hide' : 'Approve' ?></a>
            <a href="?del=<?=$x['id']?>" onclick="return confirm('Delete?')"
                    class="btn btn-sm btn-outline-danger">Delete</a>
        </div>
    </div>
    <?php
endforeach;
if (!$r)
    echo '<p class="text-muted">No reviews yet.</p>';
foot();
