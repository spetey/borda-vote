<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Authored or modified by Claude - 2025-09-25 -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borda Vote - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .auth-header p {
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            margin-bottom: 10px;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn-secondary {
            background: #95a5a6;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .auth-switch {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }

        .auth-switch a {
            color: #3498db;
            text-decoration: none;
        }

        .auth-switch a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .error {
            background: #e74c3c;
            color: white;
        }

        .success {
            background: #2ecc71;
            color: white;
        }

        .info {
            background: #3498db;
            color: white;
        }

        .hidden {
            display: none;
        }

        .role-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Borda Vote</h1>
            <p>Democratic group decision making</p>
        </div>

        <!-- Login Form -->
        <div id="login-form">
            <h2 style="margin-bottom: 20px; text-align: center; color: #2c3e50;">Login</h2>

            <form id="login" onsubmit="login(event)">
                <div class="form-group">
                    <label for="login-username">Username or Email</label>
                    <input type="text" id="login-username" required>
                </div>

                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" required>
                </div>

                <button type="submit" class="btn">Login</button>
            </form>

            <div class="auth-switch">
                <p>Don't have an account? <a href="#" onclick="showRegister()">Register here</a></p>
            </div>
        </div>

        <!-- Registration Form -->
        <div id="register-form" class="hidden">
            <h2 style="margin-bottom: 20px; text-align: center; color: #2c3e50;">Register</h2>

            <form id="register" onsubmit="register(event)">
                <div class="form-group">
                    <label for="reg-username">Username</label>
                    <input type="text" id="reg-username" required>
                </div>

                <div class="form-group">
                    <label for="reg-email">Email</label>
                    <input type="email" id="reg-email" required>
                </div>

                <div class="form-group">
                    <label for="reg-display-name">Display Name</label>
                    <input type="text" id="reg-display-name" required>
                </div>

                <div class="form-group">
                    <label for="reg-password">Password</label>
                    <input type="password" id="reg-password" required>
                </div>

                <div class="form-group">
                    <label for="reg-password-confirm">Confirm Password</label>
                    <input type="password" id="reg-password-confirm" required>
                </div>

                <button type="submit" class="btn">Register</button>
            </form>

            <div class="role-info">
                <strong>Note:</strong> New accounts are created as regular users. Contact the administrator if you need admin privileges to create votes.
            </div>

            <div class="auth-switch">
                <p>Already have an account? <a href="#" onclick="showLogin()">Login here</a></p>
            </div>
        </div>

        <!-- Message Display -->
        <div id="message-display"></div>
    </div>

    <script>
        // Authored or modified by Claude - 2025-09-25

        function showMessage(text, type = 'info') {
            const messageDiv = document.getElementById('message-display');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
            setTimeout(() => messageDiv.innerHTML = '', 5000);
        }

        function showLogin() {
            document.getElementById('login-form').classList.remove('hidden');
            document.getElementById('register-form').classList.add('hidden');
        }

        function showRegister() {
            document.getElementById('login-form').classList.add('hidden');
            document.getElementById('register-form').classList.remove('hidden');
        }

        async function login(event) {
            event.preventDefault();

            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;

            try {
                const response = await fetch('auth_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'login',
                        username: username,
                        password: password
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Login successful! Redirecting...', 'success');

                    // Redirect based on user role
                    setTimeout(() => {
                        if (result.data.user.role === 'admin') {
                            window.location.href = 'admin.php';
                        } else {
                            window.location.href = 'dashboard.php';
                        }
                    }, 1000);
                } else {
                    showMessage(result.error || 'Login failed', 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        async function register(event) {
            event.preventDefault();

            const username = document.getElementById('reg-username').value;
            const email = document.getElementById('reg-email').value;
            const displayName = document.getElementById('reg-display-name').value;
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('reg-password-confirm').value;

            // Validate passwords match
            if (password !== confirmPassword) {
                showMessage('Passwords do not match', 'error');
                return;
            }

            // Basic password validation
            if (password.length < 6) {
                showMessage('Password must be at least 6 characters', 'error');
                return;
            }

            try {
                const response = await fetch('auth_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'register',
                        username: username,
                        email: email,
                        display_name: displayName,
                        password: password
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('Registration successful! Please login with your new account.', 'success');

                    // Clear form and switch to login
                    document.getElementById('register').reset();
                    setTimeout(() => showLogin(), 2000);
                } else {
                    showMessage(result.error || 'Registration failed', 'error');
                }
            } catch (error) {
                console.error('Registration error:', error);
                showMessage('Network error occurred', 'error');
            }
        }

        // Check if user is already logged in
        window.addEventListener('load', async () => {
            try {
                const response = await fetch('auth_api.php?action=check_session');
                const result = await response.json();

                if (result.success && result.data.logged_in) {
                    // User is already logged in, redirect them
                    if (result.data.user.role === 'admin') {
                        window.location.href = 'admin.php';
                    } else {
                        window.location.href = 'dashboard.php';
                    }
                }
            } catch (error) {
                // Ignore errors, just show login form
                console.log('No active session');
            }
        });
    </script>
</body>
</html>