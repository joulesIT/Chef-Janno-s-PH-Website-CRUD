<?php
require '../config.php';
need_role();
if (isset($_POST['add'])) {
    if ($f = upload('image')) {
        q("INSERT INTO posters(image,title) VALUES(?,?)", [$f, $_POST['title']]);
        flash('Poster added to the homepage.');
    } else
        flash('Upload a jpg/png/gif/webp image.', 'danger');
    go('posters.php');
}
if (isset($_GET['toggle'])) {
    q("UPDATE posters SET active=1-active WHERE id=?", [$_GET['toggle']]);
    go('posters.php');
}
if (isset($_GET['del'])) {
    $g = q("SELECT image FROM posters WHERE id=?", [$_GET['del']])[0] ?? null;
    if ($g)
        @unlink("../uploads/" . $g['image']);
    q("DELETE FROM posters WHERE id=?", [$_GET['del']]);
    flash('Poster deleted.');
    go('posters.php');
}
head('Manage Posters', true);
$p = q("SELECT * FROM posters ORDER BY id DESC");
?>
<p class="text-muted">Posters appear in the "Promos &amp; Announcements" section of the
homepage, newest first. Hide a poster to keep it without showing it. The section disappears
when no poster is showing.</p>
<form method="post" enctype="multipart/form-data" class="row g-2 mb-4">
    <div class="col-md-4">
        <input type="file" name="image" class="form-control" accept="image/*" required>
    </div>
    <div class="col-md-5">
        <input name="title" class="form-control"
                placeholder="Title (optional), e.g. Rice Meals Day">
    </div>
    <div class="col">
        <button name="add" value="1" class="btn btn-o">Upload poster</button>
    </div>
</form>
<div class="row g-3">
    <?php foreach ($p as $x): ?>
        <div class="col-6 col-md-3">
            <div class="card">
                <img src="../uploads/<?=e($x['image'])?>" class="card-img-top"
                        style="height:260px" alt="">
                <div class="card-body small">
                    <b><?= e($x['title']) ?></b><br><?= $x['active']
                        ? '<span class="badge bg-success">Showing</span>'
                        : '<span class="badge bg-secondary">Hidden</span>' ?><br><a
                            href="?toggle=<?=$x['id']?>"><?= $x['active'] ? 'Hide' : 'Show' ?></a>
                    · <a href="?del=<?=$x['id']?>"
                            onclick="return confirm('Delete this poster?')"
                            class="text-danger">Delete</a>
                </div>
            </div>
        </div>
        <?php
    endforeach;
    if (!$p)
        echo '<p class="text-muted">No posters yet.</p>';
        ?>
</div>
<?php
foot();
