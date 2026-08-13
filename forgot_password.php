<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/config.php";

require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$errors = [];

$email = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");


    /*
    |--------------------------------------------------------------------------
    | VALIDATE EMAIL
    |--------------------------------------------------------------------------
    */

    if ($email === "") {

        $errors[] =
            "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] =
            "Please enter a valid email address.";

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
                email
            FROM customers
            WHERE email = ?
            AND email_verified = 1
            LIMIT 1
        ";


        $stmt = mysqli_prepare(
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
                    "No verified account was found with this email.";

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE RESET OTP
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $otp = (string) random_int(
            100000,
            999999
        );


        /*
         * Hash OTP before storing
         */

        $otp_hash =
            password_hash(
                $otp,
                PASSWORD_DEFAULT
            );


        /*
         * OTP expires after 5 minutes
         */

        $otp_expires =
            date(
                "Y-m-d H:i:s",
                time() + (5 * 60)
            );


        /*
        |--------------------------------------------------------------------------
        | SAVE RESET OTP
        |--------------------------------------------------------------------------
        */

        $update_sql = "
            UPDATE customers
            SET
                reset_otp_code = ?,
                reset_otp_expires = ?
            WHERE customer_id = ?
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $update_sql
            );


        if (!$stmt) {

            $errors[] =
                "Database error. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ssi",
                $otp_hash,
                $otp_expires,
                $customer["customer_id"]
            );


            if (!mysqli_stmt_execute($stmt)) {

                $errors[] =
                    "Unable to create password reset request.";

            }


            mysqli_stmt_close($stmt);

        }

    }


    /*
    |--------------------------------------------------------------------------
    | SEND RESET OTP USING PHPMailer
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $mail =
            new PHPMailer(true);


        try {

            /*
             * SMTP configuration
             */

            $mail->isSMTP();

            $mail->Host =
                SMTP_HOST;

            $mail->SMTPAuth =
                true;

            $mail->Username =
                SMTP_USERNAME;

            $mail->Password =
                SMTP_PASSWORD;

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->Port =
                SMTP_PORT;


            /*
             * Sender
             */

            $mail->setFrom(
                MAIL_FROM_EMAIL,
                MAIL_FROM_NAME
            );


            /*
             * Customer email
             */

            $mail->addAddress(
                $customer["email"],
                $customer["full_name"]
            );


            /*
             * Plain text email
             */

            $mail->isHTML(false);


            /*
             * Subject
             */

            $mail->Subject =
                "Timeout Cafe - Password Reset OTP";


            /*
             * Email body
             */

            $mail->Body =
                "Hello "
                . $customer["full_name"]
                . ",\r\n\r\n"

                . "We received a request to reset your Timeout Cafe password.\r\n\r\n"

                . "Your password reset OTP is: "
                . $otp
                . "\r\n\r\n"

                . "This OTP is valid for 5 minutes.\r\n\r\n"

                . "Please do not share this OTP with anyone.\r\n\r\n"

                . "If you did not request a password reset, "
                . "please ignore this email.\r\n\r\n"

                . "Regards,\r\n"
                . "Timeout Cafe";


            /*
             * Send email
             */

            $mail->send();


            /*
            |--------------------------------------------------------------------------
            | STORE RESET EMAIL IN SESSION
            |--------------------------------------------------------------------------
            */

            $_SESSION["reset_email"] =
                $customer["email"];


            /*
            |--------------------------------------------------------------------------
            | GO TO RESET PASSWORD PAGE
            |--------------------------------------------------------------------------
            */

            header(
                "Location: reset_password.php"
            );

            exit();


        } catch (Exception $e) {

            error_log(
                "Password Reset PHPMailer Error: "
                . $mail->ErrorInfo
            );


            /*
             * Remove reset OTP if email failed
             */

            $clear_sql = "
                UPDATE customers
                SET
                    reset_otp_code = NULL,
                    reset_otp_expires = NULL
                WHERE customer_id = ?
            ";


            $clear_stmt =
                mysqli_prepare(
                    $conn,
                    $clear_sql
                );


            if ($clear_stmt) {

                mysqli_stmt_bind_param(
                    $clear_stmt,
                    "i",
                    $customer["customer_id"]
                );

                mysqli_stmt_execute(
                    $clear_stmt
                );

                mysqli_stmt_close(
                    $clear_stmt
                );

            }


            $errors[] =
                "Unable to send reset OTP email. Please try again.";

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
        Forgot Password | Timeout Cafe
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
                Forgot Password
            </h1>


            <p>
                Enter your registered email address
                and we will send you a 6-digit OTP.
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


            <form
                method="POST"
                action=""
                class="auth-form">


                <div class="form-group">

                    <label for="email">
                        Email Address*
                    </label>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php
                        echo escape($email);
                        ?>"
                        placeholder="Enter your registered email"
                        required>

                </div>


                <button
                    type="submit"
                    class="auth-submit">

                    Send OTP

                </button>


            </form>


            <p class="auth-switch">

                Remember your password?

                <a href="login.php">
                    Login
                </a>

            </p>


        </div>

    </div>

</main>


<?php include "includes/footer.php"; ?>


</body>

</html>