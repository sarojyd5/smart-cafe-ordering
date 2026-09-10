<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireAdmin();


// --------------------------------------------------
// CHECK ORDER ID
// --------------------------------------------------

if (
    !isset($_GET['order_id']) ||
    !is_numeric($_GET['order_id'])
) {

    header("Location: orders.php");
    exit();

}

$order_id = (int) $_GET['order_id'];


// --------------------------------------------------
// GET ORDER
// --------------------------------------------------

$sql = "
    SELECT
        order_id,
        customer_id,
        customer_name,
        customer_phone,
        delivery_address,
        delivery_map_link,
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
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $order_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$order = mysqli_fetch_assoc($result);


if (!$order) {

    header("Location: orders.php");
    exit();

}


// --------------------------------------------------
// GET ORDER ITEMS
// --------------------------------------------------

$item_sql = "
    SELECT
        order_item_id,
        food_id,
        food_name,
        quantity,
        price,
        total_price
    FROM order_items
    WHERE order_id = ?
    ORDER BY order_item_id ASC
";

$item_stmt = mysqli_prepare(
    $conn,
    $item_sql
);

mysqli_stmt_bind_param(
    $item_stmt,
    "i",
    $order_id
);

mysqli_stmt_execute($item_stmt);

$items = mysqli_stmt_get_result(
    $item_stmt
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
        Order #<?php
        echo $order_id;
        ?>
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


<main class="admin-dashboard">


    <!-- HEADER -->

    <section class="admin-dashboard-header">

        <div>

            <p>TIMEOUT CAFE</p>

            <h1>
                Order #<?php
                echo $order_id;
                ?>
            </h1>

            <span>
                View order details and update delivery status.
            </span>

        </div>


        <div class="admin-header-actions">

            <a
                href="orders.php"
                class="visit-site-btn">

                ← Orders

            </a>


            <a
                href="logout.php"
                class="admin-logout-btn">

                Logout

            </a>

        </div>

    </section>



    <!-- CUSTOMER INFORMATION -->

    <section class="order-details-grid">


        <div class="order-info-card">

            <p class="card-label">
                CUSTOMER
            </p>

            <h2>

                <?php
                echo escape(
                    $order['customer_name']
                );
                ?>

            </h2>

            <p>

                Phone:
                <strong>

                    <?php
                    echo escape(
                        $order['customer_phone']
                    );
                    ?>

                </strong>

            </p>

        </div>



        <div class="order-info-card">

            <p class="card-label">
                DELIVERY ADDRESS
            </p>

            <p>

                <?php
                echo nl2br(
                    escape(
                        $order['delivery_address']
                    )
                );
                ?>

            </p>

            <?php if (
                !empty($order['delivery_map_link'])
            ): ?>

                <a
                    href="<?php
                    echo escape(
                        $order['delivery_map_link']
                    );
                    ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="map-view-btn"
                    style="display:inline-block; margin-top:12px;">

                    View on Google Maps &#8599;

                </a>

            <?php endif; ?>

        </div>



        <div class="order-info-card">

            <p class="card-label">
                PAYMENT
            </p>

            <h3>

                <?php
                echo escape(
                    $order['payment_method']
                );
                ?>

            </h3>

            <p>

                Status:

                <strong>

                    <?php
                    echo escape(
                        $order['payment_status']
                    );
                    ?>

                </strong>

            </p>

        </div>


    </section>



    <!-- ORDER ITEMS -->

    <section class="order-items-section">


        <div class="food-page-heading">

            <div>

                <p>ORDER ITEMS</p>

                <h2>
                    Food Ordered
                </h2>

            </div>

        </div>


        <div class="admin-table-container">

            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            Food
                        </th>

                        <th>
                            Quantity
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php while (
                    $item =
                    mysqli_fetch_assoc($items)
                ): ?>


                    <tr>

                        <td>

                            <strong>

                                <?php
                                echo escape(
                                    $item['food_name']
                                );
                                ?>

                            </strong>

                        </td>


                        <td>

                            <?php
                            echo escape(
                                $item['quantity']
                            );
                            ?>

                        </td>


                        <td>

                            Rs.
                            <?php
                            echo number_format(
                                $item['price'],
                                2
                            );
                            ?>

                        </td>


                        <td>

                            <strong>

                                Rs.
                                <?php
                                echo number_format(
                                    $item['total_price'],
                                    2
                                );
                                ?>

                            </strong>

                        </td>

                    </tr>


                <?php endwhile; ?>


                </tbody>

            </table>

        </div>


    </section>



    <!-- ORDER SUMMARY -->

    <section class="order-summary-card">


        <div class="summary-row">

            <span>
                Subtotal
            </span>

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


        <div class="summary-row">

            <span>
                Delivery Charge
            </span>

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


        <div class="summary-row summary-total">

            <span>
                Total
            </span>

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


    </section>



    <!-- ORDER NOTE -->

    <?php if (
        !empty($order['order_note'])
    ): ?>

        <section class="order-note-card">

            <p class="card-label">
                CUSTOMER NOTE
            </p>

            <p>

                <?php
                echo nl2br(
                    escape(
                        $order['order_note']
                    )
                );
                ?>

            </p>

        </section>

    <?php endif; ?>



    <!-- STATUS UPDATE -->

    <section class="order-status-update">


        <div>

            <p class="card-label">
                ORDER STATUS
            </p>

            <h2>
                Update Order
            </h2>

        </div>


        <form
            method="POST"
            action="update_order_status.php">


            <input
                type="hidden"
                name="order_id"
                value="<?php
                echo $order_id;
                ?>">


            <div class="status-update-form">


                <select
                    name="order_status"
                    required>


                    <option
                        value="Pending"
                        <?php
                        echo $order['order_status']
                            === 'Pending'
                            ? 'selected'
                            : '';
                        ?>>

                        Pending

                    </option>


                    <option
                        value="Confirmed"
                        <?php
                        echo $order['order_status']
                            === 'Confirmed'
                            ? 'selected'
                            : '';
                        ?>>

                        Confirmed

                    </option>


                    <option
                        value="Preparing"
                        <?php
                        echo $order['order_status']
                            === 'Preparing'
                            ? 'selected'
                            : '';
                        ?>>

                        Preparing

                    </option>


                    <option
                        value="Ready"
                        <?php
                        echo $order['order_status']
                            === 'Ready'
                            ? 'selected'
                            : '';
                        ?>>

                        Ready

                    </option>


                    <option
                        value="Out for Delivery"
                        <?php
                        echo $order['order_status']
                            === 'Out for Delivery'
                            ? 'selected'
                            : '';
                        ?>>

                        Out for Delivery

                    </option>


                    <option
                        value="Delivered"
                        <?php
                        echo $order['order_status']
                            === 'Delivered'
                            ? 'selected'
                            : '';
                        ?>>

                        Delivered

                    </option>


                    <option
                        value="Cancelled"
                        <?php
                        echo $order['order_status']
                            === 'Cancelled'
                            ? 'selected'
                            : '';
                        ?>>

                        Cancelled

                    </option>


                </select>


                <button
                    type="submit"
                    class="save-food-btn">

                    Update Status

                </button>


            </div>

        </form>


    </section>


</main>


</body>

</html>