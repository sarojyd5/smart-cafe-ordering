<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us | Timeout Cafe</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>

<body>

<?php include 'includes/navbar.php'; ?>

<section class="about-page">

    <div class="about-container">

        <div class="about-image">
            <img src="assets/images/hero1.png" alt="Timeout Cafe">
        </div>

        <div class="about-content">
            <span class="section-subtitle">ABOUT US</span>

            <h1>Welcome to <span>Timeout Cafe</span></h1>

            <p>
                Timeout Cafe is a modern cafe ordering system designed to make
                ordering food simple, convenient, and efficient.
            </p>

            <p>
                Our system allows customers to browse available food items,
                add their favorite items to the cart, provide delivery
                information, and place orders online without waiting in a queue.
            </p>

            <p>
                The system also provides an admin panel where cafe staff can
                manage food items, categories, orders, availability, and
                customer orders efficiently.
            </p>

            <a href="menu.php" class="btn-primary">Explore Our Menu</a>
        </div>

    </div>

</section>

<section class="about-features">

    <div class="about-feature">
        <div class="feature-icon">🍔</div>
        <h3>Fresh Food</h3>
        <p>Freshly prepared food using quality ingredients.</p>
    </div>

    <div class="about-feature">
        <div class="feature-icon">⚡</div>
        <h3>Easy Ordering</h3>
        <p>Simple online ordering from menu to checkout.</p>
    </div>

    <div class="about-feature">
        <div class="feature-icon">🚚</div>
        <h3>Quick Delivery</h3>
        <p>Order your favorite food and get it delivered conveniently.</p>
    </div>

</section>

<?php include 'includes/footer.php'; ?>

<script src="assets/js/script.js"></script>

</body>
</html>