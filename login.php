<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";

redirectIfLoggedIn();

$error = "";

$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        $sql = "
            SELECT
                customer_id,
                full_name,
                email,
                password,
                status
            FROM customers
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {

            if ($user["status"] !== "active") {

                $error = "Your account is currently inactive.";

            } elseif (
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {

                session_regenerate_id(true);

                $_SESSION["customer_id"] =
                    $user["customer_id"];

                $_SESSION["customer_name"] =
                    $user["full_name"];

                $_SESSION["customer_email"] =
                    $user["email"];


                header(
                    "Location: customer/dashboard.php"
                );

                exit();

            } else {

                $error = "Invalid email or password.";

            }

        } else {

            $error = "Invalid email or password.";

        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Login | Timeout Cafe</title>

    <link rel="stylesheet"
          href="assets/css/style.css">

    <link rel="stylesheet"
          href="assets/css/responsive.css">

</head>

<body>

<?php include "includes/navbar.php"; ?>


<main class="auth-page">

    <div class="auth-container">

        <div class="auth-content">

            <p class="auth-label">
                WELCOME BACK
            </p>

            <h1>
                Login to Your Account
            </h1>

            <p>
                Login to order your favorite food.
            </p>


            <?php if ($error !== ""): ?>

                <div class="alert error">

                    <?php echo escape($error); ?>

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
                        value="<?php echo escape($email); ?>"
                        placeholder="Enter your email"
                        required>

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
        placeholder="Enter your password"
        required>

    <button
        type="button"
        class="password-toggle"
        onclick="togglePassword('password', this)"
        aria-label="Show password">
        👁
    </button>

</div>
                </div>


                <button
                    type="submit"
                    class="auth-submit">

                    Login

                </button>

            </form>


           <p class="auth-switch">

    <a href="forgot_password.php">
        Forgot Password?
    </a>

</p>


<p class="auth-switch">

    Don't have an account?

    <a href="register.php">
        Create Account
    </a>

</p>

        </div>

    </div>

</main>


<?php include "includes/footer.php"; ?>
<script>

function togglePassword(inputId, button) {

    const input = document.getElementById(inputId);

    if (input.type === "password") {

        input.type = "text";

        button.textContent = "🙈";
        button.setAttribute("aria-label", "Hide password");

    } else {

        input.type = "password";

        button.textContent = "👁";
        button.setAttribute("aria-label", "Show password");

    }

}

</script>

</body>

</html>