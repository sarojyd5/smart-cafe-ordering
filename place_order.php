<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/payment_config.php";

requireCustomerLogin();


/*
|--------------------------------------------------------------------------
| POST ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: checkout.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| CHECK CART
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {

    header("Location: cart.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| CUSTOMER
|--------------------------------------------------------------------------
*/

$customer_id =
    (int) (
        $_SESSION['customer_id'] ?? 0
    );


if ($customer_id <= 0) {

    header("Location: login.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| FORM DATA
|--------------------------------------------------------------------------
*/

$customer_name =
    trim(
        $_POST['customer_name'] ?? ''
    );

$customer_phone =
    trim(
        $_POST['customer_phone'] ?? ''
    );

$delivery_address =
    trim(
        $_POST['delivery_address'] ?? ''
    );

$order_note =
    trim(
        $_POST['order_note'] ?? ''
    );

$payment_method =
    trim(
        $_POST['payment_method'] ?? ''
    );


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

$allowed_payment_methods = [

    'Cash on Delivery',
    'eSewa',
    'Khalti'

];


if (
    !in_array(
        $payment_method,
        $allowed_payment_methods,
        true
    )
) {

    die("Invalid payment method.");

}


if (
    $customer_name === '' ||
    $customer_phone === '' ||
    $delivery_address === ''
) {

    die(
        "Please fill all required information."
    );

}


/*
|--------------------------------------------------------------------------
| CART CALCULATION
|--------------------------------------------------------------------------
*/

$cart = $_SESSION['cart'];

$cart_items = [];

$subtotal = 0;


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


    $result =
        mysqli_stmt_get_result($stmt);


    $food =
        mysqli_fetch_assoc($result);


    if (!$food) {
        continue;
    }


    if (
        $food['availability']
        !== 'available'
    ) {

        die(
            "Sorry, "
            . escape($food['food_name'])
            . " is currently unavailable."
        );

    }


    $price =
        (float) $food['price'];


    $item_total =
        $price * $quantity;


    $subtotal += $item_total;


    $cart_items[] = [

        'food_id' =>
            (int) $food['food_id'],

        'food_name' =>
            $food['food_name'],

        'quantity' =>
            $quantity,

        'price' =>
            $price,

        'total_price' =>
            $item_total

    ];

}


if (empty($cart_items)) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| DELIVERY
|--------------------------------------------------------------------------
*/

$delivery_charge = 50;

$total_amount =
    $subtotal + $delivery_charge;


/*
|--------------------------------------------------------------------------
| PAYMENT STATUS
|--------------------------------------------------------------------------
*/

if (
    $payment_method === 'Cash on Delivery'
) {

    $payment_status = 'Pending';
    $payment_gateway = 'COD';
    $order_status = 'Pending';

} else {

    $payment_status = 'Pending';
    $payment_gateway = $payment_method;
    $order_status = 'Pending Payment';

}


/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conn);


try {


    /*
    |--------------------------------------------------------------------------
    | INSERT ORDER
    |--------------------------------------------------------------------------
    */

    $order_sql = "
        INSERT INTO orders
        (
            customer_id,
            customer_name,
            customer_phone,
            delivery_address,
            order_note,
            subtotal,
            delivery_charge,
            total_amount,
            payment_method,
            payment_status,
            order_status,
            payment_gateway
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";


    $order_stmt =
        mysqli_prepare(
            $conn,
            $order_sql
        );


    mysqli_stmt_bind_param(
        $order_stmt,
        "issssdddssss",
        $customer_id,
        $customer_name,
        $customer_phone,
        $delivery_address,
        $order_note,
        $subtotal,
        $delivery_charge,
        $total_amount,
        $payment_method,
        $payment_status,
        $order_status,
        $payment_gateway
    );


    if (
        !mysqli_stmt_execute(
            $order_stmt
        )
    ) {

        throw new Exception(
            "Unable to create order."
        );

    }


    $order_id =
        mysqli_insert_id($conn);


    /*
    |--------------------------------------------------------------------------
    | INSERT ORDER ITEMS
    |--------------------------------------------------------------------------
    */

    $item_sql = "
        INSERT INTO order_items
        (
            order_id,
            food_id,
            food_name,
            quantity,
            price,
            total_price
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ";


    $item_stmt =
        mysqli_prepare(
            $conn,
            $item_sql
        );


    foreach (
        $cart_items as $item
    ) {


        mysqli_stmt_bind_param(
            $item_stmt,
            "iisidd",
            $order_id,
            $item['food_id'],
            $item['food_name'],
            $item['quantity'],
            $item['price'],
            $item['total_price']
        );


        if (
            !mysqli_stmt_execute(
                $item_stmt
            )
        ) {

            throw new Exception(
                "Unable to save order items."
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    mysqli_commit($conn);


    /*
    |--------------------------------------------------------------------------
    | COD
    |--------------------------------------------------------------------------
    */

    if (
        $payment_method ===
        'Cash on Delivery'
    ) {

        $_SESSION['cart'] = [];


        header(
            "Location: order_success.php?order_id="
            . $order_id
        );

        exit();

    }


    /*
    |--------------------------------------------------------------------------
    | ONLINE PAYMENT
    |--------------------------------------------------------------------------
    |
    | Keep the cart until payment succeeds.
    |
    */

    if ($payment_method === 'eSewa') {

        header(
            "Location: payment/esewa_pay.php?order_id="
            . $order_id
        );

        exit();

    }


    if ($payment_method === 'Khalti') {

        header(
            "Location: payment/khalti_pay.php?order_id="
            . $order_id
        );

        exit();

    }


} catch (Throwable $e) {

    mysqli_rollback($conn);

    die(
        "Order could not be created."
    );

}

?>