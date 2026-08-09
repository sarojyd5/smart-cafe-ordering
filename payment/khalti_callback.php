<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/payment_config.php";

requireCustomerLogin();


/*
|--------------------------------------------------------------------------
| GET PIDX
|--------------------------------------------------------------------------
*/

$pidx =
    trim(
        $_GET['pidx'] ?? ''
    );


if ($pidx === '') {

    die(
        "Invalid Khalti payment response."
    );

}


/*
|--------------------------------------------------------------------------
| FIND ORDER
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        order_id,
        total_amount,
        payment_status
    FROM orders
    WHERE payment_reference = ?
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
    "si",
    $pidx,
    $_SESSION['customer_id']
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$order =
    mysqli_fetch_assoc($result);


if (!$order) {

    die(
        "Order could not be found."
    );

}


/*
|--------------------------------------------------------------------------
| LOOKUP PAYMENT
|--------------------------------------------------------------------------
*/

$payload = [

    'pidx' => $pidx

];


$ch =
    curl_init(
        KHALTI_LOOKUP_URL
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


curl_close($ch);


if ($response === false) {

    die(
        "Unable to verify Khalti payment."
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
    !is_array($data)
) {

    die(
        "Invalid Khalti verification response."
    );

}


/*
|--------------------------------------------------------------------------
| CHECK STATUS
|--------------------------------------------------------------------------
*/

$status =
    $data['status'] ?? '';


$paid_amount =
    (int) (
        $data['total_amount'] ?? 0
    );


$expected_amount =
    (int) round(
        (float) $order['total_amount']
        * 100
    );


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

if (
    $status === 'Completed' &&
    $paid_amount === $expected_amount
) {


    $transaction_id =
        $data['transaction_id']
        ?? $pidx;


    $update_sql = "
        UPDATE orders
        SET
            payment_status = 'Paid',
            order_status = 'Confirmed',
            payment_reference = ?
        WHERE order_id = ?
        AND payment_status = 'Pending'
    ";


    $update_stmt =
        mysqli_prepare(
            $conn,
            $update_sql
        );


    mysqli_stmt_bind_param(
        $update_stmt,
        "si",
        $transaction_id,
        $order['order_id']
    );


    mysqli_stmt_execute(
        $update_stmt
    );


    $_SESSION['cart'] = [];


    header(
        "Location: ../order_success.php?order_id="
        . $order['order_id']
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| FAILED / CANCELLED / EXPIRED
|--------------------------------------------------------------------------
*/

header(
    "Location: ../checkout.php"
);

exit();

?>