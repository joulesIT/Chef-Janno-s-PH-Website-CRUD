<?php
session_start();
// DB: change 3307 to 3306 if your XAMPP MySQL uses the default port
$db = new mysqli('127.0.0.1', 'root', '', 'chef_jannos', 3307);
$db->set_charset('utf8mb4');
date_default_timezone_set('Asia/Manila');
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES);
}
function q($sql, $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p)
        $st->bind_param(str_repeat('s', count($p)), ...$p);
    $st->execute();
    $r = $st->get_result();
    return $r === false ? $db->insert_id : $r->fetch_all(MYSQLI_ASSOC);
}
function flash($m, $t = 'success') {
    $_SESSION['f'] = [$m, $t];
}
function go($u) {
    header("Location: $u");
    exit;
}
function user() {
    static $u;
    if (!$u && isset($_SESSION['uid']))
        $u = (q("SELECT * FROM users WHERE id=?", [$_SESSION['uid']])[0] ?? null);
    return $u;
}
function need_login() {
    if (!user()) {
        flash('Please log in first.', 'warning');
        go('login.php');
    }
}
// Role-based access (FR-12 / IP-06): admin may open everything, other staff only the roles listed
function need_role(...$r) {
    $u = user();
    if (!$u || ($u['role'] !== 'admin' && !in_array($u['role'], $r)))
        go('../login.php');
}
function upload($k) {
    if (!empty($_FILES[$k]['name']) && $_FILES[$k]['error'] === 0) {
        $x = strtolower(pathinfo($_FILES[$k]['name'], PATHINFO_EXTENSION));
        if (in_array($x, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $n = uniqid() . ".$x";
            move_uploaded_file($_FILES[$k]['tmp_name'], __DIR__ . "/uploads/$n");
            return $n;
        }
    }
    return null;
}
function img($i, $p = '') {
    return $i ? '<img src="' . $p . 'uploads/' . e($i) . '" class="card-img-top" alt="">'
        : '<div class="emoji">🍔</div>';
}
function money($n) {
    return '₱' . number_format($n, 2);
}
function badge($s) {
    $c = ['pending' => 'secondary', 'preparing' => 'warning', 'served' => 'info',
        'completed' => 'success', 'cancelled' => 'danger'];
    return '<span class="badge bg-' . ($c[$s] ?? 'dark') . '">' . ucfirst($s) . '</span>';
}
function paybadge($s) {
    return $s === 'paid' ? '<span class="badge bg-success">Paid</span>'
        : '<span class="badge bg-outline text-danger border border-danger">Unpaid</span>';
}
// Order number of the day, e.g. order 0069 = 69th order of that day
function olabel($o) {
    return '#' . str_pad($o['order_no'], 4, '0', STR_PAD_LEFT);
}
function otime($o) {
    return date('g:i A', strtotime($o['created']));
}
function set_status($id, $s) {
    if (!in_array($s, ['pending', 'preparing', 'served', 'completed', 'cancelled']))
        return false;
    q("UPDATE orders SET status=? WHERE id=? AND payment_status='unpaid'", [$s, $id]);
    return true;
}
// IP-01: create order -> lands in kitchen queue as "pending"
function create_order($uid, $name, $src, $type, $tbl, $sp, $al, $lines) {
    $total = 0;
    $rows = [];
    foreach ($lines as $id => $n) {
        $n = (int)$n;
        if ($n <= 0)
            continue;
        $i = q("SELECT * FROM items WHERE id=? AND available=1 AND stock>0", [$id])[0] ?? null;
        if ($i) {
            $rows[] = [$i, $n];
            $total += $i['price'] * $n;
        }
    }
    if (!$rows)
        return 0;
    $oid = 0;
    for ($t = 0; $t < 5 && !$oid; $t++) {
        try {
            $no = q("SELECT COALESCE(MAX(order_no),0)+1 n FROM orders"
                . " WHERE order_date=CURDATE()")[0]['n'];
            $oid = q("INSERT INTO "
                . "orders(order_no,order_date,user_id,customer_name,source,order_type,table_ref,special_requests,allergies,"
                . "total) VALUES(?,CURDATE(),?,?,?,?,?,?,?,?)", [$no, $uid, $name, $src, $type, $tbl,
                $sp, $al, $total]);
        } catch (mysqli_sql_exception $e) {
            $oid = 0;
        }
    }
    if (!$oid)
        return 0;
    foreach ($rows as [$i, $n])
        q("INSERT INTO order_items(order_id,item_id,name,qty,price) VALUES(?,?,?,?,?)", [$oid,
            $i['id'], $i['name'], $n, $i['price']]);
    return $oid;
}
// IP-02 + IP-03: POS payment linked to a completed order, then inventory auto-deducts
function record_payment($oid, $method, $ref, $tend, $cid) {
    $o = q("SELECT * FROM orders WHERE id=?", [$oid])[0] ?? null;
    if (!$o || $o['status'] !== 'completed' || $o['payment_status'] !== 'unpaid')
        return 'Order must be completed and unpaid before payment.';
    if ($method === 'Cash' && (float)$tend < (float)$o['total'])
        return 'Cash tendered is less than the total.';
    q("INSERT INTO payments(order_id,amount,tendered,method,reference,"
        . "cashier_id) VALUES(?,?,?,?,?,?)", [$oid, $o['total'], $method === 'Cash' ? $tend
        : $o['total'], $method, $ref, $cid]);
    q("UPDATE orders SET payment_status='paid' WHERE id=?", [$oid]);
    foreach (q("SELECT item_id,qty FROM order_items WHERE order_id=?", [$oid]) as $r) {
        q("UPDATE items SET stock=GREATEST(stock-?,0) WHERE id=?", [$r['qty'], $r['item_id']]);
        q("INSERT INTO stock_movements(item_id,qty,order_id) VALUES(?,?,?)", [$r['item_id'],
            $r['qty'], $oid]);
    }
    return true;
}
// Auto-upgrade: brings an older database up to the current schema (safe to run repeatedly)
function col($t, $c) {
    return (bool)q("SHOW COLUMNS FROM $t LIKE '$c'");
}
function ddl($x) {
    try {
        q($x);
    } catch (mysqli_sql_exception $e) {
    }
}
if (empty($_SESSION['mig'])) {
    if (!col('users', 'demo_password'))
        ddl("ALTER TABLE users ADD demo_password VARCHAR(100) NULL");
    ddl("ALTER TABLE users MODIFY role VARCHAR(15) DEFAULT 'client'");
    ddl("CREATE TABLE IF NOT EXISTS payments(id INT AUTO_INCREMENT PRIMARY KEY,"
        . "order_id INT UNIQUE,amount DECIMAL(8,2),tendered DECIMAL(8,2),"
        . "method VARCHAR(20),reference VARCHAR(60),cashier_id INT,"
        . "paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    ddl("CREATE TABLE IF NOT EXISTS stock_movements(id INT AUTO_INCREMENT PRIMARY KEY,"
        . "item_id INT,qty INT,order_id INT,"
        . "moved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $old = !col('orders', 'payment_status');
    foreach (["order_no INT DEFAULT 0", "order_date DATE NULL",
        "customer_name VARCHAR(100) NULL", "source VARCHAR(15) DEFAULT 'online'",
        "payment_status VARCHAR(10) DEFAULT 'unpaid'"] as $c)
            if (!col('orders', explode(' ', $c)[0]))
                ddl("ALTER TABLE orders ADD $c");
    ddl("ALTER TABLE orders MODIFY status VARCHAR(15) DEFAULT 'pending'");
    if ($old) {
        ddl("UPDATE orders SET status='served' WHERE status='ready'");
        ddl("UPDATE orders SET payment_status='paid' WHERE stock_deducted=1");
        ddl("UPDATE orders o JOIN users u ON u.id=o.user_id"
            . " SET o.customer_name=u.name");
        ddl("UPDATE orders o"
            . " JOIN (SELECT id,ROW_NUMBER() OVER (PARTITION BY DATE(created)"
            . " ORDER BY id) rn FROM orders) t ON t.id=o.id"
            . " SET o.order_no=t.rn,o.order_date=DATE(o.created)");
        ddl("INSERT INTO payments(order_id,amount,tendered,method) SELECT id,total,total,'Cash'"
            . " FROM orders WHERE payment_status='paid' AND id NOT IN(SELECT order_id"
            . " FROM payments)");
        ddl("ALTER TABLE orders ADD UNIQUE KEY uq_day(order_date,order_no)");
    }
    ddl("DROP TABLE IF EXISTS promotions");
    ddl("DROP TABLE IF EXISTS messages");
    $_SESSION['mig'] = 1;
}
ddl("CREATE TABLE IF NOT EXISTS posters(id INT AUTO_INCREMENT PRIMARY KEY,"
    . "image VARCHAR(120),title VARCHAR(150),active TINYINT DEFAULT 1,"
    . "created TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
if ((int)q("SELECT COUNT(*) c FROM users"
    . " WHERE email IN('admin@chefjannos.com','kitchen@chefjannos.com','cashier@chefjannos.com')")[0]['c'] < 3) {
    foreach ([['Admin', 'admin@chefjannos.com', 'admin123', 'admin'], ['Kitchen Staff',
        'kitchen@chefjannos.com', 'kitchen123', 'kitchen'], ['Cashier', 'cashier@chefjannos.com',
        'cashier123', 'cashier']] as $x)
            if (!q("SELECT id FROM users WHERE email=?", [$x[1]]))
                q("INSERT INTO users(name,email,password,demo_password,role) VALUES(?,?,?,?,?)", [$x[0],
                $x[1], password_hash($x[2], PASSWORD_DEFAULT), $x[2], $x[3]]);
}
function flash_show() {
    if (empty($_SESSION['f']))
        return '';
    $h = '<div class="alert alert-' . $_SESSION['f'][1] . '">' . e($_SESSION['f'][0]) . '</div>';
    unset($_SESSION['f']);
    return $h;
}
function head($t, $admin = false, $wide = false) {
    $u = user();
    $n = array_sum($_SESSION['cart'] ?? []);
    $GLOBALS['adm'] = $admin;
    $GLOBALS['wide'] = $wide;
    $cur = basename($_SERVER['PHP_SELF']);
    $p = $admin ? '../' : '';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($t)
        . ' | Chef Janno\'s</title>'
        . '<link rel="stylesheet" '
        . 'href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap">'
        . '<link '
        . 'href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" '
        . 'rel="stylesheet">'
        . '<link rel="icon" href="' . $p . 'assets/logo.png"><link rel="stylesheet" href="' . $p
        . 'css/style.css"></head><body class="' . ($admin ? 'is-admin' : '') . '">';
    if ($admin) {
        $L = [['index.php', '🏠', 'Dashboard', []], ['encode.php', '✍', 'Encode Order',
            ['cashier']], ['kitchen.php', '🍳', 'Kitchen Queue', ['kitchen']], ['pos.php', '💳',
            'POS Payments', ['cashier']], ['orders.php', '🧾', 'Manage Orders', ['cashier']],
            ['reports.php', '📊', 'Reports', []], ['gallery.php', '🖼', 'Manage Gallery', []],
            ['posters.php', '📣', 'Manage Posters', []], ['reviews.php', '⭐', 'Manage Reviews',
            []], ['staff.php', '👥', 'Manage Staff', []], ['account.php', '⚙', 'Account Settings',
            ['cashier', 'kitchen']]];
        echo '<aside class="sidebar"><div class="sb-logo"><a href="index.php"><img src="' . $p
            . 'assets/logo.png" alt="Chef Janno\'s"></a><p>' . e(ucfirst($u['role']))
            . ' Panel</p></div><nav class="sb-nav">';
        foreach ($L as $l)
            if ($u['role'] === 'admin' || in_array($u['role'], $l[3]))
                echo '<a href="' . $l[0] . '" class="' . ($cur === $l[0] ? 'active' : '') . '"><span>'
                    . $l[1] . '</span>' . $l[2] . '</a>';
        echo '</nav><div class="sb-foot"><a href="../index.php">↗ View website</a>'
            . '<a href="../logout.php">⏻ Logout</a></div></aside><div class="main">'
            . '<div class="topbar"><h1>' . e($t) . '</h1><span class="chip2">' . e($u['name'])
            . '</span></div><div class="content">' . flash_show();
        return;
    }
    $L = ['index.php' => 'Home', 'about.php' => 'About Us', 'menu.php' => 'Menu',
        'gallery.php' => 'Gallery', 'cart.php' => 'Cart'
        . ($n ? '<span class="badge-c">' . $n . '</span>' : '')];
    echo '<header class="nav"><a class="nav-logo" href="index.php">'
        . '<img src="assets/logo.png" alt="Chef Janno\'s"></a>'
        . '<ul class="nav-links" id="nl">';
    foreach ($L as $h => $l)
        echo '<li><a href="' . $h . '" class="' . ($cur === $h ? 'active' : '') . '">' . $l
            . '</a></li>';
    echo '</ul><div class="nav-actions">';
    if ($u)
        echo ($u['role'] !== 'client' ? '<a class="btn btn-ghost btn-sm" href="admin/'
            . ($u['role'] === 'kitchen' ? 'kitchen' : ($u['role'] === 'cashier' ? 'pos' : 'index'))
            . '.php">Staff Panel</a>'
            : '<a class="btn btn-ghost btn-sm" href="account.php">My Orders</a>')
            . '<a class="btn btn-o btn-sm" href="logout.php">Logout</a>';
    else
        echo '<a class="btn btn-ghost btn-sm" href="login.php">Login</a>'
            . '<a class="btn btn-o btn-sm" href="signup.php">Sign Up</a>';
    echo '<button class="burger" '
        . 'onclick="document.getElementById(\'nl\').classList.toggle(\'open\')">☰</button>'
        . '</div></header>';
    if (!$wide)
        echo '<main class="container page">' . flash_show();
}
function foot() {
    if (!empty($GLOBALS['adm'])) {
        echo '</div></div></body></html>';
        return;
    }
    if (empty($GLOBALS['wide']))
        echo '</main>';
    echo '<footer class="site"><div class="fgrid"><div>'
        . '<a class="nav-logo" href="index.php">'
        . '<img src="assets/logo.png" alt="Chef Janno\'s"></a>'
        . '<p>Chill &amp; Grill — fresh-to-order gourmet burgers and Filipino rice meals. '
        . 'Family-owned by Jonas &amp; Cherish Ragas since 2021.</p>'
        . '</div><div><h4>Explore</h4><a href="menu.php">Menu</a>'
        . '<a href="gallery.php">Gallery</a><a href="about.php">About Us</a></div><div>'
        . '<h4>Your Account</h4><a href="signup.php">Sign Up</a>'
        . '<a href="login.php">Login</a><a href="cart.php">Cart</a>'
        . '<a href="account.php">My Orders</a></div></div><div class="fbot">© ' . date('Y')
        . ' Chef Janno\'s Chill &amp; Grill · All rights reserved</div></footer>'
        . '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">'
        . '</script></body></html>';
}
function profile_form() {
    $u = user();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        q("UPDATE users SET name=?,phone=? WHERE id=?", [$_POST['name'], $_POST['phone'],
            $u['id']]);
        if ($_POST['password'] !== '') {
            if (strlen($_POST['password']) < 6) {
                flash('Password must be at least 6 characters.', 'danger');
                go(basename($_SERVER['PHP_SELF']));
            }
            q("UPDATE users SET password=?,demo_password=? WHERE id=?",
                [password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['password'], $u['id']]);
        }
        flash('Account updated.');
        go(basename($_SERVER['PHP_SELF']));
    }
    echo '<h3>Account Settings</h3>'
        . '<form method="post" class="card card-body mb-4" style="max-width:480px">'
        . '<label>Name</label>'
        . '<input name="name" class="form-control mb-2" required value="' . e($u['name'])
        . '"><label>Email</label><input class="form-control mb-2" disabled value="'
        . e($u['email'])
        . '"><label>Phone</label><input name="phone" class="form-control mb-2" value="'
        . e($u['phone'])
        . '"><label>New password (leave blank to keep)</label>'
        . '<input type="password" name="password" class="form-control mb-3">'
        . '<button class="btn btn-o">Save</button></form>';
}
