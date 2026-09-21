<?php
// Hub-and-spoke REST API (JSON) — Principle 4: modules exchange structured JSON over HTTP
require '../config.php';
header('Content-Type: application/json');
function out($d) {
    echo json_encode($d);
    exit;
}
$u = user();
if (!$u) {
    http_response_code(401);
    out(['error' => 'login required']);
}
$r = $_GET['r'] ?? '';
$role = $u['role'];
$staff = $role !== 'client';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($r === 'track') {
    $o = q("SELECT * FROM orders WHERE id=?", [$_GET['id'] ?? 0])[0] ?? null;
    if (!$o || (!$staff && $o['user_id'] != $u['id']))
        out(['error' => 'not found']);
    out(['label' => olabel($o), 'status' => $o['status'], 'payment' => $o['payment_status']]);
}
if (!$staff) {
    http_response_code(403);
    out(['error' => 'forbidden']);
}
if ($r === 'queue') {
    $os = q("SELECT o.*,COALESCE(o.customer_name,u.name) who FROM orders o LEFT"
        . " JOIN users u ON u.id=o.user_id"
        . " WHERE o.status IN('pending','preparing','served') ORDER BY o.id");
    $res = [];
    foreach ($os as $o)
        $res[] = ['id' => $o['id'], 'label' => olabel($o), 'time' => otime($o),
            'who' => $o['who'], 'type' => $o['order_type'], 'table' => $o['table_ref'],
            'special' => $o['special_requests'], 'allergies' => $o['allergies'],
            'status' => $o['status'], 'source' => $o['source'],
            'items' => q("SELECT name,qty FROM order_items WHERE order_id=?", [$o['id']])];
    out($res);
}
if ($r === 'status' && $post && in_array($role, ['kitchen', 'admin'])) {
    out(['ok' => set_status($_POST['id'], $_POST['status'])]);
}
if ($r === 'pay' && $post && in_array($role, ['cashier', 'admin'])) {
    $x = record_payment($_POST['id'], $_POST['method'], $_POST['reference'] ?? '',
        $_POST['tendered'] ?? 0, $u['id']);
    out(['ok' => $x === true, 'message' => $x === true ? 'Payment recorded' : $x]);
}
if ($r === 'lowstock')
    out(q("SELECT name,stock,low_stock FROM items WHERE stock<=low_stock"));
http_response_code(400);
out(['error' => 'unknown route']);
