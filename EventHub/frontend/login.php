
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
    <title>Login | EventHub</title>

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
            max-width: 440px;
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

        .register-link {
            color: var(--eh-red);
            font-weight: 700;
            text-decoration: none;
        }

        .register-link:hover {
            color: var(--eh-red-dark);
            text-decoration: underline;
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

        </div>

        <!-- LOGIN CARD -->
        <div class="auth-card">
            <div class="card-body">

                <h2 class="auth-title">Welcome Back</h2>

                <p class="auth-subtitle">
                    Sign in to access your EventHub account.
                </p>

                <div id="errorMessage"
                     class="alert alert-danger d-none"
                     role="alert"></div>

                <div id="successMessage"
                     class="alert alert-success d-none"
                     role="status">
                    Registration successful! Please sign in.
                </div>

                <form id="loginForm">

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
                            required
                        >

                    </div>

                    <div class="mb-4">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                    <button
                        type="submit"
                        id="loginButton"
                        class="btn auth-btn w-100">
                        Sign In
                    </button>

                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <span class="text-secondary">
                        Don't have an account?
                    </span>

                    <a href="register.php" class="register-link ms-1">
                        Create an Account
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

const form = document.getElementById("loginForm");
const errorBox = document.getElementById("errorMessage");
const successBox = document.getElementById("successMessage");
const loginButton = document.getElementById("loginButton");

const params = new URLSearchParams(window.location.search);

if (params.get("registered") === "1") {
    successBox.classList.remove("d-none");
}

form.addEventListener("submit", async function (event) {
    event.preventDefault();

    errorBox.classList.add("d-none");

    loginButton.disabled = true;
    loginButton.textContent = "Signing in...";

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    try {
        const response = await fetch("../api/login.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                email: email,
                password: password
            })
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Login failed.");
        }

        window.location.href = "index.php";

    } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove("d-none");

    } finally {
        loginButton.disabled = false;
        loginButton.textContent = "Sign In";
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>

</body>
</html>
