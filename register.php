<?php

require_once "includes/config.php";
require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";

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


    /* -------------------------------------------------------
       VALIDATE FULL NAME
    ------------------------------------------------------- */

    if ($full_name === "") {

        $errors[] = "Full name is required.";

    } elseif (strlen($full_name) < 3) {

        $errors[] = "Full name must contain at least 3 characters.";

    }


    /* -------------------------------------------------------
       VALIDATE EMAIL
    ------------------------------------------------------- */

    if ($email === "") {

        $errors[] = "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }


    /* -------------------------------------------------------
       VALIDATE PHONE
    ------------------------------------------------------- */

    if ($phone === "") {

        $errors[] = "Phone number is required.";

    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {

        $errors[] = "Phone number must contain exactly 10 digits.";

    }


    /* -------------------------------------------------------
       VALIDATE PASSWORD
    ------------------------------------------------------- */

    if ($password === "") {

        $errors[] = "Password is required.";

    } elseif (strlen($password) < 6) {

        $errors[] = "Password must contain at least 6 characters.";

    }


    /* -------------------------------------------------------
       CONFIRM PASSWORD
    ------------------------------------------------------- */

    if ($password !== $confirm_password) {

        $errors[] = "Passwords do not match.";

    }


    /* -------------------------------------------------------
       CHECK EXISTING EMAIL
    ------------------------------------------------------- */

    if (empty($errors)) {

        $check_sql = "
            SELECT
                customer_id,
                email_verified
            FROM customers
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $check_sql);

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $existing_customer = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if ($existing_customer) {

            if ((int)$existing_customer['email_verified'] === 1) {

                $errors[] =
                    "An account with this email already exists.";

            } else {

                $errors[] =
                    "This email is already registered but not verified. Please complete OTP verification.";

            }

        }

    }


    /* -------------------------------------------------------
       CREATE TEMPORARY OTP
    ------------------------------------------------------- */

    if (empty($errors)) {

        /*
         * Generate 6 digit OTP
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
         * OTP valid for 5 minutes
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
         * Insert customer
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
                 * Store registration information
                 * temporarily in session
                 */

                $_SESSION['otp_customer_id'] =
                    $customer_id;

                $_SESSION['otp_email'] =
                    $email;


                /*
                 * Send OTP email
                 */

                $to = $email;

                $subject =
                    "Timeout Cafe - Your Registration OTP";


                $message =
                    "Hello " . $full_name . ",\r\n\r\n"
                    . "Thank you for registering at Timeout Cafe.\r\n\r\n"
                    . "Your OTP is: "
                    . $otp
                    . "\r\n\r\n"
                    . "This OTP is valid for 5 minutes.\r\n\r\n"
                    . "If you did not request this registration, please ignore this email.\r\n\r\n"
                    . "Regards,\r\n"
                    . "Timeout Cafe";


                $headers =
                    "From: Timeout Cafe <ydsaroj2062@gmail.com>\r\n"
                    . "Reply-To: ydsaroj2062@gmail.com\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n";


                /*
                 * Send email
                 */

                if (mail(
                    $to,
                    $subject,
                    $message,
                    $headers
                )) {

                    /*
                     * Redirect to OTP page
                     */

                    header(
                        "Location: verify_otp.php"
                    );

                    exit();

                } else {

                    /*
                     * Remove account if email
                     * could not be sent.
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


                    $errors[] =
                        "Unable to send OTP email. Please try again.";
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
                        Full Name
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

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
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
                        Phone Number
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
                        placeholder="At least 6 characters"
                        required>

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        required>

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

</body>

</html>