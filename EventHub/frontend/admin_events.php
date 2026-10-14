
<?php
session_start();

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["role"] !== "admin") {
    http_response_code(403);
    exit("Access denied. Admin only.");
}

$currentUserId = (int) $_SESSION["user_id"];

function iconSvg($name, $size = 20) {
    $icons = [
        "dashboard" => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        "users" => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        "building" => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h1m6 0h1M8 10h1m6 0h1M8 14h1m6 0h1"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1-2-2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        "star" => '<path d="m12 2 3.1 6.3 7 1-5.05 4.93 1.2 6.97L12 17.9l-6.25 3.3 1.2-6.97L1.9 9.3l7-1L12 2Z"/>',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        "refresh" => '<path d="M20 11a8 8 0 0 0-14-5L4 8m0-5v5h5M4 13a8 8 0 0 0 14 5l2-2m0 5v-5h-5"/>'
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
    <title>View Events | EventHub Admin</title>

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

        .eh-brand:hover { color: #fff; }

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

        .eh-nav-link.active { font-weight: 700; }

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

        .eh-table-card {
            border: 1px solid #e9edf2;
            border-radius: 13px;
            overflow: hidden;
        }

        .eh-table-card .card-header {
            background: #fff;
            padding: 20px 24px;
        }

        .eh-table-card .card-title {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .eh-table-card thead { background: #f8fafc; }

        .eh-table-card th {
            color: #64748b;
            font-size: .76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .eh-table-card td { vertical-align: middle; }

        .eh-filter-area {
            background: #fff;
            border-bottom: 1px solid #e9edf2;
        }

        .eh-filter-area .form-control:focus,
        .eh-filter-area .form-select:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 .2rem rgba(127,29,29,.12);
        }

        .eh-event-name {
            font-weight: 700;
            color: #202936;
        }

        .eh-ticket-count {
            font-weight: 600;
            white-space: nowrap;
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

        .eh-overlay { display: none; }

        @media (max-width: 991px) {
            .eh-sidebar {
                position: fixed;
                top: 0;
                left: -270px;
                height: 100vh;
                transition: left .25s;
            }

            .eh-sidebar.open { left: 0; }

            .eh-overlay.open {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.45);
                z-index: 1020;
            }

            .eh-menu-button { display: inline-flex; }
            .eh-topbar { padding: 0 18px; }
            .eh-content { padding: 22px 16px; }
            .eh-page-title { font-size: 1.55rem; }
        }
    </style>
</head>

<body>

<div id="sidebarOverlay" class="eh-overlay"></div>

<div class="eh-layout">

    <!-- SIDEBAR -->
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

            <a href="manage_users.php" class="eh-nav-link">
                <?php echo iconSvg("users"); ?>
                <span>Manage Users</span>
            </a>

            <a href="manage_venues.php" class="eh-nav-link">
                <?php echo iconSvg("building"); ?>
                <span>Manage Venues</span>
            </a>

            <a href="admin_events.php"
               class="eh-nav-link active"
               aria-current="page">
                <?php echo iconSvg("calendar"); ?>
                <span>View Events</span>
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
                <div class="fw-bold mt-1">Admin</div>
                <div class="small" style="color:#f1c7c7">
                    User #<?php echo $currentUserId; ?>
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

                <h2 class="eh-topbar-title">Event Overview</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="eh-role">Admin</span>
                <span class="eh-avatar">A</span>
            </div>
        </header>

        <main class="eh-content">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="eh-page-title mb-1">View Events</h1>
                    <p class="text-secondary mb-0">
                        Monitor all events, ticket availability and event status.
                    </p>
                </div>

                <button type="button"
                        id="refreshButton"
                        class="btn eh-btn d-flex align-items-center gap-2">
                    <?php echo iconSvg("refresh", 18); ?>
                    Refresh
                </button>
            </div>

            <div id="alertBox"
                 class="alert alert-danger d-none"
                 role="alert"></div>

            <!-- STATISTICS -->
            <div class="row row-cards mb-4">

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Events</div>
                            <div class="eh-stat-number" id="totalEvents">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Tickets</div>
                            <div class="eh-stat-number" id="totalTickets">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Available Tickets</div>
                            <div class="eh-stat-number" id="availableTickets">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Sold Out Events</div>
                            <div class="eh-stat-number" id="soldOutEvents">0</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- EVENTS TABLE -->
            <div class="card eh-table-card">

                <div class="card-header">
                    <h3 class="card-title mb-0">Event Directory</h3>
                </div>

                <div class="eh-filter-area p-3">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="searchInput" class="form-label">
                                Search Events
                            </label>
                            <input type="search"
                                   id="searchInput"
                                   class="form-control"
                                   placeholder="Search by event, organiser or venue...">
                        </div>

                        <div class="col-md-4">
                            <label for="venueFilter" class="form-label">
                                Filter by Venue
                            </label>
                            <select id="venueFilter" class="form-select">
                                <option value="">All Venues</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event Name</th>
                                <th>Organiser</th>
                                <th>Venue</th>
                                <th>Event Date</th>
                                <th>Price</th>
                                <th>Tickets</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody id="eventsTable">
                            <tr>
                                <td colspan="8"
                                    class="text-center py-4 text-secondary">
                                    Loading events...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    <span id="resultCount" class="text-secondary">
                        Showing 0 events
                    </span>
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
const LOGOUT_API = "../api/logout.php";

let events = [];
let venues = [];

const eventsTable = document.getElementById("eventsTable");
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
        throw new Error("Session expired. Please login again.");
    }

    let result;

    try {
        result = await response.json();
    } catch (error) {
        throw new Error("Invalid JSON response from server.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "API request failed.");
    }

    return result;
}

function createCell(value) {
    const cell = document.createElement("td");
    cell.textContent = value ?? "-";
    return cell;
}

function venueName(venueId) {
    const venue = venues.find(
        item => Number(item.venue_id) === Number(venueId)
    );

    return venue
        ? venue.venue_name
        : "Venue #" + venueId;
}

function formatDate(value) {
    if (!value) return "-";

    const date = new Date(
        String(value).replace(" ", "T")
    );

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString("en-MY", {
        dateStyle: "medium",
        timeStyle: "short"
    });
}

function formatPrice(value) {
    return "RM " + Number(value).toFixed(2);
}

function getEventStatus(event) {
    const available = Number(event.tickets_available);

    if (available <= 0) {
        return {
            label: "Sold Out",
            className: "badge bg-red-lt"
        };
    }

    const eventDate = new Date(
        String(event.event_date).replace(" ", "T")
    );

    if (
        !Number.isNaN(eventDate.getTime()) &&
        eventDate < new Date()
    ) {
        return {
            label: "Past Event",
            className: "badge bg-secondary-lt"
        };
    }

    return {
        label: "Available",
        className: "badge bg-green-lt"
    };
}

function updateStatistics() {
    const totalEvents = events.length;

    const totalTickets = events.reduce(
        (sum, event) => sum + Number(event.total_tickets || 0),
        0
    );

    const availableTickets = events.reduce(
        (sum, event) => sum + Number(event.tickets_available || 0),
        0
    );

    const soldOutEvents = events.filter(
        event => Number(event.tickets_available) <= 0
    ).length;

    document.getElementById("totalEvents").textContent =
        totalEvents.toLocaleString("en-MY");

    document.getElementById("totalTickets").textContent =
        totalTickets.toLocaleString("en-MY");

    document.getElementById("availableTickets").textContent =
        availableTickets.toLocaleString("en-MY");

    document.getElementById("soldOutEvents").textContent =
        soldOutEvents.toLocaleString("en-MY");
}

function renderEvents() {
    const searchQuery = document.getElementById("searchInput")
        .value.trim().toLowerCase();

    const selectedVenue = document.getElementById("venueFilter")
        .value;

    const filteredEvents = events.filter(event => {
        const searchableText = [
            event.event_name,
            event.organiser_id,
            venueName(event.venue_id)
        ].join(" ").toLowerCase();

        const matchesSearch =
            searchableText.includes(searchQuery);

        const matchesVenue =
            selectedVenue === "" ||
            String(event.venue_id) === selectedVenue;

        return matchesSearch && matchesVenue;
    });

    eventsTable.replaceChildren();

    document.getElementById("resultCount").textContent =
        "Showing " + filteredEvents.length + " of " +
        events.length + " events";

    if (filteredEvents.length === 0) {
        const row = document.createElement("tr");
        const cell = createCell("No events found.");

        cell.colSpan = 8;
        cell.className = "text-center py-4 text-secondary";

        row.appendChild(cell);
        eventsTable.appendChild(row);
        return;
    }

    filteredEvents.forEach(event => {
        const row = document.createElement("tr");

        row.appendChild(createCell(event.event_id));

        const nameCell = createCell(event.event_name);
        nameCell.classList.add("eh-event-name");
        row.appendChild(nameCell);

        row.appendChild(createCell(event.organiser_id));
        row.appendChild(createCell(venueName(event.venue_id)));
        row.appendChild(createCell(formatDate(event.event_date)));
        row.appendChild(createCell(formatPrice(event.ticket_price)));

        const ticketCell = createCell(
            event.tickets_available + " / " +
            event.total_tickets
        );

        ticketCell.classList.add("eh-ticket-count");
        row.appendChild(ticketCell);

        const statusCell = document.createElement("td");
        const status = getEventStatus(event);

        const badge = document.createElement("span");
        badge.className = status.className;
        badge.textContent = status.label;

        statusCell.appendChild(badge);
        row.appendChild(statusCell);

        eventsTable.appendChild(row);
    });
}

function populateVenues() {
    const venueFilter = document.getElementById("venueFilter");
    const previousSelection = venueFilter.value;

    venueFilter.replaceChildren();

    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = "All Venues";
    venueFilter.appendChild(defaultOption);

    venues.forEach(venue => {
        const option = document.createElement("option");
        option.value = venue.venue_id;
        option.textContent = venue.venue_name;
        venueFilter.appendChild(option);
    });

    if (
        previousSelection &&
        venues.some(
            venue => String(venue.venue_id) === previousSelection
        )
    ) {
        venueFilter.value = previousSelection;
    }
}

async function loadData() {
    clearError();

    const refreshButton = document.getElementById("refreshButton");
    refreshButton.disabled = true;

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

        populateVenues();
        updateStatistics();
        renderEvents();

    } catch (error) {
        showError(error.message);

        eventsTable.replaceChildren();

        const row = document.createElement("tr");
        const cell = createCell("Unable to load events.");

        cell.colSpan = 8;
        cell.className = "text-center py-4 text-secondary";

        row.appendChild(cell);
        eventsTable.appendChild(row);

    } finally {
        refreshButton.disabled = false;
    }
}

async function logout() {
    if (!confirm("Are you sure you want to logout?")) {
        return;
    }

    const logoutButton = document.getElementById("logoutButton");
    logoutButton.disabled = true;

    try {
        await apiRequest(LOGOUT_API, {
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

document.getElementById("refreshButton")
    .addEventListener("click", loadData);

document.getElementById("logoutButton")
    .addEventListener("click", logout);

loadData();
</script>

</body>
</html>
