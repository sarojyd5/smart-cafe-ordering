<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireAdmin();


// Get all food items
$sql = "
    SELECT
        f.food_id,
        f.category_id,
        f.food_name,
        f.description,
        f.price,
        f.image,
        f.preparation_time,
        f.availability,
        f.featured,
        f.created_at,
        c.category_name
    FROM foods f

    LEFT JOIN categories c
        ON f.category_id = c.category_id

    ORDER BY f.food_id DESC
";
$result = mysqli_query($conn, $sql);

$success_message = '';

if (isset($_GET['added'])) {

    $success_message =
        'Food item added successfully.';

}

if (isset($_GET['updated'])) {

    $success_message =
        'Food item updated successfully.';

}

if (isset($_GET['deleted'])) {

    $success_message =
        'Food item deleted successfully.';

}

if (isset($_GET['disabled'])) {

    $success_message =
        'Food item is already used in order history, so it was marked unavailable instead of being deleted.';

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
        Manage Food | Timeout Cafe
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

            <h1>Manage Food</h1>

            <span>
                Add and manage food items available for customers.
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



    <!-- FOOD MANAGEMENT -->

    <section class="admin-food-section">


        <div class="food-page-heading">

            <div>

                <p>FOOD MANAGEMENT</p>

                <h2>
                    Food Items
                </h2>

            </div>


            <a
                href="food_form.php"
                class="add-food-btn">

                + Add Food

            </a>

        </div>
        <?php if ($success_message !== ''): ?>

    <div class="form-success">

        <?php
        echo escape($success_message);
        ?>

    </div>

<?php endif; ?>


        <!-- FOOD TABLE -->

        <div class="admin-table-container">

            <table class="admin-table">


                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Image
                        </th>

                        <th>
                            Food
                        </th>

                        <th>
                            Category 
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Preparation
                        </th>

                        <th>
                            Availability
                        </th>

                        <th>
                            Featured
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php if (mysqli_num_rows($result) > 0): ?>


                    <?php while ($food = mysqli_fetch_assoc($result)): ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <?php
                                echo escape(
                                    $food['food_id']
                                );
                                ?>

                            </td>



                            <!-- IMAGE -->

                            <td>

                                <?php if (!empty($food['image'])): ?>

                                    <img
                                        src="../assets/images/foods/<?php
                                        echo escape(
                                            $food['image']
                                        );
                                        ?>"
                                        alt="<?php
                                        echo escape(
                                            $food['food_name']
                                        );
                                        ?>"
                                        class="admin-food-image">

                                <?php else: ?>

                                    <div class="no-food-image">
                                        No Image
                                    </div>

                                <?php endif; ?>

                            </td>



                            <!-- FOOD -->

                            <td>

                                <strong>

                                    <?php
                                    echo escape(
                                        $food['food_name']
                                    );
                                    ?>

                                </strong>


                                <?php if (!empty($food['description'])): ?>

                                    <small>

                                        <?php
                                        echo escape(
                                            $food['description']
                                        );
                                        ?>

                                    </small>

                                <?php endif; ?>

                            </td>
 <!-- CATEGORY -->

                            <td>

                                <?php

                                if (!empty($food['category_name'])) {

                                     echo escape(
                                        $food['category_name']
                                    );

                                } else {

                                    echo 'Unknown';

                                }

                                ?>

                            </td>


                            <!-- PRICE -->

                            <td>

                                <strong>

                                    Rs.
                                    <?php
                                    echo number_format(
                                        $food['price'],
                                        2
                                    );
                                    ?>

                                </strong>

                            </td>



                            <!-- PREPARATION TIME -->

                            <td>

                                <?php
                                echo escape(
                                    $food['preparation_time']
                                );
                                ?>

                                min

                            </td>



                            <!-- AVAILABILITY -->

                            <td>

                                <?php
                                if (
                                    $food['availability']
                                    === 'available'
                                ):
                                ?>

                                    <span
                                        class="status-available">

                                        Available

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status-unavailable">

                                        Unavailable

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- FEATURED -->

                            <td>

                                <?php
                                if (
                                    $food['featured'] == 1
                                ):
                                ?>

                                    <span
                                        class="featured-badge">

                                        Yes

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="not-featured-badge">

                                        No

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- ACTION -->

                            <td>

                                <div class="food-actions">


                                    <a
                                        href="food_form.php?id=<?php
                                        echo $food['food_id'];
                                        ?>"
                                        class="edit-food-btn">

                                        Edit

                                    </a>


                                    <a
                                        href="delete_food.php?id=<?php
                                        echo $food['food_id'];
                                        ?>"
                                        class="delete-food-btn"
                                        onclick="return confirm('Are you sure you want to delete this food item?');">

                                        Delete

                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="9"
                            class="empty-table">

                            No food items found.

                            <br><br>

                            Click
                            <strong>+ Add Food</strong>
                            to add your first food item.

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