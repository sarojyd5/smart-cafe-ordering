<?php

date_default_timezone_set('Asia/Kathmandu');

$to = "ydsaroj0530@gmail.com";

$otp = "123456";

$subject = "Timeout Cafe - OTP Test";

$message =
    "Hello,\r\n\r\n" .
    "Your Timeout Cafe OTP is: " . $otp . "\r\n\r\n" .
    "This OTP is valid for 5 minutes.\r\n\r\n" .
    "Timeout Cafe";

$headers =
    "From: Timeout Cafe <ydsaroj2062@gmail.com>\r\n" .
    "Reply-To: ydsaroj2062@gmail.com\r\n" .
    "MIME-Version: 1.0\r\n" .
    "Content-Type: text/plain; charset=UTF-8\r\n";

$result = mail(
    $to,
    $subject,
    $message,
    $headers
);

if ($result) {

    echo "PHP mail() returned TRUE.<br>";
    echo "Now check the receiving Gmail inbox, Spam, Promotions and All Mail.";

} else {

    echo "PHP mail() returned FALSE.";

}

?>