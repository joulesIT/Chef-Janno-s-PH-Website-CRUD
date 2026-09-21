<?php
require 'config.php';
$cart = &$_SESSION['cart'];
$cart = $cart ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['qty'] ?? [] as $id => $n) {
        if ((int)$n <= 0)
            unset($cart[$id]);
        else
            $cart[$id] = (int)$n;
    }
    if (isset($_POST['place']) && $cart) {
        need_login();
        $oid = create_order(user()['id'], user()['name'], 'online', $_POST['type'],
            $_POST['table_ref'], $_POST['special'], $_POST['allergies'], $cart);
        if ($oid) {
            $cart = [];
            go("account.php?new=$oid");
        }
        flash('Sorry, those items are unavailable right now.', 'danger');
    }
    go('cart.php');
}
head('Cart');
$total = 0;
?>
<h2>Your Cart</h2>
<?php if (!$cart): ?>
    <p>Your cart is empty. <a href="menu.php">Browse the menu</a></p>
<?php else: ?>
    <form method="post">
        <table class="table">
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th width="100">Qty</th>
                <th>Subtotal</th>
            </tr>
            <?php
            foreach ($cart as $id => $n) {
                $i = q("SELECT * FROM items WHERE id=?", [$id])[0];
                $total += $i['price'] * $n;
            ?>
            <tr>
                <td><?= e($i['name']) ?></td>
                <td><?= money($i['price']) ?></td>
                <td><input type="number" min="0" name="qty[<?=$id?>]" value="<?=$n?>"
                        class="form-control form-control-sm"></td>
                <td><?= money($i['price'] * $n) ?></td>
            </tr>
        <?php } ?>
            <tr>
                <td colspan="3" class="text-end"><b>Total</b></td>
                <td><b><?= money($total) ?></b></td>
            </tr>
        </table>
        <button class="btn btn-outline-dark mb-4">Update quantities</button>
        <div class="card card-body">
            <h5>Checkout</h5>
            <div class="row g-2">
                <div class="col-md-6">
                    <label>Order type</label>
                    <select name="type" class="form-select">
                        <option>Dine-in</option>
                        <option>Take-out</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Table number / name</label><input name="table_ref"
                            class="form-control">
                </div>
                <div class="col-md-6">
                    <label>Special requests / meal changes</label><textarea name="special"
                            class="form-control"
                            placeholder="e.g. No onions; well-done patty"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="text-danger">Allergy alerts</label><textarea
                            name="allergies" class="form-control"
                            placeholder="e.g. Peanut allergy"></textarea>
                </div>
            </div>
            <?php if (user()): ?>
                <p class="small text-muted mt-3 mb-0">You'll get today's order number. Pay
                at the counter once your order is completed.</p>
                <button name="place" value="1" class="btn btn-o mt-2">Place Order</button>
            <?php else: ?>
                <div class="alert alert-info mt-3 mb-0">
                    Please <a href="signup.php">sign up</a> or <a href="login.php">log in</a>
                    to place your order.
                </div>
            <?php endif; ?>
        </div>
    </form>
    <?php
endif;
foot();
