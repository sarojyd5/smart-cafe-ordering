<?php

$to = "ydsaroj0530@gmail.com";

$subject = "Timeout Cafe Test Email";

$message = "This is a test email from Timeout Cafe.";

$headers =
    "From: Timeout Cafe <ydsaroj2062@gmail.com>\r\n" .
    "Reply-To: ydsaroj2062@gmail.com\r\n" .
    "Content-Type: text/plain; charset=UTF-8\r\n";

if (mail($to, $subject, $message, $headers)) {

    echo "Email sent successfully.";

} else {

    echo "Email sending failed.";

}

?>