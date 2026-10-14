<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | EventHub</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">

    <style>
        :root {
            --eh-red: #7f1d1d;
            --eh-red-dark: #641616;
            --eh-bg: #f5f6fa;
        }

        body {
            min-height: 100vh;
            background: var(--eh-bg);
            font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
        }

        .auth-container {
            width: 100%;
            max-width: 460px;
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand-icon {
            width: 65px;
            height: 65px;
            background: var(--eh-red);
            border-radius: 17px;
            color: white;
            display: grid;
            place-items: center;
            margin: 0 auto 16px;
        }

        .brand-name {
            font-size: 2rem;
            font-weight: 800;
            color: var(--eh-red);
            margin-bottom: 5px;
        }

        .auth-card {
            background: #fff;
            border: 1px solid #e9edf2;
            border-radius: 15px;
            box-shadow: 0 10px 35px rgba(0,0,0,.045);
            overflow: hidden;
        }

        .auth-card .card-body {
            padding: 35px;
        }

        .auth-title {
            font-size: 1.45rem;
            font-weight: 750;
            text-align: center;
            margin-bottom: 8px;
            color: #202936;
        }

        .auth-subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 28px;
        }

        .form-label {
            font-weight: 600;
        }

        .form-control {
            padding: 11px 13px;
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: #b91c1c;
            box-shadow: 0 0 0 .2rem rgba(127,29,29,.12);
        }

        .auth-btn {
            background: var(--eh-red);
            border: 1px solid var(--eh-red);
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 700;
        }

        .auth-btn:hover,
        .auth-btn:focus {
            background: var(--eh-red-dark);
            border-color: var(--eh-red-dark);
            color: white;
        }

        .auth-btn:disabled {
            opacity: .65;
        }

        .login-link {
            color: var(--eh-red);
            font-weight: 700;
            text-decoration: none;
        }

        .login-link:hover {
            color: var(--eh-red-dark);
            text-decoration: underline;
        }

        .password-hint {
            font-size: .8rem;
            color: #6b7280;
            margin-top: 6px;
        }

        .auth-footer {
            text-align: center;
            color: #7b8492;
            font-size: .82rem;
            margin-top: 25px;
        }

        @media (max-width: 480px) {
            .auth-card .card-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

<div class="auth-wrapper">
    <div class="auth-container">

        <!-- BRAND -->
        <div class="brand">

            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     width="32"
                     height="32"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M2 9V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a3 3 0 0 0 0 6v4a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4a3 3 0 0 0 0-6Z"/>
                    <path d="M13 5v2m0 4v2m0 4v2"/>
                </svg>
            </div>

            <h1 class="brand-name">EventHub</h1>

            <p class="text-secondary mb-0">
                Event Ticketing & Venue Booking System
            </p>

        </div>

        <!-- REGISTRATION CARD -->
        <div class="auth-card">
            <div class="card-body">

                <h2 class="auth-title">Create an Account</h2>

                <p class="auth-subtitle">
                    Join EventHub and start booking your favourite events.
                </p>

                <div id="errorMessage"
                     class="alert alert-danger d-none"
                     role="alert"></div>

                <form id="registerForm">

                    <!-- FULL NAME -->
                    <div class="mb-3">

                        <label for="name" class="form-label">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            class="form-control"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            maxlength="100"
                            required
                        >

                    </div>

                    <!-- EMAIL -->
                    <div class="mb-3">

                        <label for="email" class="form-label">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            class="form-control"
                            placeholder="Enter your email"
                            autocomplete="email"
                            maxlength="254"
                            required
                        >

                    </div>

                    <!-- PASSWORD -->
                    <div class="mb-3">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            class="form-control"
                            placeholder="Create a password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                        <div class="password-hint">
                            Use at least 8 characters.
                        </div>

                    </div>

                    <!-- CONFIRM PASSWORD -->
                    <div class="mb-4">

                        <label for="confirmPassword" class="form-label">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            id="confirmPassword"
                            class="form-control"
                            placeholder="Re-enter your password"
                            autocomplete="new-password"
                            minlength="8"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        id="registerButton"
                        class="btn auth-btn w-100">
                        Create Account
                    </button>

                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <span class="text-secondary">
                        Already have an account?
                    </span>

                    <a href="login.php" class="login-link ms-1">
                        Sign In
                    </a>
                </div>

            </div>
        </div>

        <div class="auth-footer">
            © <?php echo date("Y"); ?> EventHub.
            All rights reserved.
        </div>

    </div>
</div>

<script>
"use strict";

const form = document.getElementById("registerForm");
const errorBox = document.getElementById("errorMessage");
const registerButton = document.getElementById("registerButton");

form.addEventListener("submit", async function (event) {
    event.preventDefault();

    errorBox.classList.add("d-none");

    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword =
        document.getElementById("confirmPassword").value;

    if (password !== confirmPassword) {
        errorBox.textContent = "Passwords do not match.";
        errorBox.classList.remove("d-none");
        return;
    }

    if (password.length < 8) {
        errorBox.textContent =
            "Password must contain at least 8 characters.";
        errorBox.classList.remove("d-none");
        return;
    }

    registerButton.disabled = true;
    registerButton.textContent = "Creating Account...";

    try {
        const response = await fetch("../api/register.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                name: name,
                email: email,
                password: password,
                confirm_password: confirmPassword
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(
                result.message || "Registration failed."
            );
        }

        window.location.href = "login.php?registered=1";

    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove("d-none");

    } finally {
        registerButton.disabled = false;
        registerButton.textContent = "Create Account";
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>

</body>
</html>
