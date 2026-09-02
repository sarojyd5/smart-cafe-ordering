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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Dashboard | Timeout Cafe</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <link rel="stylesheet"
          href="../assets/css/responsive.css">

</head>

<body>

<?php include "../includes/navbar.php"; ?>


<main class="customer-dashboard">

    <section class="dashboard-header">

        <p>WELCOME BACK</p>

        <h1>
            Hello,
            <?php echo escape($_SESSION["customer_name"]); ?>!
        </h1>

        <p>
            Welcome to your Timeout Cafe account.
        </p>

    </section>


    <section class="dashboard-cards">

        <a href="../menu.php" class="dashboard-card">

            <h2>Browse Menu</h2>

            <p>
                Explore our delicious food.
            </p>

        </a>


        <a href="orders.php" class="dashboard-card">

            <h2>My Orders</h2>

            <p>
                View and track your previous orders.
            </p>

</a>

<a href="profile.php" class="dashboard-card">

    <h2>My Profile</h2>

    <p>Manage your account information.</p>

</a>
</section>

</main>


<?php include "../includes/footer.php"; ?>

</body>

</html>