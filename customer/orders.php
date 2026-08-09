<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireCustomerLogin();

$customer_id = $_SESSION['customer_id'];

$sql = "
    SELECT
        order_id,
        subtotal,
        delivery_charge,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        order_date
    FROM orders
    WHERE customer_id = ?
    ORDER BY order_date DESC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $customer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>My Orders | Timeout Cafe</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <link
        rel="stylesheet"
        href="../assets/css/responsive.css">

</head>

<body>

<?php include "../includes/navbar.php"; ?>


<main class="orders-page">

    <div class="orders-header">

        <p>YOUR ORDERS</p>

        <h1>My Orders</h1>

        <p>
            View and track all your cafe orders.
        </p>

    </div>


    <?php if (mysqli_num_rows($result) === 0): ?>

        <div class="empty-orders">

            <h2>No Orders Yet</h2>

            <p>
                You haven't placed any orders yet.
            </p>

            <a
                href="../menu.php"
                class="btn-primary">

                Browse Menu

            </a>

        </div>

    <?php else: ?>


        <div class="orders-list">

            <?php while ($order = mysqli_fetch_assoc($result)): ?>

                <article class="order-card">


                    <div class="order-card-header">

                        <div>

                            <span>
                                Order
                            </span>

                            <h2>
                                #<?php
                                echo $order['order_id'];
                                ?>
                            </h2>

                        </div>


                        <span class="status-badge
                            status-<?php
                            echo strtolower(
                                str_replace(
                                    ' ',
                                    '-',
                                    $order['order_status']
                                )
                            );
                            ?>">

                            <?php
                            echo escape(
                                $order['order_status']
                            );
                            ?>

                        </span>

                    </div>


                    <div class="order-card-info">


                        <div>

                            <span>Order Date</span>

                            <strong>
                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order['order_date']
                                    )
                                );
                                ?>
                            </strong>

                        </div>


                        <div>

                            <span>Payment</span>

                            <strong>
                                <?php
                                echo escape(
                                    $order['payment_method']
                                );
                                ?>
                            </strong>

                        </div>


                        <div>

                            <span>Total</span>

                            <strong>
                                Rs.
                                <?php
                                echo number_format(
                                    $order['total_amount'],
                                    2
                                );
                                ?>
                            </strong>

                        </div>

                    </div>


                    <div class="order-card-footer">

                        <span>
                            Payment:
                            <?php
                            echo escape(
                                $order['payment_status']
                            );
                            ?>
                        </span>


                        <a
                            href="order_details.php?order_id=<?php
                            echo $order['order_id'];
                            ?>"
                            class="view-order-btn">

                            View Details

                        </a>

                    </div>

                </article>

            <?php endwhile; ?>

        </div>

    <?php endif; ?>

</main>


<?php include "../includes/footer.php"; ?>

</body>

</html>

<?php

mysqli_stmt_close($stmt);

?>