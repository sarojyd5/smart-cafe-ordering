<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireAdmin();


// --------------------------------------------------
// ONLY POST REQUEST
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: orders.php");
    exit();

}


// --------------------------------------------------
// GET VALUES
// --------------------------------------------------

$order_id = (int) (
    $_POST['order_id'] ?? 0
);

$order_status = trim(
    $_POST['order_status'] ?? ''
);


// --------------------------------------------------
// VALIDATION
// --------------------------------------------------

$allowed_statuses = [

    'Pending',
    'Confirmed',
    'Preparing',
    'Ready',
    'Out for Delivery',
    'Delivered',
    'Cancelled'

];


if (
    $order_id <= 0 ||
    !in_array(
        $order_status,
        $allowed_statuses,
        true
    )
) {

    header("Location: orders.php");
    exit();

}


// --------------------------------------------------
// UPDATE ORDER STATUS
// --------------------------------------------------

$sql = "
    UPDATE orders
    SET order_status = ?
    WHERE order_id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $order_status,
    $order_id
);


if (mysqli_stmt_execute($stmt)) {

    header(
        "Location: orders.php?updated=1"
    );

    exit();

}


// --------------------------------------------------
// ERROR
// --------------------------------------------------

die(
    "Unable to update order status."
);

?>