<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireCustomerLogin();


// --------------------------------------------------
// CHECK CART
// --------------------------------------------------

if (
    !isset($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {

    header("Location: cart.php");
    exit();

}

$cart = $_SESSION['cart'];


// --------------------------------------------------
// GET CART ITEMS
// --------------------------------------------------

$cart_items = [];
$subtotal = 0;


// Get food information one by one
foreach ($cart as $food_id => $quantity) {

    $food_id = (int) $food_id;
    $quantity = (int) $quantity;

    if (
        $food_id <= 0 ||
        $quantity <= 0
    ) {
        continue;
    }


    $sql = "
        SELECT
            food_id,
            food_name,
            price,
            image,
            availability
        FROM foods
        WHERE food_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $food_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $food = mysqli_fetch_assoc($result);


    if (!$food) {
        continue;
    }


    // Don't allow unavailable food
    if (
        $food['availability'] !== 'available'
    ) {
        continue;
    }


    $item_total =
        (float) $food['price'] * $quantity;


    $subtotal += $item_total;


    $cart_items[] = [

        'food_id' => $food['food_id'],

        'food_name' => $food['food_name'],

        'price' => (float) $food['price'],

        'image' => $food['image'],

        'quantity' => $quantity,

        'total' => $item_total

    ];

}


// If no valid items remain
if (empty($cart_items)) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit();

}


// --------------------------------------------------
// DELIVERY CHARGE
// --------------------------------------------------

$delivery_charge = 50;

$total_amount =
    $subtotal + $delivery_charge;


// --------------------------------------------------
// CUSTOMER
// --------------------------------------------------

$customer_name =
    $_SESSION['customer_name'] ?? '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Checkout | Timeout Cafe
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <link
        rel="stylesheet"
        href="assets/css/responsive.css">

</head>


<body>


<main class="checkout-page">


    <!-- HEADER -->

    <section class="checkout-header">

        <div>

            <p>TIMEOUT CAFE</p>

            <h1>
                Checkout
            </h1>

            <span>
                Complete your order details.
            </span>

        </div>


        <a
            href="cart.php"
            class="checkout-back-btn">

            ← Back to Cart

        </a>

    </section>



    <!-- CHECKOUT CONTENT -->

    <section class="checkout-layout">


        <!-- CUSTOMER FORM -->

        <div class="checkout-form-card">


            <h2>
                Delivery Information
            </h2>


            <form
                method="POST"
                action="place_order.php"
                id="checkoutForm">


                <!-- CUSTOMER NAME -->

                <div class="checkout-form-group">

                    <label for="customer_name">

                        Full Name

                    </label>


                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        value="<?php
                        echo escape(
                            $customer_name
                        );
                        ?>"
                        required
                        maxlength="100"
                        placeholder="Enter your full name">

                </div>



                <!-- PHONE -->

                <div class="checkout-form-group">

                    <label for="customer_phone">

                        Phone Number

                    </label>


                    <input
                        type="tel"
                        id="customer_phone"
                        name="customer_phone"
                        required
                        maxlength="20"
                        placeholder="Enter your phone number">

                </div>



                <!-- ADDRESS -->

                <div class="checkout-form-group">

                    <label for="delivery_address">

                        Delivery Address

                    </label>


                    <textarea
                        id="delivery_address"
                        name="delivery_address"
                        rows="4"
                        maxlength="500"
                        required
                        placeholder="Enter your complete delivery address"></textarea>

                </div>



                <!-- ORDER NOTE -->

                <div class="checkout-form-group">

                    <label for="order_note">

                        Order Note
                        <span>
                            (Optional)
                        </span>

                    </label>


                    <textarea
                        id="order_note"
                        name="order_note"
                        rows="3"
                        maxlength="500"
                        placeholder="Any special instructions?"></textarea>

                </div>
                

               <!-- PAYMENT METHOD -->

<div class="checkout-form-group">

    <label>
        Payment Method
    </label>


    <div class="payment-methods">


        <label class="payment-method-card">

            <input
                type="radio"
                name="payment_method"
                value="Cash on Delivery"
                checked>

            <span>

                <strong>
                    Cash on Delivery
                </strong>

                <small>
                    Pay when your food arrives.
                </small>

            </span>

        </label>



        <label class="payment-method-card">

            <input
                type="radio"
                name="payment_method"
                value="eSewa">

            <span>

                <strong>
                    eSewa
                </strong>

                <small>
                    Pay securely using eSewa.
                </small>

            </span>

        </label>



        <label class="payment-method-card">

            <input
                type="radio"
                name="payment_method"
                value="Khalti">

            <span>

                <strong>
                    Khalti
                </strong>

                <small>
                    Pay securely using Khalti.
                </small>

            </span>

        </label>


    </div>

</div>



                <button
                    type="submit"
                    class="place-order-btn">

                    Place Order

                </button>


            </form>


        </div>



        <!-- ORDER SUMMARY -->

        <div class="checkout-summary-card">


            <h2>
                Your Order
            </h2>


            <div class="checkout-items">


                <?php foreach (
                    $cart_items as $item
                ): ?>


                    <div class="checkout-item">


                        <div>

                            <strong>

                                <?php
                                echo escape(
                                    $item['food_name']
                                );
                                ?>

                            </strong>


                            <span>

                                <?php
                                echo $item['quantity'];
                                ?>
                                × Rs.
                                <?php
                                echo number_format(
                                    $item['price'],
                                    2
                                );
                                ?>

                            </span>

                        </div>


                        <strong>

                            Rs.
                            <?php
                            echo number_format(
                                $item['total'],
                                2
                            );
                            ?>

                        </strong>


                    </div>


                <?php endforeach; ?>


            </div>



            <!-- TOTALS -->

            <div class="checkout-total-row">

                <span>
                    Subtotal
                </span>

                <strong>

                    Rs.
                    <?php
                    echo number_format(
                        $subtotal,
                        2
                    );
                    ?>

                </strong>

            </div>


            <div class="checkout-total-row">

                <span>
                    Delivery Charge
                </span>

                <strong>

                    Rs.
                    <?php
                    echo number_format(
                        $delivery_charge,
                        2
                    );
                    ?>

                </strong>

            </div>


            <div class="checkout-grand-total">

                <span>
                    Total
                </span>

                <strong>

                    Rs.
                    <?php
                    echo number_format(
                        $total_amount,
                        2
                    );
                    ?>

                </strong>

            </div>


        </div>


    </section>


</main>


<script>

document
    .getElementById('checkoutForm')
    .addEventListener(
        'submit',
        function (event) {

            const phone =
                document
                    .getElementById('customer_phone')
                    .value
                    .trim();

            if (phone.length < 7) {

                event.preventDefault();

                alert(
                    'Please enter a valid phone number.'
                );

            }

        }
    );

</script>


</body>

</html>