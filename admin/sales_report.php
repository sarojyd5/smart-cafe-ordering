<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";


// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| TODAY'S SALES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM orders
    WHERE DATE(order_date) = CURDATE()
    AND order_status != 'Cancelled'
";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$today_sales = $row['total'];


/*
|--------------------------------------------------------------------------
| TOTAL SALES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM orders
    WHERE order_status != 'Cancelled'
";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$total_sales = $row['total'];


/*
|--------------------------------------------------------------------------
| TOTAL ORDERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status != 'Cancelled'
";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$total_orders = $row['total'];


/*
|--------------------------------------------------------------------------
| DELIVERED ORDERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'Delivered'
";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$delivered_orders = $row['total'];


/*
|--------------------------------------------------------------------------
| SALES RECORDS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        order_id,
        customer_name,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        order_date
    FROM orders
    WHERE order_status != 'Cancelled'
    ORDER BY order_date DESC
";

$sales = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Sales Report | Timeout Cafe
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <link
        rel="stylesheet"
        href="../assets/css/responsive.css">

</head>

<body>


<main class="admin-dashboard">


    <!-- HEADER -->

    <section class="admin-dashboard-header">

        <div>

            <p>TIMEOUT CAFE</p>

            <h1>Sales Report</h1>

            <span>
                View and monitor cafe sales.
            </span>

        </div>


        <div class="admin-header-actions">

            <a
                href="index.php"
                class="visit-site-btn">

                ← Dashboard

            </a>

            <button
                onclick="window.print()"
                class="admin-logout-btn">

                🖨 Print Report

            </button>

        </div>

    </section>



    <!-- SALES STATISTICS -->

    <section class="admin-statistics">


        <!-- TODAY'S SALES -->

        <div class="admin-stat-card">

            <div class="stat-icon">
                💰
            </div>

            <div>

                <span>
                    Today's Sales
                </span>

                <strong>

                    Rs.
                    <?php
                    echo number_format(
                        $today_sales,
                        2
                    );
                    ?>

                </strong>

            </div>

        </div>


        <!-- TOTAL SALES -->

        <div class="admin-stat-card">

            <div class="stat-icon">
                💵
            </div>

            <div>

                <span>
                    Total Sales
                </span>

                <strong>

                    Rs.
                    <?php
                    echo number_format(
                        $total_sales,
                        2
                    );
                    ?>

                </strong>

            </div>

        </div>


        <!-- TOTAL ORDERS -->

        <div class="admin-stat-card">

            <div class="stat-icon">
                🛒
            </div>

            <div>

                <span>
                    Sales Orders
                </span>

                <strong>
                    <?php
                    echo $total_orders;
                    ?>
                </strong>

            </div>

        </div>


        <!-- DELIVERED -->

        <div class="admin-stat-card">

            <div class="stat-icon">
                ✅
            </div>

            <div>

                <span>
                    Delivered Orders
                </span>

                <strong>
                    <?php
                    echo $delivered_orders;
                    ?>
                </strong>

            </div>

        </div>

    </section>



    <!-- SALES DETAILS -->

    <section class="recent-orders-section">


        <div class="section-heading">

            <div>

                <p>SALES MANAGEMENT</p>

                <h2>
                    Sales Details
                </h2>

            </div>

        </div>


        <?php if (
            mysqli_num_rows($sales) === 0
        ): ?>

            <div class="no-recent-orders">

                <h3>
                    No Sales Yet
                </h3>

                <p>
                    Completed customer orders will appear here.
                </p>

            </div>

        <?php else: ?>


            <div class="recent-orders-table-wrapper">

                <table class="recent-orders-table">

                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Payment Status
                            </th>

                            <th>
                                Order Status
                            </th>

                            <th>
                                Date
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while (
                            $sale =
                            mysqli_fetch_assoc($sales)
                        ): ?>

                            <tr>

                                <td>

                                    <strong>
                                        #<?php
                                        echo $sale['order_id'];
                                        ?>
                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo escape(
                                        $sale['customer_name']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <strong>

                                        Rs.
                                        <?php
                                        echo number_format(
                                            $sale['total_amount'],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo escape(
                                        $sale['payment_method']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo escape(
                                        $sale['payment_status']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span
                                        class="status-badge
                                        status-<?php
                                        echo strtolower(
                                            str_replace(
                                                ' ',
                                                '-',
                                                $sale['order_status']
                                            )
                                        );
                                        ?>">

                                        <?php
                                        echo escape(
                                            $sale['order_status']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $sale['order_date']
                                        )
                                    );
                                    ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>


    </section>


</main>


</body>

</html>