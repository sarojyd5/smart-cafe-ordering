<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireCustomerLogin();


$order_id =
    (int) (
        $_GET['order_id'] ?? 0
    );


if ($order_id <= 0) {

    header("Location: index.php");
    exit();

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Order Confirmed | Timeout Cafe
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <link
        rel="stylesheet"
        href="assets/css/responsive.css">

</head>


<body>


<main class="order-success-page">


    <div class="order-success-card">


        <div class="success-icon">

            ✓

        </div>


        <p class="success-label">
            TIMEOUT CAFE
        </p>


        <h1>
            Order Placed Successfully!
        </h1>


        <p>

            Thank you for ordering from
            Timeout Cafe.

        </p>


        <div class="success-order-number">

            Order Number

            <strong>

                #<?php
                echo $order_id;
                ?>

            </strong>

        </div>


        <p class="success-status">

            Your order is currently
            <strong>Pending</strong>.

        </p>


        <div class="success-actions">


            <a
                href="index.php"
                class="visit-site-btn">

                Continue Shopping

            </a>


            <a
                href="order_history.php"
                class="admin-view-btn">

                View My Orders

            </a>


        </div>


    </div>


</main>


</body>

</html>