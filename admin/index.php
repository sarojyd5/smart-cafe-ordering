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
| TOTAL ORDERS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*) AS total FROM orders";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);

$total_orders = $row['total'];


/*
|--------------------------------------------------------------------------
| TOTAL CUSTOMERS
|--------------------------------------------------------------------------
*/

$sql = "SELECT COUNT(*) AS total FROM customers";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);

$total_customers = $row['total'];


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
| PENDING ORDERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'Pending'
";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);

$pending_orders = $row['total'];


/*
|--------------------------------------------------------------------------
| PREPARING ORDERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'Preparing'
";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);

$preparing_orders = $row['total'];


/*
|--------------------------------------------------------------------------
| OUT FOR DELIVERY
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'Out for Delivery'
";

$result = mysqli_query($conn, $sql);

$row = mysqli_fetch_assoc($result);

$delivery_orders = $row['total'];


/*
|--------------------------------------------------------------------------
| RECENT ORDERS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        order_id,
        customer_name,
        total_amount,
        order_status,
        order_date
    FROM orders
    ORDER BY order_date DESC
    LIMIT 5
";

$recent_orders = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Admin Dashboard | Timeout Cafe
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

            <h1>Admin Dashboard</h1>

            <span>
                Manage your cafe ordering system.
            </span>

        </div>


        <div class="admin-header-actions">

            <a
                href="../index.php"
                class="visit-site-btn">

                View Website

            </a>

            <a
                href="logout.php"
                class="admin-logout-btn">

                Logout

            </a>

        </div>

    </section>



    <!-- STATISTICS -->

    <section class="admin-statistics">


        <!-- TOTAL ORDERS -->

        <div class="admin-stat-card">

            <div class="stat-icon">
                🛒
            </div>

            <div>

                <span>
                    Total Orders
                </span>

                <strong>
                    <?php
                    echo $total_orders;
                    ?>
                </strong>

            </div>

        </div>


        <!-- CUSTOMERS -->

        <div class="admin-stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div>

                <span>
                    Customers
                </span>

                <strong>
                    <?php
                    echo $total_customers;
                    ?>
                </strong>

            </div>

        </div>


        <!-- TODAY SALES -->

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


        <!-- PENDING -->

        <div class="admin-stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div>

                <span>
                    Pending Orders
                </span>

                <strong>
                    <?php
                    echo $pending_orders;
                    ?>
                </strong>

            </div>

        </div>

    </section>



    <!-- ORDER STATUS -->

    <section class="admin-status-section">

        <h2>Order Status</h2>


        <div class="admin-status-grid">


            <div class="status-summary">

                <span>
                    Pending
                </span>

                <strong>
                    <?php
                    echo $pending_orders;
                    ?>
                </strong>

            </div>


            <div class="status-summary">

                <span>
                    Preparing
                </span>

                <strong>
                    <?php
                    echo $preparing_orders;
                    ?>
                </strong>

            </div>


            <div class="status-summary">

                <span>
                    Out for Delivery
                </span>

                <strong>
                    <?php
                    echo $delivery_orders;
                    ?>
                </strong>

            </div>

        </div>

    </section>



    <!-- QUICK ACTIONS -->

    <section class="admin-quick-actions">

        <h2>Quick Actions</h2>


        <div class="quick-action-grid">


            <!-- MANAGE ORDERS -->

            <a
                href="orders.php"
                class="quick-action-card">

                <div>
                    📦
                </div>

                <h3>
                    Manage Orders
                </h3>

                <p>
                    View and update customer orders.
                </p>

            </a>



            <!-- MANAGE FOOD -->

            <a
                href="foods.php"
                class="quick-action-card">

                <div>
                    🍔
                </div>

                <h3>
                    Manage Food
                </h3>

                <p>
                    Add, edit, delete and manage
                    food items.
                </p>

            </a>



            <!-- VIEW MENU -->

            <a
                href="../menu.php"
                class="quick-action-card">

                <div>
                    🍽️
                </div>

                <h3>
                    View Menu
                </h3>

                <p>
                    See the customer food menu.
                </p>

            </a>


        </div>

    </section>



    <!-- RECENT ORDERS -->

    <section class="recent-orders-section">

        <div class="section-heading">

            <div>

                <p>ORDER MANAGEMENT</p>

                <h2>
                    Recent Orders
                </h2>

            </div>


            <a
                href="orders.php">

                View All Orders →

            </a>

        </div>


        <?php if (
            mysqli_num_rows($recent_orders) === 0
        ): ?>

            <div class="no-recent-orders">

                <h3>
                    No Orders Yet
                </h3>

                <p>
                    Customer orders will appear here.
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
                                Status
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php while (
                            $order =
                            mysqli_fetch_assoc(
                                $recent_orders
                            )
                        ): ?>

                            <tr>

                                <td>

                                    <strong>

                                        #<?php
                                        echo $order['order_id'];
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo escape(
                                        $order['customer_name']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <strong>

                                        Rs.
                                        <?php
                                        echo number_format(
                                            $order['total_amount'],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <span
                                        class="status-badge
                                        status-<?php
                                        echo strtolower(
                                            str_replace(
                                                ' ',
                                                '-',
                                                $order[
                                                    'order_status'
                                                ]
                                            )
                                        );
                                        ?>">

                                        <?php
                                        echo escape(
                                            $order[
                                                'order_status'
                                            ]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $order['order_date']
                                        )
                                    );
                                    ?>

                                </td>


                                <td>

                                    <a
                                        href="order_details.php?order_id=<?php
                                        echo $order['order_id'];
                                        ?>"
                                        class="admin-view-btn">

                                        View

                                    </a>

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