<?php
require '../config.php';
need_role();
if (isset($_POST['add'])) {
    if ($f = upload('image')) {
        q("INSERT INTO gallery(image,caption) VALUES(?,?)", [$f, $_POST['caption']]);
        flash('Photo added.');
    } else
        flash('Upload a jpg/png/gif/webp image.', 'danger');
    go('gallery.php');
}
if (isset($_GET['del'])) {
    $g = q("SELECT image FROM gallery WHERE id=?", [$_GET['del']])[0] ?? null;
    if ($g)
        @unlink("../uploads/" . $g['image']);
    q("DELETE FROM gallery WHERE id=?", [$_GET['del']]);
    go('gallery.php');
}
head('Manage Gallery', true);
$g = q("SELECT * FROM gallery ORDER BY id DESC");
?>
<form method="post" enctype="multipart/form-data" class="row g-2 mb-4">
    <div class="col-md-4">
        <input type="file" name="image" class="form-control" required>
    </div>
    <div class="col-md-5">
        <input name="caption" class="form-control" placeholder="Caption">
    </div>
    <div class="col">
        <button name="add" value="1" class="btn btn-o">Upload</button>
    </div>
</form>
<div class="row g-3">
    <?php foreach ($g as $x): ?>
        <div class="col-6 col-md-3">
            <div class="card">
                <img src="../uploads/<?=e($x['image'])?>" class="card-img-top" alt="">
                <div class="card-body small">
                    <?= e($x['caption']) ?><br><a href="?del=<?=$x['id']?>"
                            onclick="return confirm('Delete?')" class="text-danger">Delete</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php
foot();
