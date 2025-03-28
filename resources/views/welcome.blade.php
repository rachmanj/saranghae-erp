<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saranghae ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #FF4433;
            --primary-hover: #ff5e50;
            --secondary: #ff9c8f;
            --accent: #FFE53B;
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
            --text: #ffffff;
            --text-secondary: #b3b3b3;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            z-index: 10;
        }

        /* Background Elements */
        .bg-gradient {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(255, 68, 51, 0.1), transparent 60%),
                radial-gradient(circle at bottom left, rgba(255, 229, 59, 0.05), transparent 60%);
            z-index: 1;
        }

        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: 50px 50px;
            background-image:
                linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            z-index: 2;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 30px 0;
            margin-bottom: 50px;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: 0.5px;
        }

        .logo span {
            color: var(--primary);
        }

        /* Hero Section */
        .hero {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 80px;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            background: linear-gradient(90deg, var(--text) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.2rem;
            font-weight: 300;
            max-width: 600px;
            color: var(--text-secondary);
            margin-bottom: 40px;
            line-height: 1.6;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 16px 32px;
            background-color: var(--primary);
            color: var(--text);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 68, 51, 0.3);
        }

        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 68, 51, 0.4);
        }

        .btn-secondary {
            background-color: transparent;
            border: 2px solid var(--primary);
            box-shadow: none;
            margin-left: 20px;
        }

        .btn-secondary:hover {
            background-color: rgba(255, 68, 51, 0.1);
            box-shadow: none;
        }

        /* Features Section */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 80px;
        }

        .feature-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            transition: transform 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 60px;
            height: 60px;
            border-radius: 20px;
            background-color: rgba(255, 68, 51, 0.15);
            margin-bottom: 20px;
            color: var(--primary);
            font-size: 24px;
        }

        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Footer */
        .footer {
            margin-top: auto;
            width: 100%;
            text-align: center;
            padding: 30px 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .features {
                grid-template-columns: 1fr;
            }

            .btn {
                padding: 14px 28px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="bg-gradient"></div>
    <div class="bg-grid"></div>

    <div class="container">
        <header class="header">
            <div class="logo">Sarang<span>hae</span>ERP</div>

            @if (Route::has('login'))
                <div>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-secondary">Login</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-secondary">Register</a>
                        @endif
                    @endauth
                </div>
            @endif
        </header>

        <section class="hero">
            <h1>Welcome to Saranghae ERP</h1>
            <p>Modernize your business operations with our comprehensive enterprise resource planning solution.
                Streamline workflows, boost productivity, and gain valuable insights.</p>
            <a href="/admin" class="btn">Go to Admin Panel</a>
        </section>

        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">📦</div>
                <h3>Inventory Management</h3>
                <p>Track inventory in real-time, manage stock levels, and automate reordering processes to ensure
                    optimal inventory control.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Financial Management</h3>
                <p>Streamline accounting processes, track expenses, manage budgets, and generate comprehensive financial
                    reports.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔄</div>
                <h3>Supply Chain Management</h3>
                <p>Optimize your supply chain operations, from procurement to delivery, ensuring efficiency and
                    cost-effectiveness.</p>
            </div>
        </section>
    </div>

    <footer class="footer">
        <p>&copy; {{ date('Y') }} Saranghae ERP. All rights reserved.</p>
    </footer>
</body>

</html>
