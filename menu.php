<?php

require_once "includes/db.php";

$category_query = "
    SELECT *
    FROM categories
    WHERE status = 'active'
    ORDER BY category_name ASC
";

$category_result = mysqli_query($conn, $category_query);

$food_query = "
    SELECT
        foods.*,
        categories.category_name
    FROM foods
    INNER JOIN categories
        ON foods.category_id = categories.category_id
    WHERE foods.availability = 'available'
    AND categories.status = 'active'
    ORDER BY foods.created_at DESC
";

$food_result = mysqli_query($conn, $food_query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Menu | Timeout Cafe</title>

    <link rel="stylesheet"
          href="assets/css/style.css">

    <link rel="stylesheet"
          href="assets/css/responsive.css">

</head>

<body>

<?php include "includes/navbar.php"; ?>

<main class="menu-page">

    <section class="menu-header">

        <p>OUR MENU</p>

        <h1>Delicious Food For You</h1>

        <span>
            Freshly prepared and delivered to your doorstep.
        </span>

    </section>


    <!-- CATEGORIES -->

    <section class="categories">

        <h2>Categories</h2>

        <div class="category-list">

            <button
                class="category-btn active"
                data-category="all">

                All

            </button>

            <?php while ($category = mysqli_fetch_assoc($category_result)): ?>

                <button
                    class="category-btn"
                    data-category="<?php echo $category['category_id']; ?>">

                    <?php echo htmlspecialchars($category['category_name']); ?>

                </button>

            <?php endwhile; ?>

        </div>

    </section>


    <!-- FOOD ITEMS -->

    <section class="food-section">

        <div class="food-grid">

            <?php if (mysqli_num_rows($food_result) > 0): ?>

                <?php while ($food = mysqli_fetch_assoc($food_result)): ?>

                    <article
                        class="food-card"
                        data-category="<?php echo $food['category_id']; ?>">

                        <div class="food-image">

                            <img
                                src="assets/images/foods/<?php echo htmlspecialchars($food['image']); ?>"
                                alt="<?php echo htmlspecialchars($food['food_name']); ?>"
                                onerror="this.src='assets/images/foods/default.jpg';">

                        </div>


                        <div class="food-info">

                            <small>
                                <?php echo htmlspecialchars($food['category_name']); ?>
                            </small>

                            <h3>
                                <?php echo htmlspecialchars($food['food_name']); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($food['description']); ?>
                            </p>

                            <div class="food-bottom">

                                <strong>
                                    Rs. <?php echo number_format($food['price'], 2); ?>
                                </strong>

                                <form method="POST" action="cart_actions.php">

                                    <input
                                       type="hidden"
                                       name="action"
                                       value="add">

                                    <input
                                       type="hidden"
                                       name="food_id"
                                       value="<?php echo $food['food_id']; ?>">

                                    <button
                                       type="submit"
                                       class="add-cart-btn">

                                       Add to Cart

                                    </button>

                                </form>
                            </div>

                        </div>

                    </article>

                <?php endwhile; ?>

            <?php else: ?>

                <p>No food items available.</p>

            <?php endif; ?>

        </div>

    </section>

</main>


<?php include "includes/footer.php"; ?>


<script src="assets/js/script.js"></script>

</body>

</html>