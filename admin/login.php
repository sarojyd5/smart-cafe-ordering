<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

/*
|--------------------------------------------------------------------------
| Already logged in
|--------------------------------------------------------------------------
| If admin is already logged in and opens login.php,
| send them back to the dashboard.
*/

if (isAdminLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = "";


/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';


    if ($username === '' || $password === '') {

        $error = "Please enter username and password.";

    } else {

        $sql = "
            SELECT
                admin_id,
                username,
                password
            FROM admin_users
            WHERE username = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            $error = "Something went wrong. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $admin = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if (
                $admin &&
                password_verify(
                    $password,
                    $admin['password']
                )
            ) {

                session_regenerate_id(true);

                $_SESSION['admin_id'] =
                    $admin['admin_id'];

                $_SESSION['admin_username'] =
                    $admin['username'];

                header("Location: index.php");
                exit();

            } else {

                $error = "Invalid username or password.";

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

    <title>Admin Login | Timeout Cafe</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css">

    <link
        rel="stylesheet"
        href="../assets/css/responsive.css">

</head>

<body>

<main class="admin-login-page">

    <div class="admin-login-card">

        <div class="admin-login-logo">

            <span>TIMEOUT</span>

            <h1>CAFE</h1>

        </div>


        <div class="admin-login-heading">

            <p>ADMIN PANEL</p>

            <h2>Welcome Back</h2>

            <span>
                Login to manage your cafe.
            </span>

        </div>


        <?php if ($error !== ''): ?>

            <div class="login-error">

                <?php
                echo escape($error);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="login.php"
            class="admin-login-form">


            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter username"
                    autocomplete="username"
                    required>

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter password"
                    autocomplete="current-password"
                    required>

            </div>


            <button
                type="submit"
                class="admin-login-btn">

                Login to Admin Panel

            </button>

        </form>


        <a
            href="../index.php"
            class="back-to-site">

            ← Back to Website

        </a>

    </div>

</main>

</body>

</html>