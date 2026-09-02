<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart_count = 0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $quantity) {
        $cart_count += (int)$quantity;
    }
}

?>

<header>

    <nav class="navbar">

        <div class="logo">

            <a href="index.php">
                Timeout Cafe
            </a>

        </div>


        <ul class="nav-menu">

            <li>
                <a href="index.php">Home</a>
            </li>

            <li>
                <a href="menu.php">Menu</a>
            </li>

            <li>
                <a href="about.php">About</a>
            </li>

            <li>
                <a href="contact.php">Contact</a>
            </li>

        </ul>


        <div class="nav-right">

            <?php if (isset($_SESSION['customer_id'])): ?>

                <a href="customer/dashboard.php">
                    Hi, <?php echo htmlspecialchars($_SESSION['customer_name']); ?>
                </a>

                <a href="logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php">
                    Login
                </a>

                <a href="register.php" class="register-btn">
                    Register
                </a>

            <?php endif; ?>


            <a href="cart.php" class="cart-link">

                🛒

                <span class="cart-count">
                    <?php echo $cart_count; ?>
                </span>

            </a>

        </div>

        

    </nav>

</header>