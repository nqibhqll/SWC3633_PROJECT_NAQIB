
<?php
session_start();

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$role = $_SESSION["role"];

if (!in_array($role, ["admin", "organiser", "customer"], true)) {
    http_response_code(403);
    exit("Access denied.");
}

$isCustomer = $role === "customer";
$isAdmin = $role === "admin";

function reviewEscape($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function reviewIcon($name, $size = 20) {
    $icons = [
        "dashboard" => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        "calendar" => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        "ticket" => '<path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/><path d="M13 5v2m0 4v2m0 4v2"/>',
        "bookings" => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>',
        "star" => '<path d="m12 2 3.1 6.3 7 1-5.1 5 .9 7-5.9-3.2-5.9 3.2.9-7-5.1-5 7-1Z"/>',
        "users" => '<circle cx="9" cy="7" r="4"/><path d="M2 21v-2a6 6 0 0 1 12 0v2M17 11a4 4 0 0 0 0-8M17 15a6 6 0 0 1 5 6"/>',
        "building" => '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 7h2M14 7h2M8 12h2M14 12h2"/>',
        "pin" => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        "logout" => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        "menu" => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        "plus" => '<path d="M12 5v14M5 12h14"/>'
    ];

    $size = max(12, min(48, (int) $size));

    return '<svg width="' . $size . '" height="' . $size .
        '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        ($icons[$name] ?? $icons["dashboard"]) . '</svg>';
}

$menus = [
    "admin" => [
        ["Manage Users", "manage_users.php", "users"],
        ["Manage Venues", "manage_venues.php", "building"],
        ["View Events", "admin_events.php", "calendar"]
    ],
    "organiser" => [
        ["Manage Events", "manage_events.php", "calendar"],
        ["Event Bookings", "event_bookings.php", "ticket"],
        ["Venue Locations", "venue_locations.php", "pin"]
    ],
    "customer" => [
        ["Browse Events", "browse_events.php", "calendar"],
        ["Book Tickets", "book_tickets.php", "ticket"],
        ["My Bookings", "my_bookings.php", "bookings"]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Reviews | EventHub</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">

    <style>
        :root {
            --red: #7f1d1d;
            --red-dark: #641616;
            --bg: #f5f6fa;
        }

        body {
            background: var(--bg);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: var(--red);
            color: white;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 27px 24px;
            border-bottom: 1px solid rgba(255,255,255,.15);
            color: white;
            text-decoration: none;
            font-size: 1.55rem;
            font-weight: 800;
        }

        .brand:hover { color: white; }

        .brand-icon {
            background: rgba(255,255,255,.16);
            border-radius: 11px;
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
        }

        .sidebar-label {
            padding: 28px 24px 10px;
            color: #e6a9a9;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 1.4px;
        }

        .nav-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 0 12px;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 14px;
            border-radius: 9px;
            color: #f5dada;
            text-decoration: none;
            font-size: .91rem;
        }

        .nav-link-custom:hover,
        .nav-link-custom.active {
            color: white;
            background: rgba(255,255,255,.17);
        }

        .nav-link-custom.active { font-weight: 700; }

        .sidebar-bottom {
            margin-top: auto;
            padding: 18px 12px 22px;
            border-top: 1px solid rgba(255,255,255,.15);
        }

        .logout {
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            cursor: pointer;
        }

        .main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            height: 76px;
            background: white;
            border-bottom: 1px solid #e9edf2;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .role-badge {
            background: #fee2e2;
            color: var(--red);
            border-radius: 30px;
            padding: 6px 12px;
            font-size: .75rem;
            font-weight: 700;
        }

        .avatar {
            background: #fee2e2;
            color: var(--red);
            border-radius: 50%;
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .content {
            flex: 1;
            padding: 34px;
            max-width: 1500px;
            width: 100%;
            margin: 0 auto;
        }

        .page-title {
            font-size: 1.9rem;
            font-weight: 800;
            color: #202936;
        }

        .panel {
            border: 1px solid #e9edf2;
            border-radius: 13px;
        }

        .stat-label {
            color: #6b7280;
            font-size: .85rem;
            font-weight: 600;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #202936;
            margin-top: 8px;
        }

        .review-card {
            background: white;
            border: 1px solid #e9edf2;
            border-radius: 12px;
            padding: 22px;
            height: 100%;
        }

        .review-avatar {
            width: 43px;
            height: 43px;
            border-radius: 50%;
            background: #fee2e2;
            color: var(--red);
            display: grid;
            place-items: center;
            font-weight: 800;
            flex-shrink: 0;
        }

        .stars {
            color: #e9a23b;
            font-size: 1.3rem;
            letter-spacing: 2px;
        }

        .event-tag {
            display: inline-block;
            background: #fceaea;
            color: var(--red);
            border-radius: 7px;
            padding: 5px 10px;
            font-size: .78rem;
            font-weight: 700;
        }

        .comment {
            color: #495466;
            line-height: 1.65;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .red-btn {
            background: var(--red);
            border: 1px solid var(--red);
            color: white;
            font-weight: 600;
        }

        .red-btn:hover {
            background: var(--red-dark);
            border-color: var(--red-dark);
            color: white;
        }

        .access-note {
            color: var(--red);
            background: #fee2e2;
            border-radius: 9px;
            padding: 10px 14px;
            font-size: .85rem;
            font-weight: 650;
        }

        .footer {
            text-align: center;
            color: #7b8492;
            padding: 18px;
            font-size: .8rem;
        }

        .menu-button {
            display: none;
            background: none;
            border: 0;
            color: var(--red);
        }

        .overlay { display: none; }

        @media(max-width: 991px) {
            .sidebar {
                position: fixed;
                left: -270px;
                transition: left .25s;
            }

            .sidebar.open { left: 0; }

            .overlay.open {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.45);
                z-index: 1020;
            }

            .menu-button { display: inline-flex; }
            .topbar { padding: 0 18px; }
            .content { padding: 22px 16px; }
        }
    </style>
</head>

<body>
<div id="overlay" class="overlay"></div>

<div class="layout">

    <aside id="sidebar" class="sidebar">

        <a href="index.php" class="brand">
            <span class="brand-icon">
                <?php echo reviewIcon("ticket", 23); ?>
            </span>
            EventHub
        </a>

        <div class="sidebar-label">MAIN MENU</div>

        <nav class="nav-list">
            <a href="index.php" class="nav-link-custom">
                <?php echo reviewIcon("dashboard"); ?>
                Dashboard
            </a>

            <?php foreach ($menus[$role] as $menu): ?>
                <a href="<?php echo reviewEscape($menu[1]); ?>"
                   class="nav-link-custom">
                    <?php echo reviewIcon($menu[2]); ?>
                    <?php echo reviewEscape($menu[0]); ?>
                </a>
            <?php endforeach; ?>

            <a href="reviews.php"
               class="nav-link-custom active"
               aria-current="page">
                <?php echo reviewIcon("star"); ?>
                Event Reviews
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="px-3 mb-3">
                <div class="small" style="color:#e6a9a9">
                    SIGNED IN AS
                </div>

                <div class="fw-bold mt-1">
                    <?php echo reviewEscape(ucfirst($role)); ?>
                </div>

                <div class="small" style="color:#f1c7c7">
                    User #<?php echo $userId; ?>
                </div>
            </div>

            <button id="logoutButton"
                    type="button"
                    class="nav-link-custom logout">
                <?php echo reviewIcon("logout"); ?>
                Logout
            </button>
        </div>

    </aside>

    <div class="main">

        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="menuButton"
                        type="button"
                        class="menu-button"
                        aria-label="Open menu">
                    <?php echo reviewIcon("menu", 25); ?>
                </button>

                <strong>Event Reviews</strong>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="role-badge">
                    <?php echo reviewEscape(ucfirst($role)); ?>
                </span>

                <span class="avatar">
                    <?php echo reviewEscape(strtoupper($role[0])); ?>
                </span>
            </div>
        </header>

        <main class="content">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="page-title mb-1">Event Reviews</h1>
                    <p class="text-secondary mb-0">
                        View customer ratings and feedback for events.
                    </p>
                </div>

                <?php if ($isCustomer): ?>
                    <button id="writeReviewButton"
                            type="button"
                            class="btn red-btn">
                        <?php echo reviewIcon("plus", 17); ?>
                        Write a Review
                    </button>
                <?php elseif ($isAdmin): ?>
                    <span class="access-note">
                        Admin — View & Delete Reviews
                    </span>
                <?php else: ?>
                    <span class="access-note">
                        Read-Only Access
                    </span>
                <?php endif; ?>
            </div>

            <div id="alertBox"
                 class="alert d-none"
                 role="alert"></div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card panel h-100">
                        <div class="card-body">
                            <div class="stat-label">Total Reviews</div>
                            <div class="stat-value" id="totalReviews">0</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card panel h-100">
                        <div class="card-body">
                            <div class="stat-label">Average Rating</div>
                            <div class="stat-value" id="averageRating">
                                0.0 / 5
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card panel h-100">
                        <div class="card-body">
                            <div class="stat-label">
                                <?php echo $isCustomer ? "My Reviews" : "Access Level"; ?>
                            </div>

                            <div class="stat-value"
                                 id="thirdStat"
                                 style="<?php echo $isCustomer ? '' : 'font-size:1.2rem;'; ?>">
                                <?php
                                    if ($isCustomer) {
                                        echo "0";
                                    } elseif ($isAdmin) {
                                        echo "View & Delete";
                                    } else {
                                        echo "View Only";
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card panel mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="searchInput" class="form-label">
                                Search Reviews
                            </label>
                            <input id="searchInput"
                                   type="search"
                                   class="form-control"
                                   placeholder="Search event, reviewer or comment">
                        </div>

                        <div class="col-md-5">
                            <label for="eventFilter" class="form-label">
                                Filter by Event
                            </label>
                            <select id="eventFilter" class="form-select">
                                <option value="">All Events</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="reviewsList" class="row g-3">
                <div class="col-12 text-center text-secondary py-5">
                    Loading reviews...
                </div>
            </div>

        </main>

        <footer class="footer">
            © <?php echo date("Y"); ?> EventHub —
            Event Ticketing and Venue Booking System
        </footer>

    </div>
</div>

<?php if ($isCustomer): ?>
<!-- CUSTOMER ONLY: CREATE / EDIT REVIEW -->
<div class="modal modal-blur fade"
     id="reviewModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 id="reviewModalTitle" class="modal-title">
                    Write a Review
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <form id="reviewForm">
                <div class="modal-body">

                    <div id="modalError"
                         class="alert alert-danger d-none"></div>

                    <div id="eventField" class="mb-3">
                        <label for="reviewEvent" class="form-label">
                            Event
                        </label>

                        <select id="reviewEvent"
                                class="form-select"
                                required>
                            <option value="">Select an event</option>
                        </select>

                        <div class="form-hint mt-2">
                            Only completed events with a confirmed booking
                            can be reviewed.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reviewRating" class="form-label">
                            Rating
                        </label>

                        <select id="reviewRating"
                                class="form-select"
                                required>
                            <option value="">Select rating</option>
                            <option value="5">★★★★★ — Excellent</option>
                            <option value="4">★★★★☆ — Very Good</option>
                            <option value="3">★★★☆☆ — Good</option>
                            <option value="2">★★☆☆☆ — Fair</option>
                            <option value="1">★☆☆☆☆ — Poor</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reviewComment" class="form-label">
                            Your Feedback
                        </label>

                        <textarea id="reviewComment"
                                  class="form-control"
                                  rows="4"
                                  maxlength="2000"
                                  placeholder="Tell us about your experience..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button id="saveReviewButton"
                            type="submit"
                            class="btn red-btn">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
"use strict";

const USER_ID = <?php echo json_encode($userId); ?>;
const ROLE = <?php echo json_encode($role); ?>;

const IS_CUSTOMER = ROLE === "customer";
const IS_ADMIN = ROLE === "admin";

const REVIEWS_API = "../api/reviews.php";
const EVENTS_API = "../api/events.php";
const BOOKINGS_API = "../api/bookings.php";

let reviews = [];
let events = [];
let bookings = [];
let editingReviewId = null;
let savingReview = false;
let deletingReviewIds = new Set();

const reviewsList = document.getElementById("reviewsList");
const alertBox = document.getElementById("alertBox");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");
const menuButton = document.getElementById("menuButton");

const reviewModal = IS_CUSTOMER
    ? new bootstrap.Modal(document.getElementById("reviewModal"))
    : null;

function setSidebar(open) {
    sidebar.classList.toggle("open", open);
    overlay.classList.toggle("open", open);
}

menuButton.addEventListener("click", () => {
    setSidebar(!sidebar.classList.contains("open"));
});

overlay.addEventListener("click", () => setSidebar(false));

document.addEventListener("keydown", event => {
    if (event.key === "Escape") setSidebar(false);
});

function showAlert(message, type = "danger") {
    alertBox.className = "alert alert-" + type;
    alertBox.textContent = message;
    alertBox.classList.remove("d-none");
}

function showModalError(message) {
    if (!IS_CUSTOMER) return;

    const box = document.getElementById("modalError");
    box.textContent = message;
    box.classList.remove("d-none");
}

function clearModalError() {
    if (!IS_CUSTOMER) return;

    const box = document.getElementById("modalError");
    box.textContent = "";
    box.classList.add("d-none");
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

function stars(rating) {
    const value = Math.max(
        0,
        Math.min(5, Number(rating) || 0)
    );

    return "★".repeat(value) + "☆".repeat(5 - value);
}

function formatDate(value) {
    if (!value) return "N/A";

    const date = new Date(String(value).replace(" ", "T"));

    return Number.isNaN(date.getTime())
        ? String(value)
        : date.toLocaleDateString("en-MY", {
            day: "numeric",
            month: "short",
            year: "numeric"
        });
}

function createElement(tag, className, text) {
    const element = document.createElement(tag);

    if (className) element.className = className;

    if (text !== undefined) {
        element.textContent = text;
    }

    return element;
}

function getEventName(eventId) {
    const event = events.find(
        item => Number(item.event_id) === Number(eventId)
    );

    return event ? event.event_name : "Event #" + eventId;
}

function updateStats() {
    document.getElementById("totalReviews").textContent =
        reviews.length;

    const total = reviews.reduce(
        (sum, review) => sum + Number(review.rating || 0),
        0
    );

    const average = reviews.length
        ? total / reviews.length
        : 0;

    document.getElementById("averageRating").textContent =
        average.toFixed(1) + " / 5";

    if (IS_CUSTOMER) {
        document.getElementById("thirdStat").textContent =
            reviews.filter(
                review => Number(review.user_id) === USER_ID
            ).length;
    }
}

function createReviewCard(review) {
    const column = createElement("div", "col-lg-6");
    const card = createElement("div", "review-card");

    const header = createElement(
        "div",
        "d-flex align-items-center gap-3 mb-3"
    );

    const avatar = createElement(
        "div",
        "review-avatar",
        String(review.reviewer_name || "U").charAt(0).toUpperCase()
    );

    const details = createElement("div", "flex-fill");

    const name = createElement(
        "div",
        "fw-bold",
        review.reviewer_name || "Customer"
    );

    const date = createElement(
        "div",
        "small text-secondary",
        formatDate(review.created_at)
    );

    details.append(name, date);
    header.append(avatar, details);

    const rating = createElement(
        "div",
        "stars mb-2",
        stars(review.rating)
    );

    const eventContainer = createElement("div", "mb-3");

    const eventTag = createElement(
        "span",
        "event-tag",
        review.event_name || getEventName(review.event_id)
    );

    eventContainer.appendChild(eventTag);

    const comment = createElement(
        "p",
        "comment mb-3",
        review.comment || "No written feedback."
    );

    card.append(header, rating, eventContainer, comment);

    const isOwner =
        IS_CUSTOMER &&
        Number(review.user_id) === USER_ID;

    // Admin: Delete any review.
    // Customer: Edit/Delete own review.
    // Organiser: No actions.
    if (IS_ADMIN || isOwner) {
        const actions = createElement(
            "div",
            "d-flex gap-2 border-top pt-3"
        );

        if (isOwner) {
            const editButton = createElement(
                "button",
                "btn btn-outline-secondary btn-sm",
                "Edit"
            );

            editButton.type = "button";
            editButton.addEventListener("click", () => {
                openEditModal(review);
            });

            actions.appendChild(editButton);
        }

        const deleteButton = createElement(
            "button",
            "btn btn-outline-danger btn-sm",
            "Delete"
        );

        deleteButton.type = "button";
        deleteButton.addEventListener("click", () => {
            deleteReview(review.review_id);
        });

        actions.appendChild(deleteButton);
        card.appendChild(actions);
    }

    column.appendChild(card);
    return column;
}

function renderReviews() {
    reviewsList.replaceChildren();

    const search = document.getElementById("searchInput")
        .value.toLowerCase().trim();

    const eventId = document.getElementById("eventFilter").value;

    const filtered = reviews.filter(review => {
        const searchable = [
            review.reviewer_name,
            review.event_name,
            review.comment
        ].join(" ").toLowerCase();

        return searchable.includes(search) &&
            (!eventId || String(review.event_id) === eventId);
    });

    if (!filtered.length) {
        reviewsList.appendChild(
            createElement(
                "div",
                "col-12 text-center text-secondary py-5",
                "No reviews found."
            )
        );
        return;
    }

    filtered.forEach(review => {
        reviewsList.appendChild(createReviewCard(review));
    });
}

function populateEventFilter() {
    const select = document.getElementById("eventFilter");
    const selectedValue = select.value;

    select.length = 1;

    const eventMap = new Map();

    reviews.forEach(review => {
        const id = String(review.event_id);

        if (!eventMap.has(id)) {
            eventMap.set(
                id,
                review.event_name || getEventName(review.event_id)
            );
        }
    });

    for (const [id, name] of eventMap) {
        const option = document.createElement("option");
        option.value = id;
        option.textContent = name;
        select.appendChild(option);
    }

    if (eventMap.has(selectedValue)) {
        select.value = selectedValue;
    }
}

function eligibleEvents() {
    if (!IS_CUSTOMER) return [];

    const now = Date.now();

    return events.filter(event => {
        const eventTime = new Date(
            String(event.event_date).replace(" ", "T")
        ).getTime();

        const completed = Number.isFinite(eventTime) &&
            eventTime <= now;

        const confirmed = bookings.some(booking =>
            Number(booking.event_id) === Number(event.event_id) &&
            booking.booking_status === "confirmed"
        );

        const alreadyReviewed = reviews.some(review =>
            Number(review.user_id) === USER_ID &&
            Number(review.event_id) === Number(event.event_id)
        );

        return completed && confirmed && !alreadyReviewed;
    });
}

function populateReviewEvents() {
    const select = document.getElementById("reviewEvent");
    select.replaceChildren();

    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Select an event";
    select.appendChild(placeholder);

    eligibleEvents().forEach(event => {
        const option = document.createElement("option");
        option.value = event.event_id;
        option.textContent = event.event_name;
        select.appendChild(option);
    });
}

function openCreateModal() {
    if (!IS_CUSTOMER) return;

    editingReviewId = null;

    document.getElementById("reviewForm").reset();
    document.getElementById("reviewModalTitle").textContent =
        "Write a Review";

    document.getElementById("saveReviewButton").textContent =
        "Submit Review";

    document.getElementById("eventField")
        .classList.remove("d-none");

    document.getElementById("reviewEvent").required = true;

    clearModalError();
    populateReviewEvents();

    const saveButton = document.getElementById("saveReviewButton");
    saveButton.disabled = eligibleEvents().length === 0;

    if (saveButton.disabled) {
        showModalError(
            "No events are currently eligible for review. " +
            "You need a confirmed booking for a completed event."
        );
    }

    reviewModal.show();
}

function openEditModal(review) {
    if (!IS_CUSTOMER || Number(review.user_id) !== USER_ID) {
        return;
    }

    editingReviewId = Number(review.review_id);

    document.getElementById("reviewModalTitle").textContent =
        "Edit Review";

    document.getElementById("saveReviewButton").textContent =
        "Save Changes";

    document.getElementById("saveReviewButton").disabled = false;

    document.getElementById("eventField")
        .classList.add("d-none");

    document.getElementById("reviewEvent").required = false;

    document.getElementById("reviewRating").value =
        String(review.rating);

    document.getElementById("reviewComment").value =
        review.comment || "";

    clearModalError();
    reviewModal.show();
}

async function saveReview(event) {
    event.preventDefault();

    if (!IS_CUSTOMER || savingReview) return;

    clearModalError();

    const rating = Number(
        document.getElementById("reviewRating").value
    );

    const comment = document.getElementById("reviewComment")
        .value.trim();

    if (!Number.isInteger(rating) || rating < 1 || rating > 5) {
        showModalError("Please select a rating between 1 and 5.");
        return;
    }

    const isEditing = editingReviewId !== null;
    const body = {rating, comment};

    if (!isEditing) {
        const eventId = Number(
            document.getElementById("reviewEvent").value
        );

        if (!Number.isInteger(eventId) || eventId <= 0) {
            showModalError("Please select an event.");
            return;
        }

        body.event_id = eventId;
    }

    const button = document.getElementById("saveReviewButton");

    savingReview = true;
    button.disabled = true;

    try {
        const url = isEditing
            ? REVIEWS_API + "?id=" + encodeURIComponent(editingReviewId)
            : REVIEWS_API;

        const result = await apiRequest(url, {
            method: isEditing ? "PUT" : "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify(body)
        });

        reviewModal.hide();
        showAlert(result.message || "Review saved.", "success");

        await loadReviews();

    } catch (error) {
        showModalError(error.message);

    } finally {
        savingReview = false;
        button.disabled = false;
        button.textContent = isEditing
            ? "Save Changes"
            : "Submit Review";
    }
}

async function deleteReview(reviewId) {
    // Admin can delete any review.
    // Customer can delete own review.
    // Organiser cannot delete.
    const review = reviews.find(
        item => Number(item.review_id) === Number(reviewId)
    );

    if (!review) {
        showAlert("Review not found.");
        return;
    }

    const isOwner =
        IS_CUSTOMER &&
        Number(review.user_id) === USER_ID;

    if (!IS_ADMIN && !isOwner) {
        showAlert("You do not have permission to delete this review.");
        return;
    }

    const message = IS_ADMIN
        ? "Are you sure you want to permanently delete this customer's review? This action cannot be undone."
        : "Are you sure you want to delete your review?";

    if (!confirm(message)) return;

    const id = Number(reviewId);

    if (deletingReviewIds.has(id)) return;
    deletingReviewIds.add(id);

    try {
        const result = await apiRequest(
            REVIEWS_API + "?id=" + encodeURIComponent(id),
            {method: "DELETE"}
        );

        showAlert(
            result.message || "Review deleted successfully.",
            "success"
        );

        await loadReviews();

    } catch (error) {
        showAlert(error.message);

    } finally {
        deletingReviewIds.delete(id);
    }
}

async function loadReviews() {
    const result = await apiRequest(REVIEWS_API);

    reviews = Array.isArray(result.data)
        ? result.data
        : [];

    populateEventFilter();
    updateStats();
    renderReviews();
}

async function logout() {
    if (!confirm("Are you sure you want to logout?")) return;

    const button = document.getElementById("logoutButton");
    button.disabled = true;

    try {
        await apiRequest("../api/logout.php", {
            method: "POST"
        });

        window.location.replace("login.php");

    } catch (error) {
        showAlert(error.message);
        button.disabled = false;
    }
}

async function initialize() {
    document.getElementById("searchInput")
        .addEventListener("input", renderReviews);

    document.getElementById("eventFilter")
        .addEventListener("change", renderReviews);

    document.getElementById("logoutButton")
        .addEventListener("click", logout);

    if (IS_CUSTOMER) {
        document.getElementById("writeReviewButton")
            .addEventListener("click", openCreateModal);

        document.getElementById("reviewForm")
            .addEventListener("submit", saveReview);
    }

    try {
        await loadReviews();

    } catch (error) {
        showAlert(error.message);
        reviewsList.replaceChildren();

        reviewsList.appendChild(
            createElement(
                "div",
                "col-12 text-center text-danger py-5",
                "Unable to load reviews."
            )
        );
    }

    // Customer-only eligibility data.
    if (IS_CUSTOMER) {
        try {
            const [eventsResult, bookingsResult] = await Promise.all([
                apiRequest(EVENTS_API),
                apiRequest(BOOKINGS_API)
            ]);

            events = Array.isArray(eventsResult.data)
                ? eventsResult.data
                : [];

            bookings = Array.isArray(bookingsResult.data)
                ? bookingsResult.data
                : [];

        } catch (error) {
            console.error("Review eligibility data:", error);

            showAlert(
                "Reviews loaded, but booking eligibility could not be checked: " +
                error.message,
                "warning"
            );
        }
    }
}

initialize();
</script>
</body>
</html>
