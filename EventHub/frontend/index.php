
<?php
session_start();

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$role = $_SESSION["role"];

$dashboards = [
    "admin" => [
        "title" => "Admin Dashboard",
        "welcome" => "Welcome Back, Admin!",
        "description" => "Manage users, venues and system operations.",
        "welcome_description" => "Manage your users, venues, events and customer reviews all in one place.",
        "features" => [
            [
                "title" => "Manage Users",
                "description" => "View, create, update and delete users.",
                "icon" => "users",
                "url" => "manage_users.php"
            ],
            [
                "title" => "Manage Venues",
                "description" => "Manage event venues and capacities.",
                "icon" => "building",
                "url" => "manage_venues.php"
            ],
            [
                "title" => "View Events",
                "description" => "Monitor events in the system.",
                "icon" => "calendar",
                "url" => "admin_events.php"
            ],
            [
                "title" => "Event Reviews",
                "description" => "View customer feedback and delete inappropriate reviews.",
                "icon" => "star",
                "url" => "reviews.php"
            ]
        ]
    ],

    "organiser" => [
        "title" => "Organiser Dashboard",
        "welcome" => "Welcome Back, Organiser!",
        "description" => "Manage your events and customer bookings.",
        "welcome_description" => "Create and manage your events, monitor bookings, explore venue locations and read customer reviews.",
        "features" => [
            [
                "title" => "Manage Events",
                "description" => "Create, update and manage events.",
                "icon" => "calendar-event",
                "url" => "manage_events.php"
            ],
            [
                "title" => "Event Bookings",
                "description" => "View and manage customer bookings.",
                "icon" => "ticket",
                "url" => "event_bookings.php"
            ],
            [
                "title" => "Venue Locations",
                "description" => "Find venue locations using Nominatim.",
                "icon" => "map-pin",
                "url" => "venue_locations.php"
            ],
            [
                "title" => "Event Reviews",
                "description" => "Read customer reviews and ratings. View-only access.",
                "icon" => "star",
                "url" => "reviews.php"
            ]
        ]
    ],

    "customer" => [
        "title" => "EventHub",
        "welcome" => "Welcome to EventHub!",
        "description" => "Discover exciting events, book tickets, and manage your bookings.",
        "welcome_description" => "Discover exciting events, book tickets and manage your bookings all in one place.",
        "features" => [
            [
                "title" => "Browse Events",
                "description" => "Explore available events.",
                "icon" => "calendar-search",
                "url" => "browse_events.php"
            ],
            [
                "title" => "Book Tickets",
                "description" => "Reserve tickets for your favourite events.",
                "icon" => "ticket",
                "url" => "book_tickets.php"
            ],
            [
                "title" => "My Bookings",
                "description" => "View and cancel your bookings.",
                "icon" => "clipboard-list",
                "url" => "my_bookings.php"
            ],
            [
                "title" => "Event Reviews",
                "description" => "Read customer feedback, explore event ratings and share your experience.",
                "icon" => "star",
                "url" => "reviews.php"
            ]
        ]
    ]
];

if (!isset($dashboards[$role])) {
    http_response_code(403);
    exit("Invalid user role.");
}

$dashboard = $dashboards[$role];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function iconSvg($name, $size = 22) {
    $icons = [
        "dashboard" => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        "users" => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        "building" => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h1m6 0h1M8 10h1m6 0h1M8 14h1m6 0h1"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        "calendar-event" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><rect x="8" y="14" width="3" height="3" rx=".5"/>',
        "calendar-search" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><circle cx="11" cy="15" r="2"/><path d="m12.5 16.5 2 2"/>',
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        "map-pin" => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        "clipboard-list" => '<rect x="5" y="4" width="14" height="18" rx="2"/><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h6"/>',
        "star" => '<path d="m12 2 3.1 6.3 7 1-5.1 5 .9 7-5.9-3.2-5.9 3.2.9-7-5.1-5 7-1Z"/>',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        "arrow" => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        "shield" => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>'
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo e($dashboard["title"]); ?> | EventHub</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">

    <style>
        :root {
            --eh-red: #7f1d1d;
            --eh-red-dark: #641616;
            --eh-red-light: #991b1b;
            --eh-bg: #f5f6fa;
        }

        body {
            background: var(--eh-bg);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .eh-layout {
            min-height: 100vh;
            display: flex;
        }

        .eh-sidebar {
            width: 260px;
            flex-shrink: 0;
            background: var(--eh-red);
            color: #fff;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: sticky;
            top: 0;
            height: 100vh;
            z-index: 1030;
        }

        .eh-brand {
            padding: 27px 24px;
            border-bottom: 1px solid rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            text-decoration: none;
            font-size: 1.55rem;
            font-weight: 800;
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
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 1.4px;
            color: #e6a9a9;
        }

        .eh-nav {
            padding: 0 12px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .eh-nav-link {
            color: #f5dada;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 14px;
            border-radius: 9px;
            font-size: .91rem;
            font-weight: 500;
            transition: .2s ease;
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
            border: 0;
            text-align: left;
            cursor: pointer;
        }

        .eh-logout:hover {
            background: rgba(255,255,255,.17);
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
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        .eh-topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .eh-avatar {
            background: #fee2e2;
            color: var(--eh-red);
            border-radius: 50%;
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .eh-role {
            background: #fee2e2;
            color: var(--eh-red);
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 700;
            font-size: .75rem;
        }

        .eh-content {
            padding: 34px;
            flex: 1;
            max-width: 1500px;
            width: 100%;
            margin: 0 auto;
        }

        /* RED WELCOME BANNER FOR ALL ROLES */
        .eh-welcome {
            background: linear-gradient(120deg, #7f1d1d, #b91c1c);
            border: none;
            border-radius: 15px;
            color: #fff;
            overflow: hidden;
            position: relative;
        }

        .eh-welcome::after {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            border: 45px solid rgba(255,255,255,.045);
            border-radius: 50%;
            top: -100px;
            right: -70px;
            pointer-events: none;
        }

        .eh-welcome .card-body {
            padding: 34px;
            position: relative;
            z-index: 1;
        }

        .eh-welcome-label {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #ffe2e2;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .6px;
            margin-bottom: 16px;
        }

        .eh-welcome-title {
            color: #fff;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .eh-welcome p {
            color: #ffe5e5;
            font-size: .94rem;
            line-height: 1.7;
            max-width: 700px;
        }

        .eh-feature-card {
            border: 1px solid #e9edf2;
            border-radius: 13px;
            transition: transform .2s, box-shadow .2s;
            height: 100%;
        }

        .eh-feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(0,0,0,.08);
        }

        .eh-feature-icon {
            width: 54px;
            height: 54px;
            background: #fee2e2;
            color: var(--eh-red);
            border-radius: 13px;
            display: grid;
            place-items: center;
            margin-bottom: 20px;
        }

        .eh-feature-title {
            font-size: 1.08rem;
            font-weight: 750;
        }

        .eh-btn {
            background: var(--eh-red);
            color: #fff;
            border: 1px solid var(--eh-red);
            border-radius: 8px;
            padding: 10px 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: .2s;
        }

        .eh-btn:hover {
            background: var(--eh-red-dark);
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
            border: 0;
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
                left: -270px;
                top: 0;
                height: 100vh;
                transition: left .25s;
            }

            .eh-sidebar.open {
                left: 0;
            }

            .eh-menu-button {
                display: inline-flex;
            }

            .eh-overlay.open {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.45);
                z-index: 1020;
            }

            .eh-topbar {
                padding: 0 18px;
            }

            .eh-content {
                padding: 22px 16px;
            }

            .eh-welcome .card-body {
                padding: 25px;
            }

            .eh-welcome-title {
                font-size: 1.45rem;
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

        <div class="eh-sidebar-label">
            MAIN MENU
        </div>

        <nav class="eh-nav" aria-label="Main navigation">

            <a href="index.php"
               class="eh-nav-link active"
               aria-current="page">
                <?php echo iconSvg("dashboard", 20); ?>
                <span>Dashboard</span>
            </a>

            <?php foreach ($dashboard["features"] as $feature): ?>
                <a href="<?php echo e($feature["url"]); ?>"
                   class="eh-nav-link">
                    <?php echo iconSvg($feature["icon"], 20); ?>
                    <span><?php echo e($feature["title"]); ?></span>
                </a>
            <?php endforeach; ?>

        </nav>

        <div class="eh-sidebar-bottom">

            <div class="px-3 mb-3">
                <div class="small" style="color:#e6a9a9">
                    SIGNED IN AS
                </div>

                <div class="fw-bold mt-1">
                    <?php echo e(ucfirst($role)); ?>
                </div>

                <div class="small" style="color:#f1c7c7">
                    User #<?php echo $userId; ?>
                </div>
            </div>

            <button type="button"
                    id="logoutButton"
                    class="eh-nav-link eh-logout">
                <?php echo iconSvg("logout", 20); ?>
                <span>Logout</span>
            </button>

        </div>
    </aside>

    <!-- MAIN CONTENT -->
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
                    <?php echo $role === "customer"
                        ? "EventHub"
                        : e($dashboard["title"]); ?>
                </h2>

            </div>

            <div class="eh-topbar-right">

                <span class="eh-role">
                    <?php echo e(ucfirst($role)); ?>
                </span>

                <span class="eh-avatar">
                    <?php echo e(strtoupper(substr($role, 0, 1))); ?>
                </span>

            </div>
        </header>

        <main class="eh-content">

            <!-- RED WELCOME BANNER: ADMIN, ORGANISER, CUSTOMER -->
            <div class="card eh-welcome mb-4">

                <div class="card-body">

                    <div class="eh-welcome-label">
                        <?php echo iconSvg("shield", 22); ?>

                        <span>
                            EVENTHUB MANAGEMENT SYSTEM
                        </span>
                    </div>

                    <h1 class="eh-welcome-title">
                        <?php echo e($dashboard["welcome"]); ?>
                    </h1>

                    <p class="mb-0">
                        <?php echo e($dashboard["welcome_description"]); ?>
                    </p>

                </div>
            </div>

            <!-- QUICK ACCESS -->
            <div class="d-flex align-items-center justify-content-between mb-3">

                <h2 class="h3 mb-0">
                    Quick Access
                </h2>

                <span class="text-secondary small">
                    <?php echo count($dashboard["features"]); ?> modules
                </span>

            </div>

            <!-- FEATURE CARDS -->
            <div class="row row-cards">

                <?php foreach ($dashboard["features"] as $feature): ?>

                    <div class="col-md-6 col-xl-3">

                        <div class="card eh-feature-card">

                            <div class="card-body d-flex flex-column">

                                <div class="eh-feature-icon">
                                    <?php echo iconSvg($feature["icon"], 27); ?>
                                </div>

                                <h3 class="eh-feature-title">
                                    <?php echo e($feature["title"]); ?>
                                </h3>

                                <p class="text-secondary">
                                    <?php echo e($feature["description"]); ?>
                                </p>

                                <div class="mt-auto pt-3">

                                    <a href="<?php echo e($feature["url"]); ?>"
                                       class="eh-btn w-100">

                                        Open <?php echo e($feature["title"]); ?>

                                        <?php echo iconSvg("arrow", 17); ?>

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

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

const sidebar = document.getElementById("sidebar");
const sidebarOverlay = document.getElementById("sidebarOverlay");
const menuButton = document.getElementById("menuButton");
const logoutButton = document.getElementById("logoutButton");

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

logoutButton.addEventListener("click", async () => {
    if (!confirm("Are you sure you want to logout?")) {
        return;
    }

    logoutButton.disabled = true;

    try {
        const response = await fetch("../api/logout.php", {
            method: "POST",
            credentials: "same-origin"
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Logout failed.");
        }

        window.location.replace("login.php");

    } catch (error) {
        alert(error.message);
        logoutButton.disabled = false;
    }
});
</script>

</body>
</html>
