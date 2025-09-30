<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Authored or modified by Claude - 2025-09-25 -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borda Vote - Democratic Group Decision Making</title>
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
        }

        .hero {
            text-align: center;
            padding: 80px 20px;
            color: white;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            font-weight: 300;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            background: white;
            color: #667eea;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 18px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: #667eea;
        }

        .features {
            background: white;
            padding: 80px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .features h2 {
            text-align: center;
            margin-bottom: 50px;
            color: #2c3e50;
            font-size: 2.5rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            background: #f8f9fa;
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 40px 20px;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="container">
            <h1>Borda Vote</h1>
            <p>Democratic group decision making using the Borda count method</p>
            <div class="cta-buttons">
                <a href="auth.php" class="btn">Get Started</a>
                <a href="#features" class="btn btn-outline">Learn More</a>
            </div>
        </div>
    </div>

    <div id="features" class="features">
        <div class="container">
            <h2>How It Works</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📝</div>
                    <h3>Nominate Options</h3>
                    <p>Everyone suggests their preferred choices. Set limits on how many nominations each person can make.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔀</div>
                    <h3>Rank Preferences</h3>
                    <p>Drag and drop to rank all nominations from most to least preferred. Easy, intuitive interface.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h3>Fair Results</h3>
                    <p>Borda count method ensures the most democratically preferred option wins. See detailed scoring.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>User Accounts</h3>
                    <p>Secure registration and login. Participate in multiple votes with persistent identity.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Real-time Updates</h3>
                    <p>Automatic phase progression when everyone completes. Live status updates and notifications.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>Mobile Friendly</h3>
                    <p>Works perfectly on phones, tablets, and desktops. Vote from anywhere, anytime.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="container">
            <p>&copy; 2025 Borda Vote. Built with Claude Code for democratic decision making.</p>
        </div>
    </div>

    <script>
        // Check if user is already logged in and redirect appropriately
        window.addEventListener('load', async () => {
            try {
                const response = await fetch('auth_api.php?action=check_session');
                const result = await response.json();

                if (result.success && result.data.logged_in) {
                    // User is logged in, show quick access buttons
                    const ctaButtons = document.querySelector('.cta-buttons');
                    if (result.data.user.role === 'admin') {
                        ctaButtons.innerHTML = `
                            <a href="dashboard.php" class="btn">Your Dashboard</a>
                            <a href="admin.php" class="btn btn-outline">Admin Panel</a>
                        `;
                    } else {
                        ctaButtons.innerHTML = `
                            <a href="dashboard.php" class="btn">Your Dashboard</a>
                            <a href="auth.php" class="btn btn-outline">Account Settings</a>
                        `;
                    }
                }
            } catch (error) {
                // Ignore errors, just show default buttons
                console.log('No active session');
            }
        });
    </script>
</body>
</html>