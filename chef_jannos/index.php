<?php
require 'config.php';
head('Home', false, true);
$items = q("SELECT * FROM items WHERE available=1 ORDER BY id LIMIT 3");
$rev = q("SELECT r.*,u.name FROM reviews r JOIN users u ON u.id=r.user_id"
    . " WHERE approved=1 ORDER BY r.id DESC LIMIT 3");
$m = 'BURGERS ✦ FILIPINO RICE MEALS ✦ FRESH TO ORDER ✦ FAMILY-OWNED ✦ ';
?>
<section class="hero">
    <div class="hero-inner">
        <img src="assets/logo.png" class="hero-logo" alt="Chef Janno's"><span
                class="eyebrow">Est. 2021 · Chill &amp; Grill</span>
        <h1>Big flavor,<br>grilled <em>fresh</em><br>to order.</h1>
        <p>Gourmet burgers and Filipino rice meals from a family kitchen. Order online, add
        your special requests, and track it live from kitchen to table.</p>
        <a href="menu.php" class="btn btn-o btn-lg">Order Now</a> <a href="about.php"
                class="btn btn-light2 btn-lg">Our Story</a>
        <div class="hero-stats">
            <div>
                <b>2021</b><span>Founded</span>
            </div>
            <div>
                <b>Live</b><span>Order tracking</span>
            </div>
            <div>
                <b>100%</b><span>Fresh to order</span>
            </div>
        </div>
    </div>
</section>
<div class="marquee">
    <div>
        <?= str_repeat($m, 8) ?>
    </div>
</div>
<div class="container pt-4">
    <?= flash_show() ?>
</div>
<section class="sec">
    <div class="wrap">
        <span class="tag">Crowd Favorites</span>
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
            <h2 class="mb-0">Fired up &amp; <em>fan-approved</em></h2>
            <a href="menu.php" class="btn btn-ghost btn-sm">See full menu →</a>
        </div>
        <div class="grid3">
            <?php foreach ($items as $i): ?>
                <div class="card h-100">
                    <?= img($i['image']) ?>
                    <div class="card-body">
                        <small class="text-muted text-uppercase"><?= e($i['category']) ?></small>
                        <h5><?= e($i['name']) ?></h5>
                        <p class="text-muted small"><?= e($i['description']) ?></p>
                        <b class="fs-4"><?= money($i['price']) ?></b>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
$posters = q("SELECT * FROM posters WHERE active=1 ORDER BY id DESC");
if ($posters):
?>
    <section class="sec sec-yellow">
        <div class="wrap">
            <span class="tag">Promos &amp; Announcements</span>
            <h2>What's <em>cooking</em> at Janno's</h2>
            <div class="poster-grid">
                <?php foreach ($posters as $x): ?>
                    <a href="uploads/<?=e($x['image'])?>" data-bs-toggle="modal"
                            data-bs-target="#posterModal"
                            data-src="uploads/<?=e($x['image'])?>"><img
                            src="uploads/<?=e($x['image'])?>"
                            alt="<?=e($x['title']?:'Chef Janno\'s poster')?>">
                    <?php if ($x['title']): ?>
                        <span><?= e($x['title']) ?></span>
                    <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <div class="modal fade" id="posterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <img id="posterBig" class="w-100 rounded-4" alt="">
            </div>
        </div>
    </div>
    <script>
        document.getElementById('posterModal').addEventListener('show.bs.modal', (e) => {
            document.getElementById('posterBig').src = e.relatedTarget.dataset.src;
        });
    </script>
<?php endif; ?>
<section class="sec sec-mist">
    <div class="wrap">
        <span class="tag">How it works</span>
        <h2>From order to table, <em>tracked</em></h2>
        <div class="grid3">
            <div class="feat">
                <i>01</i>
                <h5 class="mt-2">Place your order</h5>
                <p class="text-muted mb-0">Sign up, add items, and include special requests
                or allergy alerts. You get an order number for the day, like #0069.</p>
            </div>
            <div class="feat">
                <i>02</i>
                <h5 class="mt-2">Watch it get made</h5>
                <p class="text-muted mb-0">Your order jumps straight into the kitchen queue.
                Track it: received, preparing, served, completed.</p>
            </div>
            <div class="feat">
                <i>03</i>
                <h5 class="mt-2">Pay at the counter</h5>
                <p class="text-muted mb-0">Once your order is completed, our cashier records
                your payment and hands you a receipt.</p>
            </div>
        </div>
    </div>
</section>
<section class="sec sec-dark">
    <div class="wrap">
        <span class="tag" style="color:var(--mustard)">Kind words</span>
        <h2>What our guests say</h2>
        <div class="grid3">
            <?php foreach ($rev as $r): ?>
                <div>
                    <div>
                        <?= str_repeat('⭐', $r['rating']) ?>
                    </div>
                    <p class="quote mt-2">“<?= e($r['comment']) ?>”</p>
                    <small style="color:var(--mustard)">— <?= e($r['name']) ?></small>
                </div>
                <?php
            endforeach;
            if (!$rev)
                echo '<p style="opacity:.6">Be the first to leave a review after your order!</p>';
                ?>
        </div>
    </div>
</section>
<section class="cta">
    <span class="eyebrow">Hungry?</span>
    <h2>Your burger is <em>waiting.</em></h2>
    <a href="menu.php" class="btn btn-o btn-lg">Start Your Order</a>
</section>
<?php
foot();
