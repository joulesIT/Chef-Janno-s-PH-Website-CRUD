<?php
require 'config.php';
head('Gallery');
$g = q("SELECT * FROM gallery ORDER BY id DESC");
?>
<h2>Gallery</h2>
<div class="row g-3">
    <?php foreach ($g as $x): ?>
        <div class="col-6 col-md-3">
            <div class="card">
                <img src="uploads/<?=e($x['image'])?>" class="card-img-top" alt="">
                <div class="card-body small">
                    <?= e($x['caption']) ?>
                </div>
            </div>
        </div>
        <?php
    endforeach;
    if (!$g)
        echo '<p class="text-muted">Photos coming soon.</p>';
        ?>
</div>
<?php
foot();
