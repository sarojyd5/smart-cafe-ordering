<?php

/*
|--------------------------------------------------------------------------
| eSewa Sandbox Configuration
|--------------------------------------------------------------------------
*/

define(
    'ESEWA_PRODUCT_CODE',
    'EPAYTEST'
);

define(
    'ESEWA_SECRET_KEY',
    '8gBm/:&EnhH.1/q'
);

define(
    'ESEWA_PAYMENT_URL',
    'https://rc-epay.esewa.com.np/api/epay/main/v2/form'
);


/*
|--------------------------------------------------------------------------
| Khalti Sandbox Configuration
|--------------------------------------------------------------------------
|
| Get your sandbox secret key from your Khalti
| merchant dashboard.
|
*/

define(
    'KHALTI_SECRET_KEY',
    'PUT_YOUR_KHALTI_SANDBOX_SECRET_KEY_HERE'
);

define(
    'KHALTI_INITIATE_URL',
    'https://dev.khalti.com/api/v2/epayment/initiate/'
);

define(
    'KHALTI_LOOKUP_URL',
    'https://dev.khalti.com/api/v2/epayment/lookup/'
);


/*
|--------------------------------------------------------------------------
| Website URL
|--------------------------------------------------------------------------
*/

define(
    'SITE_URL',
    'http://localhost/smart-cafe-ordering'
);

?>