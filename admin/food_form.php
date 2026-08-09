<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireAdmin();


// --------------------------------------------------
// CHECK EDIT MODE
// --------------------------------------------------

$edit_mode = false;
$food = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    $food_id = (int) $_GET['id'];

    $sql = "
        SELECT *
        FROM foods
        WHERE food_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $food_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $food = mysqli_fetch_assoc($result);

    if ($food) {
        $edit_mode = true;
    }
}


// --------------------------------------------------
// DEFAULT VALUES
// --------------------------------------------------

$food_name = $food['food_name'] ?? '';
$category_id = $food['category_id'] ?? '';
$description = $food['description'] ?? '';
$price = $food['price'] ?? '';
$preparation_time = $food['preparation_time'] ?? 20;
$availability = $food['availability'] ?? 'available';
$featured = $food['featured'] ?? 0;
$current_image = $food['image'] ?? '';

$error = '';


// --------------------------------------------------
// FORM SUBMISSION
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $food_name = trim($_POST['food_name'] ?? '');
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $preparation_time = (int) ($_POST['preparation_time'] ?? 20);
    $availability = $_POST['availability'] ?? 'available';
    $featured = isset($_POST['featured']) ? 1 : 0;


    // --------------------------------------------------
    // VALIDATION
    // --------------------------------------------------

    if ($food_name === '') {

        $error = "Food name is required.";

    } elseif ($category_id <= 0) {

        $error = "Category ID is required.";

    } elseif ($price === '' || !is_numeric($price)) {

        $error = "Please enter a valid price.";

    } elseif ((float) $price < 0) {

        $error = "Price cannot be negative.";

    } elseif ($preparation_time < 0) {

        $error = "Preparation time cannot be negative.";

    } elseif (
        !in_array(
            $availability,
            ['available', 'unavailable'],
            true
        )
    ) {

        $error = "Invalid availability status.";

    }


    // --------------------------------------------------
    // IMAGE UPLOAD
    // --------------------------------------------------

    $image_name = $current_image;

    if (
        $error === '' &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['image']['error'] !== UPLOAD_ERR_OK
        ) {

            $error = "There was a problem uploading the image.";

        } else {

            $allowed_types = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $file_type = $_FILES['image']['type'];

            $file_size = $_FILES['image']['size'];

            if (!in_array($file_type, $allowed_types, true)) {

                $error = "Only JPG, PNG and WEBP images are allowed.";

            } elseif ($file_size > 5 * 1024 * 1024) {

                $error = "Image size must be less than 5MB.";

            } else {

                $extension = strtolower(
                    pathinfo(
                        $_FILES['image']['name'],
                        PATHINFO_EXTENSION
                    )
                );

                $image_name =
                    time()
                    . '_'
                    . uniqid()
                    . '.'
                    . $extension;


                $upload_directory =
                    "../assets/images/foods/";


                if (!is_dir($upload_directory)) {

                    mkdir(
                        $upload_directory,
                        0755,
                        true
                    );
                }


                $upload_path =
                    $upload_directory
                    . $image_name;


                if (
                    !move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $upload_path
                    )
                ) {

                    $error = "Failed to save the uploaded image.";

                } else {

                    // Delete old image when editing
                    if (
                        $edit_mode &&
                        !empty($current_image)
                    ) {

                        $old_image =
                            $upload_directory
                            . $current_image;

                        if (
                            file_exists($old_image)
                        ) {

                            unlink($old_image);
                        }
                    }
                }
            }
        }
    }


    // --------------------------------------------------
    // INSERT / UPDATE
    // --------------------------------------------------

    if ($error === '') {

        if ($edit_mode) {

            $sql = "
                UPDATE foods
                SET
                    category_id = ?,
                    food_name = ?,
                    description = ?,
                    price = ?,
                    image = ?,
                    preparation_time = ?,
                    availability = ?,
                    featured = ?
                WHERE food_id = ?
            ";

            $stmt = mysqli_prepare(
                $conn,
                $sql
            );


            mysqli_stmt_bind_param(
                $stmt,
                "issdsisii",
                $category_id,
                $food_name,
                $description,
                $price,
                $image_name,
                $preparation_time,
                $availability,
                $featured,
                $food_id
            );


            if (mysqli_stmt_execute($stmt)) {

                header(
                    "Location: foods.php?updated=1"
                );

                exit();

            } else {

                $error =
                    "Failed to update food item.";
            }


        } else {

            $sql = "
                INSERT INTO foods (
                    category_id,
                    food_name,
                    description,
                    price,
                    image,
                    preparation_time,
                    availability,
                    featured
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare(
                $conn,
                $sql
            );


            mysqli_stmt_bind_param(
                $stmt,
                "issdsisi",
                $category_id,
                $food_name,
                $description,
                $price,
                $image_name,
                $preparation_time,
                $availability,
                $featured
            );


            if (mysqli_stmt_execute($stmt)) {

                header(
                    "Location: foods.php?added=1"
                );

                exit();

            } else {

                $error =
                    "Failed to add food item.";
            }
        }
    }
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

        <?php
        echo $edit_mode
            ? 'Edit Food'
            : 'Add Food';
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

                <?php
                echo $edit_mode
                    ? 'Edit Food'
                    : 'Add Food';
                ?>

            </h1>

            <span>

                <?php
                echo $edit_mode
                    ? 'Update food item information.'
                    : 'Add a new food item to your menu.';
                ?>

            </span>

        </div>


        <div class="admin-header-actions">

            <a
                href="foods.php"
                class="visit-site-btn">

                Back to Food

            </a>


            <a
                href="logout.php"
                class="admin-logout-btn">

                Logout

            </a>

        </div>

    </section>



    <!-- FORM -->

    <section class="food-form-section">


        <div class="food-form-card">


            <?php if ($error !== ''): ?>

                <div class="form-error">

                    <?php
                    echo escape($error);
                    ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data">


                <!-- FOOD NAME -->

                <div class="form-group">

                    <label for="food_name">
                        Food Name
                    </label>

                    <input
                        type="text"
                        id="food_name"
                        name="food_name"
                        value="<?php
                        echo escape($food_name);
                        ?>"
                        placeholder="Example: Chicken Pizza"
                        required>

                </div>



                <!-- CATEGORY -->

                <?php

$category_sql = "
    SELECT
        category_id,
        category_name
    FROM categories
    WHERE status = 'active'
    ORDER BY category_name ASC
";

$category_result = mysqli_query(
    $conn,
    $category_sql
);

?>

<div class="form-group">

    <label for="category_id">
        Category
    </label>

    <select
        id="category_id"
        name="category_id"
        required>

        <option value="">
            Select Category
        </option>

        <?php while (
            $category = mysqli_fetch_assoc(
                $category_result
            )
        ): ?>

            <option
                value="<?php
                echo $category['category_id'];
                ?>"
                <?php
                echo (
                    (int)$category_id ===
                    (int)$category['category_id']
                )
                    ? 'selected'
                    : '';
                ?>>

                <?php
                echo escape(
                    $category['category_name']
                );
                ?>

            </option>

        <?php endwhile; ?>

    </select>

</div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="Describe the food item..."><?php
                        echo escape($description);
                        ?></textarea>

                </div>



                <!-- PRICE -->

                <div class="form-group">

                    <label for="price">
                        Price (Rs.)
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        value="<?php
                        echo escape($price);
                        ?>"
                        min="0"
                        step="0.01"
                        placeholder="450.00"
                        required>

                </div>



                <!-- PREPARATION TIME -->

                <div class="form-group">

                    <label for="preparation_time">
                        Preparation Time (minutes)
                    </label>

                    <input
                        type="number"
                        id="preparation_time"
                        name="preparation_time"
                        value="<?php
                        echo escape(
                            $preparation_time
                        );
                        ?>"
                        min="0"
                        required>

                </div>



                <!-- AVAILABILITY -->

                <div class="form-group">

                    <label for="availability">
                        Availability
                    </label>

                    <select
                        id="availability"
                        name="availability">

                        <option
                            value="available"
                            <?php
                            echo $availability === 'available'
                                ? 'selected'
                                : '';
                            ?>>

                            Available

                        </option>

                        <option
                            value="unavailable"
                            <?php
                            echo $availability === 'unavailable'
                                ? 'selected'
                                : '';
                            ?>>

                            Unavailable

                        </option>

                    </select>

                </div>



                <!-- FEATURED -->

                <div class="form-checkbox">

                    <input
                        type="checkbox"
                        id="featured"
                        name="featured"
                        value="1"
                        <?php
                        echo $featured
                            ? 'checked'
                            : '';
                        ?>>

                    <label for="featured">

                        Show this food as Featured

                    </label>

                </div>



                <!-- IMAGE -->

                <div class="form-group">

                    <label for="image">
                        Food Image
                    </label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp">

                    <small>
                        JPG, PNG or WEBP. Maximum size: 5MB.
                    </small>

                </div>



                <!-- CURRENT IMAGE -->

                <?php if (
                    $edit_mode &&
                    !empty($current_image)
                ): ?>

                    <div class="current-food-image">

                        <p>
                            Current Image
                        </p>

                        <img
                            src="../assets/images/foods/<?php
                            echo escape(
                                $current_image
                            );
                            ?>"
                            alt="Current food image">

                    </div>

                <?php endif; ?>



                <!-- BUTTONS -->

                <div class="food-form-actions">

                    <a
                        href="foods.php"
                        class="cancel-food-btn">

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="save-food-btn">

                        <?php
                        echo $edit_mode
                            ? 'Update Food'
                            : 'Save Food';
                        ?>

                    </button>

                </div>


            </form>

        </div>

    </section>


</main>


</body>

</html>