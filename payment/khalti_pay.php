<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/payment_config.php";

requireCustomerLogin();


$order_id =
    (int) (
        $_GET['order_id'] ?? 0
    );


if ($order_id <= 0) {

    header("Location: ../cart.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        order_id,
        customer_name,
        customer_phone,
        total_amount,
        payment_method
    FROM orders
    WHERE order_id = ?
    AND customer_id = ?
    LIMIT 1
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $order_id,
    $_SESSION['customer_id']
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$order =
    mysqli_fetch_assoc($result);


if (!$order) {

    die("Order not found.");

}


if (
    $order['payment_method']
    !== 'Khalti'
) {

    die("Invalid payment method.");

}


/*
|--------------------------------------------------------------------------
| AMOUNT IN PAISA
|--------------------------------------------------------------------------
*/

$amount_paisa =
    (int) round(
        (float) $order['total_amount']
        * 100
    );


/*
|--------------------------------------------------------------------------
| KHALTI REQUEST
|--------------------------------------------------------------------------
*/

$payload = [

    'return_url' =>
        SITE_URL
        . '/payment/khalti_callback.php',

    'website_url' =>
        SITE_URL,

    'amount' =>
        $amount_paisa,

    'purchase_order_id' =>
        'ORDER-' . $order_id,

    'purchase_order_name' =>
        'Timeout Cafe Order #' . $order_id,

    'customer_info' => [

        'name' =>
            $order['customer_name'],

        'phone' =>
            $order['customer_phone']

    ]

];


$ch =
    curl_init(
        KHALTI_INITIATE_URL
    );


curl_setopt_array(
    $ch,
    [

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_POST =>
            true,

        CURLOPT_POSTFIELDS =>
            json_encode($payload),

        CURLOPT_HTTPHEADER =>
            [

                'Authorization: key '
                . KHALTI_SECRET_KEY,

                'Content-Type: application/json'

            ],

        CURLOPT_TIMEOUT =>
            30

    ]
);


$response =
    curl_exec($ch);


$http_code =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


$curl_error =
    curl_error($ch);


curl_close($ch);


if (
    $response === false ||
    $curl_error !== ''
) {

    die(
        "Unable to connect to Khalti."
    );

}


$data =
    json_decode(
        $response,
        true
    );


if (
    $http_code < 200 ||
    $http_code >= 300 ||
    !isset($data['pidx']) ||
    !isset($data['payment_url'])
) {

    die(
        "Khalti payment could not be initiated."
    );

}


/*
|--------------------------------------------------------------------------
| SAVE KHALTI PIDX
|--------------------------------------------------------------------------
*/

$update_sql = "
    UPDATE orders
    SET payment_reference = ?
    WHERE order_id = ?
";


$update_stmt =
    mysqli_prepare(
        $conn,
        $update_sql
    );


mysqli_stmt_bind_param(
    $update_stmt,
    "si",
    $data['pidx'],
    $order_id
);


mysqli_stmt_execute(
    $update_stmt
);


/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/

header(
    "Location: "
    . $data['payment_url']
);

exit();

?>