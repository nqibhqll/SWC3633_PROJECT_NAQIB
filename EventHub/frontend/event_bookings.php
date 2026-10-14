
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
    <title>Event Bookings | EventHub</title>
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

        .eh-stat-number.pending { color: #b45309; }
        .eh-stat-number.confirmed { color: #15803d; }
        .eh-stat-number.cancelled { color: #b91c1c; }

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

        .eh-filter-bar {
            padding: 18px 24px;
            border-bottom: 1px solid #e9edf2;
            background: #fff;
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

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .eh-confirm-btn {
            color: #15803d;
            border-color: #86efac;
        }

        .eh-confirm-btn:hover {
            background: #15803d;
            border-color: #15803d;
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

            <a href="event_bookings.php"
               class="eh-nav-link active"
               aria-current="page">
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

                <h2 class="eh-topbar-title">Booking Management</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="eh-role">Organiser</span>
                <span class="eh-avatar">O</span>
            </div>
        </header>

        <main class="eh-content">
            <div class="mb-4">
                <h1 class="eh-page-title mb-1">Event Bookings</h1>
                <p class="text-secondary mb-0">
                    View and manage customer bookings for your events.
                </p>
            </div>

            <div id="alertBox" class="alert d-none" role="alert"></div>

            <!-- BOOKING STATISTICS -->
            <div class="row row-cards mb-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Bookings</div>
                            <div class="eh-stat-number" id="totalBookings">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Pending</div>
                            <div class="eh-stat-number pending" id="pendingBookings">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Confirmed</div>
                            <div class="eh-stat-number confirmed" id="confirmedBookings">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Cancelled</div>
                            <div class="eh-stat-number cancelled" id="cancelledBookings">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BOOKING TABLE -->
            <div class="card eh-table-card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Booking Directory</h3>
                </div>

                <div class="eh-filter-bar">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="searchInput" class="form-label">
                                Search Bookings
                            </label>
                            <input id="searchInput"
                                   type="search"
                                   class="form-control"
                                   placeholder="Search booking ID, customer ID or event...">
                        </div>

                        <div class="col-md-4">
                            <label for="statusFilter" class="form-label">
                                Filter by Status
                            </label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Event</th>
                                <th>Customer ID</th>
                                <th>Quantity</th>
                                <th>Booked At</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="bookingsTable">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    Loading bookings...
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

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>

<script>
"use strict";

const BOOKINGS_API = "../api/bookings.php";
const EVENTS_API = "../api/events.php";

let bookings = [];
let events = [];

const bookingsTable = document.getElementById("bookingsTable");
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

function createCell(value) {
    const td = document.createElement("td");
    td.textContent = value ?? "";
    return td;
}

function eventName(id) {
    const event = events.find(
        item => Number(item.event_id) === Number(id)
    );

    return event ? event.event_name : "Event #" + id;
}

function statusBadge(status) {
    const badge = document.createElement("span");

    const styles = {
        pending: "bg-yellow-lt",
        confirmed: "bg-green-lt",
        cancelled: "bg-red-lt"
    };

    badge.className = "badge " +
        (styles[status] || "bg-secondary-lt");

    badge.textContent =
        status.charAt(0).toUpperCase() + status.slice(1);

    return badge;
}

function createAction(label, className, callback) {
    const button = document.createElement("button");

    button.type = "button";
    button.className = className;
    button.textContent = label;
    button.addEventListener("click", callback);

    return button;
}

function renderBookings(list) {
    bookingsTable.replaceChildren();

    if (list.length === 0) {
        const row = document.createElement("tr");
        const emptyCell = createCell("No bookings found.");

        emptyCell.colSpan = 7;
        emptyCell.className = "text-center py-4";

        row.appendChild(emptyCell);
        bookingsTable.appendChild(row);
        return;
    }

    list.forEach(booking => {
        const row = document.createElement("tr");

        row.appendChild(createCell(booking.booking_id));
        row.appendChild(createCell(eventName(booking.event_id)));
        row.appendChild(createCell(booking.customer_id));
        row.appendChild(createCell(booking.quantity));
        row.appendChild(createCell(booking.booked_at));

        const statusCell = document.createElement("td");

        statusCell.appendChild(
            statusBadge(booking.booking_status)
        );

        row.appendChild(statusCell);

        const actionCell = document.createElement("td");
        const actions = document.createElement("div");
        actions.className = "actions";

        if (booking.booking_status === "pending") {
            actions.appendChild(
                createAction(
                    "Confirm",
                    "btn btn-outline-success eh-confirm-btn btn-sm",
                    () => updateBooking(
                        booking.booking_id,
                        "confirmed"
                    )
                )
            );
        }

        if (booking.booking_status !== "cancelled") {
            actions.appendChild(
                createAction(
                    "Cancel",
                    "btn btn-outline-danger btn-sm",
                    () => updateBooking(
                        booking.booking_id,
                        "cancelled"
                    )
                )
            );
        }

        if (booking.booking_status === "cancelled") {
            const label = document.createElement("span");
            label.className = "text-secondary small";
            label.textContent = "No actions";
            actions.appendChild(label);
        }

        actionCell.appendChild(actions);
        row.appendChild(actionCell);

        bookingsTable.appendChild(row);
    });
}

function updateStats() {
    document.getElementById("totalBookings").textContent =
        bookings.length;

    for (const status of ["pending", "confirmed", "cancelled"]) {
        const count = bookings.filter(
            booking => booking.booking_status === status
        ).length;

        document.getElementById(
            status + "Bookings"
        ).textContent = count;
    }
}

function filterBookings() {
    const query = document.getElementById("searchInput")
        .value.toLowerCase().trim();

    const status = document.getElementById("statusFilter").value;

    const filtered = bookings.filter(booking => {
        const matchesSearch =
            String(booking.booking_id).includes(query) ||
            String(booking.customer_id).includes(query) ||
            eventName(booking.event_id)
                .toLowerCase().includes(query);

        const matchesStatus =
            !status || booking.booking_status === status;

        return matchesSearch && matchesStatus;
    });

    renderBookings(filtered);
}

async function loadBookings() {
    const result = await apiRequest(BOOKINGS_API);

    bookings = Array.isArray(result.data)
        ? result.data
        : [];

    updateStats();
    filterBookings();
}

async function loadEvents() {
    const result = await apiRequest(EVENTS_API);

    events = Array.isArray(result.data)
        ? result.data
        : [];
}

async function updateBooking(id, status) {
    const action = status === "confirmed"
        ? "confirm"
        : "cancel";

    if (!confirm(
        "Are you sure you want to " +
        action + " booking #" + id + "?"
    )) {
        return;
    }

    try {
        const result = await apiRequest(
            BOOKINGS_API + "?id=" + encodeURIComponent(id),
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    booking_status: status
                })
            }
        );

        showAlert(
            result.message || "Booking updated successfully"
        );

        await loadBookings();

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
    document.getElementById("searchInput")
        .addEventListener("input", filterBookings);

    document.getElementById("statusFilter")
        .addEventListener("change", filterBookings);

    document.getElementById("logoutButton")
        .addEventListener("click", logout);

    try {
        await Promise.all([
            loadEvents(),
            loadBookings()
        ]);

        filterBookings();

    } catch (error) {
        showAlert(error.message, "danger");

        bookingsTable.replaceChildren();

        const row = document.createElement("tr");
        const emptyCell = createCell("Unable to load bookings.");

        emptyCell.colSpan = 7;
        emptyCell.className = "text-center py-4";

        row.appendChild(emptyCell);
        bookingsTable.appendChild(row);
    }
});
</script>
</body>
</html>
