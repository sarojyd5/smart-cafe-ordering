<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireCustomerLogin();

/* =========================================================
   CHECK CART
========================================================= */

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$cart = $_SESSION['cart'];


/* =========================================================
   GET CART ITEMS
========================================================= */

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

    if (!$stmt) {
        continue;
    }

    mysqli_stmt_bind_param($stmt, "i", $food_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $food = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$food) {
        continue;
    }

    /* Do not allow unavailable food */
    if ($food['availability'] !== 'available') {
        continue;
    }

    $item_total = (float)$food['price'] * $quantity;

    $subtotal += $item_total;

    $cart_items[] = [
        'food_id'   => $food['food_id'],
        'food_name' => $food['food_name'],
        'price'     => (float)$food['price'],
        'image'     => $food['image'],
        'quantity'  => $quantity,
        'total'     => $item_total
    ];
}


/* =========================================================
   IF NO VALID CART ITEMS
========================================================= */

if (empty($cart_items)) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");
    exit();
}


/* =========================================================
   TOTAL
========================================================= */

$delivery_charge = 50;

$total_amount = $subtotal + $delivery_charge;


/* =========================================================
   CUSTOMER
========================================================= */

$customer_name = $_SESSION['customer_name'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout | Timeout Cafe</title>


    <!-- Your existing CSS -->
    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <link
        rel="stylesheet"
        href="assets/css/responsive.css"
    >


    <!-- Leaflet CSS -->
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    crossorigin=""
>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin="">
</script>

    <style>

        /* =====================================================
           CHECKOUT PAGE
        ===================================================== */

        .checkout-page {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
            padding: 30px 20px 60px;
            box-sizing: border-box;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .checkout-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .checkout-header p {
            margin: 0 0 6px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #8b4513;
        }

        .checkout-header h1 {
            margin: 0 0 5px;
            font-size: 32px;
            color: #222;
        }

        .checkout-header span {
            color: #777;
            font-size: 15px;
        }


        /* =====================================================
           BACK BUTTON
        ===================================================== */

        .checkout-back-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 11px 18px;

            border-radius: 8px;

            text-decoration: none;

            background: #f5f5f5;

            color: #333;

            font-weight: 600;

            transition: 0.2s ease;
        }

        .checkout-back-btn:hover {
            background: #e8e8e8;
        }


        /* =====================================================
           MAIN LAYOUT
        ===================================================== */

        .checkout-layout {
            display: grid;

            grid-template-columns:
                minmax(0, 1.5fr)
                minmax(300px, 0.8fr);

            gap: 25px;

            align-items: start;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .checkout-form-card,
        .checkout-summary-card {

            background: #ffffff;

            border-radius: 14px;

            padding: 25px;

            box-shadow:
                0 5px 25px rgba(0, 0, 0, 0.08);

            box-sizing: border-box;
        }

        .checkout-form-card h2,
        .checkout-summary-card h2 {

            margin: 0 0 25px;

            font-size: 22px;

            color: #222;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .checkout-form-group {

            margin-bottom: 22px;

            position: relative;
        }

        .checkout-form-group label {

            display: block;

            margin-bottom: 8px;

            font-weight: 700;

            font-size: 15px;

            color: #333;
        }

        .checkout-form-group label span {

            font-weight: 400;

            color: #777;
        }


        .checkout-form-group input[type="text"],
        .checkout-form-group input[type="tel"],
        .checkout-form-group textarea {

            width: 100%;

            box-sizing: border-box;

            border: 1px solid #d5d5d5;

            border-radius: 8px;

            padding: 13px 14px;

            font-size: 15px;

            font-family: inherit;

            outline: none;

            background: #fff;

            color: #333;

            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }


        .checkout-form-group input:focus,
        .checkout-form-group textarea:focus {

            border-color: #8b4513;

            box-shadow:
                0 0 0 3px
                rgba(139, 69, 19, 0.10);
        }


        .checkout-form-group textarea {

            resize: vertical;

            min-height: 100px;
        }


        /* =====================================================
           MAP
        ===================================================== */

        .map-wrapper {

            position: relative;

            width: 100%;

            border-radius: 12px;

            overflow: hidden;

            border: 1px solid #ddd;

            background: #f3f3f3;
        }


        #delivery-map {

            width: 100%;

            height: 350px !important;

            min-height: 350px;

            z-index: 1;
        }


        .leaflet-container {

            width: 100%;

            height: 100%;

            font-family: inherit;
        }


        /* =====================================================
           MAP SEARCH
        ===================================================== */

        .map-search-box {

            position: absolute;

            top: 12px;

            left: 12px;

            right: 12px;

            z-index: 1000;

            display: flex;

            gap: 8px;
        }


        #map-search-input {

            flex: 1;

            min-width: 0;

            height: 44px;

            padding: 0 14px;

            border: 1px solid #ddd;

            border-radius: 8px;

            background: white;

            font-size: 14px;

            outline: none;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.15);

            box-sizing: border-box;
        }


        #map-search-input:focus {

            border-color: #8b4513;
        }


        #map-search-btn {

            width: 46px;

            height: 44px;

            flex-shrink: 0;

            border: none;

            border-radius: 8px;

            background: #8b4513;

            color: white;

            font-size: 18px;

            cursor: pointer;

            box-shadow:
                0 2px 8px rgba(0,0,0,0.15);

            transition: 0.2s ease;
        }


        #map-search-btn:hover {

            background: #6f350e;
        }


        #map-search-btn:active {

            transform: scale(0.97);
        }


        /* =====================================================
           MAP SEARCH RESULTS
        ===================================================== */

        .map-search-results {

            position: absolute;

            top: 52px;

            left: 0;

            right: 54px;

            max-height: 220px;

            overflow-y: auto;

            background: white;

            border-radius: 8px;

            box-shadow:
                0 5px 18px rgba(0,0,0,0.18);

            z-index: 1001;
        }


        .map-search-result-item {

            padding: 12px 14px;

            border-bottom: 1px solid #eee;

            font-size: 13px;

            line-height: 1.4;

            cursor: pointer;

            color: #333;

            background: white;
        }


        .map-search-result-item:last-child {

            border-bottom: none;
        }


        .map-search-result-item:hover {

            background: #f7f7f7;
        }


        /* =====================================================
           SELECTED ADDRESS
        ===================================================== */

        .selected-address-display {

            display: flex;

            align-items: flex-start;

            gap: 8px;

            margin-top: 10px;

            padding: 10px 12px;

            border-radius: 8px;

            background: #f8f4f0;

            color: #5c351d;

            font-size: 13px;

            line-height: 1.5;
        }


        .map-icon {

            flex-shrink: 0;
        }


        /* =====================================================
           CURRENT LOCATION
        ===================================================== */

        .use-current-location-btn {

            width: 100%;

            margin-top: 12px;

            padding: 12px 15px;

            border: 1px solid #8b4513;

            border-radius: 8px;

            background: #fff;

            color: #8b4513;

            font-size: 14px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .use-current-location-btn:hover {

            background: #8b4513;

            color: white;
        }


        .use-current-location-btn:disabled {

            opacity: 0.6;

            cursor: not-allowed;
        }


        /* =====================================================
           ADDRESS SUGGESTIONS
        ===================================================== */

        .address-suggest-wrapper {

            position: relative;

            margin-top: 12px;
        }


        .address-suggest-results {

            position: absolute;

            left: 0;

            right: 0;

            top: 100%;

            margin-top: 5px;

            background: white;

            border-radius: 8px;

            box-shadow:
                0 5px 18px rgba(0,0,0,0.15);

            max-height: 220px;

            overflow-y: auto;

            z-index: 2000;
        }


        .address-suggest-item {

            padding: 12px 14px;

            border-bottom: 1px solid #eee;

            font-size: 13px;

            line-height: 1.4;

            cursor: pointer;
        }


        .address-suggest-item:hover {

            background: #f7f7f7;
        }


        /* =====================================================
           ERROR
        ===================================================== */

        .field-error {

            display: block;

            margin-top: 6px;

            min-height: 18px;

            font-size: 12px;

            color: #d93025;
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

            gap: 12px;

            padding: 14px;

            border: 1px solid #ddd;

            border-radius: 10px;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .payment-method-card:hover {

            border-color: #8b4513;

            background: #faf7f4;
        }


        .payment-method-card input {

            flex-shrink: 0;
        }


        .payment-method-card span {

            display: flex;

            flex-direction: column;

            gap: 3px;
        }


        .payment-method-card strong {

            font-size: 14px;
        }


        .payment-method-card small {

            color: #777;
        }


        /* =====================================================
           PLACE ORDER
        ===================================================== */

        .place-order-btn {

            width: 100%;

            padding: 15px;

            border: none;

            border-radius: 9px;

            background: #8b4513;

            color: white;

            font-size: 16px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s ease;
        }


        .place-order-btn:hover {

            background: #6f350e;
        }


        /* =====================================================
           ORDER SUMMARY
        ===================================================== */

        .checkout-summary-card {

            position: sticky;

            top: 20px;
        }


        .checkout-items {

            display: flex;

            flex-direction: column;

            gap: 14px;

            margin-bottom: 20px;
        }


        .checkout-item {

            display: flex;

            justify-content: space-between;

            gap: 15px;

            padding-bottom: 14px;

            border-bottom: 1px solid #eee;
        }


        .checkout-item > div {

            display: flex;

            flex-direction: column;

            gap: 5px;
        }


        .checkout-item strong {

            font-size: 14px;
        }


        .checkout-item span {

            font-size: 13px;

            color: #777;
        }


        .checkout-total-row {

            display: flex;

            justify-content: space-between;

            padding: 9px 0;

            font-size: 14px;
        }


        .checkout-grand-total {

            display: flex;

            justify-content: space-between;

            margin-top: 12px;

            padding-top: 16px;

            border-top: 2px solid #ddd;

            font-size: 19px;

            font-weight: 700;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 900px) {

            .checkout-layout {

                grid-template-columns: 1fr;
            }

            .checkout-summary-card {

                position: static;
            }
        }


        @media (max-width: 600px) {

            .checkout-page {

                padding:
                    20px
                    12px
                    40px;
            }


            .checkout-header {

                flex-direction: column;

                align-items: flex-start;
            }


            .checkout-header h1 {

                font-size: 27px;
            }


            .checkout-back-btn {

                width: 100%;

                box-sizing: border-box;
            }


            .checkout-form-card,
            .checkout-summary-card {

                padding: 18px;

                border-radius: 10px;
            }


            #delivery-map {

                height: 300px !important;

                min-height: 300px;
            }


            .map-search-box {

                top: 8px;

                left: 8px;

                right: 8px;
            }


            #map-search-input {

                height: 42px;
            }


            #map-search-btn {

                width: 42px;

                height: 42px;
            }


            .map-search-results {

                top: 50px;

                right: 50px;
            }


            .checkout-item {

                flex-direction: column;

                gap: 6px;
            }

        }

    </style>

</head>


<body>


<main class="checkout-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <section class="checkout-header">

        <div>

            <p>TIMEOUT CAFE</p>

            <h1>Checkout</h1>

            <span>
                Complete your order details.
            </span>

        </div>


        <a
            href="cart.php"
            class="checkout-back-btn"
        >
            ← Back to Cart
        </a>

    </section>



    <!-- =====================================================
         CHECKOUT LAYOUT
    ====================================================== -->

    <section class="checkout-layout">


        <!-- =================================================
             CUSTOMER FORM
        ================================================== -->

        <div class="checkout-form-card">

            <h2>
                Delivery Information
            </h2>


            <form
                method="POST"
                action="place_order.php"
                id="checkoutForm"
            >


                <!-- =========================================
                     NAME
                ========================================== -->

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

                        placeholder="Enter your full name"

                        autocomplete="name"
                    >


                    <small
                        id="name-error"
                        class="field-error"
                    ></small>

                </div>



                <!-- =========================================
                     PHONE
                ========================================== -->

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

                        placeholder="98XXXXXXXX"

                        autocomplete="tel"
                    >


                    <small
                        id="phone-error"
                        class="field-error"
                    ></small>

                </div>



                <!-- =========================================
                     DELIVERY ADDRESS
                ========================================== -->

                <div class="checkout-form-group">

                    <label for="delivery_address">
                        Delivery Address*
                    </label>


                    <!-- MAP -->

                    <div class="map-wrapper">

                        <div
                            id="delivery-map"
                        ></div>


                        <!-- MAP SEARCH -->

                        <div class="map-search-box">

                            <input
                                type="text"
                                id="map-search-input"

                                placeholder="Search delivery location..."

                                autocomplete="off"
                            >


                            <button
                                type="button"
                                id="map-search-btn"

                                title="Search"
                            >
                                🔍
                            </button>


                            <div
                                id="map-search-results"

                                class="map-search-results"

                                style="display:none;"
                            ></div>

                        </div>

                    </div>


                    <!-- SELECTED ADDRESS -->

                    <div
                        id="selected-address-display"

                        class="selected-address-display"

                        style="display:none;"
                    >

                        <span class="map-icon">
                            📍
                        </span>

                        <span
                            id="selected-address-text"
                        ></span>

                    </div>


                    <!-- CURRENT LOCATION -->

                    <button
                        type="button"

                        id="locate-current-btn"

                        class="use-current-location-btn"
                    >

                        🧭
                        Use My Current Location

                    </button>


                    <!-- ADDRESS TEXT -->

                    <div class="address-suggest-wrapper">

                        <textarea
                            id="delivery_address"

                            name="delivery_address"

                            rows="3"

                            maxlength="500"

                            required

                            placeholder="Enter your complete delivery address (e.g. street, city)"
                        ></textarea>


                        <div
                            id="address-suggest-results"

                            class="address-suggest-results"

                            style="display:none;"
                        ></div>

                    </div>


                    <small
                        id="address-error"
                        class="field-error"
                    ></small>

                </div>



                <!-- =========================================
                     HIDDEN LOCATION DATA
                ========================================== -->

                <input
                    type="hidden"

                    id="delivery_map_link"

                    name="delivery_map_link"
                >


                <input
                    type="hidden"

                    id="delivery_lat"

                    name="delivery_lat"
                >


                <input
                    type="hidden"

                    id="delivery_lng"

                    name="delivery_lng"
                >



                <!-- =========================================
                     ORDER NOTE
                ========================================== -->

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

                        placeholder="Any special instructions?"
                    ></textarea>

                </div>



                <!-- =========================================
                     PAYMENT
                ========================================== -->

                <div class="checkout-form-group">

                    <label>
                        Payment Method
                    </label>


                    <div class="payment-methods">


                        <label class="payment-method-card">

                            <input
                                type="radio"

                                name="payment_method"

                                value="Cash on Delivery"

                                checked
                            >


                            <span>

                                <strong>
                                    Cash on Delivery
                                </strong>

                                <small>
                                    Pay when your food arrives.
                                </small>

                            </span>

                        </label>



                        <label class="payment-method-card">

                            <input
                                type="radio"

                                name="payment_method"

                                value="eSewa"
                            >


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



                <!-- =========================================
                     PLACE ORDER
                ========================================== -->

                <button
                    type="submit"

                    class="place-order-btn"

                    id="placeOrderBtn"
                >
                    Place Order
                </button>


            </form>

        </div>



        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <div class="checkout-summary-card">

            <h2>
                Your Order
            </h2>


            <div class="checkout-items">


                <?php foreach ($cart_items as $item): ?>

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

const addressInput =
    document.getElementById("delivery_address");

const addressError =
    document.getElementById("address-error");

const locateBtn =
    document.getElementById("locate-current-btn");

const mapSearchInput =
    document.getElementById("map-search-input");

const mapSearchBtn =
    document.getElementById("map-search-btn");

const mapSearchResults =
    document.getElementById("map-search-results");

const addressSuggestResults =
    document.getElementById("address-suggest-results");

const mapLinkInput =
    document.getElementById("delivery_map_link");

const latInput =
    document.getElementById("delivery_lat");

const lngInput =
    document.getElementById("delivery_lng");

const addressDisplay =
    document.getElementById(
        "selected-address-display"
    );

const addressDisplayText =
    document.getElementById(
        "selected-address-text"
    );


/* =========================================================
   MAP VARIABLES
========================================================= */

let deliveryMap = null;

let deliveryMarker = null;

let skipAddressSearch = false;

let addressSearchTimer = null;

let mapSearchTimer = null;


/* =========================================================
   CREATE MAP
========================================================= */

window.addEventListener("load", function () {

    const mapElement =
        document.getElementById(
            "delivery-map"
        );

    if (!mapElement) {
        return;
    }


    /*
     * Kathmandu default location
     */
    deliveryMap =
        L.map("delivery-map")
            .setView(
                [27.7172, 85.3240],
                13
            );


    /*
     * OpenStreetMap
     */
    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 19,

            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(deliveryMap);


    /*
     * Fix map size after page loading
     */
    setTimeout(function () {

        deliveryMap.invalidateSize();

    }, 300);


    /*
     * Ask browser for current location
     * only to center map.
     *
     * It does NOT automatically submit location.
     */

    if (navigator.geolocation) {

        navigator.geolocation.getCurrentPosition(

            function (position) {

                const lat =
                    position.coords.latitude;

                const lng =
                    position.coords.longitude;

                deliveryMap.setView(
                    [lat, lng],
                    15
                );

            },

            function () {
                /*
                 * Ignore if permission denied.
                 */
            }
        );

    }


    /* =====================================================
       CLICK MAP
    ===================================================== */

    deliveryMap.on(
        "click",
        function (event) {

            const lat =
                event.latlng.lat;

            const lng =
                event.latlng.lng;


            setDeliveryLocation(
                lat,
                lng,
                true
            );

        }
    );

});


/* =========================================================
   SET DELIVERY LOCATION
========================================================= */

function setDeliveryLocation(
    lat,
    lng,
    reverseGeocode = true
) {

    if (!deliveryMap) {
        return;
    }


    latInput.value =
        Number(lat).toFixed(7);

    lngInput.value =
        Number(lng).toFixed(7);


    /*
     * Google Maps link
     */

    mapLinkInput.value =
        "https://www.google.com/maps/search/?api=1&query="
        + lat
        + ","
        + lng;


    /*
     * Remove old marker
     */

    if (deliveryMarker) {

        deliveryMap.removeLayer(
            deliveryMarker
        );

    }


    /*
     * Create new draggable marker
     */

    deliveryMarker =
        L.marker(
            [lat, lng],
            {
                draggable: true
            }
        ).addTo(deliveryMap);


    deliveryMarker
        .bindPopup(
            "Delivery Location"
        )
        .openPopup();


    /*
     * Marker dragged
     */

    deliveryMarker.on(
        "dragend",
        function () {

            const position =
                deliveryMarker.getLatLng();

            setDeliveryLocation(
                position.lat,
                position.lng,
                true
            );

        }
    );


    /*
     * Center map
     */

    deliveryMap.setView(
        [lat, lng],
        16
    );


    /*
     * Reverse geocode
     */

    if (reverseGeocode) {

        reverseGeocodeLocation(
            lat,
            lng
        );

    }

}


/* =========================================================
   REVERSE GEOCODING
   COORDINATES -> ADDRESS
========================================================= */

function reverseGeocodeLocation(
    lat,
    lng
) {

    const url =
        "https://nominatim.openstreetmap.org/reverse"
        + "?format=json"
        + "&lat="
        + encodeURIComponent(lat)
        + "&lon="
        + encodeURIComponent(lng)
        + "&addressdetails=1";


    fetch(url)

        .then(function (response) {

            return response.json();

        })

        .then(function (data) {

            if (
                data &&
                data.display_name
            ) {

                const address =
                    data.display_name;


                skipAddressSearch =
                    true;


                addressInput.value =
                    address;


                showSelectedAddress(
                    address
                );

            }

        })

        .catch(function () {

            console.log(
                "Unable to get address."
            );

        });

}


/* =========================================================
   SHOW SELECTED ADDRESS
========================================================= */

function showSelectedAddress(
    address
) {

    addressDisplay.style.display =
        "flex";

    addressDisplayText.textContent =
        address;

}


/* =========================================================
   MAP SEARCH
========================================================= */

function searchMapLocations(
    query
) {

    query = query.trim();


    if (query.length < 3) {

        mapSearchResults.style.display =
            "none";

        return;

    }


    /*
     * countrycodes=np
     *
     * This focuses results on Nepal
     * and still allows street/small-location
     * searches.
     */

    const url =
        "https://nominatim.openstreetmap.org/search"
        + "?format=json"
        + "&q="
        + encodeURIComponent(query)
        + "&limit=7"
        + "&addressdetails=1"
        + "&countrycodes=np";


    mapSearchResults.innerHTML =
        "<div class='map-search-result-item'>Searching...</div>";

    mapSearchResults.style.display =
        "block";


    fetch(url)

        .then(function (response) {

            return response.json();

        })

        .then(function (data) {

            mapSearchResults.innerHTML = "";


            if (
                !data ||
                data.length === 0
            ) {

                mapSearchResults.innerHTML =
                    "<div class='map-search-result-item'>No location found.</div>";

                return;

            }


            data.forEach(function (item) {

                const result =
                    document.createElement(
                        "div"
                    );

                result.className =
                    "map-search-result-item";


                result.textContent =
                    item.display_name;


                result.dataset.lat =
                    item.lat;

                result.dataset.lng =
                    item.lon;

                result.dataset.name =
                    item.display_name;


                result.addEventListener(
                    "click",
                    function () {

                        const lat =
                            parseFloat(
                                this.dataset.lat
                            );

                        const lng =
                            parseFloat(
                                this.dataset.lng
                            );

                        const name =
                            this.dataset.name;


                        mapSearchInput.value =
                            name;


                        mapSearchResults.style.display =
                            "none";


                        setDeliveryLocation(
                            lat,
                            lng,
                            false
                        );


                        skipAddressSearch =
                            true;


                        addressInput.value =
                            name;


                        showSelectedAddress(
                            name
                        );

                    }
                );


                mapSearchResults.appendChild(
                    result
                );

            });

        })

        .catch(function () {

            mapSearchResults.innerHTML =
                "<div class='map-search-result-item'>Search failed. Please try again.</div>";

        });

}


/* =========================================================
   MAP SEARCH INPUT
========================================================= */

mapSearchInput.addEventListener(
    "input",
    function () {

        clearTimeout(
            mapSearchTimer
        );


        const query =
            this.value.trim();


        mapSearchTimer =
            setTimeout(
                function () {

                    searchMapLocations(
                        query
                    );

                },
                500
            );

    }
);


/* =========================================================
   MAP SEARCH BUTTON
========================================================= */

mapSearchBtn.addEventListener(
    "click",
    function () {

        clearTimeout(
            mapSearchTimer
        );


        searchMapLocations(
            mapSearchInput.value
        );

    }
);


/* =========================================================
   ENTER KEY IN MAP SEARCH
========================================================= */

mapSearchInput.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Enter"
        ) {

            event.preventDefault();


            clearTimeout(
                mapSearchTimer
            );


            searchMapLocations(
                this.value
            );

        }

    }
);


/* =========================================================
   CURRENT LOCATION
========================================================= */

locateBtn.addEventListener(
    "click",
    function () {

        if (
            !navigator.geolocation
        ) {

            alert(
                "Geolocation is not supported by your browser."
            );

            return;

        }


        const originalText =
            locateBtn.innerHTML;


        locateBtn.disabled =
            true;

        locateBtn.innerHTML =
            "📍 Locating...";


        navigator.geolocation.getCurrentPosition(

            function (position) {

                const lat =
                    position.coords.latitude;

                const lng =
                    position.coords.longitude;


                setDeliveryLocation(
                    lat,
                    lng,
                    true
                );


                locateBtn.disabled =
                    false;

                locateBtn.innerHTML =
                    originalText;

            },


            function () {

                locateBtn.disabled =
                    false;

                locateBtn.innerHTML =
                    originalText;


                alert(
                    "Unable to get your location. Please allow location permission and try again."
                );

            },


            {
                enableHighAccuracy: true,

                timeout: 15000,

                maximumAge: 60000
            }

        );

    }
);


/* =========================================================
   ADDRESS TEXT SEARCH
========================================================= */

function searchAddressSuggestions(
    query
) {

    query = query.trim();


    if (query.length < 3) {

        addressSuggestResults.style.display =
            "none";

        return;

    }


    const url =
        "https://nominatim.openstreetmap.org/search"
        + "?format=json"
        + "&q="
        + encodeURIComponent(query)
        + "&limit=5"
        + "&addressdetails=1"
        + "&countrycodes=np";


    fetch(url)

        .then(function (response) {

            return response.json();

        })

        .then(function (data) {

            addressSuggestResults.innerHTML =
                "";


            if (
                !data ||
                data.length === 0
            ) {

                addressSuggestResults.style.display =
                    "none";

                return;

            }


            data.forEach(function (item) {

                const result =
                    document.createElement(
                        "div"
                    );


                result.className =
                    "address-suggest-item";


                result.textContent =
                    item.display_name;


                result.addEventListener(
                    "click",
                    function () {

                        const lat =
                            parseFloat(
                                item.lat
                            );

                        const lng =
                            parseFloat(
                                item.lon
                            );


                        const name =
                            item.display_name;


                        addressInput.value =
                            name;


                        addressSuggestResults.style.display =
                            "none";


                        skipAddressSearch =
                            true;


                        setDeliveryLocation(
                            lat,
                            lng,
                            false
                        );


                        showSelectedAddress(
                            name
                        );

                    }
                );


                addressSuggestResults.appendChild(
                    result
                );

            });


            addressSuggestResults.style.display =
                "block";

        })

        .catch(function () {

            addressSuggestResults.style.display =
                "none";

        });

}


/* =========================================================
   ADDRESS TEXTAREA
========================================================= */

addressInput.addEventListener(
    "input",
    function () {

        if (skipAddressSearch) {

            skipAddressSearch =
                false;

            return;

        }


        clearTimeout(
            addressSearchTimer
        );


        const query =
            this.value.trim();


        if (query.length < 3) {

            addressSuggestResults.style.display =
                "none";

            return;

        }


        addressSearchTimer =
            setTimeout(
                function () {

                    searchAddressSuggestions(
                        query
                    );

                },
                600
            );

    }
);


/* =========================================================
   CLOSE SEARCH RESULTS WHEN CLICKING OUTSIDE
========================================================= */

document.addEventListener(
    "click",
    function (event) {

        if (
            !event.target.closest(
                ".map-search-box"
            )
        ) {

            mapSearchResults.style.display =
                "none";

        }


        if (
            !event.target.closest(
                ".address-suggest-wrapper"
            )
        ) {

            addressSuggestResults.style.display =
                "none";

        }

    }
);


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

            nameError.textContent =
                "";

            return;

        }


        if (name.length < 3) {

            nameError.textContent =
                "Name must contain at least 3 characters.";

            return;

        }


        nameError.textContent =
            "";

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

            phoneError.textContent =
                "";

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


        if (
            phone.length < 10
        ) {

            phoneError.textContent =
                "Phone number must contain exactly 10 digits.";

            return;

        }


        phoneError.textContent =
            "";

    }
);


/* =========================================================
   FORM SUBMIT VALIDATION
========================================================= */

checkoutForm.addEventListener(
    "submit",
    function (event) {

        let valid = true;


        /* NAME */

        const name =
            nameInput.value.trim();


        if (name === "") {

            nameError.textContent =
                "Full name is required.";

            valid = false;

        }

        else if (
            name.length < 3
        ) {

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

            nameError.textContent =
                "";

        }


        /* PHONE */

        const phone =
            phoneInput.value.trim();


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

            phoneError.textContent =
                "";

        }


        /* ADDRESS */

        const address =
            addressInput.value.trim();


        if (address === "") {

            addressError.textContent =
                "Delivery address is required.";

            valid = false;

        }

        else if (
            address.length < 5
        ) {

            addressError.textContent =
                "Please enter a complete delivery address.";

            valid = false;

        }

        else {

            addressError.textContent =
                "";

        }


        /* STOP SUBMISSION */

        if (!valid) {

            event.preventDefault();

            return false;

        }

    }
);

</script>


</body>

</html>