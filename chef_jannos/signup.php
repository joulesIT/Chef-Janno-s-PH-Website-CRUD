<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $em = strtolower(trim($_POST['email']));
    if (strlen($_POST['password']) < 6)
        flash('Password must be at least 6 characters.', 'danger');
    elseif (q("SELECT id FROM users WHERE email=?", [$em]))
        flash('Email already registered.', 'danger');
    else {
        $_SESSION['uid'] = q("INSERT INTO users(name,email,phone,password,demo_password) VALUES(?,?,?,?,?)",
            [$_POST['name'], $em, $_POST['phone'],
            password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['password']]);
        flash('Welcome! You can now order.');
        go('menu.php');
    }
}
head('Sign Up');
?>
<h2>Create an account</h2>
<form method="post" class="card card-body" style="max-width:480px">
    <input name="name" class="form-control mb-2" placeholder="Full name" required><input
            type="email" name="email" class="form-control mb-2" placeholder="Email" required><input
            name="phone" class="form-control mb-2" placeholder="Phone"><input
            type="password" name="password" class="form-control mb-3"
            placeholder="Password (min 6)" required><button class="btn btn-o">Sign Up</button><small
            class="mt-2">Have an account? <a href="login.php">Log in</a></small>
</form>
<?php
foot();
