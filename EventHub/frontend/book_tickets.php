
<?php
session_start();

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["role"] !== "customer") {
    http_response_code(403);
    exit("Access denied. Customer only.");
}

$customerId = (int) $_SESSION["user_id"];

function iconSvg($name, $size = 20) {
    $icons = [
        "dashboard" => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        "bookings" => '<path d="M8 3h11a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M10 8h7M10 12h7M10 16h4M3 7v12"/>',
        "star" => '<path d="m12 2 3.1 6.3 7 1-5.1 5 .9 7-5.9-3.2-5.9 3.2.9-7-5.1-5 7-1Z"/>',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16"/>'
    ];

    $path = $icons[$name] ?? $icons["dashboard"];
    $size = max(12, min(48, (int) $size));

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' .
        $size . '" height="' . $size .
        '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        $path . '</svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets | EventHub</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">

    <style>
        :root {
            --eh-red: #7f1d1d;
            --eh-red-dark: #641616;
            --eh-bg: #f5f6fa;
        }

        body {
            background: var(--eh-bg);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .eh-layout {
            display: flex;
            min-height: 100vh;
        }

        .eh-sidebar {
            width: 260px;
            flex-shrink: 0;
            background: var(--eh-red);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 1030;
        }

        .eh-brand {
            padding: 27px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
            font-size: 1.55rem;
            font-weight: 800;
            border-bottom: 1px solid rgba(255,255,255,.15);
        }

        .eh-brand:hover {
            color: #fff;
        }

        .eh-brand-mark {
            width: 39px;
            height: 39px;
            border-radius: 11px;
            background: rgba(255,255,255,.16);
            display: grid;
            place-items: center;
        }

        .eh-sidebar-label {
            padding: 28px 24px 10px;
            color: #e6a9a9;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 1.4px;
        }

        .eh-nav {
            padding: 0 12px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .eh-nav-link {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 14px;
            border-radius: 9px;
            color: #f5dada;
            text-decoration: none;
            font-size: .91rem;
            font-weight: 500;
            transition: .2s;
        }

        .eh-nav-link:hover,
        .eh-nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.17);
        }

        .eh-nav-link.active {
            font-weight: 700;
        }

        .eh-sidebar-bottom {
            margin-top: auto;
            padding: 18px 12px 22px;
            border-top: 1px solid rgba(255,255,255,.15);
        }

        .eh-logout {
            width: 100%;
            background: transparent;
            border: none;
            text-align: left;
            cursor: pointer;
        }

        .eh-main {
            min-width: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .eh-topbar {
            height: 76px;
            background: #fff;
            border-bottom: 1px solid #e9edf2;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .eh-topbar-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
        }

        .eh-role {
            background: #fee2e2;
            color: var(--eh-red);
            padding: 6px 12px;
            border-radius: 30px;
            font-size: .75rem;
            font-weight: 700;
        }

        .eh-avatar {
            background: #fee2e2;
            color: var(--eh-red);
            width: 39px;
            height: 39px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .eh-content {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 34px;
            flex: 1;
        }

        .eh-page-title {
            font-size: 1.9rem;
            font-weight: 800;
            color: #202936;
        }

        .eh-card {
            border: 1px solid #e9edf2;
            border-radius: 13px;
            overflow: hidden;
        }

        .eh-card .card-header {
            background: #fff;
            padding: 20px 24px;
        }

        .eh-card .card-title {
            font-size: 1.05rem;
            font-weight: 750;
        }

        .eh-card .card-body {
            padding: 24px;
        }

        .available-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 11px;
            padding: 20px;
        }

        .available-number {
            font-size: 2.3rem;
            font-weight: 800;
            color: #15803d;
        }

        .summary-price {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--eh-red);
        }

        .eh-summary-event {
            font-size: 1.2rem;
            font-weight: 750;
            color: #202936;
        }

        .eh-btn {
            background: var(--eh-red);
            border: 1px solid var(--eh-red);
            color: #fff;
            border-radius: 8px;
            font-weight: 650;
            padding: 11px 16px;
        }

        .eh-btn:hover,
        .eh-btn:focus {
            background: var(--eh-red-dark);
            border-color: var(--eh-red-dark);
            color: #fff;
        }

        .eh-btn:disabled {
            background: #d1d5db;
            border-color: #d1d5db;
            color: #6b7280;
            opacity: 1;
        }

        .eh-outline-btn {
            border-color: #d6a4a4;
            color: var(--eh-red);
            border-radius: 8px;
            font-weight: 600;
        }

        .eh-outline-btn:hover {
            background: var(--eh-red);
            border-color: var(--eh-red);
            color: #fff;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 .2rem rgba(127,29,29,.12);
        }

        .eh-footer {
            text-align: center;
            color: #7b8492;
            padding: 18px;
            font-size: .8rem;
        }

        .eh-menu-button {
            display: none;
            border: none;
            background: transparent;
            color: var(--eh-red);
            cursor: pointer;
        }

        .eh-overlay {
            display: none;
        }

        @media (max-width: 991px) {
            .eh-sidebar {
                position: fixed;
                top: 0;
                left: -270px;
                height: 100vh;
                transition: left .25s;
            }

            .eh-sidebar.open {
                left: 0;
            }

            .eh-overlay.open {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.45);
                z-index: 1020;
            }

            .eh-menu-button {
                display: inline-flex;
            }

            .eh-topbar {
                padding: 0 18px;
            }

            .eh-content {
                padding: 22px 16px;
            }

            .eh-page-title {
                font-size: 1.55rem;
            }
        }
    </style>
</head>

<body>

<div id="sidebarOverlay" class="eh-overlay"></div>

<div class="eh-layout">

    <!-- LEFT SIDEBAR -->
    <aside id="sidebar" class="eh-sidebar">

        <a href="index.php" class="eh-brand">
            <span class="eh-brand-mark">
                <?php echo iconSvg("ticket", 23); ?>
            </span>
            EventHub
        </a>

        <div class="eh-sidebar-label">MAIN MENU</div>

        <nav class="eh-nav" aria-label="Main navigation">

            <a href="index.php" class="eh-nav-link">
                <?php echo iconSvg("dashboard"); ?>
                <span>Dashboard</span>
            </a>

            <a href="browse_events.php" class="eh-nav-link">
                <?php echo iconSvg("calendar"); ?>
                <span>Browse Events</span>
            </a>

            <a href="book_tickets.php"
               class="eh-nav-link active"
               aria-current="page">
                <?php echo iconSvg("ticket"); ?>
                <span>Book Tickets</span>
            </a>

            <a href="my_bookings.php" class="eh-nav-link">
                <?php echo iconSvg("bookings"); ?>
                <span>My Bookings</span>
            </a>

            <!-- NEW: EVENT REVIEWS -->
            <a href="reviews.php" class="eh-nav-link">
                <?php echo iconSvg("star"); ?>
                <span>Event Reviews</span>
            </a>

        </nav>

        <div class="eh-sidebar-bottom">
            <div class="px-3 mb-3">
                <div class="small" style="color:#e6a9a9">
                    SIGNED IN AS
                </div>
                <div class="fw-bold mt-1">Customer</div>
                <div class="small" style="color:#f1c7c7">
                    User #<?php echo $customerId; ?>
                </div>
            </div>

            <button type="button"
                    id="logoutButton"
                    class="eh-nav-link eh-logout">
                <?php echo iconSvg("logout"); ?>
                <span>Logout</span>
            </button>
        </div>
    </aside>

    <!-- MAIN AREA -->
    <div class="eh-main">

        <header class="eh-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button"
                        id="menuButton"
                        class="eh-menu-button"
                        aria-label="Open navigation menu"
                        aria-expanded="false"
                        aria-controls="sidebar">
                    <?php echo iconSvg("menu", 25); ?>
                </button>

                <h2 class="eh-topbar-title">Ticket Reservation</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="eh-role">Customer</span>
                <span class="eh-avatar">C</span>
            </div>
        </header>

        <main class="eh-content">

            <div class="mb-4">
                <h1 class="eh-page-title mb-1">Book Tickets</h1>
                <p class="text-secondary mb-0">
                    Select an event and reserve your tickets.
                </p>
            </div>

            <div id="alertBox" class="alert d-none" role="alert"></div>

            <div class="row row-cards">

                <!-- BOOKING DETAILS -->
                <div class="col-lg-7">
                    <div class="card eh-card">

                        <div class="card-header">
                            <h3 class="card-title mb-0">Booking Details</h3>
                        </div>

                        <div class="card-body">
                            <form id="bookingForm">

                                <div class="mb-3">
                                    <label for="eventSelect" class="form-label">
                                        Select Event
                                    </label>

                                    <select id="eventSelect"
                                            class="form-select"
                                            required>
                                        <option value="">Loading events...</option>
                                    </select>
                                </div>

                                <div id="ticketAvailability"
                                     class="mb-4 d-none">
                                    <div class="available-box">
                                        <div class="text-secondary mb-1">
                                            Available Tickets
                                        </div>
                                        <div class="available-number"
                                             id="availableTickets">0</div>
                                        <div class="small text-secondary">
                                            tickets remaining
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="quantityInput" class="form-label">
                                        Ticket Quantity
                                    </label>

                                    <input id="quantityInput"
                                           type="number"
                                           class="form-control"
                                           min="1"
                                           step="1"
                                           value="1"
                                           required>

                                    <div class="form-hint mt-2"
                                         id="quantityHint">
                                        Select an event to see available tickets.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        Booking Status
                                    </label>

                                    <input type="text"
                                           class="form-control"
                                           value="Pending"
                                           readonly>
                                </div>

                                <button id="submitButton"
                                        type="submit"
                                        class="btn eh-btn w-100"
                                        disabled>
                                    Confirm Booking
                                </button>

                            </form>
                        </div>
                    </div>
                </div>

                <!-- ORDER SUMMARY -->
                <div class="col-lg-5">
                    <div class="card eh-card">

                        <div class="card-header">
                            <h3 class="card-title mb-0">Order Summary</h3>
                        </div>

                        <div class="card-body">

                            <h3 id="summaryEvent" class="eh-summary-event">
                                Select an event
                            </h3>

                            <p class="text-secondary mb-2" id="summaryDate">
                                Date: -
                            </p>

                            <p class="text-secondary mb-2" id="summaryAvailable">
                                Available: -
                            </p>

                            <p class="text-secondary mb-2" id="summaryUnitPrice">
                                Price per ticket: -
                            </p>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <span>Quantity</span>
                                <strong id="summaryQuantity">1</strong>
                            </div>

                            <div class="mt-3">
                                <div class="text-secondary">Total Price</div>
                                <div class="summary-price" id="summaryTotal">
                                    RM 0.00
                                </div>
                            </div>

                            <p class="text-secondary small mt-3 mb-0">
                                Booking will initially be marked as Pending.
                                No online payment is required.
                            </p>

                        </div>
                    </div>
                </div>

            </div>

            <!-- NAVIGATION -->
            <div class="mt-4 d-flex flex-wrap gap-2">
                <a href="browse_events.php"
                   class="btn btn-outline-secondary">
                    Back to Browse Events
                </a>

                <a href="my_bookings.php"
                   class="btn eh-outline-btn">
                    My Bookings
                </a>
            </div>

        </main>

        <footer class="eh-footer">
            © <?php echo date("Y"); ?> EventHub —
            Event Ticketing and Venue Booking System
        </footer>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>

<script>
"use strict";

const CUSTOMER_ID = <?php echo json_encode($customerId); ?>;

const EVENTS_API = "../api/events.php";
const BOOKINGS_API = "../api/bookings.php";

let events = [];
let processing = false;

const eventSelect = document.getElementById("eventSelect");
const quantityInput = document.getElementById("quantityInput");
const submitButton = document.getElementById("submitButton");
const alertBox = document.getElementById("alertBox");

const sidebar = document.getElementById("sidebar");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const menuButton = document.getElementById("menuButton");

function setSidebar(open) {
    sidebar.classList.toggle("open", open);
    sidebarOverlay.classList.toggle("open", open);
    menuButton.setAttribute("aria-expanded", String(open));
}

menuButton.addEventListener("click", () => {
    setSidebar(!sidebar.classList.contains("open"));
});

sidebarOverlay.addEventListener("click", () => {
    setSidebar(false);
});

document.addEventListener("keydown", event => {
    if (event.key === "Escape") {
        setSidebar(false);
    }
});

function showAlert(message, type = "success") {
    alertBox.className = "alert alert-" + type;
    alertBox.textContent = message;
    alertBox.classList.remove("d-none");

    window.scrollTo({
        top: 0,
        behavior: "smooth"
    });
}

function clearAlert() {
    alertBox.textContent = "";
    alertBox.className = "alert d-none";
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options
    });

    if (response.status === 401) {
        window.location.replace("login.php");
        throw new Error("Session expired.");
    }

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("Invalid server response.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Request failed.");
    }

    return result;
}

function formatDate(value) {
    if (!value) {
        return "-";
    }

    const date = new Date(
        String(value).replace(" ", "T")
    );

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString("en-MY", {
            dateStyle: "medium",
            timeStyle: "short"
        });
}

function formatNumber(value) {
    return Number(value).toLocaleString("en-MY");
}

function selectedEvent() {
    return events.find(
        event => String(event.event_id) === eventSelect.value
    );
}

function updateAvailability(event) {
    const container = document.getElementById(
        "ticketAvailability"
    );

    if (!event) {
        container.classList.add("d-none");
        quantityInput.removeAttribute("max");

        document.getElementById("quantityHint").textContent =
            "Select an event to see available tickets.";

        return;
    }

    const available = Math.max(
        0,
        Number(event.tickets_available) || 0
    );

    document.getElementById("availableTickets").textContent =
        formatNumber(available);

    quantityInput.max = available;

    document.getElementById("quantityHint").textContent =
        "Maximum " + formatNumber(available) +
        " ticket(s) available.";

    container.classList.remove("d-none");
}

function updateSummary() {
    const event = selectedEvent();
    const quantity = Number(quantityInput.value);

    document.getElementById("summaryQuantity").textContent =
        Number.isInteger(quantity) && quantity > 0
            ? formatNumber(quantity)
            : "-";

    updateAvailability(event);

    if (!event) {
        document.getElementById("summaryEvent").textContent =
            "Select an event";

        document.getElementById("summaryDate").textContent =
            "Date: -";

        document.getElementById("summaryAvailable").textContent =
            "Available: -";

        document.getElementById("summaryUnitPrice").textContent =
            "Price per ticket: -";

        document.getElementById("summaryTotal").textContent =
            "RM 0.00";

        submitButton.disabled = true;
        return;
    }

    const price = Number(event.ticket_price);
    const available = Number(event.tickets_available);

    document.getElementById("summaryEvent").textContent =
        event.event_name;

    document.getElementById("summaryDate").textContent =
        "Date: " + formatDate(event.event_date);

    document.getElementById("summaryAvailable").textContent =
        "Available: " + formatNumber(available) + " tickets";

    document.getElementById("summaryUnitPrice").textContent =
        "Price per ticket: RM " + price.toFixed(2);

    const validQuantity =
        Number.isInteger(quantity) &&
        quantity >= 1 &&
        quantity <= available;

    document.getElementById("summaryTotal").textContent =
        "RM " + (
            validQuantity ? quantity * price : 0
        ).toFixed(2);

    submitButton.disabled =
        !validQuantity || processing;
}

async function loadEvents(preferredEventId = null) {
    const previousEventId =
        preferredEventId !== null
            ? String(preferredEventId)
            : eventSelect.value;

    const result = await apiRequest(EVENTS_API);

    events = Array.isArray(result.data)
        ? result.data.filter(
            event => Number(event.tickets_available) > 0
        )
        : [];

    eventSelect.replaceChildren();

    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Select an event";

    eventSelect.appendChild(placeholder);

    events.forEach(event => {
        const option = document.createElement("option");

        option.value = event.event_id;

        option.textContent =
            event.event_name + " — RM " +
            Number(event.ticket_price).toFixed(2);

        eventSelect.appendChild(option);
    });

    const urlEventId = new URLSearchParams(
        window.location.search
    ).get("event_id");

    const desiredEventId =
        previousEventId || urlEventId;

    if (
        desiredEventId &&
        events.some(
            event => String(event.event_id) === desiredEventId
        )
    ) {
        eventSelect.value = desiredEventId;
    }

    updateSummary();
}

document.getElementById("bookingForm")
    .addEventListener("submit", async event => {
        event.preventDefault();

        if (processing) {
            return;
        }

        const chosenEvent = selectedEvent();
        const quantity = Number(quantityInput.value);

        if (!chosenEvent) {
            showAlert("Please select an event.", "danger");
            return;
        }

        const available = Number(
            chosenEvent.tickets_available
        );

        if (
            !Number.isInteger(quantity) ||
            quantity < 1 ||
            quantity > available
        ) {
            showAlert(
                "Invalid quantity. Maximum available tickets: " +
                formatNumber(available),
                "danger"
            );
            return;
        }

        if (
            !confirm(
                "Confirm booking for " +
                quantity + " ticket(s)?"
            )
        ) {
            return;
        }

        processing = true;
        submitButton.disabled = true;
        submitButton.textContent = "Processing...";

        try {
            const result = await apiRequest(
                BOOKINGS_API,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        customer_id: CUSTOMER_ID,
                        event_id: Number(
                            chosenEvent.event_id
                        ),
                        quantity: quantity,
                        booking_status: "pending"
                    })
                }
            );

            const bookingId =
                result.data?.booking_id ?? "-";

            showAlert(
                "Booking created successfully! Booking ID: " +
                bookingId,
                "success"
            );

            quantityInput.value = 1;

            await loadEvents(chosenEvent.event_id);

        } catch (error) {
            showAlert(error.message, "danger");

            try {
                await loadEvents(chosenEvent.event_id);
            } catch {
                // Keep the original error visible.
            }

        } finally {
            processing = false;
            submitButton.textContent = "Confirm Booking";
            updateSummary();
        }
    });

eventSelect.addEventListener("change", () => {
    clearAlert();
    updateSummary();
});

quantityInput.addEventListener("input", updateSummary);

document.getElementById("logoutButton")
    .addEventListener("click", async () => {
        if (!confirm("Are you sure you want to logout?")) {
            return;
        }

        const button = document.getElementById("logoutButton");
        button.disabled = true;

        try {
            await apiRequest("../api/logout.php", {
                method: "POST"
            });

            window.location.replace("login.php");

        } catch (error) {
            showAlert(error.message, "danger");
            button.disabled = false;
        }
    });

loadEvents().catch(error => {
    showAlert(error.message, "danger");
    submitButton.disabled = true;
});
</script>

</body>
</html>
