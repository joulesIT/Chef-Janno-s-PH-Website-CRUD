<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = q("SELECT * FROM users WHERE email=?", [strtolower(trim($_POST['email']))])[0] ?? null;
    if ($u && password_verify($_POST['password'], $u['password'])) {
        $_SESSION['uid'] = $u['id'];
        go(['admin' => 'admin/index.php', 'kitchen' => 'admin/kitchen.php',
            'cashier' => 'admin/pos.php'][$u['role']] ?? 'index.php');
    }
    flash('Invalid email or password.', 'danger');
}
head('Login');
?>
<h2>Login</h2>
<form method="post" class="card card-body" style="max-width:420px">
    <input type="email" name="email" class="form-control mb-2" placeholder="Email" required><input
            type="password" name="password" class="form-control mb-3" placeholder="Password"
            required><button class="btn btn-o">Login</button><small class="mt-2">New here?
    <a href="signup.php">Sign up</a></small>
</form>
<?php
foot();
