<?php
require '../config.php';
need_role();
if (isset($_POST['add'])) {
    $em = strtolower(trim($_POST['email']));
    if (q("SELECT id FROM users WHERE email=?", [$em]))
        flash('Email already used.', 'danger');
    elseif (strlen($_POST['password']) < 6)
        flash('Password min 6 characters.', 'danger');
    else {
        q("INSERT INTO users(name,email,password,demo_password,role) VALUES(?,?,?,?,?)",
            [$_POST['name'], $em, password_hash($_POST['password'], PASSWORD_DEFAULT),
            $_POST['password'], $_POST['role']]);
        flash('Staff account created.');
    }
    go('staff.php');
}
if (isset($_GET['del']) && $_GET['del'] != user()['id']) {
    q("DELETE FROM users WHERE id=? AND role IN('kitchen','cashier')", [$_GET['del']]);
    go('staff.php');
}
head('Manage Staff', true);
$s = q("SELECT * FROM users WHERE role<>'client' ORDER BY role,id");
?>
<p class="text-muted">Role-based access (FR-12): <b>kitchen</b> sees only the Kitchen Queue;
<b>cashier</b> can encode orders, take payments and view orders; <b>admin</b> (owner) sees
everything.</p>
<form method="post" class="row g-2 mb-4">
    <div class="col-md-3">
        <input name="name" class="form-control" placeholder="Name" required>
    </div>
    <div class="col-md-3">
        <input type="email" name="email" class="form-control" placeholder="Email" required>
    </div>
    <div class="col-md-2">
        <input name="password" class="form-control" placeholder="Password" required>
    </div>
    <div class="col-md-2">
        <select name="role" class="form-select">
            <option>kitchen</option>
            <option>cashier</option>
        </select>
    </div>
    <div class="col">
        <button name="add" value="1" class="btn btn-o">Add staff</button>
    </div>
</form>
<table class="table">
    <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th></th>
    </tr>
    <?php
    foreach ($s as $x)
        echo '<tr><td>' . e($x['name']) . '</td><td>' . e($x['email'])
            . '</td><td><span class="badge bg-dark">' . e($x['role']) . '</span></td><td>'
            . (in_array($x['role'], ['kitchen', 'cashier']) ? '<a href="?del=' . $x['id']
            . '" onclick="return confirm(\'Delete?\')" class="btn btn-sm btn-outline-danger">Delete</a>'
            : '') . '</td></tr>';
    ?>
</table>
<?php
foot();
