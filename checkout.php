<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireCustomerLogin();


// ==========================================================
// CHECK CART
// ==========================================================

if (
    !isset($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    header("Location: cart.php");
    exit();
}

$cart = $_SESSION['cart'];


// ==========================================================
// GET CART ITEMS
// ==========================================================

$cart_items = [];
$subtotal = 0;

foreach ($cart as $food_id => $quantity) {

    $food_id = (int)$food_id;
    $quantity = (int)$quantity;

    if ($food_id <= 0 || $quantity <= 0) {
        continue;
    }

    $sql = "
        SELECT
            food_id,
            food_name,
            price,
            image,
            availability
        FROM foods
        WHERE food_id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $food_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $food = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    if (!$food) {
        continue;
    }


    // Don't allow unavailable food
    if ($food['availability'] !== 'available') {
        continue;
    }


    $item_total =
        (float)$food['price'] * $quantity;

    $subtotal += $item_total;


    $cart_items[] = [

        'food_id' => $food['food_id'],

        'food_name' => $food['food_name'],

        'price' => (float)$food['price'],

        'image' => $food['image'],

        'quantity' => $quantity,

        'total' => $item_total

    ];
}


// ==========================================================
// IF NO VALID ITEMS
// ==========================================================

if (empty($cart_items)) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit();
}


// ==========================================================
// DELIVERY CHARGE
// ==========================================================

$delivery_charge = 50;

$total_amount =
    $subtotal + $delivery_charge;


// ==========================================================
// CUSTOMER
// ==========================================================

$customer_name =
    $_SESSION['customer_name'] ?? '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Checkout | Timeout Cafe
    </title>


    <!-- ==================================================
         LEAFLET
    ================================================== -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin="">


    <style>

    /* =====================================================
       GENERAL
    ===================================================== */

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;

        background: #faf6f2;

        color: #3f3029;

        font-family:
            Arial,
            Helvetica,
            sans-serif;
    }


    /* =====================================================
       CHECKOUT PAGE
    ===================================================== */

    .checkout-page {

        width: 100%;

        max-width: 1200px;

        margin: 0 auto;

        padding: 45px 20px 60px;
    }


    /* =====================================================
       HEADER
    ===================================================== */

    .checkout-header {

        display: flex;

        justify-content: space-between;

        align-items: center;

        gap: 20px;

        margin-bottom: 35px;
    }


    .checkout-header p {

        margin: 0 0 6px;

        color: #b85c38;

        font-size: 12px;

        font-weight: bold;

        letter-spacing: 2px;
    }


    .checkout-header h1 {

        margin: 0 0 8px;

        color: #3f3029;

        font-size: 38px;
    }


    .checkout-header span {

        color: #777;

        font-size: 15px;
    }


    .checkout-back-btn {

        text-decoration: none;

        color: #5c3d2e;

        font-weight: bold;

        transition: 0.2s;
    }


    .checkout-back-btn:hover {

        color: #b85c38;
    }


    /* =====================================================
       LAYOUT
    ===================================================== */

    .checkout-layout {

        display: grid;

        grid-template-columns:
            minmax(0, 1.4fr)
            minmax(320px, 1fr);

        gap: 30px;

        align-items: start;
    }


    /* =====================================================
       CARDS
    ===================================================== */

    .checkout-form-card,
    .checkout-summary-card {

        background: #ffffff;

        padding: 28px;

        border-radius: 14px;

        box-shadow:
            0 5px 25px rgba(0, 0, 0, 0.06);
    }


    .checkout-form-card h2,
    .checkout-summary-card h2 {

        margin-top: 0;

        margin-bottom: 25px;

        color: #5c3d2e;

        font-size: 24px;
    }


    /* =====================================================
       FORM
    ===================================================== */

    .checkout-form-group {

        margin-bottom: 21px;
    }


    .checkout-form-group > label {

        display: block;

        margin-bottom: 8px;

        color: #3f3029;

        font-size: 15px;

        font-weight: 600;
    }


    .checkout-form-group label span {

        color: #888;

        font-size: 12px;

        font-weight: normal;
    }


    .checkout-form-group input[type="text"],
    .checkout-form-group input[type="tel"],
    .checkout-form-group textarea {

        width: 100%;

        padding: 13px 14px;

        border: 1px solid #d9d2cd;

        border-radius: 8px;

        background: #ffffff;

        color: #333;

        font-family: inherit;

        font-size: 14px;

        outline: none;

        transition:
            border-color 0.2s,
            box-shadow 0.2s;
    }


    .checkout-form-group input[type="text"]:focus,
    .checkout-form-group input[type="tel"]:focus,
    .checkout-form-group textarea:focus {

        border-color: #b85c38;

        box-shadow:
            0 0 0 3px rgba(184, 92, 56, 0.10);
    }


    .checkout-form-group textarea {

        min-height: 80px;

        resize: vertical;

        line-height: 1.5;
    }


    /* =====================================================
       FIELD ERROR
    ===================================================== */

    .field-error {

        display: block;

        min-height: 17px;

        margin-top: 5px;

        color: #c0392b;

        font-size: 12px;
    }


    /* =====================================================
       LOCATION SECTION
    ===================================================== */

    .location-container {

        position: relative;

        width: 100%;
    }


    /* MAP */

    .map-container {

        position: relative;

        width: 100%;

        height: 330px;

        margin-bottom: 12px;

        overflow: hidden;

        border: 1px solid #ddd;

        border-radius: 12px;

        background: #eeeeee;

        box-shadow:
            0 4px 15px rgba(0, 0, 0, 0.07);
    }


    #map {

        width: 100%;

        height: 100%;
    }


    /* =====================================================
       SEARCH BOX
    ===================================================== */

    .location-search-wrapper {

        position: relative;

        width: 100%;

        margin-bottom: 6px;
    }


    #locationSearch {

        width: 100%;

        height: 48px;

        padding:
            0 48px 0 15px;

        border: 1px solid #d8d0cb;

        border-radius: 9px;

        background: #ffffff;

        color: #333;

        font-family: inherit;

        font-size: 14px;

        outline: none;

        box-shadow:
            0 2px 8px rgba(0, 0, 0, 0.04);

        transition: 0.2s;
    }


    #locationSearch:focus {

        border-color: #b85c38;

        box-shadow:
            0 0 0 3px rgba(184, 92, 56, 0.10);
    }


    #locationSearch::placeholder {

        color: #999;
    }


    /* SEARCH BUTTON */

    .search-location-btn {

        position: absolute;

        top: 6px;

        right: 6px;

        width: 36px;

        height: 36px;

        border: none;

        border-radius: 7px;

        background: #b85c38;

        color: #ffffff;

        font-size: 16px;

        cursor: pointer;

        transition: 0.2s;
    }


    .search-location-btn:hover {

        background: #8f452c;

        transform: scale(1.03);
    }


    /* =====================================================
       SUGGESTIONS
    ===================================================== */

    .address-suggestions {

        position: absolute;

        left: 0;

        right: 0;

        top: 378px;

        z-index: 2000;

        display: none;

        overflow: hidden;

        border: 1px solid #ddd;

        border-radius: 9px;

        background: #ffffff;

        box-shadow:
            0 8px 25px rgba(0, 0, 0, 0.14);
    }


    .address-suggestion {

        padding: 12px 14px;

        border-bottom: 1px solid #eeeeee;

        color: #333;

        font-size: 13px;

        line-height: 1.45;

        cursor: pointer;

        transition: background 0.2s;
    }


    .address-suggestion:last-child {

        border-bottom: none;
    }


    .address-suggestion:hover {

        background: #faf3ed;
    }


    .suggestion-icon {

        margin-right: 6px;

        color: #b85c38;
    }


    /* LOADING */

    .address-loading {

        padding: 13px 15px;

        color: #777;

        font-size: 13px;

        text-align: center;
    }


    .address-no-result {

        padding: 13px 15px;

        color: #777;

        font-size: 13px;
    }


    /* =====================================================
       CURRENT LOCATION BUTTON
    ===================================================== */

    .current-location-btn {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        gap: 7px;

        margin-top: 9px;

        padding: 9px 14px;

        border: 1px solid #d8d0cb;

        border-radius: 7px;

        background: #ffffff;

        color: #5c3d2e;

        font-family: inherit;

        font-size: 13px;

        font-weight: 600;

        cursor: pointer;

        transition: 0.2s;
    }


    .current-location-btn:hover {

        border-color: #b85c38;

        background: #faf3ed;

        color: #b85c38;
    }


    /* =====================================================
       ADDRESS TEXTAREA
    ===================================================== */

    .selected-address-wrapper {

        margin-top: 12px;
    }


    .selected-address-label {

        display: block;

        margin-bottom: 7px;

        color: #3f3029;

        font-size: 13px;

        font-weight: 600;
    }


    #delivery_address {

        min-height: 80px;

        resize: vertical;
    }


    #delivery_address.location-selected {

        border-color: #6a994e;

        box-shadow:
            0 0 0 3px rgba(106, 153, 78, 0.10);
    }


    .address-help {

        display: block;

        margin-top: 7px;

        color: #777;

        font-size: 12px;

        line-height: 1.5;
    }


    .location-error {

        display: block;

        margin-top: 6px;

        color: #c0392b;

        font-size: 12px;
    }


    /* =====================================================
       PAYMENT
    ===================================================== */

    .payment-methods {

        display: flex;

        flex-direction: column;

        gap: 10px;
    }


    .payment-method-card {

        display: flex;

        align-items: center;

        gap: 10px;

        padding: 13px;

        border: 1px solid #ddd;

        border-radius: 8px;

        cursor: pointer;

        transition: 0.2s;
    }


    .payment-method-card:hover {

        border-color: #b85c38;

        background: #fffaf6;
    }


    .payment-method-card input {

        width: auto;

        accent-color: #b85c38;
    }


    .payment-method-card span {

        display: flex;

        flex-direction: column;

        gap: 3px;
    }


    .payment-method-card strong {

        color: #3f3029;

        font-size: 13px;
    }


    .payment-method-card small {

        color: #777;

        font-size: 11px;
    }


    /* =====================================================
       PLACE ORDER BUTTON
    ===================================================== */

    .place-order-btn {

        width: 100%;

        border: none;

        padding: 14px;

        border-radius: 8px;

        background: #b85c38;

        color: #ffffff;

        font-size: 15px;

        font-weight: bold;

        cursor: pointer;

        transition: 0.25s;
    }


    .place-order-btn:hover {

        background: #8f452c;

        transform: translateY(-1px);
    }


    .place-order-btn:disabled {

        opacity: 0.6;

        cursor: not-allowed;

        transform: none;
    }


    /* =====================================================
       ORDER SUMMARY
    ===================================================== */

    .checkout-summary-card {

        position: sticky;

        top: 25px;
    }


    .checkout-item {

        display: flex;

        justify-content: space-between;

        gap: 15px;

        padding: 14px 0;

        border-bottom: 1px solid #eeeeee;
    }


    .checkout-item div {

        display: flex;

        flex-direction: column;

        gap: 5px;
    }


    .checkout-item strong {

        color: #3f3029;
    }


    .checkout-item span {

        color: #777;

        font-size: 13px;
    }


    .checkout-total-row {

        display: flex;

        justify-content: space-between;

        padding: 12px 0;

        color: #666;
    }


    .checkout-grand-total {

        display: flex;

        justify-content: space-between;

        margin-top: 10px;

        padding-top: 18px;

        border-top: 1px solid #ddd;

        color: #5c3d2e;

        font-size: 19px;
    }


    /* =====================================================
       LEAFLET
    ===================================================== */

    .leaflet-control-zoom {

        border: none !important;

        box-shadow:
            0 3px 10px rgba(0, 0, 0, 0.15) !important;
    }


    .leaflet-control-zoom a {

        color: #5c3d2e !important;

        background: #ffffff !important;
    }


    .leaflet-control-zoom a:hover {

        background: #faf3ed !important;
    }


    .leaflet-popup-content-wrapper {

        border-radius: 10px;
    }


    .leaflet-popup-content {

        font-family: Arial, sans-serif;

        font-size: 13px;
    }


    /* =====================================================
       RESPONSIVE
    ===================================================== */

    @media (max-width: 900px) {

        .checkout-layout {

            grid-template-columns: 1fr;
        }


        .checkout-summary-card {

            position: static;
        }

    }


    @media (max-width: 700px) {

        .checkout-page {

            padding:
                30px 15px 50px;
        }


        .checkout-header {

            align-items: flex-start;

            flex-direction: column;

            margin-bottom: 25px;
        }


        .checkout-header h1 {

            font-size: 32px;
        }


        .checkout-form-card,
        .checkout-summary-card {

            padding: 22px;

            border-radius: 12px;
        }


        .map-container {

            height: 300px;
        }


        .address-suggestions {

            top: 348px;
        }

    }


    @media (max-width: 500px) {

        .checkout-page {

            padding:
                25px 10px 40px;
        }


        .checkout-header h1 {

            font-size: 28px;
        }


        .checkout-header span {

            font-size: 13px;
        }


        .checkout-form-card,
        .checkout-summary-card {

            padding: 18px;
        }


        .map-container {

            height: 260px;
        }


        .address-suggestions {

            top: 308px;
        }


        #locationSearch {

            height: 46px;

            font-size: 13px;
        }


        .current-location-btn {

            width: 100%;
        }


        .checkout-item {

            font-size: 13px;
        }

    }

    </style>

</head>


<body>


<main class="checkout-page">


    <!-- ==================================================
         CHECKOUT HEADER
    ================================================== -->

    <section class="checkout-header">

        <div>

            <p>
                TIMEOUT CAFE
            </p>

            <h1>
                Checkout
            </h1>

            <span>
                Complete your order details.
            </span>

        </div>


        <a
            href="cart.php"
            class="checkout-back-btn">

            ← Back to Cart

        </a>

    </section>



    <!-- ==================================================
         CHECKOUT LAYOUT
    ================================================== -->

    <section class="checkout-layout">


        <!-- ==================================================
             CUSTOMER FORM
        ================================================== -->

        <div class="checkout-form-card">


            <h2>
                Delivery Information
            </h2>


            <form
                method="POST"
                action="place_order.php"
                id="checkoutForm">


                <!-- ==========================================
                     FULL NAME
                =========================================== -->

                <div class="checkout-form-group">

                    <label for="customer_name">

                        Full Name*

                    </label>


                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        value="<?php
                        echo escape($customer_name);
                        ?>"
                        required
                        maxlength="100"
                        autocomplete="name"
                        placeholder="Enter your full name">


                    <small
                        id="name-error"
                        class="field-error">
                    </small>

                </div>



                <!-- ==========================================
                     PHONE
                =========================================== -->

                <div class="checkout-form-group">

                    <label for="customer_phone">

                        Phone Number*

                    </label>


                    <input
                        type="tel"
                        id="customer_phone"
                        name="customer_phone"
                        required
                        maxlength="10"
                        minlength="10"
                        inputmode="numeric"
                        autocomplete="tel"
                        placeholder="98XXXXXXXX">


                    <small
                        id="phone-error"
                        class="field-error">
                    </small>

                </div>



                <!-- ==========================================
                     DELIVERY LOCATION
                =========================================== -->

                <div class="checkout-form-group">

                    <label for="delivery_address">

                        Delivery Address*

                    </label>


                    <div
                        class="location-container">


                        <!-- MAP -->

                        <div
                            class="map-container">

                            <div id="map"></div>

                        </div>



                        <!-- SEARCH -->

                        <div
                            class="location-search-wrapper">


                            <input
                                type="text"
                                id="locationSearch"
                                placeholder="Search delivery location..."
                                autocomplete="off">


                            <button
                                type="button"
                                id="searchLocationBtn"
                                class="search-location-btn"
                                title="Search location">

                                🔍

                            </button>


                        </div>



                        <!-- SUGGESTIONS -->

                        <div
                            id="addressSuggestions"
                            class="address-suggestions">
                        </div>



                        <!-- CURRENT LOCATION -->

                        <button
                            type="button"
                            id="currentLocationBtn"
                            class="current-location-btn">

                            📍
                            Use My Current Location

                        </button>



                        <!-- SELECTED ADDRESS -->

                        <div
                            class="selected-address-wrapper">


                            <label
                                for="delivery_address"
                                class="selected-address-label">

                                Selected Delivery Address

                            </label>


                            <textarea
                                id="delivery_address"
                                name="delivery_address"
                                maxlength="500"
                                required
                                placeholder="Search for your delivery location above, then select a suggestion."><?php
                                echo "";
                                ?></textarea>


                            <small
                                class="address-help">

                                Search for your street, area or nearby location and select a suggestion.

                            </small>


                            <small
                                id="location-error"
                                class="location-error">
                            </small>


                        </div>


                    </div>

                </div>



                <!-- ==========================================
                     ORDER NOTE
                =========================================== -->

                <div class="checkout-form-group">

                    <label for="order_note">

                        Order Note

                        <span>
                            (Optional)
                        </span>

                    </label>


                    <textarea
                        id="order_note"
                        name="order_note"
                        rows="3"
                        maxlength="500"
                        placeholder="Any special instructions?"></textarea>

                </div>



                <!-- ==========================================
                     PAYMENT
                =========================================== -->

                <div class="checkout-form-group">

                    <label>

                        Payment Method

                    </label>


                    <div class="payment-methods">


                        <!-- CASH -->

                        <label
                            class="payment-method-card">

                            <input
                                type="radio"
                                name="payment_method"
                                value="Cash on Delivery"
                                checked>


                            <span>

                                <strong>
                                    Cash on Delivery
                                </strong>

                                <small>
                                    Pay when your food arrives.
                                </small>

                            </span>

                        </label>



                        <!-- ESEWA -->

                        <label
                            class="payment-method-card">

                            <input
                                type="radio"
                                name="payment_method"
                                value="eSewa">


                            <span>

                                <strong>
                                    eSewa
                                </strong>

                                <small>
                                    Pay securely using eSewa.
                                </small>

                            </span>

                        </label>


                    </div>

                </div>



                <!-- ==========================================
                     PLACE ORDER
                =========================================== -->

                <button
                    type="submit"
                    class="place-order-btn"
                    id="placeOrderBtn">

                    Place Order

                </button>


            </form>


        </div>



        <!-- ==================================================
             ORDER SUMMARY
        ================================================== -->

        <div class="checkout-summary-card">


            <h2>
                Your Order
            </h2>


            <div class="checkout-items">


                <?php foreach (
                    $cart_items as $item
                ): ?>


                    <div class="checkout-item">


                        <div>

                            <strong>

                                <?php
                                echo escape(
                                    $item['food_name']
                                );
                                ?>

                            </strong>


                            <span>

                                <?php
                                echo $item['quantity'];
                                ?>

                                × Rs.

                                <?php
                                echo number_format(
                                    $item['price'],
                                    2
                                );
                                ?>

                            </span>

                        </div>


                        <strong>

                            Rs.

                            <?php
                            echo number_format(
                                $item['total'],
                                2
                            );
                            ?>

                        </strong>


                    </div>


                <?php endforeach; ?>


            </div>



            <!-- SUBTOTAL -->

            <div class="checkout-total-row">

                <span>
                    Subtotal
                </span>

                <strong>

                    Rs.

                    <?php
                    echo number_format(
                        $subtotal,
                        2
                    );
                    ?>

                </strong>

            </div>



            <!-- DELIVERY -->

            <div class="checkout-total-row">

                <span>
                    Delivery Charge
                </span>

                <strong>

                    Rs.

                    <?php
                    echo number_format(
                        $delivery_charge,
                        2
                    );
                    ?>

                </strong>

            </div>



            <!-- TOTAL -->

            <div class="checkout-grand-total">

                <span>
                    Total
                </span>

                <strong>

                    Rs.

                    <?php
                    echo number_format(
                        $total_amount,
                        2
                    );
                    ?>

                </strong>

            </div>


        </div>


    </section>


</main>



<!-- ========================================================
     LEAFLET JAVASCRIPT
========================================================= -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin="">
</script>



<script>

/* =========================================================
   ELEMENTS
========================================================= */

const checkoutForm =
    document.getElementById("checkoutForm");

const nameInput =
    document.getElementById("customer_name");

const nameError =
    document.getElementById("name-error");

const phoneInput =
    document.getElementById("customer_phone");

const phoneError =
    document.getElementById("phone-error");

const locationSearch =
    document.getElementById("locationSearch");

const searchLocationBtn =
    document.getElementById("searchLocationBtn");

const addressSuggestions =
    document.getElementById("addressSuggestions");

const deliveryAddress =
    document.getElementById("delivery_address");

const locationError =
    document.getElementById("location-error");

const currentLocationBtn =
    document.getElementById("currentLocationBtn");



/* =========================================================
   NAME VALIDATION
========================================================= */

nameInput.addEventListener(
    "input",
    function () {

        this.value =
            this.value.replace(
                /[^A-Za-z ]/g,
                ""
            );


        const name =
            this.value.trim();


        if (name === "") {

            nameError.textContent = "";

            return;
        }


        if (name.length < 3) {

            nameError.textContent =
                "Name must contain at least 3 characters.";

            return;
        }


        nameError.textContent = "";

    }
);



/* =========================================================
   PHONE VALIDATION
========================================================= */

phoneInput.addEventListener(
    "input",
    function () {

        this.value =
            this.value
                .replace(
                    /[^0-9]/g,
                    ""
                )
                .slice(0, 10);


        const phone =
            this.value;


        if (phone === "") {

            phoneError.textContent = "";

            return;
        }


        if (
            phone.length >= 2 &&
            !phone.startsWith("97") &&
            !phone.startsWith("98")
        ) {

            phoneError.textContent =
                "Phone number must start with 97 or 98.";

            return;
        }


        if (phone.length < 10) {

            phoneError.textContent =
                "Phone number must contain exactly 10 digits.";

            return;
        }


        phoneError.textContent = "";

    }
);



/* =========================================================
   MAP
   Default location: Kathmandu Valley
========================================================= */

const map =
    L.map("map").setView(
        [27.7172, 85.3240],
        13
    );


/* =========================================================
   OPENSTREETMAP TILES
========================================================= */

L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {
        maxZoom: 19,

        attribution:
            '&copy; OpenStreetMap contributors'
    }
).addTo(map);



/* =========================================================
   MARKER
========================================================= */

let selectedMarker = null;

let selectedLatitude = null;

let selectedLongitude = null;



/* =========================================================
   CREATE / MOVE MARKER
========================================================= */

function setMarker(
    latitude,
    longitude,
    addressText = ""
) {

    selectedLatitude =
        latitude;

    selectedLongitude =
        longitude;


    if (selectedMarker) {

        selectedMarker.setLatLng(
            [latitude, longitude]
        );

    }

    else {

        selectedMarker =
            L.marker(
                [latitude, longitude],
                {
                    draggable: true
                }
            ).addTo(map);


        selectedMarker.on(
            "dragend",
            function () {

                const position =
                    selectedMarker.getLatLng();

                reverseGeocode(
                    position.lat,
                    position.lng
                );

            }
        );

    }


    selectedMarker.bindPopup(
        "<strong>📍 Delivery Location</strong>"
    );


    if (addressText !== "") {

        deliveryAddress.value =
            addressText;

        deliveryAddress.classList.add(
            "location-selected"
        );

        locationError.textContent = "";

    }


    map.setView(
        [latitude, longitude],
        16
    );

}



/* =========================================================
   MAP CLICK
========================================================= */

map.on(
    "click",
    function (event) {

        const latitude =
            event.latlng.lat;

        const longitude =
            event.latlng.lng;


        setMarker(
            latitude,
            longitude
        );


        reverseGeocode(
            latitude,
            longitude
        );

    }
);



/* =========================================================
   REVERSE GEOCODING
   Converts coordinates → address
========================================================= */

async function reverseGeocode(
    latitude,
    longitude
) {

    deliveryAddress.value =
        "Finding address...";


    try {

        const url =
            "https://nominatim.openstreetmap.org/reverse" +
            "?format=json" +
            "&lat=" + encodeURIComponent(latitude) +
            "&lon=" + encodeURIComponent(longitude) +
            "&zoom=18" +
            "&addressdetails=1";


        const response =
            await fetch(url, {
                headers: {
                    "Accept":
                        "application/json"
                }
            });


        if (!response.ok) {

            throw new Error(
                "Unable to find address."
            );

        }


        const data =
            await response.json();


        const address =
            data.display_name || "";


        if (address !== "") {

            deliveryAddress.value =
                address;

            deliveryAddress.classList.add(
                "location-selected"
            );

            locationError.textContent = "";

        }

        else {

            deliveryAddress.value = "";

            locationError.textContent =
                "Address could not be found. Please enter it manually.";

        }

    }

    catch (error) {

        deliveryAddress.value = "";

        locationError.textContent =
            "Unable to find this location. Please enter the address manually.";

    }

}



/* =========================================================
   SEARCH LOCATION
========================================================= */

let searchTimer = null;


locationSearch.addEventListener(
    "input",
    function () {

        const query =
            this.value.trim();


        clearTimeout(searchTimer);


        if (query.length < 3) {

            hideSuggestions();

            return;
        }


        /*
         * Wait before searching.
         * This prevents too many requests.
         */

        searchTimer =
            setTimeout(
                function () {

                    searchAddress(
                        query
                    );

                },
                800
            );

    }
);



/* =========================================================
   SEARCH ADDRESS
========================================================= */

async function searchAddress(
    query
) {

    showLoading();


    try {

        /*
         * Adding Nepal helps return
         * more relevant Nepal locations.
         *
         * You can remove countrycodes
         * if you want worldwide search.
         */

        const url =
            "https://nominatim.openstreetmap.org/search" +

            "?format=json" +

            "&q=" +
            encodeURIComponent(query + ", Nepal") +

            "&countrycodes=np" +

            "&limit=8" +

            "&addressdetails=1";


        const response =
            await fetch(url, {
                headers: {
                    "Accept":
                        "application/json"
                }
            });


        if (!response.ok) {

            throw new Error(
                "Search failed"
            );

        }


        const results =
            await response.json();


        displaySuggestions(
            results
        );

    }

    catch (error) {

        addressSuggestions.innerHTML =
            `
            <div class="address-no-result">
                Unable to search right now. Please try again.
            </div>
            `;

        addressSuggestions.style.display =
            "block";

    }

}



/* =========================================================
   DISPLAY SUGGESTIONS
========================================================= */

function displaySuggestions(
    results
) {

    addressSuggestions.innerHTML = "";


    if (
        !results ||
        results.length === 0
    ) {

        addressSuggestions.innerHTML =
            `
            <div class="address-no-result">
                No location found. Try entering a street, chowk, area or landmark.
            </div>
            `;

        addressSuggestions.style.display =
            "block";

        return;
    }


    results.forEach(
        function (place) {

            const item =
                document.createElement(
                    "div"
                );


            item.className =
                "address-suggestion";


            item.innerHTML =
                `
                <span class="suggestion-icon">
                    📍
                </span>
                ${escapeHTML(
                    place.display_name
                )}
                `;


            item.addEventListener(
                "click",
                function () {

                    selectSuggestion(
                        place
                    );

                }
            );


            addressSuggestions.appendChild(
                item
            );

        }
    );


    addressSuggestions.style.display =
        "block";

}



/* =========================================================
   SELECT SUGGESTION
========================================================= */

function selectSuggestion(
    place
) {

    const latitude =
        parseFloat(
            place.lat
        );

    const longitude =
        parseFloat(
            place.lon
        );


    const address =
        place.display_name;


    setMarker(
        latitude,
        longitude,
        address
    );


    locationSearch.value =
        getShortAddress(place);


    hideSuggestions();

}



/* =========================================================
   SHORT SEARCH ADDRESS
========================================================= */

function getShortAddress(
    place
) {

    const address =
        place.address || {};


    const parts = [];


    if (address.road) {

        parts.push(
            address.road
        );

    }


    if (address.suburb) {

        parts.push(
            address.suburb
        );

    }


    if (address.city) {

        parts.push(
            address.city
        );

    }

    else if (address.town) {

        parts.push(
            address.town
        );

    }

    else if (address.village) {

        parts.push(
            address.village
        );

    }


    return parts.length > 0
        ? parts.join(", ")
        : place.display_name;

}



/* =========================================================
   LOADING SUGGESTIONS
========================================================= */

function showLoading() {

    addressSuggestions.innerHTML =
        `
        <div class="address-loading">
            🔍 Searching locations...
        </div>
        `;

    addressSuggestions.style.display =
        "block";

}



/* =========================================================
   HIDE SUGGESTIONS
========================================================= */

function hideSuggestions() {

    addressSuggestions.style.display =
        "none";

}



/* =========================================================
   SEARCH BUTTON
========================================================= */

searchLocationBtn.addEventListener(
    "click",
    function () {

        const query =
            locationSearch.value.trim();


        if (query.length < 3) {

            return;

        }


        searchAddress(
            query
        );

    }
);



/* =========================================================
   ENTER KEY SEARCH
========================================================= */

locationSearch.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Enter"
        ) {

            event.preventDefault();


            const query =
                this.value.trim();


            if (query.length >= 3) {

                searchAddress(
                    query
                );

            }

        }

    }
);



/* =========================================================
   CURRENT LOCATION
========================================================= */

currentLocationBtn.addEventListener(
    "click",
    function () {

        if (!navigator.geolocation) {

            locationError.textContent =
                "Your browser does not support location.";

            return;
        }


        currentLocationBtn.disabled =
            true;

        currentLocationBtn.innerHTML =
            "📍 Finding your location...";


        navigator.geolocation.getCurrentPosition(

            function (position) {

                const latitude =
                    position.coords.latitude;

                const longitude =
                    position.coords.longitude;


                setMarker(
                    latitude,
                    longitude
                );


                reverseGeocode(
                    latitude,
                    longitude
                );


                currentLocationBtn.disabled =
                    false;

                currentLocationBtn.innerHTML =
                    "📍 Use My Current Location";

            },


            function () {

                locationError.textContent =
                    "Unable to access your current location. Please allow location permission or search manually.";

                currentLocationBtn.disabled =
                    false;

                currentLocationBtn.innerHTML =
                    "📍 Use My Current Location";

            },

            {
                enableHighAccuracy: true,

                timeout: 10000,

                maximumAge: 0
            }

        );

    }
);



/* =========================================================
   CLICK OUTSIDE SUGGESTIONS
========================================================= */

document.addEventListener(
    "click",
    function (event) {

        if (
            !event.target.closest(
                ".location-container"
            )
        ) {

            hideSuggestions();

        }

    }
);



/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHTML(
    text
) {

    const div =
        document.createElement(
            "div"
        );

    div.textContent =
        text;

    return div.innerHTML;

}



/* =========================================================
   FORM SUBMIT VALIDATION
========================================================= */

checkoutForm.addEventListener(
    "submit",
    function (event) {

        const name =
            nameInput.value.trim();

        const phone =
            phoneInput.value.trim();

        const address =
            deliveryAddress.value.trim();


        let valid = true;


        /* NAME */

        if (name === "") {

            nameError.textContent =
                "Full name is required.";

            valid = false;

        }

        else if (name.length < 3) {

            nameError.textContent =
                "Name must contain at least 3 characters.";

            valid = false;

        }

        else if (
            !/^[A-Za-z ]+$/.test(name)
        ) {

            nameError.textContent =
                "Name can contain only letters and spaces.";

            valid = false;

        }

        else {

            nameError.textContent = "";

        }



        /* PHONE */

        if (phone === "") {

            phoneError.textContent =
                "Phone number is required.";

            valid = false;

        }

        else if (
            !/^(97|98)[0-9]{8}$/.test(phone)
        ) {

            phoneError.textContent =
                "Phone number must start with 97 or 98 and contain exactly 10 digits.";

            valid = false;

        }

        else {

            phoneError.textContent = "";

        }



        /* ADDRESS */

        if (address === "") {

            locationError.textContent =
                "Please enter your delivery address.";

            valid = false;

        }

        else {

            locationError.textContent = "";

        }



        /* STOP */

        if (!valid) {

            event.preventDefault();

        }

    }
);

</script>


</body>

</html>