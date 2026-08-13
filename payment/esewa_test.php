<?php

$amount = "100";
$tax_amount = "0";
$total_amount = "100";

$transaction_uuid = "TEST-" . time();

$product_code = "EPAYTEST";

$product_service_charge = "0";
$product_delivery_charge = "0";

$success_url = "https://developer.esewa.com.np/success";
$failure_url = "https://developer.esewa.com.np/failure";

$signed_field_names =
    "total_amount,transaction_uuid,product_code";

$message =
    "total_amount=" . $total_amount .
    ",transaction_uuid=" . $transaction_uuid .
    ",product_code=" . $product_code;

$secret_key = "8gBm/:&EnhH.1/q";

$hash = hash_hmac(
    "sha256",
    $message,
    $secret_key,
    true
);

$signature = base64_encode($hash);

?>

<!DOCTYPE html>
<html>
<head>
    <title>eSewa Test</title>
</head>

<body>

<form
    action="https://rc-epay.esewa.com.np/api/epay/main/v2/form"
    method="POST"
>

    <input
        type="hidden"
        name="amount"
        value="<?php echo $amount; ?>"
    >

    <input
        type="hidden"
        name="tax_amount"
        value="<?php echo $tax_amount; ?>"
    >

    <input
        type="hidden"
        name="total_amount"
        value="<?php echo $total_amount; ?>"
    >

    <input
        type="hidden"
        name="transaction_uuid"
        value="<?php echo $transaction_uuid; ?>"
    >

    <input
        type="hidden"
        name="product_code"
        value="<?php echo $product_code; ?>"
    >

    <input
        type="hidden"
        name="product_service_charge"
        value="<?php echo $product_service_charge; ?>"
    >

    <input
        type="hidden"
        name="product_delivery_charge"
        value="<?php echo $product_delivery_charge; ?>"
    >

    <input
        type="hidden"
        name="success_url"
        value="<?php echo $success_url; ?>"
    >

    <input
        type="hidden"
        name="failure_url"
        value="<?php echo $failure_url; ?>"
    >

    <input
        type="hidden"
        name="signed_field_names"
        value="<?php echo $signed_field_names; ?>"
    >

    <input
        type="hidden"
        name="signature"
        value="<?php echo $signature; ?>"
    >

    <button type="submit">
        Test eSewa Payment
    </button>

</form>

</body>
</html>