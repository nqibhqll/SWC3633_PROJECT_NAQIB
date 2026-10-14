
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
        "star" => '<path d="m12 2 3.1 6.3 7 1-5.1 5 .9 7-5.9-3.2-5.9 3.2.9-7-5.1-5 7-1Z"/>',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        "plus" => '<path d="M12 5v14M5 12h14"/>',
        "pin" => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        "edit" => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L9 17l-4 1 1-4Z"/>',
        "trash" => '<path d="M3 6h18M8 6V4h8v2m3 0-1 15H6L5 6m5 4v7m4-7v7"/>'
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
    <title>Manage Venues | EventHub</title>

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

            <a href="manage_users.php" class="eh-nav-link">
                <?php echo iconSvg("users"); ?>
                <span>Manage Users</span>
            </a>

            <a href="manage_venues.php"
               class="eh-nav-link active"
               aria-current="page">
                <?php echo iconSvg("building"); ?>
                <span>Manage Venues</span>
            </a>

            <a href="admin_events.php" class="eh-nav-link">
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

                <h2 class="eh-topbar-title">Venue Management</h2>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="eh-role">Admin</span>
                <span class="eh-avatar">A</span>
            </div>
        </header>

        <main class="eh-content">

            <!-- PAGE HEADER -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>
                    <h1 class="eh-page-title mb-1">Manage Venues</h1>
                    <p class="text-secondary mb-0">
                        Create, view, update and delete EventHub venues.
                    </p>
                </div>

                <button type="button"
                        id="addVenueButton"
                        class="btn eh-btn d-flex align-items-center gap-2">
                    <?php echo iconSvg("plus", 18); ?>
                    Add New Venue
                </button>

            </div>

            <div id="alertBox"
                 class="alert d-none"
                 role="alert"></div>

            <!-- STATISTICS -->
            <div class="row row-cards mb-4">

                <div class="col-sm-6 col-xl-6">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Venues</div>
                            <div class="eh-stat-number" id="totalVenues">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Capacity</div>
                            <div class="eh-stat-number" id="totalCapacity">0</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- VENUE DIRECTORY -->
            <div class="card eh-table-card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <h3 class="card-title mb-0">Venue Directory</h3>

                    <input type="search"
                           id="searchInput"
                           class="form-control eh-search"
                           placeholder="Search venues..."
                           aria-label="Search venues">

                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Venue Name</th>
                                <th>Address</th>
                                <th>Capacity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="venuesTable">
                            <tr>
                                <td colspan="5"
                                    class="text-center py-4 text-secondary">
                                    Loading venues...
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

<!-- ADD / EDIT VENUE MODAL -->
<div class="modal modal-blur fade"
     id="venueModal"
     tabindex="-1"
     aria-labelledby="venueModalTitle"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form id="venueForm">

                <div class="modal-header">
                    <h5 class="modal-title" id="venueModalTitle">
                        Add Venue
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="venueId">

                    <div class="mb-3">
                        <label for="venueName" class="form-label">
                            Venue Name
                        </label>

                        <input type="text"
                               id="venueName"
                               class="form-control"
                               maxlength="150"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="venueAddress" class="form-label">
                            Address
                        </label>

                        <textarea id="venueAddress"
                                  class="form-control"
                                  rows="3"
                                  required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="venueCapacity" class="form-label">
                            Capacity
                        </label>

                        <input type="number"
                               id="venueCapacity"
                               class="form-control"
                               min="1"
                               step="1"
                               required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            id="saveVenueButton"
                            class="btn eh-btn">
                        Save Venue
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- VIEW VENUE LOCATION MODAL -->
<div class="modal modal-blur fade"
     id="locationModal"
     tabindex="-1"
     aria-labelledby="locationModalTitle"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg">
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

                <div id="locationStatus"
                     class="text-secondary mb-3">
                    Loading location...
                </div>

                <div id="locationDetails" class="d-none">

                    <div class="mb-3">
                        <strong>Venue:</strong>
                        <span id="locationVenue"></span>
                    </div>

                    <div class="mb-3">
                        <strong>Address:</strong>
                        <span id="locationAddress"></span>
                    </div>

                    <div class="mb-3">
                        <strong>Latitude:</strong>
                        <span id="locationLatitude"></span>
                    </div>

                    <div class="mb-3">
                        <strong>Longitude:</strong>
                        <span id="locationLongitude"></span>
                    </div>

                    <a id="openMapButton"
                       class="btn eh-btn"
                       href="#"
                       target="_blank"
                       rel="noopener noreferrer">
                        Open Google Maps
                    </a>

                </div>

            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>

<script>
"use strict";

const VENUES_API = "../api/venues.php";
const LOCATION_API = "../api/venue_location.php";
const LOGOUT_API = "../api/logout.php";

let venues = [];
let venueModal;
let locationModal;

const venuesTable = document.getElementById("venuesTable");
const alertBox = document.getElementById("alertBox");
const venueForm = document.getElementById("venueForm");
const saveVenueButton = document.getElementById("saveVenueButton");

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

function createCell(value) {
    const cell = document.createElement("td");
    cell.textContent = value ?? "-";
    return cell;
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
    } catch {
        throw new Error("Invalid JSON response from server.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "API request failed.");
    }

    return result;
}

function renderVenues(list = venues) {
    venuesTable.replaceChildren();

    if (list.length === 0) {
        const row = document.createElement("tr");
        const cell = createCell("No venues found.");

        cell.colSpan = 5;
        cell.className = "text-center py-4 text-secondary";

        row.appendChild(cell);
        venuesTable.appendChild(row);
        return;
    }

    list.forEach(venue => {
        const row = document.createElement("tr");

        row.appendChild(createCell(venue.venue_id));

        const nameCell = createCell(venue.venue_name);
        nameCell.classList.add("fw-bold");
        row.appendChild(nameCell);

        row.appendChild(createCell(venue.address));

        row.appendChild(
            createCell(
                Number(venue.capacity || 0).toLocaleString("en-MY")
            )
        );

        const actionsCell = document.createElement("td");
        const actions = document.createElement("div");
        actions.className = "table-actions";

        const locationButton = document.createElement("button");
        locationButton.type = "button";
        locationButton.className = "btn btn-outline-secondary btn-sm";
        locationButton.textContent = "Location";
        locationButton.addEventListener("click", () => {
            viewLocation(venue.venue_id);
        });

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.className = "btn btn-outline-primary eh-edit-btn btn-sm";
        editButton.textContent = "Edit";
        editButton.addEventListener("click", () => {
            openEditModal(venue.venue_id);
        });

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.className = "btn btn-outline-danger btn-sm";
        deleteButton.textContent = "Delete";
        deleteButton.addEventListener("click", () => {
            deleteVenue(venue.venue_id);
        });

        actions.append(locationButton, editButton, deleteButton);
        actionsCell.appendChild(actions);
        row.appendChild(actionsCell);

        venuesTable.appendChild(row);
    });
}

function updateStats() {
    document.getElementById("totalVenues").textContent =
        venues.length.toLocaleString("en-MY");

    const capacity = venues.reduce(
        (sum, venue) => sum + Number(venue.capacity || 0),
        0
    );

    document.getElementById("totalCapacity").textContent =
        capacity.toLocaleString("en-MY");
}

async function loadVenues() {
    try {
        const result = await apiRequest(VENUES_API);

        venues = Array.isArray(result.data)
            ? result.data
            : [];

        updateStats();
        filterVenues();

    } catch (error) {
        showAlert(error.message, "danger");

        venuesTable.replaceChildren();

        const row = document.createElement("tr");
        const cell = createCell("Unable to load venues.");

        cell.colSpan = 5;
        cell.className = "text-center py-4 text-secondary";

        row.appendChild(cell);
        venuesTable.appendChild(row);
    }
}

function filterVenues() {
    const keyword = document.getElementById("searchInput")
        .value.trim().toLowerCase();

    const filtered = venues.filter(venue => {
        const searchableText = [
            venue.venue_id,
            venue.venue_name,
            venue.address,
            venue.capacity
        ].join(" ").toLowerCase();

        return searchableText.includes(keyword);
    });

    renderVenues(filtered);
}

function openAddModal() {
    venueForm.reset();

    document.getElementById("venueId").value = "";
    document.getElementById("venueModalTitle").textContent =
        "Add New Venue";

    venueModal.show();
}

function openEditModal(id) {
    const venue = venues.find(
        item => Number(item.venue_id) === Number(id)
    );

    if (!venue) {
        showAlert("Venue not found.", "danger");
        return;
    }

    venueForm.reset();

    document.getElementById("venueId").value =
        venue.venue_id;

    document.getElementById("venueName").value =
        venue.venue_name || "";

    document.getElementById("venueAddress").value =
        venue.address || "";

    document.getElementById("venueCapacity").value =
        venue.capacity || "";

    document.getElementById("venueModalTitle").textContent =
        "Edit Venue";

    venueModal.show();
}

async function saveVenue(event) {
    event.preventDefault();

    const id = document.getElementById("venueId").value;

    const payload = {
        venue_name: document.getElementById("venueName").value.trim(),
        address: document.getElementById("venueAddress").value.trim(),
        capacity: Number(document.getElementById("venueCapacity").value)
    };

    if (!payload.venue_name || !payload.address) {
        showAlert("Please complete all venue details.", "danger");
        return;
    }

    if (!Number.isInteger(payload.capacity) || payload.capacity <= 0) {
        showAlert("Capacity must be a positive whole number.", "danger");
        return;
    }

    saveVenueButton.disabled = true;
    saveVenueButton.textContent = "Saving...";

    try {
        const url = id
            ? VENUES_API + "?id=" + encodeURIComponent(id)
            : VENUES_API;

        const result = await apiRequest(url, {
            method: id ? "PUT" : "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        venueModal.hide();

        showAlert(
            result.message || "Venue saved successfully."
        );

        await loadVenues();

    } catch (error) {
        showAlert(error.message, "danger");

    } finally {
        saveVenueButton.disabled = false;
        saveVenueButton.textContent = "Save Venue";
    }
}

async function deleteVenue(id) {
    const venue = venues.find(
        item => Number(item.venue_id) === Number(id)
    );

    if (!venue) {
        showAlert("Venue not found.", "danger");
        return;
    }

    const confirmed = confirm(
        'Are you sure you want to delete "' +
        venue.venue_name + '"?'
    );

    if (!confirmed) return;

    try {
        const result = await apiRequest(
            VENUES_API + "?id=" + encodeURIComponent(id),
            { method: "DELETE" }
        );

        showAlert(
            result.message || "Venue deleted successfully."
        );

        await loadVenues();

    } catch (error) {
        showAlert(error.message, "danger");
    }
}

async function viewLocation(id) {
    const venue = venues.find(
        item => Number(item.venue_id) === Number(id)
    );

    if (!venue) {
        showAlert("Venue not found.", "danger");
        return;
    }

    document.getElementById("locationModalTitle").textContent =
        "Venue Location";

    const status = document.getElementById("locationStatus");
    const details = document.getElementById("locationDetails");

    status.textContent = "Loading location...";
    status.classList.remove("d-none");
    details.classList.add("d-none");

    locationModal.show();

    try {
        const result = await apiRequest(
            LOCATION_API + "?id=" + encodeURIComponent(id)
        );

        const data = result.data || {};

        const name =
            data.venue_name || venue.venue_name;

        const address =
            data.address || venue.address || "";

        const latitude =
            data.latitude ?? null;

        const longitude =
            data.longitude ?? null;

        document.getElementById("locationVenue").textContent =
            name;

        document.getElementById("locationAddress").textContent =
            address || "-";

        document.getElementById("locationLatitude").textContent =
            latitude ?? "Not available";

        document.getElementById("locationLongitude").textContent =
            longitude ?? "Not available";

        const mapButton = document.getElementById("openMapButton");

        const hasCoordinates =
            latitude !== null &&
            longitude !== null &&
            latitude !== "" &&
            longitude !== "";

        const query = hasCoordinates
            ? latitude + "," + longitude
            : address || name;

        mapButton.href =
            "https://www.google.com/maps/search/?api=1&query=" +
            encodeURIComponent(query);

        status.classList.add("d-none");
        details.classList.remove("d-none");

    } catch (error) {
        status.textContent = error.message;
        details.classList.add("d-none");
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
        showAlert(error.message, "danger");
        logoutButton.disabled = false;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    venueModal = new bootstrap.Modal(
        document.getElementById("venueModal")
    );

    locationModal = new bootstrap.Modal(
        document.getElementById("locationModal")
    );

    document.getElementById("addVenueButton")
        .addEventListener("click", openAddModal);

    document.getElementById("searchInput")
        .addEventListener("input", filterVenues);

    document.getElementById("logoutButton")
        .addEventListener("click", logout);

    venueForm.addEventListener("submit", saveVenue);

    loadVenues();
});
</script>

</body>
</html>
