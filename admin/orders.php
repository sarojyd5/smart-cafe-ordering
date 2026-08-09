<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireAdmin();


// --------------------------------------------------
// SUCCESS / ERROR MESSAGES
// --------------------------------------------------

$message = '';

if (isset($_GET['updated'])) {
    $message = "Order status updated successfully.";
}


// --------------------------------------------------
// GET ALL ORDERS
// --------------------------------------------------

$sql = "
    SELECT
        order_id,
        customer_name,
        customer_phone,
        delivery_address,
        subtotal,
        delivery_charge,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        order_date
    FROM orders
    ORDER BY order_date DESC
";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Manage Orders | Timeout Cafe
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

            <h1>Manage Orders</h1>

            <span>
                View and manage customer food orders.
            </span>

        </div>


        <div class="admin-header-actions">

            <a
                href="index.php"
                class="visit-site-btn">

                Dashboard

            </a>


            <a
                href="logout.php"
                class="admin-logout-btn">

                Logout

            </a>

        </div>

    </section>



    <!-- SUCCESS MESSAGE -->

    <?php if ($message !== ''): ?>

        <div class="form-success">

            <?php
            echo escape($message);
            ?>

        </div>

    <?php endif; ?>



    <!-- ORDERS -->

    <section class="admin-food-section">


        <div class="food-page-heading">

            <div>

                <p>ORDER MANAGEMENT</p>

                <h2>
                    Customer Orders
                </h2>

            </div>

        </div>



        <div class="admin-table-container">

            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            Order
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Payment
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


                <?php if (
                    mysqli_num_rows($result) > 0
                ): ?>


                    <?php while (
                        $order =
                        mysqli_fetch_assoc($result)
                    ): ?>


                        <tr>


                            <!-- ORDER ID -->

                            <td>

                                <strong>

                                    #<?php
                                    echo escape(
                                        $order['order_id']
                                    );
                                    ?>

                                </strong>

                            </td>



                            <!-- CUSTOMER -->

                            <td>

                                <?php
                                echo escape(
                                    $order['customer_name']
                                );
                                ?>

                            </td>



                            <!-- PHONE -->

                            <td>

                                <?php
                                echo escape(
                                    $order['customer_phone']
                                );
                                ?>

                            </td>



                            <!-- TOTAL -->

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



                            <!-- PAYMENT -->

                            <td>

                                <?php
                                echo escape(
                                    $order['payment_method']
                                );
                                ?>

                                <br>

                                <small>

                                    <?php
                                    echo escape(
                                        $order['payment_status']
                                    );
                                    ?>

                                </small>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span class="order-status-badge">

                                    <?php
                                    echo escape(
                                        $order['order_status']
                                    );
                                    ?>

                                </span>

                            </td>



                            <!-- DATE -->

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



                            <!-- ACTION -->

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


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="empty-table">

                            No customer orders found.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


    </section>


</main>


</body>

</html>