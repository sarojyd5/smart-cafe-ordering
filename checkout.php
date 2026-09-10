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


// Get food information one by one
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

    $result = mysqli_stmt_get_result($stmt);

    $food = mysqli_fetch_assoc($result);


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

        'food_id' => $food['food_id'],

        'food_name' => $food['food_name'],

        'price' => (float) $food['price'],

        'image' => $food['image'],

        'quantity' => $quantity,

        'total' => $item_total

    ];

}


// If no valid items remain
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
        content="width=device-width, initial-scale=1.0">

    <title>
        Checkout | Timeout Cafe
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css">

    <link
        rel="stylesheet"
        href="assets/css/responsive.css">

    <link
        rel="stylesheet"
        href="assets/js/leaflet/leaflet.css">

    <script src="assets/js/leaflet/leaflet.js"></script>

</head>


<body>


<main class="checkout-page">


    <!-- HEADER -->

    <section class="checkout-header">

        <div>

            <p>TIMEOUT CAFE</p>

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



    <!-- CHECKOUT CONTENT -->

    <section class="checkout-layout">


        <!-- CUSTOMER FORM -->

        <div class="checkout-form-card">


            <h2>
                Delivery Information
            </h2>


            <form
                method="POST"
                action="place_order.php"
                id="checkoutForm">


                <!-- CUSTOMER NAME -->

                <div class="checkout-form-group">

                    <label for="customer_name">

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
                        autocomplete="name">

                    <small
                        id="name-error"
                        class="field-error">
                    </small>

                </div>



                <!-- PHONE -->

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
                        autocomplete="tel">

                    <small
                        id="phone-error"
                        class="field-error">
                    </small>

                </div>



                <!-- DELIVERY ADDRESS WITH MAP -->

                <div class="checkout-form-group">

                    <label for="delivery_address">

                        Delivery Address*

                    </label>


                    <div class="map-wrapper">
                        <div
                            id="delivery-map"
                            class="delivery-map"
                            style="width:100%;height:350px;">
                        </div>
                        <div class="map-search-box">
                            <input
                                type="text"
                                id="map-search-input"
                                placeholder="Search delivery location..."
                                autocomplete="off">
                            <button
                                type="button"
                                id="map-search-btn"
                                title="Search">&#128269;</button>
                            <div
                                id="map-search-results"
                                class="map-search-results"
                                style="display:none;">
                            </div>
                        </div>
                    </div>

                    <div id="selected-address-display" class="selected-address-display" style="display:none;">
                        <span class="map-icon">&#128205;</span>
                        <span id="selected-address-text"></span>
                    </div>


                    <button
                        type="button"
                        id="locate-current-btn"
                        class="use-current-location-btn">

                        <span class="locate-icon">&#128506;</span>

                        Use My Current Location

                    </button>


                    <div class="address-suggest-wrapper">

                        <textarea
                            id="delivery_address"
                            name="delivery_address"
                            rows="3"
                            maxlength="500"
                            required
                            placeholder="Enter your complete delivery address (e.g. street, city)"></textarea>

                        <div
                            id="address-suggest-results"
                            class="address-suggest-results"
                            style="display:none;">
                        </div>

                    </div>

                    <small
                        id="address-error"
                        class="field-error">
                    </small>

                </div>


                <input
                    type="hidden"
                    id="delivery_map_link"
                    name="delivery_map_link"
                    value="">

                <input
                    type="hidden"
                    id="delivery_lat"
                    name="delivery_lat"
                    value="">

                <input
                    type="hidden"
                    id="delivery_lng"
                    name="delivery_lng"
                    value="">



                <!-- ORDER NOTE -->

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



                <!-- PAYMENT METHOD -->

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



                        <label class="payment-method-card">

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



                <!-- PLACE ORDER -->

                <button
                    type="submit"
                    class="place-order-btn"
                    id="placeOrderBtn">

                    Place Order

                </button>


            </form>


        </div>



        <!-- ORDER SUMMARY -->

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



            <!-- DELIVERY CHARGE -->

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



            <!-- GRAND TOTAL -->

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

/*
|--------------------------------------------------------------------------
| GET ELEMENTS
|--------------------------------------------------------------------------
*/

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

const locateBtn =
    document.getElementById("locate-current-btn");

const addressSuggestResults =
    document.getElementById("address-suggest-results");

const mapLinkInput =
    document.getElementById("delivery_map_link");

const latInput =
    document.getElementById("delivery_lat");

const lngInput =
    document.getElementById("delivery_lng");

const addressDisplay =
    document.getElementById("selected-address-display");

const addressDisplayText =
    document.getElementById("selected-address-text");


/*
|--------------------------------------------------------------------------
| LEAFLET INTERACTIVE MAP
|--------------------------------------------------------------------------
*/

window.addEventListener("load", function () {

    L.Icon.Default.imagePath =
        "assets/js/leaflet/images";

    const mapEl =
        document.getElementById("delivery-map");

    if (!mapEl) return;

    const deliveryMap =
        L.map("delivery-map").setView(
            [27.6463616, 85.3381417],
            14
        );

    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }
    ).addTo(deliveryMap);


    let deliveryMarker = null;


    function updateMapLink(lat, lng) {

        const googleUrl =
            "https://www.google.com/maps/search/?api=1&query="
            + lat + "," + lng;

        mapLinkInput.value = googleUrl;

    }


    function reverseGeocode(lat, lng) {

        const url =
            "https://nominatim.openstreetmap.org/reverse?format=json&lat="
            + lat + "&lon=" + lng
            + "&addressdetails=1";

        fetch(url)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {

                if (data && data.display_name) {

                    const address =
                        data.display_name;

                    addressInput.value = address;

                    addressDisplay.style.display = "flex";
                    addressDisplayText.textContent = address;

                    updateMapLink(lat, lng);

                }

            })
            .catch(function () {

                updateMapLink(lat, lng);

            });

    }


    deliveryMap.on("click", function (e) {

        const lat =
            e.latlng.lat.toFixed(7);

        const lng =
            e.latlng.lng.toFixed(7);

        latInput.value = lat;
        lngInput.value = lng;

        if (deliveryMarker) {

            deliveryMap.removeLayer(
                deliveryMarker
            );

        }

        deliveryMarker = L.marker(
            [lat, lng],
            { draggable: true }
        ).addTo(deliveryMap);

        deliveryMarker.bindPopup(
            "Delivery location",
            { offset: [0, -30] }
        ).openPopup();

        deliveryMarker.on("dragend", function () {

            const pos =
                deliveryMarker.getLatLng();

            latInput.value = pos.lat.toFixed(7);
            lngInput.value = pos.lng.toFixed(7);

            skipForward = true;

            reverseGeocode(
                pos.lat,
                pos.lng
            );

        });

        reverseGeocode(
            parseFloat(lat),
            parseFloat(lng)
        );

    });


    /*
    |--------------------------------------------------------------------------
    | MAP SEARCH BOX
    |--------------------------------------------------------------------------
    */

    const mapSearchInput =
        document.getElementById("map-search-input");

    const mapSearchBtn =
        document.getElementById("map-search-btn");

    const mapSearchResults =
        document.getElementById("map-search-results");

    let searchTimer = null;


    function placeSearchMarker(lat, lng, label) {

        deliveryMap.setView([lat, lng], 16);

        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);

        if (deliveryMarker) {

            deliveryMap.removeLayer(
                deliveryMarker
            );

        }

        deliveryMarker = L.marker(
            [lat, lng],
            { draggable: true }
        ).addTo(deliveryMap);

        deliveryMarker.bindPopup(
            "Delivery location",
            { offset: [0, -30] }
        ).openPopup();

        deliveryMarker.on("dragend", function () {

            const pos =
                deliveryMarker.getLatLng();

            latInput.value = pos.lat.toFixed(7);
            lngInput.value = pos.lng.toFixed(7);

            skipForward = true;

            reverseGeocode(
                pos.lat,
                pos.lng
            );

        });

        addressInput.value = label;

        addressDisplay.style.display = "flex";
        addressDisplayText.textContent = label;

        skipForward = true;

        updateMapLink(lat, lng);

    }


    function searchMapLocations(query) {

        if (query.length < 3) {

            mapSearchResults.style.display =
                "none";

            return;

        }

        const url =
            "https://nominatim.openstreetmap.org/search?format=json&q="
            + encodeURIComponent(query)
            + "&limit=5"
            + "&addressdetails=1";

        fetch(url)
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {

                if (
                    !data ||
                    data.length === 0
                ) {

                    mapSearchResults.style.display =
                        "none";

                    return;

                }

                let html = "";

                data.forEach(function (item) {

                    html +=
                        '<div class="map-search-result-item" '
                        + 'data-lat="'
                        + item.lat + '" '
                        + 'data-lng="'
                        + item.lon + '" '
                        + 'data-name="'
                        + item.display_name
                            .replace(/"/g, '&quot;')
                        + '">'
                        + item.display_name
                        + "</div>";

                });

                mapSearchResults.innerHTML = html;
                mapSearchResults.style.display =
                    "block";

            })
            .catch(function () {

                mapSearchResults.style.display =
                    "none";

            });

    }


    mapSearchInput.addEventListener(
        "input",
        function () {

            clearTimeout(searchTimer);

            const query =
                this.value.trim();

            searchTimer = setTimeout(
                function () {

                    searchMapLocations(query);

                },
                400
            );

        }
    );


    mapSearchInput.addEventListener(
        "keydown",
        function (e) {

            if (e.key === "Enter") {

                e.preventDefault();

                clearTimeout(searchTimer);

                searchMapLocations(
                    this.value.trim()
                );

            }

        }
    );


    mapSearchBtn.addEventListener(
        "click",
        function () {

            clearTimeout(searchTimer);

            searchMapLocations(
                mapSearchInput.value.trim()
            );

        }
    );


    mapSearchResults.addEventListener(
        "click",
        function (e) {

            const item =
                e.target.closest(
                    ".map-search-result-item"
                );

            if (!item) return;

            const lat =
                parseFloat(
                    item.getAttribute("data-lat")
                );

            const lng =
                parseFloat(
                    item.getAttribute("data-lng")
                );

            const name =
                item.getAttribute("data-name");

            mapSearchInput.value = name;

            mapSearchResults.style.display =
                "none";

            placeSearchMarker(lat, lng, name);

        }
    );


    document.addEventListener(
        "click",
        function (e) {

            if (
                !e.target.closest(".map-search-box")
            ) {

                mapSearchResults.style.display =
                    "none";

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | AUTO-CENTER MAP ON BROWSER GEOLOCATION
    |--------------------------------------------------------------------------
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

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | USE MY CURRENT LOCATION BUTTON
    |--------------------------------------------------------------------------
    */

    locateBtn.addEventListener(
        "click",
        function () {

            if (!navigator.geolocation) {

                alert(
                    "Geolocation is not supported by your browser."
                );

                return;

            }

            const originalHTML =
                locateBtn.innerHTML;

            locateBtn.disabled = true;
            locateBtn.innerHTML =
                "Locating...";

            navigator.geolocation.getCurrentPosition(
                function (position) {

                    locateBtn.disabled = false;
                    locateBtn.innerHTML =
                        originalHTML;

                    const lat =
                        position.coords.latitude;

                    const lng =
                        position.coords.longitude;

                    deliveryMap.setView(
                        [lat, lng],
                        16
                    );

                    if (deliveryMarker) {

                        deliveryMap.removeLayer(
                            deliveryMarker
                        );

                    }

                    deliveryMarker =
                        L.marker(
                            [lat, lng],
                            { draggable: true }
                        ).addTo(deliveryMap);

                    deliveryMarker
                        .bindPopup(
                            "Delivery location",
                            { offset: [0, -30] }
                        )
                        .openPopup();

                    deliveryMarker.on(
                        "dragend",
                        function () {

                            const pos =
                                deliveryMarker
                                    .getLatLng();

                            latInput.value =
                                pos.lat.toFixed(7);

                            lngInput.value =
                                pos.lng.toFixed(7);

                            skipForward = true;

                            reverseGeocode(
                                pos.lat,
                                pos.lng
                            );

                        }
                    );

                    latInput.value =
                        lat.toFixed(7);

                    lngInput.value =
                        lng.toFixed(7);

                    reverseGeocode(lat, lng);

                },
                function () {

                    locateBtn.disabled = false;
                    locateBtn.innerHTML =
                        originalHTML;

                    alert(
                        "Unable to get your location. Please allow location access and try again."
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


    /*
    |--------------------------------------------------------------------------
    | ADDRESS AUTOCOMPLETE SUGGESTIONS
    |--------------------------------------------------------------------------
    */

    let addressSuggestTimer =
        null;


    function fetchAddressSuggestions(query) {

        const url =
            "https://nominatim.openstreetmap.org/search?format=json&q="
            + encodeURIComponent(query)
            + "&limit=5"
            + "&addressdetails=1";

        fetch(url)
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {

                if (
                    !data ||
                    data.length === 0
                ) {

                    addressSuggestResults.style.display =
                        "none";

                    return;

                }

                let html = "";

                data.forEach(function (item) {

                    html +=
                        '<div class="address-suggest-item" '
                        + 'data-lat="'
                        + item.lat + '" '
                        + 'data-lng="'
                        + item.lon + '" '
                        + 'data-name="'
                        + item.display_name
                            .replace(/"/g, '&quot;')
                        + '">'
                        + item.display_name
                        + "</div>";

                });

                addressSuggestResults.innerHTML = html;
                addressSuggestResults.style.display =
                    "block";

            })
            .catch(function () {

                addressSuggestResults.style.display =
                    "none";

            });

    }


    addressInput.addEventListener(
        "input",
        function () {

            if (skipForward) {

                skipForward = false;

                return;

            }

            clearTimeout(addressSuggestTimer);

            const query =
                this.value.trim();

            if (query.length < 3) {

                addressSuggestResults.style.display =
                    "none";

                return;

            }

            addressSuggestTimer =
                setTimeout(
                    function () {

                        fetchAddressSuggestions(query);

                    },
                    400
                );

        }
    );


    addressSuggestResults.addEventListener(
        "click",
        function (e) {

            const item =
                e.target.closest(
                    ".address-suggest-item"
                );

            if (!item) return;

            const lat =
                parseFloat(
                    item.getAttribute("data-lat")
                );

            const lng =
                parseFloat(
                    item.getAttribute("data-lng")
                );

            const name =
                item.getAttribute("data-name");

            addressSuggestResults.style.display =
                "none";

            placeSearchMarker(lat, lng, name);

        }
    );


    document.addEventListener(
        "click",
        function (e) {

            if (
                !e.target.closest(
                    ".address-suggest-wrapper"
                )
            ) {

                addressSuggestResults.style.display =
                    "none";

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | FORWARD GEOCODE: TYPE ADDRESS -> MOVE MAP
    |--------------------------------------------------------------------------
    */

    let skipForward = false;

    let geocodeTimer = null;


    addressInput.addEventListener(
        "input",
        function () {

            if (skipForward) {

                skipForward = false;

                return;

            }

            clearTimeout(geocodeTimer);

            const query =
                this.value.trim();

            if (query.length < 5) return;

            geocodeTimer = setTimeout(
                function () {

                    const searchUrl =
                        "https://nominatim.openstreetmap.org/search?format=json&q="
                        + encodeURIComponent(query)
                        + "&limit=1";

                    fetch(searchUrl)
                        .then(function (r) {
                            return r.json();
                        })
                        .then(function (data) {

                            if (
                                data &&
                                data.length > 0
                            ) {

                                const result =
                                    data[0];

                                const lat =
                                    parseFloat(
                                        result.lat
                                    );

                                const lng =
                                    parseFloat(
                                        result.lon
                                    );

                                deliveryMap.setView(
                                    [lat, lng],
                                    16
                                );

                                latInput.value =
                                    lat.toFixed(7);

                                lngInput.value =
                                    lng.toFixed(7);

                                if (deliveryMarker) {

                                    deliveryMap.removeLayer(
                                        deliveryMarker
                                    );

                                }

                                deliveryMarker =
                                    L.marker(
                                        [lat, lng],
                                        { draggable: true }
                                    ).addTo(
                                        deliveryMap
                                    );

                                deliveryMarker
                                    .bindPopup(
                                        "Delivery location",
                                        { offset: [0, -30] }
                                    )
                                    .openPopup();

                                deliveryMarker
                                    .on(
                                        "dragend",
                                        function () {

                                            const pos =
                                                deliveryMarker
                                                    .getLatLng();

                                            latInput.value =
                                                pos.lat.toFixed(7);

                                            lngInput.value =
                                                pos.lng.toFixed(7);

                                            skipForward = true;

                                            reverseGeocode(
                                                pos.lat,
                                                pos.lng
                                            );

                                        }
                                    );

                                const gUrl =
                                    "https://www.google.com/maps/search/?api=1&query="
                                    + lat + "," + lng;

                                mapLinkInput.value = gUrl;

                            }

                        })
                        .catch(
                            function () {}
                        );

                },
                800
            );

        }
    );


    const origReverseGeocode = reverseGeocode;

    reverseGeocode = function (lat, lng) {

        skipForward = true;

        origReverseGeocode(lat, lng);

    };

});


/*
|--------------------------------------------------------------------------
| FULL NAME LIVE VALIDATION
|--------------------------------------------------------------------------
*/

nameInput.addEventListener("input", function () {

    /*
     * Allow only letters and spaces
     */
    this.value =
        this.value.replace(
            /[^A-Za-z ]/g,
            ""
        );


    const name =
        this.value.trim();


    /*
     * Empty
     */
    if (name === "") {

        nameError.textContent = "";

        return;
    }


    /*
     * Minimum 3 characters
     */
    if (name.length < 3) {

        nameError.textContent =
            "Name must contain only letters and atleast 3 characters.";

        return;
    }


    /*
     * Valid
     */
    nameError.textContent = "";

});



/*
|--------------------------------------------------------------------------
| PHONE LIVE VALIDATION
|--------------------------------------------------------------------------
*/

phoneInput.addEventListener("input", function () {

    /*
     * Numbers only
     */
    this.value =
        this.value
            .replace(
                /[^0-9]/g,
                ""
            )
            .slice(0, 10);


    const phone =
        this.value;


    /*
     * Empty
     */
    if (phone === "") {

        phoneError.textContent = "";

        return;
    }


    /*
     * Check starting digits
     */
    if (
        phone.length >= 2 &&
        !phone.startsWith("97") &&
        !phone.startsWith("98")
    ) {

        phoneError.textContent =
            "Phone number must start with 97 or 98.";

        return;
    }


    /*
     * Check length
     */
    if (phone.length < 10) {

        phoneError.textContent =
            "Phone number must contain exactly 10 digits.";

        return;
    }


    /*
     * Valid
     */
    phoneError.textContent = "";

});



/*
|--------------------------------------------------------------------------
| FORM SUBMIT VALIDATION
|--------------------------------------------------------------------------
*/

checkoutForm.addEventListener(
    "submit",
    function (event) {

        const name =
            nameInput.value.trim();

        const phone =
            phoneInput.value.trim();


        let valid = true;


        /*
         * NAME VALIDATION
         */

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

        else if (!/^[A-Za-z ]+$/.test(name)) {

            nameError.textContent =
                "Name can contain only letters and spaces.";

            valid = false;

        }

        else {

            nameError.textContent = "";

        }



        /*
         * PHONE VALIDATION
         */

        if (phone === "") {

            phoneError.textContent =
                "Phone number is required.";

            valid = false;

        }

        else if (!/^(97|98)[0-9]{8}$/.test(phone)) {

            phoneError.textContent =
                "Phone number must start with 97 or 98 and contain exactly 10 digits.";

            valid = false;

        }

        else {

            phoneError.textContent = "";

        }



        /*
         * STOP FORM IF INVALID
         */

        if (!valid) {

            event.preventDefault();

        }
    }

);

</script>


</body>

</html>