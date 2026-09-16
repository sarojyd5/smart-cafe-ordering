<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

/*
|--------------------------------------------------------------------------
| SALES REPORT
|--------------------------------------------------------------------------
| Database table:
| orders
|
| Important existing columns:
| order_id
| customer_name
| total_amount
| payment_method
| payment_status
| order_status
| order_date
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SELECT REPORT PERIOD
|--------------------------------------------------------------------------
*/

$period = $_GET['period'] ?? 'today';

$allowed_periods = [
    'today',
    'week',
    'month',
    'all'
];

if (!in_array($period, $allowed_periods, true)) {
    $period = 'today';
}


/*
|--------------------------------------------------------------------------
| CALCULATE DATE RANGE
|--------------------------------------------------------------------------
*/

$today = date('Y-m-d');

switch ($period) {

    case 'week':

        // Current week: Monday to Sunday
        $start_date = date(
            'Y-m-d',
            strtotime('monday this week')
        );

        $end_date = date(
            'Y-m-d',
            strtotime('monday next week')
        );

        $period_title = "This Week";

        break;


    case 'month':

        // Current month
        $start_date = date(
            'Y-m-01'
        );

        $end_date = date(
            'Y-m-d',
            strtotime('first day of next month')
        );

        $period_title = "This Month";

        break;


    case 'all':

        $start_date = '1970-01-01';

        $end_date = date(
            'Y-m-d',
            strtotime('+1 day')
        );

        $period_title = "All Time";

        break;


    default:

        // Today
        $start_date = $today;

        $end_date = date(
            'Y-m-d',
            strtotime('+1 day')
        );

        $period_title = "Today";

        break;
}


/*
|--------------------------------------------------------------------------
| SUMMARY DATA
|--------------------------------------------------------------------------
*/

$summary_sql = "
    SELECT
        COALESCE(SUM(total_amount), 0) AS total_sales,
        COUNT(*) AS sales_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN order_status = 'Delivered'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS delivered_orders,
        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'Paid'
                    THEN total_amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_sales
    FROM orders
    WHERE order_date >= ?
    AND order_date < ?
    AND order_status <> 'Cancelled'
";


$stmt = mysqli_prepare(
    $conn,
    $summary_sql
);


mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $start_date,
    $end_date
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


$summary = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| TODAY'S SALES
|--------------------------------------------------------------------------
*/

$today_start = date('Y-m-d');

$today_end = date(
    'Y-m-d',
    strtotime('+1 day')
);


$today_sql = "
    SELECT
        COALESCE(SUM(total_amount), 0) AS today_sales
    FROM orders
    WHERE order_date >= ?
    AND order_date < ?
    AND order_status <> 'Cancelled'
";


$stmt = mysqli_prepare(
    $conn,
    $today_sql
);


mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $today_start,
    $today_end
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


$today_data = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


$today_sales =
    (float)($today_data['today_sales'] ?? 0);


/*
|--------------------------------------------------------------------------
| FORMAT SUMMARY
|--------------------------------------------------------------------------
*/

$total_sales =
    (float)($summary['total_sales'] ?? 0);

$sales_orders =
    (int)($summary['sales_orders'] ?? 0);

$delivered_orders =
    (int)($summary['delivered_orders'] ?? 0);

$paid_sales =
    (float)($summary['paid_sales'] ?? 0);


/*
|--------------------------------------------------------------------------
| WEEKLY SALES - LAST 8 WEEKS
|--------------------------------------------------------------------------
*/

$weekly_sql = "
    SELECT
        YEARWEEK(order_date, 1) AS week_number,
        MIN(DATE(order_date)) AS first_order_date,
        MAX(DATE(order_date)) AS last_order_date,
        COUNT(*) AS order_count,
        COALESCE(SUM(total_amount), 0) AS sales
    FROM orders
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
    AND order_status <> 'Cancelled'
    GROUP BY YEARWEEK(order_date, 1)
    ORDER BY week_number DESC
";


$weekly_result =
    mysqli_query(
        $conn,
        $weekly_sql
    );


$weekly_sales = [];


if ($weekly_result) {

    while (
        $row =
        mysqli_fetch_assoc($weekly_result)
    ) {

        $weekly_sales[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| MONTHLY SALES - LAST 12 MONTHS
|--------------------------------------------------------------------------
*/

$monthly_sql = "
    SELECT
        YEAR(order_date) AS sale_year,
        MONTH(order_date) AS sale_month,
        COUNT(*) AS order_count,
        COALESCE(SUM(total_amount), 0) AS sales
    FROM orders
    WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    AND order_status <> 'Cancelled'
    GROUP BY
        YEAR(order_date),
        MONTH(order_date)
    ORDER BY
        sale_year DESC,
        sale_month DESC
";


$monthly_result =
    mysqli_query(
        $conn,
        $monthly_sql
    );


$monthly_sales = [];


if ($monthly_result) {

    while (
        $row =
        mysqli_fetch_assoc($monthly_result)
    ) {

        $monthly_sales[] = $row;

    }

}


/*
|--------------------------------------------------------------------------
| DETAILED SALES
|--------------------------------------------------------------------------
*/

$details_sql = "
    SELECT
        order_id,
        customer_name,
        total_amount,
        payment_method,
        payment_status,
        order_status,
        order_date
    FROM orders
    WHERE order_date >= ?
    AND order_date < ?
    AND order_status <> 'Cancelled'
    ORDER BY order_date DESC
";


$stmt = mysqli_prepare(
    $conn,
    $details_sql
);


mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $start_date,
    $end_date
);


mysqli_stmt_execute($stmt);


$details_result =
    mysqli_stmt_get_result($stmt);


?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Sales Report | Timeout Cafe
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        #faf7f4;

    color:
        #4a2d1f;

    line-height:
        1.5;

}


/* =========================================================
   MAIN CONTAINER
========================================================= */

.sales-page {

    width: 100%;

    max-width: 1250px;

    margin: 0 auto;

    padding:
        45px 25px 60px;

}


/* =========================================================
   HEADER
========================================================= */

.sales-header {

    display: flex;

    justify-content:
        space-between;

    align-items:
        flex-end;

    gap: 25px;

    margin-bottom:
        30px;

}


.header-label {

    color:
        #bd5c35;

    font-size:
        13px;

    font-weight:
        700;

    letter-spacing:
        3px;

    margin-bottom:
        5px;

}


.sales-header h1 {

    font-size:
        34px;

    color:
        #4b2e20;

    margin-bottom:
        5px;

}


.sales-header p {

    color:
        #806f66;

    font-size:
        15px;

}


/* =========================================================
   HEADER BUTTONS
========================================================= */

.header-actions {

    display: flex;

    gap: 10px;

    flex-wrap:
        wrap;

}


.header-btn {

    text-decoration:
        none;

    border:
        none;

    padding:
        11px 18px;

    border-radius:
        7px;

    font-size:
        14px;

    font-weight:
        700;

    cursor:
        pointer;

    transition:
        0.2s ease;

}


.dashboard-btn {

    background:
        #bd5c35;

    color:
        white;

}


.dashboard-btn:hover {

    background:
        #a94e2c;

    transform:
        translateY(-1px);

}


.print-btn {

    background:
        #b91c1c;

    color:
        white;

}


.print-btn:hover {

    background:
        #991b1b;

}


/* =========================================================
   PERIOD FILTER
========================================================= */

.period-section {

    background:
        white;

    border-radius:
        14px;

    padding:
        18px 20px;

    box-shadow:
        0 5px 22px rgba(70, 40, 20, 0.07);

    margin-bottom:
        25px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        15px;

    flex-wrap:
        wrap;

}


.period-title {

    font-size:
        15px;

    font-weight:
        700;

    color:
        #573728;

}


.period-buttons {

    display:
        flex;

    gap:
        8px;

    flex-wrap:
        wrap;

}


.period-buttons a {

    text-decoration:
        none;

    padding:
        9px 16px;

    border-radius:
        7px;

    border:
        1px solid #eadfd9;

    color:
        #654638;

    background:
        #fff;

    font-size:
        13px;

    font-weight:
        600;

    transition:
        0.2s ease;

}


.period-buttons a:hover {

    border-color:
        #bd5c35;

    color:
        #bd5c35;

}


.period-buttons a.active {

    background:
        #bd5c35;

    border-color:
        #bd5c35;

    color:
        white;

}


/* =========================================================
   SUMMARY CARDS
========================================================= */

.summary-grid {

    display:
        grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:
        18px;

    margin-bottom:
        30px;

}


.summary-card {

    background:
        white;

    border-radius:
        14px;

    padding:
        21px;

    display:
        flex;

    align-items:
        center;

    gap:
        15px;

    box-shadow:
        0 5px 22px rgba(70, 40, 20, 0.07);

    min-width:
        0;

}


.summary-icon {

    width:
        48px;

    height:
        48px;

    min-width:
        48px;

    border-radius:
        12px;

    background:
        #f8e8df;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        23px;

}


.summary-content {

    min-width:
        0;

}


.summary-content span {

    display:
        block;

    font-size:
        13px;

    color:
        #806f66;

    margin-bottom:
        3px;

}


.summary-content strong {

    display:
        block;

    font-size:
        21px;

    color:
        #4a2d1f;

    white-space:
        nowrap;

}


/* =========================================================
   REPORT CARD
========================================================= */

.report-card {

    background:
        white;

    border-radius:
        15px;

    padding:
        22px;

    box-shadow:
        0 5px 22px rgba(70, 40, 20, 0.07);

    margin-bottom:
        30px;

}


.report-heading {

    margin-bottom:
        20px;

}


.report-heading .label {

    color:
        #bd5c35;

    font-size:
        11px;

    font-weight:
        700;

    letter-spacing:
        2px;

    margin-bottom:
        3px;

}


.report-heading h2 {

    font-size:
        22px;

    color:
        #553525;

}


/* =========================================================
   TABLE WRAPPER
========================================================= */

.table-wrapper {

    width:
        100%;

    overflow-x:
        auto;

    -webkit-overflow-scrolling:
        touch;

}


table {

    width:
        100%;

    border-collapse:
        collapse;

    min-width:
        750px;

}


thead th {

    background:
        #f8f4f1;

    color:
        #553525;

    text-align:
        left;

    font-size:
        13px;

    font-weight:
        700;

    padding:
        13px 12px;

    white-space:
        nowrap;

}


tbody td {

    padding:
        14px 12px;

    border-bottom:
        1px solid #eee5e0;

    color:
        #68574e;

    font-size:
        13px;

}


tbody tr:hover {

    background:
        #fdfaf8;

}


.order-number {

    font-weight:
        700;

    color:
        #4a2d1f;

}


.amount {

    font-weight:
        700;

    color:
        #4a2d1f;

}


/* =========================================================
   STATUS BADGES
========================================================= */

.status {

    display:
        inline-block;

    padding:
        5px 11px;

    border-radius:
        20px;

    font-size:
        11px;

    font-weight:
        700;

    white-space:
        nowrap;

}


.status-pending {

    background:
        #fff0c7;

    color:
        #9a6700;

}


.status-confirmed {

    background:
        #e5efff;

    color:
        #1854a4;

}


.status-delivered {

    background:
        #dcf7e7;

    color:
        #16733c;

}


.status-cancelled {

    background:
        #fee2e2;

    color:
        #b91c1c;

}


.status-paid {

    background:
        #dcf7e7;

    color:
        #16733c;

}


.status-unpaid {

    background:
        #fff0c7;

    color:
        #9a6700;

}


/* =========================================================
   VIEW BUTTON
========================================================= */

.view-order {

    display:
        inline-block;

    text-decoration:
        none;

    background:
        #bd5c35;

    color:
        white;

    padding:
        6px 11px;

    border-radius:
        6px;

    font-size:
        11px;

    font-weight:
        700;

}


.view-order:hover {

    background:
        #a94e2c;

}


/* =========================================================
   EMPTY
========================================================= */

.empty-message {

    text-align:
        center;

    padding:
        35px 15px;

    color:
        #8b7a70;

    font-size:
        14px;

}


/* =========================================================
   REPORT INFORMATION
========================================================= */

.report-info {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        15px;

    margin-bottom:
        18px;

    flex-wrap:
        wrap;

}


.report-info p {

    color:
        #7c6b61;

    font-size:
        13px;

}


.report-info strong {

    color:
        #4a2d1f;

}


/* =========================================================
   FOOTER
========================================================= */

.report-footer {

    text-align:
        center;

    color:
        #99877d;

    font-size:
        12px;

    margin-top:
        10px;

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    body {

        background:
            white;

    }


    .sales-page {

        max-width:
            none;

        padding:
            0;

    }


    .header-actions,
    .period-section,
    .view-order {

        display:
            none !important;

    }


    .summary-card,
    .report-card {

        box-shadow:
            none;

        border:
            1px solid #ddd;

    }


    .report-card {

        break-inside:
            avoid;

    }

}


/* =========================================================
   LARGE TABLET
========================================================= */

@media (max-width: 1050px) {

    .summary-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 800px) {

    .sales-page {

        padding:
            30px 18px 45px;

    }


    .sales-header {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .header-actions {

        width:
            100%;

    }


    .header-btn {

        flex:
            1;

        text-align:
            center;

    }


    .period-section {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .period-buttons {

        width:
            100%;

    }


    .period-buttons a {

        flex:
            1;

        text-align:
            center;

    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 600px) {

    .sales-page {

        padding:
            22px 12px 35px;

    }


    .sales-header h1 {

        font-size:
            28px;

    }


    .sales-header p {

        font-size:
            13px;

    }


    .summary-grid {

        grid-template-columns:
            1fr;

        gap:
            12px;

    }


    .summary-card {

        padding:
            17px;

    }


    .summary-content strong {

        font-size:
            19px;

    }


    .period-buttons {

        display:
            grid;

        grid-template-columns:
            repeat(2, 1fr);

        width:
            100%;

    }


    .period-buttons a {

        width:
            100%;

    }


    .report-card {

        padding:
            16px;

        border-radius:
            12px;

    }


    .report-heading h2 {

        font-size:
            19px;

    }


    .header-actions {

        flex-direction:
            column;

    }


    .header-btn {

        width:
            100%;

    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 380px) {

    .period-buttons {

        grid-template-columns:
            1fr;

    }


    .summary-card {

        gap:
            10px;

    }


    .summary-icon {

        width:
            42px;

        height:
            42px;

        min-width:
            42px;

        font-size:
            19px;

    }

}

</style>

</head>


<body>


<main class="sales-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="sales-header">

        <div>

            <div class="header-label">
                TIMEOUT CAFE
            </div>

            <h1>
                Sales Report
            </h1>

            <p>
                View and monitor cafe sales.
            </p>

        </div>


        <div class="header-actions">

            <a
                href="index.php"
                class="header-btn dashboard-btn"
            >
                ← Dashboard
            </a>


            <button
                type="button"
                class="header-btn print-btn"
                onclick="window.print()"
            >
                🖨 Print Report
            </button>

        </div>

    </header>



    <!-- =====================================================
         PERIOD FILTER
    ====================================================== -->

    <section class="period-section">

        <div class="period-title">

            Sales Period:
            <strong>
                <?php echo escape($period_title); ?>
            </strong>

        </div>


        <div class="period-buttons">


            <a
                href="sales_report.php?period=today"
                class="<?php echo $period === 'today' ? 'active' : ''; ?>"
            >
                Today
            </a>


            <a
                href="sales_report.php?period=week"
                class="<?php echo $period === 'week' ? 'active' : ''; ?>"
            >
                This Week
            </a>


            <a
                href="sales_report.php?period=month"
                class="<?php echo $period === 'month' ? 'active' : ''; ?>"
            >
                This Month
            </a>


            <a
                href="sales_report.php?period=all"
                class="<?php echo $period === 'all' ? 'active' : ''; ?>"
            >
                All Time
            </a>


        </div>

    </section>



    <!-- =====================================================
         SUMMARY CARDS
    ====================================================== -->

    <section class="summary-grid">


        <!-- TODAY -->

        <div class="summary-card">

            <div class="summary-icon">
                💰
            </div>

            <div class="summary-content">

                <span>
                    Today's Sales
                </span>

                <strong>
                    Rs. <?php echo number_format($today_sales, 2); ?>
                </strong>

            </div>

        </div>



        <!-- SELECTED PERIOD -->

        <div class="summary-card">

            <div class="summary-icon">
                💵
            </div>

            <div class="summary-content">

                <span>
                    <?php echo escape($period_title); ?> Sales
                </span>

                <strong>
                    Rs. <?php echo number_format($total_sales, 2); ?>
                </strong>

            </div>

        </div>



        <!-- ORDERS -->

        <div class="summary-card">

            <div class="summary-icon">
                🛒
            </div>

            <div class="summary-content">

                <span>
                    Sales Orders
                </span>

                <strong>
                    <?php echo number_format($sales_orders); ?>
                </strong>

            </div>

        </div>



        <!-- DELIVERED -->

        <div class="summary-card">

            <div class="summary-icon">
                ✅
            </div>

            <div class="summary-content">

                <span>
                    Delivered Orders
                </span>

                <strong>
                    <?php echo number_format($delivered_orders); ?>
                </strong>

            </div>

        </div>


    </section>



    <!-- =====================================================
         SELECTED PERIOD DETAILS
    ====================================================== -->

    <section class="report-card">


        <div class="report-info">

            <div>

                <div class="report-heading">

                    <div class="label">
                        SALES MANAGEMENT
                    </div>

                    <h2>
                        <?php echo escape($period_title); ?> Sales Details
                    </h2>

                </div>

            </div>


            <p>

                Paid sales:
                <strong>
                    Rs. <?php echo number_format($paid_sales, 2); ?>
                </strong>

            </p>

        </div>



        <div class="table-wrapper">

            <table>

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

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    mysqli_num_rows($details_result) > 0
                ): ?>


                    <?php while (
                        $order =
                        mysqli_fetch_assoc($details_result)
                    ): ?>


                        <tr>


                            <td class="order-number">

                                #<?php
                                echo (int)$order['order_id'];
                                ?>

                            </td>


                            <td>

                                <?php
                                echo escape(
                                    $order['customer_name']
                                );
                                ?>

                            </td>


                            <td class="amount">

                                Rs.
                                <?php
                                echo number_format(
                                    (float)$order['total_amount'],
                                    2
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo escape(
                                    $order['payment_method']
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                $payment_status =
                                    $order['payment_status'];

                                if (
                                    strtolower(
                                        $payment_status
                                    ) === 'paid'
                                ) {

                                    $payment_class =
                                        'status-paid';

                                } else {

                                    $payment_class =
                                        'status-unpaid';

                                }

                                ?>

                                <span
                                    class="status <?php
                                    echo $payment_class;
                                    ?>"
                                >

                                    <?php
                                    echo escape(
                                        $payment_status
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                $order_status =
                                    $order['order_status'];

                                $status_lower =
                                    strtolower(
                                        $order_status
                                    );


                                $status_class =
                                    'status-pending';


                                if (
                                    $status_lower ===
                                    'delivered'
                                ) {

                                    $status_class =
                                        'status-delivered';

                                } elseif (
                                    $status_lower ===
                                    'confirmed'
                                ) {

                                    $status_class =
                                        'status-confirmed';

                                } elseif (
                                    $status_lower ===
                                    'cancelled'
                                ) {

                                    $status_class =
                                        'status-cancelled';

                                }

                                ?>

                                <span
                                    class="status <?php
                                    echo $status_class;
                                    ?>"
                                >

                                    <?php
                                    echo escape(
                                        $order_status
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
                                    href="../order_details.php?order_id=<?php
                                    echo (int)$order['order_id'];
                                    ?>"
                                    class="view-order"
                                >
                                    View
                                </a>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="8"
                            class="empty-message"
                        >

                            No sales records found
                            for this period.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         WEEKLY SALES
    ====================================================== -->

    <section class="report-card">


        <div class="report-heading">

            <div class="label">
                WEEKLY SALES
            </div>

            <h2>
                Last 8 Weeks
            </h2>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Week
                        </th>

                        <th>
                            Date Range
                        </th>

                        <th>
                            Orders
                        </th>

                        <th>
                            Sales
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (!empty($weekly_sales)): ?>


                    <?php

                    $week_counter = 1;

                    foreach (
                        $weekly_sales as $week
                    ):

                    ?>


                        <tr>


                            <td>

                                Week
                                <?php
                                echo $week_counter;
                                ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $week['first_order_date']
                                    )
                                );

                                ?>

                                -

                                <?php

                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $week['last_order_date']
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <?php
                                echo number_format(
                                    (int)$week['order_count']
                                );
                                ?>

                            </td>


                            <td class="amount">

                                Rs.
                                <?php

                                echo number_format(
                                    (float)$week['sales'],
                                    2
                                );

                                ?>

                            </td>


                        </tr>


                    <?php

                    $week_counter++;

                    endforeach;

                    ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="4"
                            class="empty-message"
                        >

                            No weekly sales records available.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         MONTHLY SALES
    ====================================================== -->

    <section class="report-card">


        <div class="report-heading">

            <div class="label">
                MONTHLY SALES
            </div>

            <h2>
                Last 12 Months
            </h2>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Month
                        </th>

                        <th>
                            Orders
                        </th>

                        <th>
                            Sales
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (!empty($monthly_sales)): ?>


                    <?php foreach (
                        $monthly_sales as $month
                    ): ?>


                        <tr>


                            <td>

                                <?php

                                $month_date =
                                    $month['sale_year']
                                    . '-'
                                    . str_pad(
                                        $month['sale_month'],
                                        2,
                                        '0',
                                        STR_PAD_LEFT
                                    )
                                    . '-01';


                                echo date(
                                    'F Y',
                                    strtotime(
                                        $month_date
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo number_format(
                                    (int)$month['order_count']
                                );

                                ?>

                            </td>


                            <td class="amount">

                                Rs.
                                <?php

                                echo number_format(
                                    (float)$month['sales'],
                                    2
                                );

                                ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="3"
                            class="empty-message"
                        >

                            No monthly sales records available.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <div class="report-footer">

        Timeout Cafe Sales Management System

    </div>


</main>


</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>