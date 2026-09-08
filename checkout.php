<?php

require_once "includes/session.php";
require_once "includes/db.php";
require_once "includes/functions.php";

requireCustomerLogin();


// --------------------------------------------------
// CHECK CART
// --------------------------------------------------

if (
    !isset($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {

    header("Location: cart.php");
    exit();

}

$cart = $_SESSION['cart'];


// --------------------------------------------------
// GET CART ITEMS
// --------------------------------------------------

$cart_items = [];
$subtotal = 0;


foreach ($cart as $food_id => $quantity) {

    $food_id = (int) $food_id;
    $quantity = (int) $quantity;

    if (
        $food_id <= 0 ||
        $quantity <= 0
    ) {
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


    $stmt = mysqli_prepare(
        $conn,
        $sql
    );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $food_id
    );


    mysqli_stmt_execute($stmt);


    $result =
        mysqli_stmt_get_result($stmt);


    $food =
        mysqli_fetch_assoc($result);


    if (!$food) {
        continue;
    }


    // Don't allow unavailable food

    if (
        $food['availability'] !== 'available'
    ) {
        continue;
    }


    $item_total =
        (float) $food['price'] * $quantity;


    $subtotal += $item_total;


    $cart_items[] = [

        'food_id' =>
            $food['food_id'],

        'food_name' =>
            $food['food_name'],

        'price' =>
            (float) $food['price'],

        'image' =>
            $food['image'],

        'quantity' =>
            $quantity,

        'total' =>
            $item_total

    ];

}


// --------------------------------------------------
// IF NO VALID ITEMS
// --------------------------------------------------

if (empty($cart_items)) {

    $_SESSION['cart'] = [];

    header("Location: cart.php");

    exit();

}


// --------------------------------------------------
// DELIVERY CHARGE
// --------------------------------------------------

$delivery_charge = 50;


$total_amount =
    $subtotal + $delivery_charge;


// --------------------------------------------------
// CUSTOMER
// --------------------------------------------------

$customer_name =
    $_SESSION['customer_name'] ?? '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Checkout | Timeout Cafe
    </title>


    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <!-- RESPONSIVE CSS -->

    <link
        rel="stylesheet"
        href="assets/css/responsive.css"
    >


    <!-- LEAFLET CSS -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >


    <style>

        /* =========================================
           DELIVERY LOCATION
        ========================================= */

        .location-container {

            width: 100%;

        }


        /* MAP SEARCH AREA */

        .map-search-wrapper {

            position: relative;

            width: 100%;

            z-index: 1000;

        }


        .map-search-box {

            position: absolute;

            top: 10px;

            left: 10px;

            right: 10px;

            z-index: 1000;

            display: flex;

            gap: 8px;

        }


        .map-search-box input {

            flex: 1;

            padding: 13px 14px;

            border: none;

            border-radius: 7px;

            background: #ffffff;

            box-shadow:
                0 2px 10px
                rgba(0,0,0,0.18);

            font-family: inherit;

            font-size: 14px;

            outline: none;

        }


        .map-search-box button {

            width: 55px;

            border: none;

            border-radius: 7px;

            background: #b85c38;

            color: white;

            font-size: 18px;

            cursor: pointer;

            box-shadow:
                0 2px 10px
                rgba(0,0,0,0.18);

        }


        .map-search-box button:hover {

            background: #8f452c;

        }


        /* MAP */

        #delivery-map {

            width: 100%;

            height: 360px;

            border-radius: 8px;

            border: 1px solid #ddd;

            overflow: hidden;

            z-index: 1;

        }


        /* SUGGESTIONS */

        .location-suggestions {

            position: absolute;

            top: 58px;

            left: 10px;

            right: 70px;

            background: white;

            border-radius: 7px;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.15);

            overflow: hidden;

            display: none;

            z-index: 2000;

        }


        .location-suggestion {

            padding: 12px 14px;

            border-bottom: 1px solid #eee;

            cursor: pointer;

            font-size: 13px;

            line-height: 1.4;

            color: #3f3029;

            background: #ffffff;

        }


        .location-suggestion:last-child {

            border-bottom: none;

        }


        .location-suggestion:hover {

            background: #f8f1ec;

        }


        .location-loading {

            padding: 12px 14px;

            font-size: 13px;

            color: #777;

            background: white;

        }


        .location-message {

            margin-top: 7px;

            color: #777;

            font-size: 12px;

            line-height: 1.5;

        }


        /* SELECTED LOCATION */

        .selected-location {

            margin-top: 10px;

            padding: 10px 12px;

            background: #f8f1ec;

            border-left: 3px solid #b85c38;

            border-radius: 5px;

            color: #5c3d2e;

            font-size: 13px;

            display: none;

        }


        /* ADDRESS TEXTAREA */

        #delivery_address {

            margin-top: 12px;

        }


        /* ERRORS */

        .field-error {

            display: block;

            margin-top: 6px;

            color: #d93025;

            font-size: 12px;

        }


        /* =========================================
           MOBILE
        ========================================= */

        @media (max-width: 600px) {

            #delivery-map {

                height: 300px;

            }


            .map-search-box {

                left: 8px;

                right: 8px;

                top: 8px;

            }


            .map-search-box input {

                font-size: 13px;

                padding: 11px;

            }


            .map-search-box button {

                width: 48px;

            }


            .location-suggestions {

                top: 52px;

                left: 8px;

                right: 64px;

            }

        }

    </style>

</head>


<body>


<main class="checkout-page">


    <!-- =========================================
         HEADER
    ========================================== -->

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
            class="checkout-back-btn"
        >

            ← Back to Cart

        </a>

    </section>



    <!-- =========================================
         CHECKOUT CONTENT
    ========================================== -->

    <section class="checkout-layout">


        <!-- =====================================
             CUSTOMER FORM
        ====================================== -->

        <div class="checkout-form-card">


            <h2>
                Delivery Information
            </h2>


            <form
                method="POST"
                action="place_order.php"
                id="checkoutForm"
            >


                <!-- =================================
                     FULL NAME
                ================================== -->

                <div class="checkout-form-group">

                    <label
                        for="customer_name"
                    >

                        Full Name*

                    </label>


                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        value="<?php
                        echo escape(
                            $customer_name
                        );
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



                <!-- =================================
                     PHONE
                ================================== -->

                <div class="checkout-form-group">

                    <label
                        for="customer_phone"
                    >

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



                <!-- =================================
                     DELIVERY LOCATION
                ================================== -->

                <div class="checkout-form-group">

                    <label>

                        Delivery Location*

                    </label>


                    <div class="location-container">


                        <!-- MAP -->

                        <div
                            class="map-search-wrapper"
                        >


                            <!-- SEARCH -->

                            <div
                                class="map-search-box"
                            >

                                <input
                                    type="text"
                                    id="location-search"
                                    placeholder="Search delivery location..."
                                    autocomplete="off"
                                >


                                <button
                                    type="button"
                                    id="search-location-btn"
                                    title="Search"
                                >

                                    🔍

                                </button>


                            </div>


                            <!-- SUGGESTIONS -->

                            <div
                                id="location-suggestions"
                                class="location-suggestions"
                            ></div>


                            <!-- MAP -->

                            <div
                                id="delivery-map"
                            ></div>

                        </div>


                        <!-- SELECTED LOCATION -->

                        <div
                            id="selected-location"
                            class="selected-location"
                        ></div>


                        <!-- ACTUAL FORM ADDRESS -->

                        <textarea
                            id="delivery_address"
                            name="delivery_address"
                            rows="3"
                            maxlength="500"
                            required
                            placeholder="Enter your complete delivery address"
                        ></textarea>


                        <small
                            class="location-message"
                        >

                            Search for your location above
                            and select a suggestion.
                            The selected address will
                            automatically appear here.

                        </small>


                        <small
                            id="address-error"
                            class="field-error"
                        ></small>


                    </div>

                </div>



                <!-- =================================
                     ORDER NOTE
                ================================== -->

                <div class="checkout-form-group">

                    <label
                        for="order_note"
                    >

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



                <!-- =================================
                     PAYMENT METHOD
                ================================== -->

                <div class="checkout-form-group">

                    <label>

                        Payment Method

                    </label>


                    <div class="payment-methods">


                        <!-- CASH -->

                        <label
                            class="payment-method-card"
                        >

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



                        <!-- ESEWA -->

                        <label
                            class="payment-method-card"
                        >

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



                <!-- =================================
                     PLACE ORDER
                ================================== -->

                <button
                    type="submit"
                    class="place-order-btn"
                    id="placeOrderBtn"
                >

                    Place Order

                </button>


            </form>


        </div>



        <!-- =====================================
             ORDER SUMMARY
        ====================================== -->

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



<!-- =========================================
     LEAFLET JS
========================================= -->

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>



<script>

/*
|==================================================
| MAP SETTINGS
|==================================================
*/


const map =
    L.map("delivery-map")
    .setView(
        [27.7172, 85.3240],
        13
    );


/*
|--------------------------------------------------
| OPENSTREETMAP TILES
|--------------------------------------------------
*/

L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    {

        maxZoom: 19,

        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'

    }
).addTo(map);



/*
|==================================================
| VARIABLES
|==================================================
*/


let marker = null;

let searchTimer = null;

let lastQuery = "";

let requestInProgress = false;



const searchInput =
    document.getElementById(
        "location-search"
    );


const searchButton =
    document.getElementById(
        "search-location-btn"
    );


const suggestionsBox =
    document.getElementById(
        "location-suggestions"
    );


const addressInput =
    document.getElementById(
        "delivery_address"
    );


const selectedLocation =
    document.getElementById(
        "selected-location"
    );


const addressError =
    document.getElementById(
        "address-error"
    );



/*
|==================================================
| ADD MARKER
|==================================================
*/


function setLocation(
    latitude,
    longitude,
    address
) {


    /*
     * Remove old marker
     */

    if (marker) {

        map.removeLayer(marker);

    }


    /*
     * Create marker
     */

    marker =
        L.marker([
            latitude,
            longitude
        ])
        .addTo(map);


    /*
     * Center map
     */

    map.setView(
        [
            latitude,
            longitude
        ],
        16
    );


    /*
     * Address field
     */

    addressInput.value =
        address;


    /*
     * Selected location display
     */

    selectedLocation.textContent =
        "📍 " + address;


    selectedLocation.style.display =
        "block";


    /*
     * Remove error
     */

    addressError.textContent = "";



    /*
     * Close suggestions
     */

    suggestionsBox.style.display =
        "none";


}



/*
|==================================================
| SHOW SUGGESTIONS
|==================================================
*/


function showSuggestions(
    results
) {


    suggestionsBox.innerHTML =
        "";


    if (
        !results ||
        results.length === 0
    ) {

        suggestionsBox.innerHTML =
            '<div class="location-loading">' +
            'No locations found.' +
            '</div>';

        suggestionsBox.style.display =
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
                "location-suggestion";


            item.textContent =
                place.display_name;


            item.addEventListener(
                "click",
                function () {


                    const latitude =
                        parseFloat(
                            place.lat
                        );


                    const longitude =
                        parseFloat(
                            place.lon
                        );


                    setLocation(
                        latitude,
                        longitude,
                        place.display_name
                    );


                    searchInput.value =
                        place.display_name;


                }
            );


            suggestionsBox.appendChild(
                item
            );

        }
    );


    suggestionsBox.style.display =
        "block";

}



/*
|==================================================
| SEARCH LOCATION
|==================================================
*/


async function searchLocation(
    query
) {


    query =
        query.trim();


    /*
     * Minimum characters
     */

    if (query.length < 3) {

        suggestionsBox.style.display =
            "none";

        return;

    }


    /*
     * Avoid duplicate request
     */

    if (
        query === lastQuery ||
        requestInProgress
    ) {

        return;

    }


    lastQuery =
        query;


    requestInProgress =
        true;


    suggestionsBox.innerHTML =
        '<div class="location-loading">' +
        'Searching locations...' +
        '</div>';


    suggestionsBox.style.display =
        "block";


    try {


        /*
         * OpenStreetMap Nominatim
         *
         * countrycodes=np
         * restricts results to Nepal.
         */

        const url =
            "https://nominatim.openstreetmap.org/search?" +
            "format=jsonv2" +
            "&addressdetails=1" +
            "&limit=5" +
            "&countrycodes=np" +
            "&q=" +
            encodeURIComponent(query);


        const response =
            await fetch(url);


        if (!response.ok) {

            throw new Error(
                "Location search failed"
            );

        }


        const results =
            await response.json();


        showSuggestions(
            results
        );


    }

    catch (error) {


        console.error(
            "Location search error:",
            error
        );


        suggestionsBox.innerHTML =
            '<div class="location-loading">' +
            'Unable to search location. Please try again.' +
            '</div>';


        suggestionsBox.style.display =
            "block";


    }

    finally {

        requestInProgress =
            false;

    }

}



/*
|==================================================
| LIVE SEARCH
|==================================================
*/


searchInput.addEventListener(
    "input",
    function () {


        const query =
            this.value.trim();


        /*
         * Clear previous timer
         */

        clearTimeout(
            searchTimer
        );


        /*
         * If customer changes
         * selected location,
         * clear selected address.
         */

        if (
            query.length === 0
        ) {

            addressInput.value =
                "";

            selectedLocation.style.display =
                "none";

            suggestionsBox.style.display =
                "none";

            return;

        }


        /*
         * Wait before sending
         * request.
         *
         * This prevents too
         * many API requests.
         */

        searchTimer =
            setTimeout(
                function () {

                    lastQuery =
                        "";

                    searchLocation(
                        query
                    );

                },
                800
            );

    }
);



/*
|==================================================
| SEARCH BUTTON
|==================================================
*/


searchButton.addEventListener(
    "click",
    function () {


        lastQuery =
            "";


        searchLocation(
            searchInput.value
        );

    }
);



/*
|==================================================
| ENTER KEY
|==================================================
*/


searchInput.addEventListener(
    "keydown",
    function (event) {


        if (
            event.key === "Enter"
        ) {

            event.preventDefault();


            lastQuery =
                "";


            searchLocation(
                this.value
            );

        }

    }
);



/*
|==================================================
| CLOSE SUGGESTIONS
|==================================================
*/


document.addEventListener(
    "click",
    function (event) {


        if (
            !event.target.closest(
                ".map-search-wrapper"
            )
        ) {

            suggestionsBox.style.display =
                "none";

        }

    }
);



/*
|==================================================
| FORM VALIDATION
|==================================================
*/


const checkoutForm =
    document.getElementById(
        "checkoutForm"
    );


const nameInput =
    document.getElementById(
        "customer_name"
    );


const nameError =
    document.getElementById(
        "name-error"
    );


const phoneInput =
    document.getElementById(
        "customer_phone"
    );


const phoneError =
    document.getElementById(
        "phone-error"
    );



/*
|--------------------------------------------------
| NAME VALIDATION
|--------------------------------------------------
*/


nameInput.addEventListener(
    "input",
    function () {


        /*
         * Letters and spaces only
         */

        this.value =
            this.value.replace(
                /[^A-Za-z ]/g,
                ""
            );


        const name =
            this.value.trim();


        if (
            name === ""
        ) {

            nameError.textContent =
                "";

            return;

        }


        if (
            name.length < 3
        ) {

            nameError.textContent =
                "Name must contain at least 3 characters.";

            return;

        }


        nameError.textContent =
            "";

    }
);



/*
|--------------------------------------------------
| PHONE VALIDATION
|--------------------------------------------------
*/


phoneInput.addEventListener(
    "input",
    function () {


        /*
         * Numbers only
         */

        this.value =
            this.value
                .replace(
                    /[^0-9]/g,
                    ""
                )
                .slice(
                    0,
                    10
                );


        const phone =
            this.value;


        if (
            phone === ""
        ) {

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



/*
|==================================================
| SUBMIT VALIDATION
|==================================================
*/


checkoutForm.addEventListener(
    "submit",
    function (event) {


        const name =
            nameInput.value.trim();


        const phone =
            phoneInput.value.trim();


        const address =
            addressInput.value.trim();


        let valid =
            true;



        /*
         * NAME
         */

        if (
            name === ""
        ) {

            nameError.textContent =
                "Full name is required.";

            valid =
                false;

        }

        else if (
            name.length < 3
        ) {

            nameError.textContent =
                "Name must contain at least 3 characters.";

            valid =
                false;

        }

        else if (
            !/^[A-Za-z ]+$/.test(name)
        ) {

            nameError.textContent =
                "Name can contain only letters and spaces.";

            valid =
                false;

        }

        else {

            nameError.textContent =
                "";

        }



        /*
         * PHONE
         */

        if (
            phone === ""
        ) {

            phoneError.textContent =
                "Phone number is required.";

            valid =
                false;

        }

        else if (
            !/^(97|98)[0-9]{8}$/.test(phone)
        ) {

            phoneError.textContent =
                "Phone number must start with 97 or 98 and contain exactly 10 digits.";

            valid =
                false;

        }

        else {

            phoneError.textContent =
                "";

        }



        /*
         * ADDRESS
         */

        if (
            address === ""
        ) {

            addressError.textContent =
                "Please search and select your delivery location.";

            valid =
                false;

        }

        else {

            addressError.textContent =
                "";

        }



        /*
         * STOP SUBMISSION
         */

        if (!valid) {

            event.preventDefault();

        }

    }
);

</script>


</body>

</html>