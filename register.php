<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";
require_once "includes/config.php";

require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

redirectIfLoggedIn();

$errors = [];
$success = "";

$full_name = "";
$email = "";
$phone = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATE FULL NAME
    |--------------------------------------------------------------------------
    */

    if ($full_name === "") {

        $errors[] = "Full name is required.";

   } elseif (strlen($full_name) < 3) {

    $errors[] =
        "Full name must contain at least 3 characters.";

} elseif (!preg_match('/^[A-Za-z ]+$/', $full_name)) {

    $errors[] =
        "Full name can contain only letters and spaces.";

}

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
    | VALIDATE PHONE
    |--------------------------------------------------------------------------
    */

   if ($phone === "") {

    $errors[] = "Phone number is required.";

} elseif (!preg_match('/^(98|97)[0-9]{8}$/', $phone)) {

    $errors[] =
        "Phone number must be exactly 10 digits and start with 97 or 98.";

}


    /*
    |--------------------------------------------------------------------------
    | VALIDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($password === "") {

        $errors[] =
            "Password is required.";

    } elseif (strlen($password) < 6) {

        $errors[] =
            "Password must contain at least 6 characters.";

    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRM PASSWORD
    |--------------------------------------------------------------------------
    */

    if ($password !== $confirm_password) {

        $errors[] =
            "Passwords do not match.";

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EXISTING EMAIL
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $check_sql = "
            SELECT
                customer_id,
                email_verified
            FROM customers
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $check_sql
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

            $existing_customer =
                mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if ($existing_customer) {

                if (
                    (int)$existing_customer['email_verified'] === 1
                ) {

                    $errors[] =
                        "An account with this email already exists.";

                } else {

                    $errors[] =
                        "This email is already registered but not verified. Please complete OTP verification.";

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CREATE OTP
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        /*
         * Generate 6-digit OTP
         */

        $otp = (string) random_int(
            100000,
            999999
        );


        /*
         * Hash OTP before storing it
         */

        $otp_hash = password_hash(
            $otp,
            PASSWORD_DEFAULT
        );


        /*
         * OTP expires after 5 minutes
         */

        $otp_expires = date(
            "Y-m-d H:i:s",
            time() + (5 * 60)
        );


        /*
         * Hash customer password
         */

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        /*
         |--------------------------------------------------------------------------
         | INSERT CUSTOMER
         |--------------------------------------------------------------------------
         */

        $insert_sql = "
            INSERT INTO customers
            (
                full_name,
                email,
                phone,
                password,
                otp_code,
                otp_expires,
                status,
                email_verified
            )
            VALUES
            (?, ?, ?, ?, ?, ?, 'inactive', 0)
        ";


        $stmt = mysqli_prepare(
            $conn,
            $insert_sql
        );


        if (!$stmt) {

            $errors[] =
                "Database error. Please try again.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ssssss",
                $full_name,
                $email,
                $phone,
                $hashed_password,
                $otp_hash,
                $otp_expires
            );


            if (mysqli_stmt_execute($stmt)) {

                $customer_id =
                    mysqli_insert_id($conn);


                mysqli_stmt_close($stmt);


                /*
                 |--------------------------------------------------------------------------
                 | SEND OTP USING PHPMailer
                 |--------------------------------------------------------------------------
                 */
/*
|--------------------------------------------------------------------------
| SEND OTP USING PHPMailer
|--------------------------------------------------------------------------
*/

$mail = new PHPMailer(true);

try {

    /*
     * SMTP configuration
     */

    $mail->isSMTP();

    $mail->Host = SMTP_HOST;

    $mail->SMTPAuth = true;

    $mail->Username = SMTP_USERNAME;

    $mail->Password = SMTP_PASSWORD;

    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = SMTP_PORT;


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
        $email,
        $full_name
    );


    /*
     * Email format
     */

    $mail->isHTML(false);


    /*
     * Subject
     */

    $mail->Subject =
        "Timeout Cafe - Your Registration OTP";


    /*
     * Message
     */

    $mail->Body =
        "Hello " . $full_name . ",\r\n\r\n"
        . "Thank you for registering at Timeout Cafe.\r\n\r\n"
        . "Your verification OTP is: "
        . $otp
        . "\r\n\r\n"
        . "This OTP is valid for 5 minutes.\r\n\r\n"
        . "Please do not share this OTP with anyone.\r\n\r\n"
        . "If you did not request this registration, "
        . "please ignore this email.\r\n\r\n"
        . "Regards,\r\n"
        . "Timeout Cafe";


    /*
     * Send email
     */

    $mail->send();


    /*
     |--------------------------------------------------------------------------
     | STORE OTP REGISTRATION SESSION
     |--------------------------------------------------------------------------
     */

    $_SESSION['otp_customer_id'] =
        $customer_id;

    $_SESSION['otp_email'] =
        $email;


    /*
     |--------------------------------------------------------------------------
     | REDIRECT TO OTP PAGE
     |--------------------------------------------------------------------------
     */

    header(
        "Location: verify_otp.php"
    );

    exit();


} catch (Exception $e) {

    /*
     |--------------------------------------------------------------------------
     | EMAIL FAILED
     |--------------------------------------------------------------------------
     */

    error_log(
        "PHPMailer Error: "
        . $mail->ErrorInfo
    );


    /*
     * Delete unverified customer
     */

    $delete_sql = "
        DELETE FROM customers
        WHERE customer_id = ?
    ";

    $delete_stmt =
        mysqli_prepare(
            $conn,
            $delete_sql
        );


    if ($delete_stmt) {

        mysqli_stmt_bind_param(
            $delete_stmt,
            "i",
            $customer_id
        );

        mysqli_stmt_execute(
            $delete_stmt
        );

        mysqli_stmt_close(
            $delete_stmt
        );

    }


    $errors[] =
        "Unable to send OTP email. Please check your email configuration and try again.";

}

            } else {

                mysqli_stmt_close($stmt);

                $errors[] =
                    "Registration failed. Please try again.";

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
        Create Account | Timeout Cafe
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
                JOIN TIMEOUT CAFE
            </p>


            <h1>
                Create Your Account
            </h1>


            <p>
                Register now and verify your
                email with an OTP.
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

                    <label for="full_name">
                        Full Name*
                    </label>


<input
    type="text"
    id="full_name"
    name="full_name"
    value="<?php
    echo escape($full_name);
    ?>"
    placeholder="Enter your full name"
    required>

<small id="name-error" class="field-error"></small>
                </div>


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
                        placeholder="Enter your email"
                        required>

                </div>


                <div class="form-group">

                    <label for="phone">
                        Phone Number*
                    </label>

<input
    type="tel"
    id="phone"
    name="phone"
    value="<?php
    echo escape($phone);
    ?>"
    placeholder="98XXXXXXXX"
    maxlength="10"
    inputmode="numeric"
    required>

<small id="phone-error" class="field-error"></small>
                </div>

<div class="form-group">

    <label for="password">
        Password*
    </label>

    <div class="password-wrapper">

        <input
            type="password"
            id="password"
            name="password"
            placeholder="At least 6 characters"
            required>

        <button
            type="button"
            class="password-toggle"
            onclick="togglePassword('password', 'password-eye')">

            <span id="password-eye">👁</span>

        </button>

    </div>

</div>

               <div class="form-group">

    <label for="confirm_password">
        Confirm Password*
    </label>

    <div class="password-wrapper">

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            placeholder="Re-enter your password"
            required>

        <button
            type="button"
            class="password-toggle"
            onclick="togglePassword('confirm_password', 'confirm-password-eye')">

            <span id="confirm-password-eye">👁</span>

        </button>

    </div>

</div>


                <button
                    type="submit"
                    class="auth-submit">

                    Create Account

                </button>


            </form>


            <p class="auth-switch">

                Already have an account?

                <a href="login.php">
                    Login
                </a>

            </p>


        </div>

    </div>

</main>


<?php include "includes/footer.php"; ?>

<script>

const nameInput = document.getElementById("full_name");
const nameError = document.getElementById("name-error");

const phoneInput = document.getElementById("phone");
const phoneError = document.getElementById("phone-error");


/*
|--------------------------------------------------------------------------
| FULL NAME LIVE VALIDATION
|--------------------------------------------------------------------------
*/

nameInput.addEventListener("input", function () {

    // Allow letters and spaces only
    this.value = this.value.replace(/[^A-Za-z ]/g, "");

    const name = this.value.trim();

    if (name === "") {

        nameError.textContent = "";

    } else if (name.length < 3) {

        nameError.textContent =
            "Name must contain at least 3 characters and only letters.";

    } else {

        nameError.textContent = "";

    }

});


/*
|--------------------------------------------------------------------------
| PHONE LIVE VALIDATION
|--------------------------------------------------------------------------
*/

phoneInput.addEventListener("input", function () {

    // Numbers only
    this.value = this.value
        .replace(/[^0-9]/g, "")
        .slice(0, 10);

    const phone = this.value;


    if (phone === "") {

        phoneError.textContent = "";

    }

    else if (
        phone.length >= 2 &&
        !phone.startsWith("97") &&
        !phone.startsWith("98")
    ) {

        phoneError.textContent =
            "Phone number must start with 97 or 98.";

    }

    else if (phone.length < 10) {

        phoneError.textContent =
            "Phone number must start with 98 or 97 and contain 10 digits.";

    }

    else {

        phoneError.textContent = "";

    }

});

function togglePassword(inputId, eyeId) {

    const passwordInput =
        document.getElementById(inputId);

    const eye =
        document.getElementById(eyeId);

    if (passwordInput.type === "password") {

        passwordInput.type = "text";

        eye.textContent = "🙈";

    } else {

        passwordInput.type = "password";

        eye.textContent = "👁";

    }

}



</script>




</body>

</html>