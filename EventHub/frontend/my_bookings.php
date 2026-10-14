
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
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1 2 2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
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
    <title>My Bookings | EventHub</title>

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

        .eh-stat-number.pending {
            color: #d97706;
        }

        .eh-stat-number.confirmed {
            color: #15803d;
        }

        .eh-stat-number.cancelled {
            color: #dc2626;
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
            padding: 22px 24px;
        }

        .eh-table thead th {
            background: #fafbfc;
            color: #6b7280;
            font-size: .77rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
        }

        .eh-table td {
            vertical-align: middle;
            padding-top: 15px;
            padding-bottom: 15px;
        }

        .eh-booking-id {
            font-weight: 750;
            color: var(--eh-red);
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

        .eh-cancel-btn {
            border-radius: 7px;
            font-weight: 600;
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

            <a href="book_tickets.php" class="eh-nav-link">
                <?php echo iconSvg("ticket"); ?>
                <span>Book Tickets</span>
            </a>

            <a href="my_bookings.php"
               class="eh-nav-link active"
               aria-current="page">
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

                <h2 class="eh-topbar-title">Booking Management</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="eh-role">Customer</span>
                <span class="eh-avatar">C</span>
            </div>
        </header>

        <main class="eh-content">

            <!-- PAGE HEADER -->
            <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>
                    <h1 class="eh-page-title mb-1">My Bookings</h1>
                    <p class="text-secondary mb-0">
                        Track and manage your event reservations.
                    </p>
                </div>

                <a href="browse_events.php" class="btn eh-btn">
                    Browse Events
                </a>
            </div>

            <div id="alertBox" class="alert d-none" role="alert"></div>

            <!-- STATISTICS -->
            <div class="row row-cards mb-4">

                <div class="col-md-3 col-6">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Bookings</div>
                            <div class="eh-stat-number" id="totalBookings">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Pending</div>
                            <div class="eh-stat-number pending"
                                 id="pendingBookings">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Confirmed</div>
                            <div class="eh-stat-number confirmed"
                                 id="confirmedBookings">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Cancelled</div>
                            <div class="eh-stat-number cancelled"
                                 id="cancelledBookings">0</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- BOOKING HISTORY -->
            <div class="card eh-card">

                <div class="card-header">
                    <h3 class="card-title mb-0">Booking History</h3>
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-8">
                            <label for="searchInput" class="form-label">
                                Search Bookings
                            </label>

                            <input type="search"
                                   id="searchInput"
                                   class="form-control"
                                   placeholder="Search booking ID or event name">
                        </div>

                        <div class="col-md-4">
                            <label for="statusFilter" class="form-label">
                                Booking Status
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
                    <table class="table table-vcenter card-table eh-table">

                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Event</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                                <th>Booked At</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="bookingsTable">
                            <tr>
                                <td colspan="7"
                                    class="text-center py-4 text-secondary">
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
let cancellingBookingId = null;

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

function getEvent(eventId) {
    return events.find(
        event => Number(event.event_id) === Number(eventId)
    );
}

function eventName(eventId) {
    const event = getEvent(eventId);
    return event ? event.event_name : "Event #" + eventId;
}

function totalPrice(booking) {
    const event = getEvent(booking.event_id);

    if (!event) {
        return "N/A";
    }

    // Calculated using the event's current ticket price.
    return "RM " + (
        Number(event.ticket_price) * Number(booking.quantity)
    ).toFixed(2);
}

function createCell(value) {
    const td = document.createElement("td");
    td.textContent = value ?? "";
    return td;
}

function createStatusBadge(status) {
    const styles = {
        pending: "bg-yellow-lt",
        confirmed: "bg-green-lt",
        cancelled: "bg-red-lt"
    };

    const badge = document.createElement("span");

    badge.className = "badge " +
        (styles[status] || "bg-secondary-lt");

    const statusText = String(status || "unknown");

    badge.textContent =
        statusText.charAt(0).toUpperCase() + statusText.slice(1);

    return badge;
}

function updateStats() {
    document.getElementById("totalBookings").textContent =
        bookings.length.toLocaleString("en-MY");

    ["pending", "confirmed", "cancelled"].forEach(status => {
        const count = bookings.filter(
            booking => booking.booking_status === status
        ).length;

        document.getElementById(
            status + "Bookings"
        ).textContent = count.toLocaleString("en-MY");
    });
}

function renderBookings(list) {
    bookingsTable.replaceChildren();

    if (list.length === 0) {
        const row = document.createElement("tr");
        const cell = createCell("No bookings found.");

        cell.colSpan = 7;
        cell.className = "text-center py-4 text-secondary";

        row.appendChild(cell);
        bookingsTable.appendChild(row);
        return;
    }

    list.forEach(booking => {
        const row = document.createElement("tr");

        const bookingIdCell = createCell(
            "#" + booking.booking_id
        );

        bookingIdCell.className = "eh-booking-id";

        row.appendChild(bookingIdCell);
        row.appendChild(createCell(eventName(booking.event_id)));
        row.appendChild(createCell(booking.quantity));
        row.appendChild(createCell(totalPrice(booking)));
        row.appendChild(createCell(booking.booked_at));

        const statusCell = document.createElement("td");

        statusCell.appendChild(
            createStatusBadge(booking.booking_status)
        );

        row.appendChild(statusCell);

        const actionCell = document.createElement("td");

        if (booking.booking_status !== "cancelled") {
            const button = document.createElement("button");

            button.type = "button";
            button.className =
                "btn btn-outline-danger btn-sm eh-cancel-btn";

            button.textContent =
                cancellingBookingId === booking.booking_id
                    ? "Cancelling..."
                    : "Cancel";

            button.disabled =
                cancellingBookingId !== null;

            button.addEventListener("click", () => {
                cancelBooking(booking.booking_id);
            });

            actionCell.appendChild(button);

        } else {
            actionCell.textContent = "No actions";
            actionCell.className = "text-secondary";
        }

        row.appendChild(actionCell);
        bookingsTable.appendChild(row);
    });
}

function filterBookings() {
    const query = document.getElementById("searchInput")
        .value.toLowerCase().trim();

    const status = document.getElementById("statusFilter").value;

    const filtered = bookings.filter(booking => {
        const matchesSearch =
            String(booking.booking_id).includes(query) ||
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

    bookings.sort(
        (a, b) => Number(b.booking_id) - Number(a.booking_id)
    );

    updateStats();
    filterBookings();
}

async function cancelBooking(bookingId) {
    if (cancellingBookingId !== null) {
        return;
    }

    if (!confirm(
        "Are you sure you want to cancel booking #" +
        bookingId + "?"
    )) {
        return;
    }

    cancellingBookingId = bookingId;
    filterBookings();

    try {
        const result = await apiRequest(
            BOOKINGS_API + "?id=" + encodeURIComponent(bookingId),
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    booking_status: "cancelled"
                })
            }
        );

        showAlert(
            result.message || "Booking cancelled successfully."
        );

        await loadBookings();

    } catch (error) {
        showAlert(error.message, "danger");

    } finally {
        cancellingBookingId = null;
        filterBookings();
    }
}

async function logout() {
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
}

async function initialize() {
    document.getElementById("searchInput")
        .addEventListener("input", filterBookings);

    document.getElementById("statusFilter")
        .addEventListener("change", filterBookings);

    document.getElementById("logoutButton")
        .addEventListener("click", logout);

    try {
        const eventsResult = await apiRequest(EVENTS_API);

        events = Array.isArray(eventsResult.data)
            ? eventsResult.data
            : [];

        await loadBookings();

    } catch (error) {
        showAlert(error.message, "danger");

        bookingsTable.replaceChildren();

        const row = document.createElement("tr");
        const cell = createCell("Unable to load bookings.");

        cell.colSpan = 7;
        cell.className = "text-center py-4 text-secondary";

        row.appendChild(cell);
        bookingsTable.appendChild(row);
    }
}

initialize();
</script>

</body>
</html>
