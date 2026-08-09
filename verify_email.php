<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";


// Get token from URL
$token = trim($_GET['token'] ?? '');


// Check token exists
if ($token === '') {
    die("Verification token is missing.");
}


// Token should be 64 hexadecimal characters
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    die("Invalid verification token.");
}


// DEBUG ONLY
echo "<pre>";
echo "Received token:\n";
echo $token;
echo "\n\nToken length: ";
echo strlen($token);
echo "\n\nToken hash:\n";
echo hash('sha256', $token);
echo "</pre>";

exit();

// Find customer
$sql = "
    SELECT
        customer_id,
        full_name,
        email,
        email_verified,
        verification_expires
    FROM customers
    WHERE verification_token = ?
    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error.");
}


mysqli_stmt_bind_param(
    $stmt,
    "s",
    $token_hash
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);

$customer = mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


// Token not found
if (!$customer) {

    die(
        "Invalid or expired verification link.<br><br>
        The verification token could not be found in the database."
    );
}


// Already verified
if ((int)$customer['email_verified'] === 1) {

    die(
        "Your email is already verified.<br><br>
        <a href='login.php'>Login here</a>"
    );
}


// Check expiry
if (
    empty($customer['verification_expires']) ||
    strtotime($customer['verification_expires']) < time()
) {

    die(
        "This verification link has expired."
    );
}


// Verify customer
$update_sql = "
    UPDATE customers
    SET
        email_verified = 1,
        verification_token = NULL,
        verification_expires = NULL
    WHERE customer_id = ?
";


$update_stmt = mysqli_prepare(
    $conn,
    $update_sql
);


if (!$update_stmt) {
    die("Database update error.");
}


mysqli_stmt_bind_param(
    $update_stmt,
    "i",
    $customer['customer_id']
);


if (mysqli_stmt_execute($update_stmt)) {

    mysqli_stmt_close($update_stmt);

    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0">

        <title>
            Email Verified | Timeout Cafe
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
                    Email Verified!
                </h1>

                <p>
                    Hello
                    <?php echo escape($customer['full_name']); ?>,
                </p>

                <p>
                    Your email address has been
                    successfully verified.
                </p>

                <p>
                    You can now login to your
                    Timeout Cafe account.
                </p>

                <br>

                <a
                    href="login.php"
                    class="auth-submit">

                    Login Now

                </a>

            </div>

        </div>

    </main>

    <?php include "includes/footer.php"; ?>

    </body>

    </html>

    <?php

} else {

    mysqli_stmt_close($update_stmt);

    die(
        "Email verification failed."
    );
}

?>