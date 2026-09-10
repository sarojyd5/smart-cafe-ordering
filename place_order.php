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
| CUSTOMER ID
|--------------------------------------------------------------------------
*/

$customer_id = (int) (
    $_SESSION['customer_id'] ?? 0
);


if ($customer_id <= 0) {

    header("Location: login.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$customer_name = trim(
    $_POST['customer_name'] ?? ''
);

$customer_phone = trim(
    $_POST['customer_phone'] ?? ''
);

$delivery_address = trim(
    $_POST['delivery_address'] ?? ''
);

$delivery_map_link = trim(
    $_POST['delivery_map_link'] ?? ''
);

$delivery_lat = isset($_POST['delivery_lat'])
    && $_POST['delivery_lat'] !== ''
    ? (float) $_POST['delivery_lat']
    : 0;

$delivery_lng = isset($_POST['delivery_lng'])
    && $_POST['delivery_lng'] !== ''
    ? (float) $_POST['delivery_lng']
    : 0;

$order_note = trim(
    $_POST['order_note'] ?? ''
);

$payment_method = trim(
    $_POST['payment_method'] ?? ''
);


/*
|--------------------------------------------------------------------------
| VALID PAYMENT METHODS
|--------------------------------------------------------------------------
*/

$allowed_payment_methods = [

    'Cash on Delivery',
    'eSewa'

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


/*
|--------------------------------------------------------------------------
| VALIDATE CUSTOMER NAME
|--------------------------------------------------------------------------
*/

if ($customer_name === '') {

    die("Full name is required.");

}


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


if (
    strlen($customer_name) < 3
) {

    die(
        "Full name must contain at least 3 characters."
    );

}


/*
|--------------------------------------------------------------------------
| VALIDATE PHONE
|--------------------------------------------------------------------------
*/

if ($customer_phone === '') {

    die("Phone number is required.");

}


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
| GENERATE GOOGLE MAP LINK
|--------------------------------------------------------------------------
|
| If checkout.php does not send delivery_map_link,
| create a Google Maps search link using the address.
|
*/

if (
    $delivery_map_link === ''
) {

    $delivery_map_link =
        "https://www.google.com/maps/search/?api=1&query="
        . urlencode($delivery_address);

}


/*
|--------------------------------------------------------------------------
| CART
|--------------------------------------------------------------------------
*/

$cart = $_SESSION['cart'];

$cart_items = [];

$subtotal = 0;


foreach (
    $cart as $food_id => $quantity
) {

    $food_id = (int) $food_id;

    $quantity = (int) $quantity;


    if (
        $food_id <= 0 ||
        $quantity <= 0
    ) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | GET FOOD
    |--------------------------------------------------------------------------
    */

    $food_sql = "
        SELECT
            food_id,
            food_name,
            price,
            availability
        FROM foods
        WHERE food_id = ?
        LIMIT 1
    ";


    $food_stmt = mysqli_prepare(
        $conn,
        $food_sql
    );


    if (!$food_stmt) {

        die(
            "Unable to check food information."
        );

    }


    mysqli_stmt_bind_param(
        $food_stmt,
        "i",
        $food_id
    );


    mysqli_stmt_execute(
        $food_stmt
    );


    $food_result =
        mysqli_stmt_get_result(
            $food_stmt
        );


    $food =
        mysqli_fetch_assoc(
            $food_result
        );


    mysqli_stmt_close(
        $food_stmt
    );


    /*
    |--------------------------------------------------------------------------
    | FOOD NOT FOUND
    |--------------------------------------------------------------------------
    */

    if (!$food) {

        continue;

    }


    /*
    |--------------------------------------------------------------------------
    | FOOD AVAILABILITY
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | PRICE
    |--------------------------------------------------------------------------
    */

    $price =
        (float) $food['price'];


    $item_total =
        $price * $quantity;


    $subtotal +=
        $item_total;


    /*
    |--------------------------------------------------------------------------
    | STORE ITEM
    |--------------------------------------------------------------------------
    */

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
| VALID CART CHECK
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
| DELIVERY CHARGE
|--------------------------------------------------------------------------
*/

$delivery_charge = 50;


$total_amount =
    $subtotal +
    $delivery_charge;


/*
|--------------------------------------------------------------------------
| PAYMENT INFORMATION
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
        'eSewa';

    $order_status =
        'Pending Payment';

}


/*
|--------------------------------------------------------------------------
| START DATABASE TRANSACTION
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
            delivery_map_link,
            delivery_lat,
            delivery_lng,
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
            mysqli_error($conn)
        );

    }


    /*
    |--------------------------------------------------------------------------
    | BIND ORDER VALUES
    |--------------------------------------------------------------------------
    |
    | i = integer
    | s = string
    | d = decimal/double
    |
    */

    mysqli_stmt_bind_param(
        $order_stmt,
        "issssddsdddssss",
        $customer_id,
        $customer_name,
        $customer_phone,
        $delivery_address,
        $delivery_map_link,
        $delivery_lat,
        $delivery_lng,
        $order_note,
        $subtotal,
        $delivery_charge,
        $total_amount,
        $payment_method,
        $payment_status,
        $order_status,
        $payment_gateway
    );


    /*
    |--------------------------------------------------------------------------
    | EXECUTE ORDER
    |--------------------------------------------------------------------------
    */

    if (
        !mysqli_stmt_execute(
            $order_stmt
        )
    ) {

        throw new Exception(
            mysqli_stmt_error($order_stmt)
        );

    }


    /*
    |--------------------------------------------------------------------------
    | GET ORDER ID
    |--------------------------------------------------------------------------
    */

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
            mysqli_error($conn)
        );

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT EACH FOOD ITEM
    |--------------------------------------------------------------------------
    */

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
                mysqli_stmt_error($item_stmt)
            );

        }

    }


    mysqli_stmt_close(
        $item_stmt
    );


    /*
    |--------------------------------------------------------------------------
    | COMMIT
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
        | Clear cart after successful order
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
    | eSEWA
    |--------------------------------------------------------------------------
    */

    if (
        $payment_method ===
        'eSewa'
    ) {


        /*
        | Keep cart until payment is completed.
        */

        header(
            "Location: payment/esewa_pay.php?order_id="
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
    |--------------------------------------------------------------------------
    | LOG ACTUAL ERROR
    |--------------------------------------------------------------------------
    */

    error_log(
        "Timeout Cafe Order Error: "
        . $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | DEVELOPMENT ERROR
    |--------------------------------------------------------------------------
    |
    | During development, show the actual MySQL error.
    | You can change this later to a generic message.
    |
    */

    die(
        "Order could not be created.<br><br>"
        . "Error: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );

}

?>