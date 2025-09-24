<?php
// Mika's QA-infowallet
// Author: Mika Fekadu
// Website: https://www.mikafekadu.com
// Email: i@mikafekadu.com
// Copyright (c) 2025 Mika Fekadu. All rights reserved.

// login.php

// IMPORTANT: Replace this with the hash you generated in Step 1.
define('ADMIN_PASSWORD_HASH', '$2y$12$aT7GbRMgMxIQCF654BgZuuZ0xa1WiAvsIgcheoYAaRvRFxTt5vIRm'); // <-- PASTE YOUR GENERATED HASH HERE

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

// If the user is already logged in, redirect them to the admin panel.
if (!empty($_SESSION['authenticated'])) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Verify the submitted password against the stored hash.
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        // Password is correct. Regenerate session ID for security.
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;

        // Secure "Remember Me": If checked, extend the session cookie's lifetime.
        if ($remember_me) {
            $session_params = session_get_cookie_params();
            // Set the session cookie to last for 30 days
            setcookie(
                session_name(),
                session_id(),
                time() + (86400 * 30), // 30 days
                $session_params['path'],
                $session_params['domain'],
                $session_params['secure'],
                $session_params['httponly']
            );
        }

        // Redirect to the admin panel upon successful login.
        header("Location: index.php");
        exit;
    } else {
        $error = 'Invalid password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QA InfoWallet</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.waves.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@600;700&family=Open+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="qa-fav.png">
    
    <style>
        :root {
            --primary-orange: #ff5001;
            --primary-blue: #01013d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            position: relative;
            /* Slower, smoother color transition over 30 seconds */
            background: linear-gradient(45deg, var(--primary-blue), var(--primary-orange));
            background-size: 400% 400%;
            animation: gradientShift 30s ease-in-out infinite;
        }

        /* Smoother gradient animation */
        @keyframes gradientShift {
            0%, 100% { 
                background-position: 0% 50%; 
            }
            50% { 
                background-position: 100% 50%; 
            }
        }

        #vanta-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            /* More subtle opacity for background */
            opacity: 0.3;
        }

        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }

        /* Updated to match screenshot - cleaner white background */
        .login-container {
            width: 100%;
            max-width: 380px;
            padding: 3rem 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Larger logo to match screenshot */
        /* .qa-logo {
            width: 140px;
            height: 140px;
            margin: 0 auto 1.5rem auto;
            display: block;
        } */

        .wallet-title {
            font-family: 'Work Sans', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 2rem;
            letter-spacing: 0.5px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .password-label {
            font-family: 'Work Sans', sans-serif;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
            text-align: left;
            margin-bottom: 0.5rem;
        }

        /* Password field with integrated GO button like screenshot */
        .password-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-field {
            width: 100%;
            padding: 1rem 4rem 1rem 1rem;
            border: 2px solid var(--primary-orange);
            border-radius: 0.75rem;
            font-size: 1rem;
            background: white;
            color: #333;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(255, 80, 1, 0.1);
        }

        .go-button {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-orange);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .go-button:hover {
            background: #e64501;
        }

        /* Bottom row with Remember toggle and Login button side by side */
        .bottom-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .remember-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .remember-label {
            font-family: 'Work Sans', sans-serif;
            font-weight: 500;
            color: #333;
            font-size: 0.95rem;
        }

        /* Smaller toggle switch to match screenshot */
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            background: #ddd;
            border-radius: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-switch.active {
            background: var(--primary-orange);
        }

        .toggle-slider {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 22px;
            height: 22px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(24px);
        }

        /* Orange login button to match screenshot */
        .submit-btn {
            background: var(--primary-orange);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            font-family: 'Work Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .submit-btn:hover {
            background: #e64501;
            transform: translateY(-1px);
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 1rem;
            }
            
            .login-container {
                padding: 2rem 1.5rem;
            }

            .bottom-row {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
        }


          body {
            font-family: "Syne", sans-serif;
            /* background-color: #f9fafb; */
            /* background-image: linear-gradient(to bottom right, #fff6f2, #ffffff, #ffcfb9); */
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 350px;
            padding: 1rem;
            background-color: none;
            border-radius: 0.75rem;
            /* box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); */
            /* height: 60vh; */
        }
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        input[type="password"] {
            border: 1px solid #d1d5db;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            width: 100%;
        }
        input:focus {
             outline: 2px solid transparent;
             outline-offset: 2px;
             --tw-ring-color: #ff5001;
             --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
             --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(3px + var(--tw-ring-offset-width)) var(--tw-ring-color);
             box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
             border-color: #ff5001;
        }
         .submit-btn {
            background-color: #ff5001;
            color: white;
            padding: 0.75rem 1.5rem;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            width: 100%;
        }
        .submit-btn:hover {
            background-color: white;
            color: #ff5001;
            border-color: #ff5001;
        }
        .login-error {
            color: #ef4444;
            background-color: #fee2e2;
            padding: 0.75rem;
            border-radius: 0.375rem;
            text-align: center;
        }
        /* Switch control styles */
        .switch { position: relative; display: inline-block; width: 34px; height: 20px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #ff5001; }
        input:checked + .slider:before { transform: translateX(14px); }
    </style>
</head>
<body>
    <div id="vanta-bg"></div>

    <div class="main-container">
        <div class="login-container">
                    <form method="POST" action="" class="login-form">
            <img src="qaicon-2.svg" alt="QA Icon" class="qa-logo">
          

            <?php if ($error): ?>
                <p class="login-error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password:</label>
                <input type="password" id="password" name="password" required autofocus>
            </div>

            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center cursor-pointer my-2">
                    <span class="mr-3 text-sm text-gray-700">Remember</span>
                    <label class="switch">
                        <input type="checkbox" id="remember_me" name="remember_me">
                        <span class="slider"></span>
                    </label>
                </label>
                <button type="submit" class="submit-btn mx-2">Login</button>
            </div>
        </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let vantaEffect = VANTA.WAVES({
                el: "#vanta-bg",
                mouseControls: true,
                touchControls: true,
                gyroControls: false,
                minHeight: 200.00,
                minWidth: 200.00,
                scale: 1.00,
                scaleMobile: 1.00,
                color: 0x01013d,
                shininess: 20.00,
                waveHeight: 15.00,
                waveSpeed: 0.5,
                zoom: 0.8
            });

            setInterval(() => {
                const currentColor = vantaEffect.options.color;
                const newColor = currentColor === 0x01013d ? 0xff5001 : 0x01013d;
                vantaEffect.setOptions({ color: newColor });
            }, 15000);

            $('#rememberToggle').click(function() {
                $(this).toggleClass('active');
                $('#remember_me').prop('checked', $(this).hasClass('active'));
            });

            <?php if ($error): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: '<?php echo htmlspecialchars($error); ?>',
                    background: 'rgba(255, 255, 255, 0.95)',
                    backdrop: 'rgba(0, 0, 0, 0.4)',
                    confirmButtonColor: '#ff5001'
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>
