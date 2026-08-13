<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/config.php";

redirectIfLoggedIn();

$errors = [];
$success = "";

$otp = "";
$new_password = "";
$confirm_password = "";


/*
|--------------------------------------------------------------------------
| CHECK RESET SESSION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["reset_email"])) {

    die(
        "Password reset session has expired.<br><br>
        <a href='forgot_password.php'>Try again</a>"
    );

}

$email = $_SESSION["reset_email"];


/*
|--------------------------------------------------------------------------
| RESET PASSWORD
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp = trim($_POST["otp"] ?? "");

    $new_password =
        $_POST["new_password"] ?? "";

    $confirm_password =
        $_POST["confirm_password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATE OTP
    |--------------------------------------------------------------------------
    */

    if ($otp === "") {

        $errors[] =
            "Please enter the OTP.";

    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {

        $errors[] =
            "OTP must contain exactly 6 digits.";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE NEW PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($new_password === "") {

        $errors[] =
            "New password is required.";

    } elseif (strlen($new_password) < 6) {

        $errors[] =
            "Password must contain at least 6 characters.";

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRM PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($new_password !== $confirm_password) {

        $errors[] =
            "Passwords do not match.";

    }


    /*
    |--------------------------------------------------------------------------
    | FIND CUSTOMER
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $sql = "
            SELECT
                customer_id,
                full_name,
                email,
                reset_otp_code,
                reset_otp_expires
            FROM customers
            WHERE email = ?
            AND email_verified = 1
            LIMIT 1
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            $errors[] =
                "Database error. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            mysqli_stmt_execute($stmt);


            $result =
                mysqli_stmt_get_result($stmt);


            $customer =
                mysqli_fetch_assoc($result);


            mysqli_stmt_close($stmt);


            if (!$customer) {

                $errors[] =
                    "Customer account was not found.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY RESET OTP
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        isset($customer)
    ) {

        /*
         * Check OTP exists
         */

        if (
            empty($customer["reset_otp_code"]) ||
            empty($customer["reset_otp_expires"])
        ) {

            $errors[] =
                "OTP is invalid or has already been used.";

        }


        /*
         * Check OTP expiry
         */

        elseif (
            strtotime(
                $customer["reset_otp_expires"]
            ) < time()
        ) {

            $errors[] =
                "This OTP has expired. Please request a new OTP.";

        }


        /*
         * Verify OTP
         */

        elseif (
            !password_verify(
                $otp,
                $customer["reset_otp_code"]
            )
        ) {

            $errors[] =
                "Incorrect OTP. Please try again.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        isset($customer)
    ) {

        $hashed_password =
            password_hash(
                $new_password,
                PASSWORD_DEFAULT
            );


        $update_sql = "
            UPDATE customers
            SET
                password = ?,
                reset_otp_code = NULL,
                reset_otp_expires = NULL
            WHERE customer_id = ?
        ";


        $update_stmt =
            mysqli_prepare(
                $conn,
                $update_sql
            );


        if (!$update_stmt) {

            $errors[] =
                "Unable to update password.";

        } else {

            mysqli_stmt_bind_param(
                $update_stmt,
                "si",
                $hashed_password,
                $customer["customer_id"]
            );


            if (
                mysqli_stmt_execute(
                    $update_stmt
                )
            ) {

                mysqli_stmt_close(
                    $update_stmt
                );


                /*
                 * Remove reset session
                 */

                unset(
                    $_SESSION["reset_email"]
                );


                $success =
                    "Your password has been reset successfully.";

            } else {

                mysqli_stmt_close(
                    $update_stmt
                );

                $errors[] =
                    "Password reset failed. Please try again.";

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
        Reset Password | Timeout Cafe
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css">


    <link
        rel="stylesheet"
        href="assets/css/responsive.css">


    <style>

        /*
        |--------------------------------------------------------------------------
        | PASSWORD FIELD WITH EYE BUTTON
        |--------------------------------------------------------------------------
        */

        .password-wrapper {

            position: relative;

            width: 100%;

        }


        .password-wrapper input {

            width: 100%;

            padding-right: 48px;

            box-sizing: border-box;

        }


        .password-toggle {

            position: absolute;

            right: 10px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            cursor: pointer;

            padding: 5px;

            font-size: 18px;

            line-height: 1;

            color: #555;

        }


        .password-toggle:hover {

            color: #c45f38;

        }


        .password-toggle:focus {

            outline: none;

        }

    </style>

</head>


<body>


<?php include "includes/navbar.php"; ?>


<main class="auth-page">

    <div class="auth-container">

        <div class="auth-content">

            <p class="auth-label">
                TIMEOUT CAFE
            </p>


            <h1>
                Reset Password
            </h1>


            <p>
                Enter the OTP sent to:
            </p>


            <p>

                <strong>

                    <?php
                    echo escape($email);
                    ?>

                </strong>

            </p>


            <?php if (!empty($errors)): ?>

                <div class="alert error">

                    <?php foreach ($errors as $error): ?>

                        <p>

                            <?php
                            echo escape($error);
                            ?>

                        </p>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>


            <?php if ($success !== ""): ?>

                <div class="alert success">

                    <p>

                        <?php
                        echo escape($success);
                        ?>

                    </p>

                </div>


                <p>

                    <a
                        href="login.php"
                        class="auth-submit">

                        Login Now

                    </a>

                </p>


            <?php else: ?>


                <form
                    method="POST"
                    action=""
                    class="auth-form">


                    <!-- OTP -->

                    <div class="form-group">

                        <label for="otp">
                            OTP*
                        </label>


                        <input
                            type="text"
                            id="otp"
                            name="otp"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            placeholder="Enter 6-digit OTP"
                            autocomplete="one-time-code"
                            required>

                    </div>


                    <!-- NEW PASSWORD -->

                    <div class="form-group">

                        <label for="new_password">
                            New Password*
                        </label>


                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="new_password"
                                name="new_password"
                                placeholder="At least 6 characters"
                                required>


                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('new_password', this)"
                                aria-label="Show password">

                                👁

                            </button>

                        </div>

                    </div>


                    <!-- CONFIRM NEW PASSWORD -->

                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm New Password*
                        </label>


                        <div class="password-wrapper">

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Re-enter your new password"
                                required>


                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword('confirm_password', this)"
                                aria-label="Show password">

                                👁

                            </button>

                        </div>

                    </div>


                    <!-- RESET BUTTON -->

                    <button
                        type="submit"
                        class="auth-submit">

                        Reset Password

                    </button>


                </form>


                <p class="auth-switch">

                    OTP is valid for
                    <strong>5 minutes</strong>.

                </p>


                <p class="auth-switch">

                    Didn't receive the OTP?

                    <a href="forgot_password.php">
                        Request Again
                    </a>

                </p>


            <?php endif; ?>


        </div>

    </div>

</main>


<?php include "includes/footer.php"; ?>


<script>

/*
|--------------------------------------------------------------------------
| SHOW / HIDE PASSWORD
|--------------------------------------------------------------------------
*/

function togglePassword(inputId, button) {

    const input =
        document.getElementById(inputId);


    if (input.type === "password") {

        input.type = "text";

        button.textContent = "🙈";

        button.setAttribute(
            "aria-label",
            "Hide password"
        );

    } else {

        input.type = "password";

        button.textContent = "👁";

        button.setAttribute(
            "aria-label",
            "Show password"
        );

    }

}

</script>


</body>

</html>