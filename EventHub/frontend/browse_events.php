
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

$userId = (int) $_SESSION["user_id"];

function iconSvg($name, $size = 20) {
    $icons = [
        "dashboard" => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1 2 2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        "bookings" => '<path d="M8 3h11a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M10 8h7M10 12h7M10 16h4M3 7v12"/>',
        "star" => '<path d="m12 2 3.1 6.3 7 1-5.1 5 .9 7-5.9-3.2-5.9 3.2.9-7-5.1-5 7-1Z"/>',
        "pin" => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
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
    <title>Browse Events | EventHub</title>

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

        .eh-filter-card,
        .event-card {
            border: 1px solid #e9edf2;
            border-radius: 13px;
        }

        .event-card {
            height: 100%;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }

        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,.07);
        }

        .event-card .card-body {
            padding: 24px;
        }

        .event-card .card-title {
            font-size: 1.15rem;
            font-weight: 750;
            color: #202936;
            line-height: 1.4;
        }

        .event-description {
            min-height: 52px;
            line-height: 1.65;
        }

        .event-info {
            color: #475569;
            font-size: .9rem;
            line-height: 1.6;
        }

        .event-address {
            color: #64748b;
            font-size: .83rem;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .event-price {
            font-size: 1.65rem;
            font-weight: 800;
            color: var(--eh-red);
        }

        .available-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 10px 13px;
            border-radius: 9px;
            font-weight: 650;
            font-size: .88rem;
        }

        .sold-out-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 10px 13px;
            border-radius: 9px;
            font-weight: 650;
            font-size: .88rem;
        }

        .eh-btn {
            background: var(--eh-red);
            border: 1px solid var(--eh-red);
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            padding: 11px 16px;
        }

        .eh-btn:hover,
        .eh-btn:focus {
            background: var(--eh-red-dark);
            border-color: var(--eh-red-dark);
            color: #fff;
        }

        .eh-btn:disabled {
            background: #e5e7eb;
            border-color: #e5e7eb;
            color: #6b7280;
            opacity: 1;
            cursor: not-allowed;
        }

        .eh-location-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--eh-red);
            border-radius: 8px;
            background: #fff;
            color: var(--eh-red);
            font-size: .9rem;
            font-weight: 700;
            text-decoration: none;
            transition: .2s;
        }

        .eh-location-btn:hover {
            background: #fee2e2;
            color: var(--eh-red-dark);
        }

        .eh-location-btn svg {
            flex-shrink: 0;
        }

        .eh-count {
            background: #fee2e2;
            color: var(--eh-red);
            border-radius: 30px;
            padding: 8px 15px;
            font-size: .8rem;
            font-weight: 750;
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

        @media (max-width: 575px) {
            .event-description {
                min-height: auto;
            }

            .eh-content {
                padding: 18px 14px;
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

            <a href="browse_events.php"
               class="eh-nav-link active"
               aria-current="page">
                <?php echo iconSvg("calendar"); ?>
                <span>Browse Events</span>
            </a>

            <a href="book_tickets.php" class="eh-nav-link">
                <?php echo iconSvg("ticket"); ?>
                <span>Book Tickets</span>
            </a>

            <a href="my_bookings.php" class="eh-nav-link">
                <?php echo iconSvg("bookings"); ?>
                <span>My Bookings</span>
            </a>

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
                    User #<?php echo $userId; ?>
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

                <h2 class="eh-topbar-title">Event Discovery</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="eh-role">Customer</span>
                <span class="eh-avatar">C</span>
            </div>
        </header>

        <main class="eh-content">

            <!-- PAGE HEADER -->
            <div class="mb-4">
                <h1 class="eh-page-title mb-1">Browse Events</h1>
                <p class="text-secondary mb-0">
                    Discover upcoming events and find your next experience.
                </p>
            </div>

            <div id="alertBox"
                 class="alert alert-danger d-none"
                 role="alert"></div>

            <!-- SEARCH AND FILTER -->
            <div class="card eh-filter-card mb-4">
                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-8">
                            <label for="searchInput" class="form-label">
                                Search Events
                            </label>

                            <input id="searchInput"
                                   type="search"
                                   class="form-control"
                                   placeholder="Search by event name or description">
                        </div>

                        <div class="col-md-4">
                            <label for="venueFilter" class="form-label">
                                Venue
                            </label>

                            <select id="venueFilter" class="form-select">
                                <option value="">All Venues</option>
                            </select>
                        </div>

                    </div>
                </div>
            </div>

            <!-- EVENTS HEADER -->
            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                <h2 class="mb-0" style="font-size:1.2rem;font-weight:750;">
                    Available Events
                </h2>

                <span id="eventCount" class="eh-count">
                    Loading...
                </span>
            </div>

            <!-- EVENT CARDS -->
            <div id="eventsGrid" class="row row-cards">
                <div class="col-12 text-center py-5 text-secondary">
                    Loading events...
                </div>
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

const EVENTS_API = "../api/events.php";
const VENUES_API = "../api/venues.php";

let events = [];
let venues = [];

const eventsGrid = document.getElementById("eventsGrid");
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

function showError(message) {
    alertBox.textContent = message;
    alertBox.classList.remove("d-none");
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options
    });

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("Invalid server response.");
    }

    if (response.status === 401) {
        window.location.replace("login.php");
        throw new Error("Session expired.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Request failed.");
    }

    return result;
}

function getVenue(venueId) {
    return venues.find(
        item => Number(item.venue_id) === Number(venueId)
    ) || null;
}

function venueName(venueId) {
    const venue = getVenue(venueId);
    return venue ? venue.venue_name : "Venue #" + venueId;
}

function venueAddress(venue) {
    if (!venue) return "";

    // Supports common address field names.
    const possibleFields = [
        "address",
        "venue_address",
        "location_address",
        "full_address",
        "venue_location",
        "location"
    ];

    for (const field of possibleFields) {
        const value = venue[field];

        if (typeof value === "string" && value.trim()) {
            return value.trim();
        }
    }

    return "";
}

function googleMapsUrl(venue) {
    if (!venue) return "";

    const name = String(venue.venue_name || "").trim();
    const address = venueAddress(venue);

    if (!name && !address) return "";

    const query = [name, address]
        .filter(Boolean)
        .join(", ");

    return "https://www.google.com/maps/search/?api=1&query=" +
        encodeURIComponent(query);
}

function formatDate(value) {
    if (!value) return "-";

    const date = new Date(String(value).replace(" ", "T"));

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString("en-MY", {
            dateStyle: "medium",
            timeStyle: "short"
        });
}

function createElement(tag, className, text) {
    const element = document.createElement(tag);

    if (className) {
        element.className = className;
    }

    if (text !== undefined) {
        element.textContent = text;
    }

    return element;
}

function renderEvents() {
    const search = document.getElementById("searchInput")
        .value.toLowerCase().trim();

    const venueFilter = document.getElementById("venueFilter").value;

    const filtered = events.filter(event => {
        const searchableText = (
            String(event.event_name || "") + " " +
            String(event.description || "")
        ).toLowerCase();

        const matchesSearch = searchableText.includes(search);

        const matchesVenue = !venueFilter ||
            String(event.venue_id) === venueFilter;

        return matchesSearch && matchesVenue;
    });

    document.getElementById("eventCount").textContent =
        filtered.length + " Events";

    eventsGrid.replaceChildren();

    if (filtered.length === 0) {
        eventsGrid.appendChild(
            createElement(
                "div",
                "col-12 text-center py-5 text-secondary",
                "No events found."
            )
        );
        return;
    }

    filtered.forEach(event => {
        const column = createElement(
            "div",
            "col-md-6 col-lg-4"
        );

        const card = createElement(
            "div",
            "card event-card"
        );

        const body = createElement(
            "div",
            "card-body d-flex flex-column"
        );

        const heading = createElement(
            "h3",
            "card-title mb-3",
            event.event_name
        );

        const description = createElement(
            "p",
            "text-secondary event-description mb-3",
            event.description || "No description available."
        );

        const currentVenue = getVenue(event.venue_id);
        const address = venueAddress(currentVenue);

        const venue = createElement(
            "p",
            "event-info mb-2",
            "📍 " + venueName(event.venue_id)
        );

        const addressElement = createElement(
            "p",
            "event-address mb-2",
            address || "Address not provided"
        );

        const date = createElement(
            "p",
            "event-info mb-3",
            "📅 " + formatDate(event.event_date)
        );

        const available = Number(event.tickets_available);

        const tickets = createElement(
            "div",
            available > 0
                ? "available-box mb-3"
                : "sold-out-box mb-3",
            available > 0
                ? "🎟️ " + available + " tickets available"
                : "🎟️ Sold Out"
        );

        const priceLabel = createElement(
            "div",
            "text-secondary small mb-1",
            "Ticket Price"
        );

        const price = createElement(
            "div",
            "event-price mb-3",
            "RM " + Number(event.ticket_price).toFixed(2)
        );

        // NEW: GOOGLE MAPS LOCATION BUTTON
        const locationButton = createElement(
            "a",
            "eh-location-btn mb-2",
            "📍 View Location"
        );

        const mapsUrl = googleMapsUrl(currentVenue);

        if (mapsUrl) {
            locationButton.href = mapsUrl;
            locationButton.target = "_blank";
            locationButton.rel = "noopener noreferrer";
            locationButton.title = address
                ? "Search this venue on Google Maps"
                : "Search by venue name on Google Maps";
        } else {
            locationButton.removeAttribute("href");
            locationButton.setAttribute("aria-disabled", "true");
            locationButton.style.opacity = ".5";
            locationButton.style.pointerEvents = "none";
            locationButton.textContent = "Location Unavailable";
        }

        // EXISTING: BOOK TICKETS BUTTON
        const button = createElement(
            "button",
            "btn eh-btn w-100 mt-auto",
            available > 0 ? "Book Tickets" : "Sold Out"
        );

        button.type = "button";
        button.disabled = available <= 0;

        if (available > 0) {
            button.addEventListener("click", () => {
                window.location.href =
                    "book_tickets.php?event_id=" +
                    encodeURIComponent(event.event_id);
            });
        }

        body.append(
            heading,
            description,
            venue,
            addressElement,
            date,
            tickets,
            priceLabel,
            price,
            locationButton,
            button
        );

        card.appendChild(body);
        column.appendChild(card);
        eventsGrid.appendChild(column);
    });
}

async function loadData() {
    try {
        const [eventsResult, venuesResult] = await Promise.all([
            apiRequest(EVENTS_API),
            apiRequest(VENUES_API)
        ]);

        events = Array.isArray(eventsResult.data)
            ? eventsResult.data
            : [];

        venues = Array.isArray(venuesResult.data)
            ? venuesResult.data
            : [];

        const venueFilter = document.getElementById("venueFilter");

        venueFilter.replaceChildren();

        const allOption = document.createElement("option");
        allOption.value = "";
        allOption.textContent = "All Venues";
        venueFilter.appendChild(allOption);

        venues.forEach(venue => {
            const option = document.createElement("option");
            option.value = venue.venue_id;
            option.textContent = venue.venue_name;
            venueFilter.appendChild(option);
        });

        renderEvents();

    } catch (error) {
        showError(error.message);
        eventsGrid.replaceChildren();

        eventsGrid.appendChild(
            createElement(
                "div",
                "col-12 text-center py-5 text-secondary",
                "Unable to load events."
            )
        );
    }
}

async function logout() {
    if (!confirm("Are you sure you want to logout?")) {
        return;
    }

    const logoutButton =
        document.getElementById("logoutButton");

    logoutButton.disabled = true;

    try {
        await apiRequest("../api/logout.php", {
            method: "POST"
        });

        window.location.replace("login.php");

    } catch (error) {
        showError(error.message);
        logoutButton.disabled = false;
    }
}

document.getElementById("searchInput")
    .addEventListener("input", renderEvents);

document.getElementById("venueFilter")
    .addEventListener("change", renderEvents);

document.getElementById("logoutButton")
    .addEventListener("click", logout);

loadData();
</script>

</body>
</html>
