<?php
require 'config.php';
// Resets the 3 staff accounts to their default passwords. Delete this file after use.
$defs = [['Admin', 'admin@chefjannos.com', 'admin123', 'admin'], ['Kitchen Staff',
    'kitchen@chefjannos.com', 'kitchen123', 'kitchen'], ['Cashier', 'cashier@chefjannos.com',
    'cashier123', 'cashier']];
foreach ($defs as $x) {
    $h = password_hash($x[2], PASSWORD_DEFAULT);
    if (q("SELECT id FROM users WHERE email=?", [$x[1]]))
        q("UPDATE users SET name=?,password=?,demo_password=?,role=?"
            . " WHERE email=?", [$x[0], $h, $x[2], $x[3], $x[1]]);
    else
        q("INSERT INTO users(name,email,password,demo_password,role) VALUES(?,?,?,?,?)", [$x[0],
            $x[1], $h, $x[2], $x[3]]);
}
echo '<h3>Staff accounts reset</h3><table border="1" cellpadding="6"><tr>'
    . '<th>Role</th><th>Email</th><th>Password</th></tr>';
foreach ($defs as $x)
    echo '<tr><td>' . $x[3] . '</td><td>' . $x[1] . '</td><td>' . $x[2] . '</td></tr>';
echo '</table><p>'
    . '<a href="login.php">Go to login</a> — then delete reset_staff.php.</p>';
