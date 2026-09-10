<?php

/*
|--------------------------------------------------------------------------
| Website Configuration
|--------------------------------------------------------------------------
*/

define('SITE_URL', 'http://localhost/smart-cafe-ordering');


/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
*/

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smart_cafe');


/*
|--------------------------------------------------------------------------
| Email Configuration
|--------------------------------------------------------------------------
*/

define('MAIL_FROM_EMAIL', 'ydsaroj2062@gmail.com');
define('MAIL_FROM_NAME', 'Timeout Cafe');


/*
|--------------------------------------------------------------------------
| SMTP Configuration
|--------------------------------------------------------------------------
|
| Gmail SMTP is used by PHPMailer.
|
*/

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'ydsaroj2062@gmail.com');

/*
 * IMPORTANT:
 * Use your Gmail APP PASSWORD here.
 * Do NOT use your normal Gmail password.
 */
define('SMTP_PASSWORD', 'awnyvhddagonurbe');


/*
|--------------------------------------------------------------------------
| OTP Configuration
|--------------------------------------------------------------------------
*/

define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 5);


/*
|--------------------------------------------------------------------------
| Application Name
|--------------------------------------------------------------------------
*/

define('APP_NAME', 'Timeout Cafe');


/*
|--------------------------------------------------------------------------
| Google Maps API Key
|--------------------------------------------------------------------------
|
| Get your API key from: https://console.cloud.google.com/apis/credentials
|
| Enable these APIs in Google Cloud Console:
|   1. Maps JavaScript API
|   2. Places API
|   3. Geocoding API
|
*/

define('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY_HERE');

?>