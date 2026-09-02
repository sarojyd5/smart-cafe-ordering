<?php
require_once 'includes/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$successMessage = "";
$errorMessage = "";

$name = "";
$email = "";
$subject = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $message = trim($_POST["message"] ?? "");

    /* =========================
       VALIDATION
    ========================= */

    if ($name === "" || $email === "" || $subject === "" || $message === "") {

        $errorMessage = "Please fill in all fields.";

    } elseif (!preg_match("/^[A-Za-z ]+$/", $name)) {

        $errorMessage = "Name can contain letters and spaces only.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errorMessage = "Please enter a valid email address.";

    } elseif (strlen($message) < 5) {

        $errorMessage = "Message must contain at least 5 characters.";

    } else {

        try {

            $mail = new PHPMailer(true);

            /* =========================
               SMTP CONFIGURATION
            ========================= */

            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            /* =========================
               EMAIL
            ========================= */

            // Your cafe Gmail sends the email
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

            // Message will be delivered to your cafe Gmail
            $mail->addAddress(MAIL_FROM_EMAIL, MAIL_FROM_NAME);

            // Customer's Gmail becomes Reply-To
            $mail->addReplyTo($email, $name);

            $mail->isHTML(true);

            $mail->Subject = "Contact Message: " . $subject;

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6;'>

                    <h2 style='color:#5C3D2E;'>
                        New Contact Message - Timeout Cafe
                    </h2>

                    <p>
                        <strong>Name:</strong> " . htmlspecialchars($name) . "
                    </p>

                    <p>
                        <strong>Email:</strong> " . htmlspecialchars($email) . "
                    </p>

                    <p>
                        <strong>Subject:</strong> " . htmlspecialchars($subject) . "
                    </p>

                    <hr>

                    <p>
                        <strong>Message:</strong>
                    </p>

                    <p>" . nl2br(htmlspecialchars($message)) . "</p>

                    <hr>

                    <p style='color:#777; font-size:13px;'>
                        This message was sent through the Timeout Cafe
                        Contact Us form.
                    </p>

                </div>
            ";

            // Plain-text version
            $mail->AltBody =
                "New Contact Message - Timeout Cafe\n\n" .
                "Name: " . $name . "\n" .
                "Email: " . $email . "\n" .
                "Subject: " . $subject . "\n\n" .
                "Message:\n" . $message;

            $mail->send();

            $successMessage = "Your message has been sent successfully! We will get back to you soon.";

            // Clear form after successful sending
            $name = "";
            $email = "";
            $subject = "";
            $message = "";

        } catch (Exception $e) {

            $errorMessage = "Sorry, your message could not be sent. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Contact Us | Timeout Cafe</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">

</head>

<body>

<?php include 'includes/navbar.php'; ?>


<section class="contact-page">

    <div class="contact-heading">

        <span class="section-subtitle">CONTACT US</span>

        <h1>
            Get in <span>Touch</span>
        </h1>

        <p>
            Have a question or need assistance?
            Feel free to contact Timeout Cafe.
        </p>

    </div>


    <!-- SUCCESS MESSAGE -->

    <?php if ($successMessage): ?>

        <div class="contact-success">
            <?= htmlspecialchars($successMessage) ?>
        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if ($errorMessage): ?>

        <div class="contact-error">
            <?= htmlspecialchars($errorMessage) ?>
        </div>

    <?php endif; ?>


    <div class="contact-container">


        <!-- CONTACT INFORMATION -->

        <div class="contact-info">

            <div class="contact-box">

                <div class="contact-icon">📍</div>

                <div>
                    <h3>Address</h3>
                    <p>Balkumari, Lalitpur, Nepal</p>
                </div>

            </div>


            <div class="contact-box">

                <div class="contact-icon">📞</div>

                <div>
                    <h3>Phone</h3>
                    <p>+977 9810899601</p>
                </div>

            </div>


            <div class="contact-box">

                <div class="contact-icon">✉️</div>

                <div>
                    <h3>Email</h3>
                    <p><?= htmlspecialchars(MAIL_FROM_EMAIL) ?></p>
                </div>

            </div>


            <div class="contact-box">

                <div class="contact-icon">🕐</div>

                <div>
                    <h3>Opening Hours</h3>
                    <p>Everyday: 8:00 AM – 9:00 PM</p>
                </div>

            </div>

        </div>


        <!-- CONTACT FORM -->

        <div class="contact-form">

            <h2>Send Us a Message</h2>

            <form action="contact.php" method="POST">

                <div class="form-group">

    <label for="name">Name*</label>

    <input
        type="text"
        id="name"
        name="name"
        value="<?= htmlspecialchars($name) ?>"
        placeholder="Enter your name"
        autocomplete="name"
        required
    >

    <small id="nameError" class="field-error"></small>

</div>

                <div class="form-group">

                    <label for="email">Email*</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        placeholder="Enter your Gmail"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="subject">Subject*</label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        value="<?= htmlspecialchars($subject) ?>"
                        placeholder="Enter subject"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="message">Message*</label>

                    <textarea
                        id="message"
                        name="message"
                        rows="6"
                        placeholder="Write your message..."
                        required
                    ><?= htmlspecialchars($message) ?></textarea>

                </div>


                <button type="submit" class="btn-primary">
                    Send Message
                </button>

            </form>

        </div>

    </div>

</section>

<script>
const nameInput = document.getElementById("name");
const nameError = document.getElementById("nameError");

nameInput.addEventListener("input", function () {

    const name = this.value;

    // Allow only letters and spaces
    const namePattern = /^[A-Za-z ]*$/;

    if (name === "") {
        nameError.textContent = "";
        this.classList.remove("input-error", "input-valid");
    }

    else if (!namePattern.test(name)) {
        nameError.textContent =
            "Name can contain letters and spaces only.";

        this.classList.add("input-error");
        this.classList.remove("input-valid");
    }

    else {
        nameError.textContent = "";

        this.classList.remove("input-error");
        this.classList.add("input-valid");
    }
});
</script>


<?php include 'includes/footer.php'; ?>

<script src="assets/js/script.js"></script>

</body>

</html>