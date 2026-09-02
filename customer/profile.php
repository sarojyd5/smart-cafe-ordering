<?php

require_once "../includes/session.php";
require_once "../includes/db.php";
require_once "../includes/functions.php";

requireCustomerLogin();

$customerId = $_SESSION["customer_id"];

$successMessage = "";
$errorMessage = "";

/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullName = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    /* Validate name */
    if ($fullName === "") {

        $errorMessage = "Please enter your name.";

    } elseif (!preg_match("/^[A-Za-z ]+$/", $fullName)) {

        $errorMessage = "Name can contain letters and spaces only.";

    /* Validate phone */
    } elseif (!preg_match("/^(97|98)[0-9]{8}$/", $phone)) {

        $errorMessage = "Phone number must be 10 digits and start with 97 or 98.";

    } else {

        $stmt = $conn->prepare("
            UPDATE customers
            SET full_name = ?, phone = ?
            WHERE customer_id = ?
        ");

        $stmt->bind_param("ssi", $fullName, $phone, $customerId);

        if ($stmt->execute()) {

            $_SESSION["customer_name"] = $fullName;

            $successMessage = "Profile updated successfully.";

        } else {

            $errorMessage = "Unable to update profile. Please try again.";
        }

        $stmt->close();
    }
}


/* =========================
   GET CUSTOMER INFORMATION
========================= */

$stmt = $conn->prepare("
    SELECT full_name, email, phone
    FROM customers
    WHERE customer_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $customerId);
$stmt->execute();

$result = $stmt->get_result();
$customer = $result->fetch_assoc();

$stmt->close();

if (!$customer) {
    die("Customer account not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>My Profile | Timeout Cafe</title>

    <link rel="stylesheet"
          href="../assets/css/style.css">

    <link rel="stylesheet"
          href="../assets/css/responsive.css">

</head>

<body>

<?php include "../includes/navbar.php"; ?>


<section class="profile-page">

    <div class="profile-container">

        <div class="profile-header">

            <div class="profile-icon">
                👤
            </div>

            <div>
                <h1>My Profile</h1>

                <p>
                    Manage your Timeout Cafe account information.
                </p>
            </div>

        </div>


        <?php if ($successMessage): ?>

            <div class="profile-success">
                <?= htmlspecialchars($successMessage) ?>
            </div>

        <?php endif; ?>


        <?php if ($errorMessage): ?>

            <div class="profile-error">
                <?= htmlspecialchars($errorMessage) ?>
            </div>

        <?php endif; ?>


        <form method="POST"
              action="profile.php"
              class="profile-form">


            <!-- NAME -->

            <div class="profile-form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="<?= htmlspecialchars($customer["full_name"]) ?>"
                    placeholder="Enter your full name"
                    required
                >

                <small id="nameError"
                       class="profile-field-error">
                </small>

            </div>


            <!-- EMAIL -->

            <div class="profile-form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    value="<?= htmlspecialchars($customer["email"]) ?>"
                    readonly
                >

                <small>
                    Email address cannot be changed.
                </small>

            </div>


            <!-- PHONE -->

            <div class="profile-form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="text"
                    id="phone"
                    name="phone"
                    value="<?= htmlspecialchars($customer["phone"]) ?>"
                    maxlength="10"
                    placeholder="98XXXXXXXX"
                    required
                >

                <small id="phoneError"
                       class="profile-field-error">
                </small>

            </div>


            <button type="submit"
                    class="profile-update-btn">

                Update Profile

            </button>

        </form>


        <div class="profile-footer">

            <a href="dashboard.php">
                ← Back to Dashboard
            </a>

        </div>

    </div>

</section>


<?php include "../includes/footer.php"; ?>


<script>

const nameInput = document.getElementById("full_name");
const nameError = document.getElementById("nameError");

const phoneInput = document.getElementById("phone");
const phoneError = document.getElementById("phoneError");


/* =========================
   NAME VALIDATION
========================= */

nameInput.addEventListener("input", function () {

    const name = this.value;

    if (name === "") {

        nameError.textContent = "";

    } else if (!/^[A-Za-z ]+$/.test(name)) {

        nameError.textContent =
            "Name can contain letters and spaces only.";

    } else {

        nameError.textContent = "";
    }

});


/* =========================
   PHONE VALIDATION
========================= */

phoneInput.addEventListener("input", function () {

    const phone = this.value;

    if (phone === "") {

        phoneError.textContent = "";

    } else if (!/^[0-9]*$/.test(phone)) {

        phoneError.textContent =
            "Phone number can contain digits only.";

    } else if (phone.length >= 2 &&
               !phone.startsWith("97") &&
               !phone.startsWith("98")) {

        phoneError.textContent =
            "Phone number must start with 97 or 98.";

    } else if (phone.length < 10) {

        phoneError.textContent =
            "Phone number must contain 10 digits.";

    } else {

        phoneError.textContent = "";
    }

});

</script>


</body>
</html>