<?php

require_once "includes/session.php";
require_once "includes/db.php";

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: menu.php");
    exit();
}

$action = $_POST['action'] ?? '';

/*
|--------------------------------------------------------------------------
| ADD TO CART
|--------------------------------------------------------------------------
*/

if ($action === 'add') {

    $food_id = isset($_POST['food_id'])
        ? (int) $_POST['food_id']
        : 0;

    if ($food_id <= 0) {
        header("Location: menu.php");
        exit();
    }

    $sql = "
        SELECT food_id
        FROM foods
        WHERE food_id = ?
        AND availability = 'available'
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Database query failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $food_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {

        if (isset($_SESSION['cart'][$food_id])) {
            $_SESSION['cart'][$food_id]++;
        } else {
            $_SESSION['cart'][$food_id] = 1;
        }
    }

    mysqli_stmt_close($stmt);

    header("Location: cart.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| UPDATE CART
|--------------------------------------------------------------------------
*/

if ($action === 'update') {

    $food_id = (int) ($_POST['food_id'] ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($food_id > 0 && isset($_SESSION['cart'][$food_id])) {

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$food_id]);
        } else {
            $_SESSION['cart'][$food_id] = $quantity;
        }
    }

    header("Location: cart.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| REMOVE FROM CART
|--------------------------------------------------------------------------
*/

if ($action === 'remove') {

    $food_id = (int) ($_POST['food_id'] ?? 0);

    if (isset($_SESSION['cart'][$food_id])) {
        unset($_SESSION['cart'][$food_id]);
    }

    header("Location: cart.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| CLEAR CART
|--------------------------------------------------------------------------
*/

if ($action === 'clear') {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit();
}


header("Location: menu.php");
exit();

?>