<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireCustomerLogin();

$customer_id = $_SESSION['customer_id'];

$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    header("Location: orders.php");
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
        customer_name,
        customer_phone,
        delivery_address,
        order_note,
        subtotal,
        delivery_charge,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        order_date
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
    $customer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$order = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$order) {
    header("Location: orders.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| GET ORDER ITEMS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        food_name,
        quantity,
        price,
        total_price
    FROM order_items
    WHERE order_id = ?
    ORDER BY order_item_id ASC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $order_id
);

mysqli_stmt_execute($stmt);

$items_result = mysqli_stmt_get_result($stmt);


/*
|--------------------------------------------------------------------------
| TRACKING STEPS
|--------------------------------------------------------------------------
*/

$statuses = [
    "Pending",
    "Confirmed",
    "Preparing",
    "Out for Delivery",
    "Delivered"
];

$current_status = $order['order_status'];

$current_index = array_search(
    $current_status,
    $statuses
);

if ($current_index === false) {
    $current_index = 0;
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
        Order #<?php echo $order['order_id']; ?>
        | Timeout Cafe
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <link
        rel="stylesheet"
        href="../assets/css/responsive.css">

</head>

<body>

<?php include "../includes/navbar.php"; ?>


<main class="order-details-page">


    <!-- HEADER -->

    <div class="order-details-header">

        <a
            href="orders.php"
            class="back-link">

            ← Back to My Orders

        </a>

        <p>ORDER DETAILS</p>

        <h1>
            Order #<?php
            echo $order['order_id'];
            ?>
        </h1>

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


    <!-- ORDER TRACKING -->

    <section class="order-tracking">

        <h2>Order Tracking</h2>


        <div class="tracking-container">

            <?php foreach (
                $statuses as $index => $status
            ): ?>

                <div class="tracking-step
                    <?php

                    if ($index < $current_index) {
                        echo 'completed';
                    }

                    if ($index === $current_index) {
                        echo 'active';
                    }

                    ?>">

                    <div class="tracking-circle">

                        <?php if ($index < $current_index): ?>

                            ✓

                        <?php else: ?>

                            <?php echo $index + 1; ?>

                        <?php endif; ?>

                    </div>


                    <span>
                        <?php echo $status; ?>
                    </span>

                </div>

            <?php endforeach; ?>

        </div>

    </section>


    <div class="order-details-layout">


        <!-- ITEMS -->

        <section class="order-items-section">

            <h2>Ordered Items</h2>


            <?php while (
                $item = mysqli_fetch_assoc(
                    $items_result
                )
            ): ?>

                <div class="order-detail-item">

                    <div>

                        <h3>
                            <?php
                            echo escape(
                                $item['food_name']
                            );
                            ?>
                        </h3>

                        <p>
                            Quantity:
                            <?php
                            echo $item['quantity'];
                            ?>
                        </p>

                        <p>
                            Rs.
                            <?php
                            echo number_format(
                                $item['price'],
                                2
                            );
                            ?>
                            each
                        </p>

                    </div>


                    <strong>

                        Rs.
                        <?php
                        echo number_format(
                            $item['total_price'],
                            2
                        );
                        ?>

                    </strong>

                </div>

            <?php endwhile; ?>


            <div class="order-total-box">

                <div>

                    <span>Subtotal</span>

                    <strong>
                        Rs.
                        <?php
                        echo number_format(
                            $order['subtotal'],
                            2
                        );
                        ?>
                    </strong>

                </div>


                <div>

                    <span>Delivery Charge</span>

                    <strong>
                        Rs.
                        <?php
                        echo number_format(
                            $order['delivery_charge'],
                            2
                        );
                        ?>
                    </strong>

                </div>


                <div class="final-total">

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

        </section>


        <!-- DELIVERY INFORMATION -->

        <aside class="delivery-details">

            <h2>Delivery Information</h2>


            <div class="delivery-info-row">

                <span>Customer</span>

                <strong>
                    <?php
                    echo escape(
                        $order['customer_name']
                    );
                    ?>
                </strong>

            </div>


            <div class="delivery-info-row">

                <span>Phone</span>

                <strong>
                    <?php
                    echo escape(
                        $order['customer_phone']
                    );
                    ?>
                </strong>

            </div>


            <div class="delivery-info-row">

                <span>Address</span>

                <strong>
                    <?php
                    echo escape(
                        $order['delivery_address']
                    );
                    ?>
                </strong>

            </div>


            <?php if (
                !empty($order['order_note'])
            ): ?>

                <div class="delivery-info-row">

                    <span>Order Note</span>

                    <strong>
                        <?php
                        echo escape(
                            $order['order_note']
                        );
                        ?>
                    </strong>

                </div>

            <?php endif; ?>


            <div class="delivery-info-row">

                <span>Payment</span>

                <strong>
                    <?php
                    echo escape(
                        $order['payment_method']
                    );
                    ?>
                </strong>

            </div>


            <div class="delivery-info-row">

                <span>Payment Status</span>

                <strong>
                    <?php
                    echo escape(
                        $order['payment_status']
                    );
                    ?>
                </strong>

            </div>


            <div class="delivery-info-row">

                <span>Ordered On</span>

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

        </aside>


    </div>

</main>


<?php include "../includes/footer.php"; ?>


</body>

</html>

<?php

mysqli_stmt_close($stmt);

?>