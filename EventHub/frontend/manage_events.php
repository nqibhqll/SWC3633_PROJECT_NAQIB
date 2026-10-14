
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
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        "map" => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        "star" => '<path d="m12 2 3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2Z"/>',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        "plus" => '<path d="M12 5v14M5 12h14"/>'
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
    <title>Manage Events | EventHub</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">

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

        .eh-table-card thead {
            background: #f8fafc;
        }

        .eh-table-card th {
            color: #64748b;
            font-size: .76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .eh-table-card td {
            vertical-align: middle;
        }

        .eh-search {
            max-width: 280px;
            min-width: 200px;
        }

        .eh-search:focus,
        .form-control:focus,
        .form-select:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 .2rem rgba(127,29,29,.12);
        }

        .table-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .eh-edit-btn {
            color: var(--eh-red);
            border-color: #e5b8b8;
        }

        .eh-edit-btn:hover {
            background: var(--eh-red);
            border-color: var(--eh-red);
            color: #fff;
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

        .modal-content {
            border-radius: 13px;
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
            .eh-search {
                max-width: 100%;
                width: 100%;
            }

            .eh-table-card .card-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
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

            <a href="manage_events.php"
               class="eh-nav-link active"
               aria-current="page">
                <?php echo iconSvg("calendar"); ?>
                <span>Manage Events</span>
            </a>

            <a href="event_bookings.php" class="eh-nav-link">
                <?php echo iconSvg("ticket"); ?>
                <span>Event Bookings</span>
            </a>

            <a href="venue_locations.php" class="eh-nav-link">
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

                <h2 class="eh-topbar-title">Event Management</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="eh-role">Organiser</span>
                <span class="eh-avatar">O</span>
            </div>

        </header>

        <main class="eh-content">

            <!-- PAGE HEADER -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>
                    <h1 class="eh-page-title mb-1">Manage Events</h1>
                    <p class="text-secondary mb-0">
                        Create and manage your own events.
                    </p>
                </div>

                <button type="button"
                        id="addEventButton"
                        class="btn eh-btn d-flex align-items-center gap-2">
                    <?php echo iconSvg("plus", 18); ?>
                    Add New Event
                </button>

            </div>

            <div id="alertBox" class="alert d-none" role="alert"></div>

            <!-- STATISTICS -->
            <div class="row row-cards mb-4">

                <div class="col-md-4">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">My Events</div>
                            <div class="eh-stat-number" id="totalEvents">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Tickets</div>
                            <div class="eh-stat-number" id="totalTickets">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Available Tickets</div>
                            <div class="eh-stat-number" id="availableTickets">0</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- EVENT DIRECTORY -->
            <div class="card eh-table-card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h3 class="card-title mb-0">My Event Directory</h3>

                    <input id="searchInput"
                           type="search"
                           class="form-control eh-search"
                           placeholder="Search my events..."
                           aria-label="Search my events">

                </div>

                <div class="table-responsive">

                    <table class="table table-vcenter card-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Event</th>
                                <th>Venue</th>
                                <th>Date</th>
                                <th>Price</th>
                                <th>Tickets</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="eventsTable">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    Loading events...
                                </td>
                            </tr>
                        </tbody>

                    </table>

                </div>
            </div>

        </main>

        <footer class="eh-footer">
            © <?php echo date("Y"); ?> EventHub —
            Event Ticketing and Venue Booking System
        </footer>

    </div>
</div>

<!-- ADD / EDIT EVENT MODAL -->
<div class="modal modal-blur fade"
     id="eventModal"
     tabindex="-1"
     aria-labelledby="eventModalTitle"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form id="eventForm">

                <div class="modal-header">

                    <h5 class="modal-title" id="eventModalTitle">
                        Add Event
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="eventId">

                    <div class="mb-3">
                        <label for="eventName" class="form-label">
                            Event Name
                        </label>

                        <input id="eventName"
                               class="form-control"
                               maxlength="150"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="eventDescription" class="form-label">
                            Description
                        </label>

                        <textarea id="eventDescription"
                                  class="form-control"
                                  rows="3"></textarea>
                    </div>

                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label for="eventVenue" class="form-label">
                                Venue
                            </label>

                            <select id="eventVenue"
                                    class="form-select"
                                    required>
                                <option value="">Select venue</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="eventDate" class="form-label">
                                Event Date & Time
                            </label>

                            <input id="eventDate"
                                   type="datetime-local"
                                   class="form-control"
                                   step="1"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="eventPrice" class="form-label">
                                Ticket Price (RM)
                            </label>

                            <input id="eventPrice"
                                   type="number"
                                   min="0"
                                   max="99999999.99"
                                   step="0.01"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="eventTotalTickets" class="form-label">
                                Total Tickets
                            </label>

                            <input id="eventTotalTickets"
                                   type="number"
                                   min="1"
                                   step="1"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="eventAvailableTickets" class="form-label">
                                Available Tickets
                            </label>

                            <input id="eventAvailableTickets"
                                   type="number"
                                   min="0"
                                   step="1"
                                   class="form-control"
                                   required>
                        </div>

                    </div>

                    <div class="alert alert-info mb-0">
                        Available tickets cannot exceed total tickets.
                        For existing bookings, do not change ticket counts
                        without accounting for tickets already booked.
                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            id="saveEventButton"
                            class="btn eh-btn">
                        Save Event
                    </button>

                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>

<script>
"use strict";

const EVENTS_API = "../api/events.php";
const VENUES_API = "../api/venues.php";
const CURRENT_USER_ID = <?php echo json_encode($userId); ?>;

let events = [];
let venues = [];
let eventModal;

const eventsTable = document.getElementById("eventsTable");
const alertBox = document.getElementById("alertBox");
const eventForm = document.getElementById("eventForm");
const saveButton = document.getElementById("saveEventButton");

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

function cell(value) {
    const td = document.createElement("td");
    td.textContent = value ?? "";
    return td;
}

function button(label, className, action) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = className;
    btn.textContent = label;
    btn.addEventListener("click", action);
    return btn;
}

async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        credentials: "same-origin",
        ...options
    });

    if (response.status === 401) {
        window.location.replace("login.php");
        throw new Error("Session expired");
    }

    let result;

    try {
        result = await response.json();
    } catch {
        throw new Error("Invalid response from server");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Request failed");
    }

    return result;
}

function venueName(id) {
    const venue = venues.find(
        v => Number(v.venue_id) === Number(id)
    );

    return venue ? venue.venue_name : "Venue #" + id;
}

function renderEvents(list = events) {
    eventsTable.replaceChildren();

    if (!list.length) {
        const tr = document.createElement("tr");
        const td = cell("No events found.");

        td.colSpan = 7;
        td.className = "text-center py-4";

        tr.appendChild(td);
        eventsTable.appendChild(tr);
        return;
    }

    list.forEach(event => {
        const tr = document.createElement("tr");

        tr.appendChild(cell(event.event_id));
        tr.appendChild(cell(event.event_name));
        tr.appendChild(cell(venueName(event.venue_id)));
        tr.appendChild(cell(event.event_date));

        tr.appendChild(cell(
            "RM " + Number(event.ticket_price).toFixed(2)
        ));

        tr.appendChild(cell(
            event.tickets_available + " / " + event.total_tickets
        ));

        const td = document.createElement("td");
        const actions = document.createElement("div");
        actions.className = "table-actions";

        actions.append(
            button(
                "Edit",
                "btn btn-outline-primary eh-edit-btn btn-sm",
                () => openEditModal(event.event_id)
            ),
            button(
                "Delete",
                "btn btn-outline-danger btn-sm",
                () => deleteEvent(event.event_id)
            )
        );

        td.appendChild(actions);
        tr.appendChild(td);
        eventsTable.appendChild(tr);
    });
}

function updateStats() {
    document.getElementById("totalEvents").textContent =
        events.length;

    document.getElementById("totalTickets").textContent =
        events.reduce(
            (sum, e) => sum + Number(e.total_tickets || 0),
            0
        ).toLocaleString();

    document.getElementById("availableTickets").textContent =
        events.reduce(
            (sum, e) => sum + Number(e.tickets_available || 0),
            0
        ).toLocaleString();
}

function filterEvents() {
    const query = document.getElementById("searchInput")
        .value.toLowerCase().trim();

    const filtered = events.filter(e =>
        String(e.event_id).includes(query) ||
        e.event_name.toLowerCase().includes(query) ||
        venueName(e.venue_id).toLowerCase().includes(query)
    );

    renderEvents(filtered);
}

async function loadEvents() {
    try {
        const result = await apiRequest(EVENTS_API);

        const allEvents = Array.isArray(result.data)
            ? result.data
            : [];

        events = allEvents.filter(
            e => Number(e.organiser_id) === CURRENT_USER_ID
        );

        updateStats();
        filterEvents();

    } catch (error) {
        showAlert(error.message, "danger");

        eventsTable.replaceChildren();

        const tr = document.createElement("tr");
        const td = cell("Unable to load events.");

        td.colSpan = 7;
        tr.appendChild(td);
        eventsTable.appendChild(tr);
    }
}

async function loadVenues() {
    const result = await apiRequest(VENUES_API);

    venues = Array.isArray(result.data)
        ? result.data
        : [];

    const select = document.getElementById("eventVenue");
    select.replaceChildren();

    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Select venue";
    select.appendChild(placeholder);

    venues.forEach(venue => {
        const option = document.createElement("option");

        option.value = venue.venue_id;
        option.textContent = venue.venue_name;

        select.appendChild(option);
    });
}

function openAddModal() {
    eventForm.reset();

    document.getElementById("eventId").value = "";
    document.getElementById("eventModalTitle").textContent =
        "Add New Event";

    eventModal.show();
}

function openEditModal(id) {
    const event = events.find(
        e => Number(e.event_id) === Number(id)
    );

    if (!event) {
        showAlert("Event not found", "danger");
        return;
    }

    eventForm.reset();

    document.getElementById("eventId").value = event.event_id;
    document.getElementById("eventName").value = event.event_name;
    document.getElementById("eventDescription").value =
        event.description || "";

    document.getElementById("eventVenue").value = event.venue_id;

    document.getElementById("eventDate").value =
        String(event.event_date).replace(" ", "T");

    document.getElementById("eventPrice").value =
        event.ticket_price;

    document.getElementById("eventTotalTickets").value =
        event.total_tickets;

    document.getElementById("eventAvailableTickets").value =
        event.tickets_available;

    document.getElementById("eventModalTitle").textContent =
        "Edit Event";

    eventModal.show();
}

async function saveEvent(e) {
    e.preventDefault();

    const id = document.getElementById("eventId").value;

    const total = Number(
        document.getElementById("eventTotalTickets").value
    );

    const available = Number(
        document.getElementById("eventAvailableTickets").value
    );

    if (
        !Number.isInteger(total) ||
        total <= 0 ||
        !Number.isInteger(available) ||
        available < 0 ||
        available > total
    ) {
        showAlert("Invalid ticket quantities", "danger");
        return;
    }

    const dateValue = document.getElementById("eventDate").value;
    const dateFormatted = dateValue.replace("T", " ");

    const payload = {
        event_name: document.getElementById("eventName").value.trim(),
        description: document.getElementById("eventDescription").value.trim(),
        venue_id: Number(document.getElementById("eventVenue").value),
        event_date: dateFormatted.length === 16
            ? dateFormatted + ":00"
            : dateFormatted,
        ticket_price: Number(
            document.getElementById("eventPrice").value
        ),
        total_tickets: total,
        tickets_available: available
    };

    saveButton.disabled = true;
    saveButton.textContent = "Saving...";

    try {
        const url = id
            ? EVENTS_API + "?id=" + encodeURIComponent(id)
            : EVENTS_API;

        const result = await apiRequest(url, {
            method: id ? "PUT" : "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        eventModal.hide();

        showAlert(result.message || "Event saved");

        await loadEvents();

    } catch (error) {
        showAlert(error.message, "danger");

    } finally {
        saveButton.disabled = false;
        saveButton.textContent = "Save Event";
    }
}

async function deleteEvent(id) {
    const event = events.find(
        e => Number(e.event_id) === Number(id)
    );

    if (!event) return;

    if (!confirm(
        'Delete event "' + event.event_name + '"?'
    )) {
        return;
    }

    try {
        const result = await apiRequest(
            EVENTS_API + "?id=" + encodeURIComponent(id),
            { method: "DELETE" }
        );

        showAlert(result.message || "Event deleted");

        await loadEvents();

    } catch (error) {
        showAlert(error.message, "danger");
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
        showAlert(error.message, "danger");
        logoutButton.disabled = false;
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    eventModal = new bootstrap.Modal(
        document.getElementById("eventModal")
    );

    document.getElementById("addEventButton")
        .addEventListener("click", openAddModal);

    document.getElementById("searchInput")
        .addEventListener("input", filterEvents);

    document.getElementById("logoutButton")
        .addEventListener("click", logout);

    eventForm.addEventListener("submit", saveEvent);

    try {
        await loadVenues();
        await loadEvents();

    } catch (error) {
        showAlert(error.message, "danger");
    }
});
</script>

</body>
</html>
