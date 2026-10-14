
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

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function iconSvg($name, $size = 20) {
    $icons = [
        "dashboard" => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        "users" => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        "building" => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h1m6 0h1M8 10h1m6 0h1M8 14h1m6 0h1"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        "star" => '<path d="m12 2 3.1 6.3 7 1-5.1 5 .9 7-5.9-3.2-5.9 3.2.9-7-5.1-5 7-1Z"/>',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        "plus" => '<path d="M12 5v14M5 12h14"/>',
        "search" => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>',
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
    <title>Manage Users | EventHub</title>

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
            color: white;
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
            color: white;
            text-decoration: none;
            font-size: 1.55rem;
            font-weight: 800;
            border-bottom: 1px solid rgba(255,255,255,.15);
        }

        .eh-brand:hover {
            color: white;
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
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 0 12px;
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
            color: white;
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
            background: white;
            border-bottom: 1px solid #e9edf2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 32px;
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
            color: white;
            border-radius: 8px;
            font-weight: 600;
        }

        .eh-btn:hover,
        .eh-btn:focus {
            background: var(--eh-red-dark);
            border-color: var(--eh-red-dark);
            color: white;
        }

        .eh-stat-card {
            border: 1px solid #e9edf2;
            border-radius: 13px;
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
            background: white;
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

        .user-avatar {
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: #fee2e2;
            color: var(--eh-red);
            font-weight: 800;
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
            color: white;
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

            <a href="manage_users.php"
               class="eh-nav-link active"
               aria-current="page">
                <?php echo iconSvg("users"); ?>
                <span>Manage Users</span>
            </a>

            <a href="manage_venues.php" class="eh-nav-link">
                <?php echo iconSvg("building"); ?>
                <span>Manage Venues</span>
            </a>

            <a href="admin_events.php" class="eh-nav-link">
                <?php echo iconSvg("calendar"); ?>
                <span>View Events</span>
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

                <h2 class="eh-topbar-title">
                    User Management
                </h2>
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
                    <h1 class="eh-page-title mb-1">
                        Manage Users
                    </h1>
                    <p class="text-secondary mb-0">
                        Create, view, update and delete EventHub users.
                    </p>
                </div>

                <button type="button"
                        id="addUserButton"
                        class="btn eh-btn d-flex align-items-center gap-2">
                    <?php echo iconSvg("plus", 18); ?>
                    Add New User
                </button>
            </div>

            <div id="alertBox"
                 class="alert d-none"
                 role="alert"></div>

            <!-- STATISTICS -->
            <div class="row row-cards mb-4">

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Total Users</div>
                            <div class="eh-stat-number" id="totalUsers">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Admins</div>
                            <div class="eh-stat-number" id="totalAdmins">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Organisers</div>
                            <div class="eh-stat-number" id="totalOrganisers">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card eh-stat-card">
                        <div class="card-body">
                            <div class="eh-stat-label">Customers</div>
                            <div class="eh-stat-number" id="totalCustomers">0</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- USER DIRECTORY -->
            <div class="card eh-table-card">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        User Directory
                    </h3>

                    <input type="search"
                           id="searchInput"
                           class="form-control eh-search"
                           placeholder="Search users..."
                           aria-label="Search users">
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="usersTable">
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    Loading users...
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

<!-- ADD / EDIT USER MODAL -->
<div class="modal modal-blur fade"
     id="userModal"
     tabindex="-1"
     aria-labelledby="modalTitle"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form id="userForm">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        Add User
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="userId">

                    <div class="mb-3">
                        <label for="userName" class="form-label">
                            Full Name
                        </label>

                        <input type="text"
                               id="userName"
                               class="form-control"
                               maxlength="150"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="userEmail" class="form-label">
                            Email
                        </label>

                        <input type="email"
                               id="userEmail"
                               class="form-control"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="userRole" class="form-label">
                            Role
                        </label>

                        <select id="userRole"
                                class="form-select"
                                required>
                            <option value="admin">Admin</option>
                            <option value="organiser">Organiser</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="userPassword" class="form-label">
                            Password
                        </label>

                        <input type="password"
                               id="userPassword"
                               class="form-control"
                               minlength="8"
                               maxlength="72"
                               autocomplete="new-password">

                        <small id="passwordHint" class="text-secondary">
                            Minimum 8 characters.
                        </small>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            id="saveUserButton"
                            class="btn eh-btn">
                        Save User
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

const API_URL = "../api/users.php";
const CURRENT_USER_ID = <?php echo json_encode($currentUserId); ?>;

let users = [];
let userModal;

const usersTable = document.getElementById("usersTable");
const alertBox = document.getElementById("alertBox");
const userForm = document.getElementById("userForm");
const saveUserButton = document.getElementById("saveUserButton");

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

function createCell(text) {
    const cell = document.createElement("td");
    cell.textContent = text ?? "";
    return cell;
}

function roleBadge(role) {
    const badge = document.createElement("span");
    badge.className = "badge";

    const styles = {
        admin: "bg-red-lt",
        organiser: "bg-purple-lt",
        customer: "bg-green-lt"
    };

    badge.classList.add(styles[role] || "bg-secondary-lt");

    badge.textContent =
        role.charAt(0).toUpperCase() + role.slice(1);

    return badge;
}

function renderUsers(list = users) {
    usersTable.replaceChildren();

    if (list.length === 0) {
        const row = document.createElement("tr");
        const cell = createCell("No users found.");

        cell.colSpan = 6;
        cell.className = "text-center py-4";

        row.appendChild(cell);
        usersTable.appendChild(row);
        return;
    }

    list.forEach(user => {
        const row = document.createElement("tr");

        row.appendChild(createCell(user.user_id));

        const nameCell = document.createElement("td");
        const nameWrapper = document.createElement("div");

        nameWrapper.className =
            "d-flex align-items-center gap-2";

        const avatar = document.createElement("span");
        avatar.className = "user-avatar";

        avatar.textContent =
            (user.name || "U").charAt(0).toUpperCase();

        const name = document.createElement("strong");
        name.textContent = user.name;

        nameWrapper.append(avatar, name);
        nameCell.appendChild(nameWrapper);

        row.appendChild(nameCell);
        row.appendChild(createCell(user.email));

        const roleCell = document.createElement("td");
        roleCell.appendChild(roleBadge(user.role));
        row.appendChild(roleCell);

        row.appendChild(createCell(user.created_at));

        const actionsCell = document.createElement("td");
        const actions = document.createElement("div");

        actions.className = "table-actions";

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.className =
            "btn btn-outline-primary eh-edit-btn btn-sm";
        editButton.textContent = "Edit";

        editButton.addEventListener("click", () => {
            openEditModal(user.user_id);
        });

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.className =
            "btn btn-outline-danger btn-sm";
        deleteButton.textContent = "Delete";

        deleteButton.disabled =
            Number(user.user_id) === CURRENT_USER_ID;

        deleteButton.addEventListener("click", () => {
            deleteUser(user.user_id);
        });

        actions.append(editButton, deleteButton);
        actionsCell.appendChild(actions);
        row.appendChild(actionsCell);

        usersTable.appendChild(row);
    });
}

function updateStats() {
    document.getElementById("totalUsers").textContent =
        users.length;

    document.getElementById("totalAdmins").textContent =
        users.filter(u => u.role === "admin").length;

    document.getElementById("totalOrganisers").textContent =
        users.filter(u => u.role === "organiser").length;

    document.getElementById("totalCustomers").textContent =
        users.filter(u => u.role === "customer").length;
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
        throw new Error("Invalid response from server");
    }

    if (response.status === 401) {
        window.location.replace("login.php");
        throw new Error("Session expired. Please login again.");
    }

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Request failed");
    }

    return result;
}

async function loadUsers() {
    try {
        const result = await apiRequest(API_URL);

        users = Array.isArray(result.data)
            ? result.data
            : [];

        updateStats();
        filterUsers();

    } catch (error) {
        showAlert(error.message, "danger");

        usersTable.replaceChildren();

        const row = document.createElement("tr");
        const cell = createCell("Unable to load users.");

        cell.colSpan = 6;
        row.appendChild(cell);
        usersTable.appendChild(row);
    }
}

function filterUsers() {
    const keyword = document.getElementById("searchInput")
        .value.toLowerCase().trim();

    const filtered = users.filter(user =>
        String(user.user_id).includes(keyword) ||
        String(user.name || "").toLowerCase().includes(keyword) ||
        String(user.email || "").toLowerCase().includes(keyword) ||
        String(user.role || "").toLowerCase().includes(keyword)
    );

    renderUsers(filtered);
}

function openAddModal() {
    userForm.reset();

    document.getElementById("userId").value = "";
    document.getElementById("modalTitle").textContent =
        "Add New User";

    document.getElementById("userPassword").required = true;

    document.getElementById("passwordHint").textContent =
        "Required: 8 to 72 characters.";

    userModal.show();
}

function openEditModal(id) {
    const user = users.find(
        u => Number(u.user_id) === Number(id)
    );

    if (!user) {
        showAlert("User not found", "danger");
        return;
    }

    userForm.reset();

    document.getElementById("userId").value =
        user.user_id;

    document.getElementById("userName").value =
        user.name;

    document.getElementById("userEmail").value =
        user.email;

    document.getElementById("userRole").value =
        user.role;

    document.getElementById("userPassword").required =
        false;

    document.getElementById("passwordHint").textContent =
        "Leave blank to keep the existing password.";

    document.getElementById("modalTitle").textContent =
        "Edit User";

    userModal.show();
}

async function saveUser(event) {
    event.preventDefault();

    const id = document.getElementById("userId").value;
    const password =
        document.getElementById("userPassword").value;

    const payload = {
        name: document.getElementById("userName").value.trim(),
        email: document.getElementById("userEmail").value.trim(),
        role: document.getElementById("userRole").value
    };

    if (!id || password !== "") {
        payload.password = password;
    }

    saveUserButton.disabled = true;
    saveUserButton.textContent = "Saving...";

    try {
        const url = id
            ? API_URL + "?id=" + encodeURIComponent(id)
            : API_URL;

        const result = await apiRequest(url, {
            method: id ? "PUT" : "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        userModal.hide();

        showAlert(
            result.message || "User saved successfully"
        );

        await loadUsers();

    } catch (error) {
        showAlert(error.message, "danger");

    } finally {
        saveUserButton.disabled = false;
        saveUserButton.textContent = "Save User";
    }
}

async function deleteUser(id) {
    if (Number(id) === CURRENT_USER_ID) {
        showAlert(
            "You cannot delete your own admin account.",
            "danger"
        );
        return;
    }

    const user = users.find(
        u => Number(u.user_id) === Number(id)
    );

    if (!user) return;

    const confirmed = confirm(
        'Are you sure you want to delete "' +
        user.name + '"?'
    );

    if (!confirmed) return;

    try {
        const result = await apiRequest(
            API_URL + "?id=" + encodeURIComponent(id),
            { method: "DELETE" }
        );

        showAlert(
            result.message || "User deleted successfully"
        );

        await loadUsers();

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
        const result = await apiRequest(
            "../api/logout.php",
            { method: "POST" }
        );

        if (result.success) {
            window.location.replace("login.php");
        }

    } catch (error) {
        showAlert(error.message, "danger");
        logoutButton.disabled = false;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    userModal = new bootstrap.Modal(
        document.getElementById("userModal")
    );

    document.getElementById("addUserButton")
        .addEventListener("click", openAddModal);

    document.getElementById("searchInput")
        .addEventListener("input", filterUsers);

    document.getElementById("logoutButton")
        .addEventListener("click", logout);

    userForm.addEventListener("submit", saveUser);

    loadUsers();
});
</script>

</body>
</html>
