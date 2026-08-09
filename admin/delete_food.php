<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireAdmin();


// --------------------------------------------------
// CHECK FOOD ID
// --------------------------------------------------

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {

    header("Location: foods.php");
    exit();

}

$food_id = (int) $_GET['id'];


// --------------------------------------------------
// GET FOOD INFORMATION
// --------------------------------------------------

$sql = "
    SELECT
        food_id,
        food_name,
        image
    FROM foods
    WHERE food_id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $food_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$food = mysqli_fetch_assoc($result);


// Food doesn't exist
if (!$food) {

    header("Location: foods.php");
    exit();

}


// --------------------------------------------------
// DELETE FOOD
// --------------------------------------------------

try {

    $delete_sql = "
        DELETE FROM foods
        WHERE food_id = ?
    ";

    $delete_stmt = mysqli_prepare(
        $conn,
        $delete_sql
    );

    mysqli_stmt_bind_param(
        $delete_stmt,
        "i",
        $food_id
    );

    mysqli_stmt_execute(
        $delete_stmt
    );


    // --------------------------------------------------
    // DELETE IMAGE
    // --------------------------------------------------

    if (!empty($food['image'])) {

        $image_path =
            "../assets/images/foods/"
            . $food['image'];

        if (file_exists($image_path)) {

            unlink($image_path);

        }

    }


    header(
        "Location: foods.php?deleted=1"
    );

    exit();


} catch (mysqli_sql_exception $e) {

    /*
     * If this food is already referenced by
     * another table, don't destroy the order history.
     *
     * Instead, make the food unavailable.
     */

    try {

        $update_sql = "
            UPDATE foods
            SET availability = 'unavailable'
            WHERE food_id = ?
        ";

        $update_stmt = mysqli_prepare(
            $conn,
            $update_sql
        );

        mysqli_stmt_bind_param(
            $update_stmt,
            "i",
            $food_id
        );

        mysqli_stmt_execute(
            $update_stmt
        );


        header(
            "Location: foods.php?disabled=1"
        );

        exit();

    } catch (mysqli_sql_exception $update_error) {

        die(
            "Unable to delete or disable this food item."
        );

    }

}

?>