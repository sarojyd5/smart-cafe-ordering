<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";
require_once "../includes/payment_config.php";

requireCustomerLogin();


$order_id = (int) ($_GET['order_id'] ?? 0);

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
        total_amount,
        payment_status,
        payment_method
    FROM orders
    WHERE order_id = ?
    AND customer_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $order_id,
    $_SESSION['customer_id']
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$order = mysqli_fetch_assoc($result);


if (!$order) {
    die("Order not found.");
}


if ($order['payment_method'] !== 'eSewa') {
    die("Invalid payment method.");
}


/*
|--------------------------------------------------------------------------
| TRANSACTION UUID
|--------------------------------------------------------------------------
|
| eSewa requires alphanumeric characters and hyphens only.
|
*/

$transaction_uuid =
    $order_id . '-' . time();


/*
|--------------------------------------------------------------------------
| AMOUNTS
|--------------------------------------------------------------------------
*/

$amount = (float) $order['total_amount'];

$tax_amount = 0;

$product_service_charge = 0;

$product_delivery_charge = 0;

$total_amount =
    $amount
    + $tax_amount
    + $product_service_charge
    + $product_delivery_charge;


/*
|--------------------------------------------------------------------------
| SIGNATURE
|--------------------------------------------------------------------------
*/

$signed_field_names =
    'total_amount,transaction_uuid,product_code';


$message =
    'total_amount=' . $total_amount
    . ',transaction_uuid=' . $transaction_uuid
    . ',product_code=' . ESEWA_PRODUCT_CODE;


$hash = hash_hmac(
    'sha256',
    $message,
    ESEWA_SECRET_KEY,
    true
);


$signature = base64_encode($hash);


/*
|--------------------------------------------------------------------------
| SAVE TRANSACTION REFERENCE
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
    $transaction_uuid,
    $order_id
);


mysqli_stmt_execute(
    $update_stmt
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Redirecting to eSewa
    </title>

</head>


<body>

<p>
    Redirecting you to eSewa...
</p>


<form
    id="esewaForm"
    action="<?php echo ESEWA_PAYMENT_URL; ?>"
    method="POST">


    <input
        type="hidden"
        name="amount"
        value="<?php echo $amount; ?>">


    <input
        type="hidden"
        name="tax_amount"
        value="<?php echo $tax_amount; ?>">


    <input
        type="hidden"
        name="total_amount"
        value="<?php echo $total_amount; ?>">


    <input
        type="hidden"
        name="transaction_uuid"
        value="<?php echo escape($transaction_uuid); ?>">


    <input
        type="hidden"
        name="product_code"
        value="<?php echo ESEWA_PRODUCT_CODE; ?>">


    <input
        type="hidden"
        name="product_service_charge"
        value="<?php echo $product_service_charge; ?>">


    <input
        type="hidden"
        name="product_delivery_charge"
        value="<?php echo $product_delivery_charge; ?>">


    <!-- CORRECT: NO SEMICOLON -->

    <input
        type="hidden"
        name="success_url"
        value="<?php echo SITE_URL; ?>/payment/esewa_success.php">


    <!-- CORRECT: NO SEMICOLON -->

    <input
        type="hidden"
        name="failure_url"
        value="<?php echo SITE_URL; ?>/payment/esewa_failure.php">


    <input
        type="hidden"
        name="signed_field_names"
        value="<?php echo $signed_field_names; ?>">


    <input
        type="hidden"
        name="signature"
        value="<?php echo $signature; ?>">


</form>


<script>

document
    .getElementById("esewaForm")
    .submit();

</script>


</body>

</html>