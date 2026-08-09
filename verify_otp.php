<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";

redirectIfLoggedIn();

$errors = [];
$success = "";

$otp = "";


/*
|--------------------------------------------------------------------------
| CHECK REGISTRATION SESSION
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['otp_customer_id']) ||
    empty($_SESSION['otp_email'])
) {

    die(
        "OTP verification session has expired.<br><br>
        <a href='register.php'>Register again</a>"
    );

}


$customer_id =
    (int) $_SESSION['otp_customer_id'];

$email =
    $_SESSION['otp_email'];


/*
|--------------------------------------------------------------------------
| VERIFY OTP
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $otp =
        trim($_POST['otp'] ?? "");


    /*
    |----------------------------------------------------------------------
    | Validate OTP format
    |----------------------------------------------------------------------
    */

    if ($otp === "") {

        $errors[] =
            "Please enter the OTP.";

    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {

        $errors[] =
            "OTP must contain exactly 6 digits.";

    }


    /*
    |----------------------------------------------------------------------
    | Get customer OTP
    |----------------------------------------------------------------------
    */

    if (empty($errors)) {

        $sql = "
            SELECT
                customer_id,
                full_name,
                email,
                otp_code,
                otp_expires
            FROM customers
            WHERE customer_id = ?
            AND email = ?
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
                "is",
                $customer_id,
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
    |----------------------------------------------------------------------
    | Check OTP
    |----------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        isset($customer)
    ) {


        /*
         * Check whether OTP exists
         */

        if (
            empty($customer['otp_code']) ||
            empty($customer['otp_expires'])
        ) {

            $errors[] =
                "OTP is invalid or has already been used.";

        }


        /*
         * Check expiry
         */

        elseif (
            strtotime(
                $customer['otp_expires']
            ) < time()
        ) {

            $errors[] =
                "This OTP has expired. Please register again.";

        }


        /*
         * Verify OTP
         */

        elseif (
            !password_verify(
                $otp,
                $customer['otp_code']
            )
        ) {

            $errors[] =
                "Incorrect OTP. Please try again.";

        }

    }


    /*
    |--------------------------------------------------------------------------
    | ACTIVATE ACCOUNT
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        isset($customer)
    ) {

        $update_sql = "
            UPDATE customers
            SET
                email_verified = 1,
                status = 'active',
                otp_code = NULL,
                otp_expires = NULL
            WHERE customer_id = ?
        ";


        $update_stmt =
            mysqli_prepare(
                $conn,
                $update_sql
            );


        if (!$update_stmt) {

            $errors[] =
                "Unable to verify account.";

        } else {

            mysqli_stmt_bind_param(
                $update_stmt,
                "i",
                $customer_id
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
                 * Remove OTP registration session
                 */

                unset(
                    $_SESSION['otp_customer_id']
                );

                unset(
                    $_SESSION['otp_email']
                );


                $success =
                    "Email verified successfully. Your account is now active.";

            } else {

                mysqli_stmt_close(
                    $update_stmt
                );

                $errors[] =
                    "Account verification failed.";

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
        Verify OTP | Timeout Cafe
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css">


    <link
        rel="stylesheet"
        href="assets/css/responsive.css">

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
                Verify Your Email
            </h1>


            <p>
                We sent a 6-digit OTP to:
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


                    <div class="form-group">

                        <label for="otp">

                            Enter OTP

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


                    <button
                        type="submit"
                        class="auth-submit">

                        Verify OTP

                    </button>


                </form>


                <p class="auth-switch">

                    OTP is valid for
                    <strong>5 minutes</strong>.

                </p>


                <p class="auth-switch">

                    Didn't receive the OTP?

                    <a href="register.php">
                        Register Again
                    </a>

                </p>


            <?php endif; ?>


        </div>

    </div>

</main>


<?php include "includes/footer.php"; ?>


</body>

</html>