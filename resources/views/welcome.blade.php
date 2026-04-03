<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Lavendr') }} - Retro Vending Machine</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">

    <style>
        /* ========================================
           RETRO WELCOME PAGE STYLES
           ======================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Press Start 2P', cursive;
            background-color: #000000;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Scanline Overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                0deg,
                rgba(255, 255, 255, 0.03),
                rgba(255, 255, 255, 0.03) 1px,
                transparent 1px,
                transparent 2px
            );
            pointer-events: none;
            z-index: 1000;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Logo Section */
        .logo {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeIn 1s ease-out;
        }

        .logo-emoji {
            font-size: 6rem;
            margin-bottom: 1rem;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .logo h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            text-shadow: 4px 4px 0 #cccccc;
        }

        .logo p {
            font-family: 'VT323', monospace;
            font-size: 1.5rem;
            color: #cccccc;
        }

        /* Vending Machine Preview */
        .vending-preview {
            background-color: #000000;
            border: 8px solid #ffffff;
            box-shadow:
                8px 8px 0 #cccccc,
                -4px -4px 0 #333333;
            padding: 3rem;
            margin-bottom: 3rem;
            max-width: 600px;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .preview-slot {
            background-color: #000000;
            border: 4px solid #ffffff;
            padding: 1rem;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }

        .preview-slot:hover {
            background-color: #333333;
            transform: scale(1.1);
            box-shadow: 0 0 10px #ffffff;
        }

        .preview-slot .emoji {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .preview-slot .number {
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
        }

        /* Features Section */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
            width: 100%;
        }

        .feature-card {
            background-color: #000000;
            border: 4px solid #ffffff;
            padding: 2rem;
            text-align: center;
            box-shadow: 4px 4px 0 #cccccc;
            transition: all 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 6px 6px 0 #cccccc;
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            font-family: 'VT323', monospace;
            font-size: 1.2rem;
            color: #cccccc;
        }

        /* CTA Section */
        .cta {
            text-align: center;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .btn-retro {
            display: inline-block;
            background-color: #ffffff;
            color: #000000;
            padding: 1.5rem 3rem;
            font-size: 1.2rem;
            text-decoration: none;
            border: 4px solid #ffffff;
            box-shadow: 4px 4px 0 #cccccc;
            transition: all 0.1s;
            font-family: 'Press Start 2P', cursive;
            margin: 0.5rem;
        }

        .btn-retro:hover {
            background-color: #cccccc;
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 #cccccc;
        }

        .btn-retro:active {
            transform: translate(0, 0);
            box-shadow: 2px 2px 0 #cccccc;
        }

        .btn-secondary {
            background-color: #000000;
            color: #ffffff;
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 2rem;
            font-family: 'VT323', monospace;
            font-size: 1rem;
            color: #cccccc;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .logo h1 {
                font-size: 1.5rem;
            }

            .logo-emoji {
                font-size: 4rem;
            }

            .vending-preview {
                padding: 2rem;
            }

            .preview-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .features {
                grid-template-columns: 1fr;
            }

            .btn-retro {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }

            body::before {
                display: none;
            }
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo Section -->
        <div class="logo">
            <div class="logo-emoji">🕹️</div>
            <h1>{{ config('app.name', 'LAVENDR') }}</h1>
            <p>RETRO VENDING MACHINE SYSTEM</p>
        </div>

        <!-- Vending Machine Preview -->
        <div class="vending-preview">
            <div class="preview-grid">
                <div class="preview-slot">
                    <div class="emoji">🧃</div>
                    <div class="number">01</div>
                </div>
                <div class="preview-slot">
                    <div class="emoji">🍱</div>
                    <div class="number">02</div>
                </div>
                <div class="preview-slot">
                    <div class="emoji">🍪</div>
                    <div class="number">03</div>
                </div>
                <div class="preview-slot">
                    <div class="emoji">🧃</div>
                    <div class="number">04</div>
                </div>
                <div class="preview-slot">
                    <div class="emoji">🍱</div>
                    <div class="number">05</div>
                </div>
                <div class="preview-slot">
                    <div class="emoji">🍪</div>
                    <div class="number">06</div>
                </div>
                <div class="preview-slot">
                    <div class="emoji">🧃</div>
                    <div class="number">07</div>
                </div>
                <div class="preview-slot">
                    <div class="emoji">🍱</div>
                    <div class="number">08</div>
                </div>
            </div>
            <p style="font-family: 'VT323', monospace; font-size: 1.2rem; text-align: center; color: #cccccc;">
                ◆ PRESS START TO BEGIN ◆
            </p>
        </div>

        <!-- Features -->
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">💳</div>
                <h3>ACCESS CARD</h3>
                <p>Tap your card to purchase</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>DAILY QUOTA</h3>
                <p>Managed balance system</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎮</div>
                <h3>RETRO UI</h3>
                <p>8-bit pixel art style</p>
            </div>
        </div>

        <!-- CTA Buttons -->
        <div class="cta">
            @if(auth()->check())
                <a href="{{ route('dashboard') }}" class="btn-retro">
                    ► CONTINUE TO VENDING
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-retro">
                    ► INSERT COIN (LOGIN)
                </a>
                @if(Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-retro btn-secondary">
                        NEW GAME (REGISTER)
                    </a>
                @endif
            @endif
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>◆ {{ date('Y') }} {{ config('app.name') }} - POWERED BY LARAVEL ◆</p>
    </footer>

    <!-- Screen Reader Announcements -->
    <div aria-live="polite" class="sr-only">
        Welcome to {{ config('app.name') }} - A retro vending machine management system
    </div>
</body>
</html>
