<?php

require_once "includes/session.php";
require_once "includes/db.php";

$cart = $_SESSION['cart'] ?? [];

$items = [];

$subtotal = 0;

if (!empty($cart)) {

    $food_ids = array_keys($cart);

    $placeholders = implode(
        ',',
        array_fill(0, count($food_ids), '?')
    );

    $types = str_repeat('i', count($food_ids));

    $sql = "
        SELECT
            food_id,
            food_name,
            price,
            image,
            availability
        FROM foods
        WHERE food_id IN ($placeholders)
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$food_ids
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($food = mysqli_fetch_assoc($result)) {

        $food_id = $food['food_id'];

        $quantity = $cart[$food_id];

        $total = $food['price'] * $quantity;

        $food['quantity'] = $quantity;

        $food['total'] = $total;

        $items[] = $food;

        $subtotal += $total;
    }

    mysqli_stmt_close($stmt);
}

$delivery_charge = 0;

if ($subtotal > 0) {
    $delivery_charge = 50;
}

$grand_total = $subtotal + $delivery_charge;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Your Cart | Timeout Cafe</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <link
        rel="stylesheet"
        href="assets/css/responsive.css">

</head>

<body>

<?php include "includes/navbar.php"; ?>


<main class="cart-page">

    <div class="cart-header">

        <p>YOUR ORDER</p>

        <h1>Shopping Cart</h1>

    </div>


    <?php if (empty($items)): ?>

        <div class="empty-cart">

            <h2>Your cart is empty</h2>

            <p>
                Add some delicious food to your cart.
            </p>

            <a
                href="menu.php"
                class="btn-primary">

                Browse Menu

            </a>

        </div>

    <?php else: ?>


        <div class="cart-layout">


            <!-- CART ITEMS -->

            <section class="cart-items">

                <?php foreach ($items as $item): ?>

                    <article class="cart-item">


                        <div class="cart-item-image">

                            <img
                                src="assets/images/foods/<?php echo htmlspecialchars($item['image']); ?>"
                                alt="<?php echo htmlspecialchars($item['food_name']); ?>"
                                onerror="this.src='assets/images/foods/default.jpg';">

                        </div>


                        <div class="cart-item-info">

                            <h3>
                                <?php echo htmlspecialchars($item['food_name']); ?>
                            </h3>

                            <p>
                                Rs.
                                <?php echo number_format($item['price'], 2); ?>
                            </p>


                            <div class="quantity-control">

                                <form
                                    method="POST"
                                    action="cart_actions.php">

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update">

                                    <input
                                        type="hidden"
                                        name="food_id"
                                        value="<?php echo $item['food_id']; ?>">

                                    <input
                                        type="number"
                                        name="quantity"
                                        value="<?php echo $item['quantity']; ?>"
                                        min="1"
                                        max="20">

                                    <button type="submit">
                                        Update
                                    </button>

                                </form>


                                <form
                                    method="POST"
                                    action="cart_actions.php">

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="remove">

                                    <input
                                        type="hidden"
                                        name="food_id"
                                        value="<?php echo $item['food_id']; ?>">

                                    <button
                                        type="submit"
                                        class="remove-btn">

                                        Remove

                                    </button>

                                </form>

                            </div>

                        </div>


                        <div class="cart-item-total">

                            Rs.
                            <?php echo number_format($item['total'], 2); ?>

                        </div>

                    </article>

                <?php endforeach; ?>


                <form
                    method="POST"
                    action="cart_actions.php">

                    <input
                        type="hidden"
                        name="action"
                        value="clear">

                    <button
                        type="submit"
                        class="clear-cart">

                        Clear Cart

                    </button>

                </form>

            </section>


            <!-- SUMMARY -->

            <aside class="cart-summary">

                <h2>Order Summary</h2>


                <div class="summary-row">

                    <span>Subtotal</span>

                    <strong>
                        Rs. <?php echo number_format($subtotal, 2); ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>Delivery</span>

                    <strong>
                        Rs. <?php echo number_format($delivery_charge, 2); ?>
                    </strong>

                </div>


                <hr>


                <div class="summary-total">

                    <span>Total</span>

                    <strong>
                        Rs. <?php echo number_format($grand_total, 2); ?>
                    </strong>

                </div>


                <?php if (!empty($_SESSION['customer_id'])): ?>

                    <a
                        href="checkout.php"
                        class="checkout-btn">

                        Proceed to Checkout

                    </a>

                <?php else: ?>

                    <a
                        href="login.php"
                        class="checkout-btn">

                        Login to Checkout

                    </a>

                <?php endif; ?>


                <a
                    href="menu.php"
                    class="continue-shopping">

                    Continue Shopping

                </a>

            </aside>

        </div>

    <?php endif; ?>

</main>


<?php include "includes/footer.php"; ?>

</body>

</html>