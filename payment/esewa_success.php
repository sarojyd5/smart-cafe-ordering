<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/payment_config.php";

requireCustomerLogin();


/*
|--------------------------------------------------------------------------
| eSewa sends encoded response in "data"
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['data'])
) {

    die(
        "Invalid eSewa response."
    );

}


$encoded =
    $_GET['data'];


$decoded =
    base64_decode(
        $encoded,
        true
    );


if ($decoded === false) {

    die(
        "Unable to decode eSewa response."
    );

}


$data =
    json_decode(
        $decoded,
        true
    );


if (
    !is_array($data)
) {

    die(
        "Invalid eSewa response."
    );

}


$transaction_uuid =
    $data['transaction_uuid']
    ?? '';

$status =
    $data['status']
    ?? '';

$total_amount =
    $data['total_amount']
    ?? '';


/*
|--------------------------------------------------------------------------
| BASIC VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $transaction_uuid === '' ||
    $status !== 'COMPLETE'
) {

    header(
        "Location: esewa_failure.php"
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| VERIFY SIGNATURE
|--------------------------------------------------------------------------
*/

$signed_field_names =
    $data['signed_field_names']
    ?? '';


$signature =
    $data['signature']
    ?? '';


$message_parts = [];


foreach (
    explode(
        ',',
        $signed_field_names
    ) as $field
) {

    if (
        !array_key_exists(
            $field,
            $data
        )
    ) {

        die(
            "Invalid signed response."
        );

    }


    $message_parts[] =
        $field
        . '='
        . $data[$field];

}


$message =
    implode(
        ',',
        $message_parts
    );


$expected_signature =
    base64_encode(
        hash_hmac(
            'sha256',
            $message,
            ESEWA_SECRET_KEY,
            true
        )
    );


if (
    !hash_equals(
        $expected_signature,
        $signature
    )
) {

    die(
        "eSewa payment verification failed."
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
    $transaction_uuid,
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
| VERIFY AMOUNT
|--------------------------------------------------------------------------
*/

if (
    abs(
        (float) $order['total_amount']
        -
        (float) $total_amount
    ) > 0.01
) {

    die(
        "Payment amount verification failed."
    );

}


/*
|--------------------------------------------------------------------------
| UPDATE ORDER
|--------------------------------------------------------------------------
*/

$update_sql = "
    UPDATE orders
    SET
        payment_status = 'Paid',
        order_status = 'Confirmed'
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
    "i",
    $order['order_id']
);


mysqli_stmt_execute(
    $update_stmt
);


/*
|--------------------------------------------------------------------------
| CLEAR CART
|--------------------------------------------------------------------------
*/

$_SESSION['cart'] = [];


header(
    "Location: ../order_success.php?order_id="
    . $order['order_id']
);

exit();

?>