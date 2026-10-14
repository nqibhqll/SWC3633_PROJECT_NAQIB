
<?php
session_start();

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["role"] !== "organiser") {
    http_response_code(403);
    exit("Access denied. Organiser only.");
}

$userId = (int) $_SESSION["user_id"];

function iconSvg($name, $size = 20) {
    $icons = [
        "dashboard" => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1-2-2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        "map" => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        "star" => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2Z"/>',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        "refresh" => '<path d="M20 11a8 8 0 0 0-14-5L4 8"/><path d="M4 4v4h4M4 13a8 8 0 0 0 14 5l2-2"/><path d="M20 20v-4h-4"/>'
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
    <title>Venue Locations | EventHub</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">

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

        .eh-stat-card {
            border: 1px solid #e9edf2;
            border-radius: 13px;
            height: 100%;
            transition: transform .2s, box-shadow .2s;
        }

        .eh-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,.06);
        }

        .eh-stat-label {
            color: #6b7280;
            font-size: .85rem;
            font-weight: 600;
        }

        .eh-stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #202936;
            margin-top: 8px;
        }

        .eh-filter-card {
            border: 1px solid #e9edf2;
            border-radius: 13px;
        }

        .venue-card {
            height: 100%;
            border: 1px solid #e9edf2;
            border-radius: 13px;
            transition: transform .2s, box-shadow .2s;
        }

        .venue-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,.07);
        }

        .venue-card .card-body {
            padding: 23px;
        }

        .venue-card .card-title {
            font-size: 1.08rem;
            font-weight: 750;
            color: #202936;
        }

        .venue-address {
            min-height: 48px;
            line-height: 1.6;
        }

        .eh-btn {
            background: var(--eh-red);
            border: 1px solid var(--eh-red);
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
        }

        .eh-btn:hover,
        .eh-btn:focus {
            background: var(--eh-red-dark);
            border-color: var(--eh-red-dark);
            color: #fff;
        }

        .eh-refresh-btn {
            color: var(--eh-red);
            border-color: #e5b8b8;
            border-radius: 8px;
            font-weight: 600;
        }

        .eh-refresh-btn:hover {
            background: var(--eh-red);
            border-color: var(--eh-red);
            color: #fff;
        }

        .form-control:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 .2rem rgba(127,29,29,.12);
        }

        .modal-content {
            border-radius: 13px;
        }

        #venueMap {
            height: 380px;
            width: 100%;
            border-radius: 10px;
            border: 1px solid #e9edf2;
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

            <a href="manage_events.php" class="eh-nav-link">
                <?php echo iconSvg("calendar"); ?>
                <span>Manage Events</span>
            </a>

            <a href="event_bookings.php" class="eh-nav-link">
                <?php echo iconSvg("ticket"); ?>
                <span>Event Bookings</span>
            </a>

            <a href="venue_locations.php"
               class="eh-nav-link active"
               aria-current="page">
                <?php echo iconSvg("map"); ?>
                <span>Venue Locations</span>
            </a>

            <!-- EVENT REVIEWS -->
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

                <div class="fw-bold mt-1">Organiser</div>

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

                <h2 class="eh-topbar-title">Venue Directory</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="eh-role">Organiser</span>
                <span class="eh-avatar">O</span>
            </div>

        </header>

        <main class="eh-content">

            <!-- PAGE HEADER -->
            <div class="mb-4">
                <h1 class="eh-page-title mb-1">Venue Locations</h1>
                <p class="text-secondary mb-0">
                    Explore available venues and view their locations
                    using OpenStreetMap.
                </p>
            </div>

            <div id="alertBox"
                 class="alert alert-danger d-none"
                 role="alert"></div>

            <!-- STATISTICS -->
            <div class="row row-cards mb-4">

                <div class="col-md-6">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Venues</div>
                            <div class="eh-stat-number" id="totalVenues">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Capacity</div>
                            <div class="eh-stat-number" id="totalCapacity">0</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- SEARCH AND REFRESH -->
            <div class="card eh-filter-card mb-4">
                <div class="card-body">

                    <div class="row g-3 align-items-end">

                        <div class="col-md-9">
                            <label for="searchInput" class="form-label">
                                Search Venues
                            </label>

                            <input type="search"
                                   id="searchInput"
                                   class="form-control"
                                   placeholder="Search venue name or address">
                        </div>

                        <div class="col-md-3">
                            <button type="button"
                                    id="refreshButton"
                                    class="btn eh-refresh-btn w-100 d-flex align-items-center justify-content-center gap-2">
                                <?php echo iconSvg("refresh", 17); ?>
                                Refresh Venues
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- VENUE CARDS -->
            <div class="row row-cards" id="venuesContainer">

                <div class="col-12 text-center py-4 text-secondary">
                    Loading venues...
                </div>

            </div>

            <div class="mt-4 text-secondary" id="resultCount">
                Showing 0 venues
            </div>

        </main>

        <footer class="eh-footer">
            © <?php echo date("Y"); ?> EventHub —
            Event Ticketing and Venue Booking System
        </footer>

    </div>
</div>

<!-- LOCATION MODAL -->
<div class="modal modal-blur fade"
     id="locationModal"
     tabindex="-1"
     aria-labelledby="locationModalTitle"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="locationModalTitle">
                    Venue Location
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <div id="locationLoading"
                     class="text-center py-4 text-secondary">
                    Loading location...
                </div>

                <div id="locationError"
                     class="alert alert-danger d-none"></div>

                <div id="locationDetails" class="d-none">

                    <p class="mb-2">
                        <strong>Address:</strong>
                        <span id="locationAddress"></span>
                    </p>

                    <p class="mb-3">
                        <strong>Coordinates:</strong>
                        <span id="locationCoordinates"></span>
                    </p>

                    <div id="venueMap"></div>

                    <div class="mt-3">
                        <a id="openMapLink"
                           href="#"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn eh-btn">
                            Open in OpenStreetMap
                        </a>
                    </div>

                    <div class="mt-2 small text-secondary">
                        Map data © OpenStreetMap contributors
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
"use strict";

const VENUES_API = "../api/venues.php";
const LOCATION_API = "../api/venue_location.php";
const LOGOUT_API = "../api/logout.php";

let venues = [];
let map = null;
let mapMarker = null;
let locationRequestId = 0;

const venuesContainer = document.getElementById("venuesContainer");
const alertBox = document.getElementById("alertBox");

const sidebar = document.getElementById("sidebar");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const menuButton = document.getElementById("menuButton");

const locationModalElement =
    document.getElementById("locationModal");

const locationModal =
    new bootstrap.Modal(locationModalElement);

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

function clearError() {
    alertBox.textContent = "";
    alertBox.classList.add("d-none");
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

function createTextElement(tag, text, className = "") {
    const element = document.createElement(tag);
    element.textContent = text;

    if (className) {
        element.className = className;
    }

    return element;
}

function updateStatistics() {
    document.getElementById("totalVenues").textContent =
        venues.length.toLocaleString("en-MY");

    const capacity = venues.reduce(
        (sum, venue) => sum + Number(venue.capacity || 0),
        0
    );

    document.getElementById("totalCapacity").textContent =
        capacity.toLocaleString("en-MY");
}

function renderVenues() {
    const query = document.getElementById("searchInput")
        .value.trim().toLowerCase();

    const filteredVenues = venues.filter(venue => {
        const searchable = [
            venue.venue_name,
            venue.address
        ].join(" ").toLowerCase();

        return searchable.includes(query);
    });

    venuesContainer.replaceChildren();

    document.getElementById("resultCount").textContent =
        "Showing " + filteredVenues.length +
        " of " + venues.length + " venues";

    if (filteredVenues.length === 0) {
        const empty = createTextElement(
            "div",
            "No venues found.",
            "col-12 text-center py-4 text-secondary"
        );

        venuesContainer.appendChild(empty);
        return;
    }

    filteredVenues.forEach(venue => {
        const column = document.createElement("div");
        column.className = "col-md-6 col-lg-4";

        const card = document.createElement("div");
        card.className = "card venue-card";

        const body = document.createElement("div");
        body.className = "card-body d-flex flex-column";

        const title = createTextElement(
            "h3",
            venue.venue_name,
            "card-title mb-3"
        );

        const address = createTextElement(
            "p",
            venue.address,
            "text-secondary venue-address"
        );

        const capacity = createTextElement(
            "p",
            "Capacity: " +
                Number(venue.capacity).toLocaleString("en-MY"),
            "mb-3"
        );

        const button = createTextElement(
            "button",
            "View Location",
            "btn eh-btn mt-auto"
        );

        button.type = "button";

        button.addEventListener("click", () => {
            viewLocation(venue);
        });

        body.appendChild(title);
        body.appendChild(address);
        body.appendChild(capacity);
        body.appendChild(button);

        card.appendChild(body);
        column.appendChild(card);

        venuesContainer.appendChild(column);
    });
}

async function loadVenues() {
    clearError();

    const refreshButton = document.getElementById("refreshButton");
    refreshButton.disabled = true;

    try {
        const result = await apiRequest(VENUES_API);

        venues = Array.isArray(result.data)
            ? result.data
            : [];

        updateStatistics();
        renderVenues();

    } catch (error) {
        showError(error.message);

        venuesContainer.replaceChildren();

        venuesContainer.appendChild(
            createTextElement(
                "div",
                "Unable to load venues.",
                "col-12 text-center py-4 text-secondary"
            )
        );

    } finally {
        refreshButton.disabled = false;
    }
}

async function viewLocation(venue) {
    const requestId = ++locationRequestId;

    document.getElementById("locationModalTitle").textContent =
        venue.venue_name + " - Location";

    document.getElementById("locationLoading")
        .classList.remove("d-none");

    document.getElementById("locationError")
        .classList.add("d-none");

    document.getElementById("locationDetails")
        .classList.add("d-none");

    locationModal.show();

    try {
        const result = await apiRequest(
            LOCATION_API + "?id=" +
            encodeURIComponent(venue.venue_id)
        );

        if (requestId !== locationRequestId) {
            return;
        }

        const location = result.data;

        if (!location) {
            throw new Error("Location data not available.");
        }

        const latitude = Number(location.latitude);
        const longitude = Number(location.longitude);

        if (
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude) ||
            latitude < -90 || latitude > 90 ||
            longitude < -180 || longitude > 180
        ) {
            throw new Error("Invalid venue coordinates.");
        }

        document.getElementById("locationAddress").textContent =
            location.display_name || location.address || venue.address;

        document.getElementById("locationCoordinates").textContent =
            latitude.toFixed(6) + ", " + longitude.toFixed(6);

        const openMapLink = document.getElementById("openMapLink");

        openMapLink.href =
            "https://www.openstreetmap.org/?mlat=" +
            encodeURIComponent(latitude) +
            "&mlon=" +
            encodeURIComponent(longitude) +
            "#map=16/" + latitude + "/" + longitude;

        document.getElementById("locationLoading")
            .classList.add("d-none");

        document.getElementById("locationDetails")
            .classList.remove("d-none");

        if (!map) {
            map = L.map("venueMap");

            L.tileLayer(
                "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
                {
                    maxZoom: 19,
                    attribution: "© OpenStreetMap contributors"
                }
            ).addTo(map);
        }

        map.setView([latitude, longitude], 16);

        if (mapMarker) {
            map.removeLayer(mapMarker);
        }

        mapMarker = L.marker([latitude, longitude])
            .addTo(map)
            .bindPopup(venue.venue_name)
            .openPopup();

        setTimeout(() => {
            map.invalidateSize();
        }, 250);

    } catch (error) {
        if (requestId !== locationRequestId) {
            return;
        }

        document.getElementById("locationLoading")
            .classList.add("d-none");

        const errorBox = document.getElementById("locationError");
        errorBox.textContent = error.message;
        errorBox.classList.remove("d-none");
    }
}

locationModalElement.addEventListener("hidden.bs.modal", () => {
    locationRequestId++;
});

document.getElementById("searchInput")
    .addEventListener("input", renderVenues);

document.getElementById("refreshButton")
    .addEventListener("click", loadVenues);

document.getElementById("logoutButton")
    .addEventListener("click", async () => {
        if (!confirm("Are you sure you want to logout?")) {
            return;
        }

        const button = document.getElementById("logoutButton");
        button.disabled = true;

        try {
            await apiRequest(LOGOUT_API, {
                method: "POST"
            });

            window.location.replace("login.php");

        } catch (error) {
            showError(error.message);
            button.disabled = false;
        }
    });

loadVenues();
</script>

</body>
</html>
