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


/*
|--------------------------------------------------------------------------
| VALIDATE PAYMENT METHOD
|--------------------------------------------------------------------------
*/

if (
    !in_array(
        $payment_method,
        $allowed_payment_methods,
        true
    )
) {

    die("Invalid payment method.");

}


/*
|--------------------------------------------------------------------------
| VALIDATE CUSTOMER NAME
|--------------------------------------------------------------------------
*/

if ($customer_name === '') {

    die(
        "Full name is required."
    );

}


/*
 * Only letters and spaces are allowed.
 */

if (
    !preg_match(
        '/^[A-Za-z ]+$/',
        $customer_name
    )
) {

    die(
        "Full name can contain only letters and spaces."
    );

}


/*
 * Minimum 3 characters.
 */

if (
    strlen($customer_name) < 3
) {

    die(
        "Full name must contain at least 3 characters ."
    );

}


/*
|--------------------------------------------------------------------------
| VALIDATE PHONE NUMBER
|--------------------------------------------------------------------------
*/

if ($customer_phone === '') {

    die(
        "Phone number is required."
    );

}


/*
 * Exactly 10 digits
 * and must start with 97 or 98.
 */

if (
    !preg_match(
        '/^(97|98)[0-9]{8}$/',
        $customer_phone
    )
) {

    die(
        "Phone number must be exactly 10 digits and start with 97 or 98."
    );

}


/*
|--------------------------------------------------------------------------
| VALIDATE DELIVERY ADDRESS
|--------------------------------------------------------------------------
*/

if ($delivery_address === '') {

    die(
        "Delivery address is required."
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


foreach (
    $cart as $food_id => $quantity
) {

    $food_id =
        (int) $food_id;

    $quantity =
        (int) $quantity;


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


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $food_id
    );


    mysqli_stmt_execute(
        $stmt
    );


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    $food =
        mysqli_fetch_assoc(
            $result
        );


    if (!$food) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK FOOD AVAILABILITY
    |--------------------------------------------------------------------------
    */

    if (
        $food['availability']
        !== 'available'
    ) {

        die(
            "Sorry, "
            . escape(
                $food['food_name']
            )
            . " is currently unavailable."
        );

    }


    $price =
        (float) $food['price'];


    $item_total =
        $price * $quantity;


    $subtotal +=
        $item_total;


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


/*
|--------------------------------------------------------------------------
| CHECK VALID CART ITEMS
|--------------------------------------------------------------------------
*/

if (
    empty($cart_items)
) {

    $_SESSION['cart'] = [];

    header(
        "Location: cart.php"
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| DELIVERY
|--------------------------------------------------------------------------
*/

$delivery_charge =
    50;


$total_amount =
    $subtotal +
    $delivery_charge;


/*
|--------------------------------------------------------------------------
| PAYMENT STATUS
|--------------------------------------------------------------------------
*/

if (
    $payment_method ===
    'Cash on Delivery'
) {

    $payment_status =
        'Pending';

    $payment_gateway =
        'COD';

    $order_status =
        'Pending';

} else {

    $payment_status =
        'Pending';

    $payment_gateway =
        $payment_method;

    $order_status =
        'Pending Payment';

}


/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction(
    $conn
);


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


    if (!$order_stmt) {

        throw new Exception(
            "Unable to prepare order."
        );

    }


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
        mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $order_stmt
    );


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


    if (!$item_stmt) {

        throw new Exception(
            "Unable to prepare order items."
        );

    }


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


    mysqli_stmt_close(
        $item_stmt
    );


    /*
    |--------------------------------------------------------------------------
    | COMMIT TRANSACTION
    |--------------------------------------------------------------------------
    */

    mysqli_commit(
        $conn
    );


    /*
    |--------------------------------------------------------------------------
    | CASH ON DELIVERY
    |--------------------------------------------------------------------------
    */

    if (
        $payment_method ===
        'Cash on Delivery'
    ) {

        /*
         * Clear cart after successful COD order.
         */

        $_SESSION['cart'] = [];


        header(
            "Location: order_success.php?order_id="
            . $order_id
        );

        exit();

    }


    /*
    |--------------------------------------------------------------------------
    | eSEWA PAYMENT
    |--------------------------------------------------------------------------
    */

    if (
        $payment_method ===
        'eSewa'
    ) {

        /*
         * Keep cart until online payment succeeds.
         */

        header(
            "Location: payment/esewa_pay.php?order_id="
            . $order_id
        );

        exit();

    }


    /*
    |--------------------------------------------------------------------------
    | KHALTI PAYMENT
    |--------------------------------------------------------------------------
    */

    if (
        $payment_method ===
        'Khalti'
    ) {

        /*
         * Keep cart until online payment succeeds.
         */

        header(
            "Location: payment/khalti_pay.php?order_id="
            . $order_id
        );

        exit();

    }


} catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    mysqli_rollback(
        $conn
    );


    /*
     * Log actual error for debugging.
     */

    error_log(
        "Order Error: "
        . $e->getMessage()
    );


    die(
        "Order could not be created. Please try again."
    );

}

?>