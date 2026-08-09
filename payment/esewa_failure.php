<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireCustomerLogin();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Payment Failed | Timeout Cafe
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <link
        rel="stylesheet"
        href="../assets/css/responsive.css">

</head>


<body>


<main class="order-success-page">


    <div class="order-success-card">


        <div class="success-icon">

            !

        </div>


        <p class="success-label">
            TIMEOUT CAFE
        </p>


        <h1>
            Payment Failed
        </h1>


        <p>
            Your eSewa payment was not completed.
        </p>


        <div class="success-actions">

            <a
                href="../checkout.php"
                class="visit-site-btn">

                Try Again

            </a>


            <a
                href="../cart.php"
                class="admin-view-btn">

                Back to Cart

            </a>

        </div>


    </div>


</main>


</body>

</html>