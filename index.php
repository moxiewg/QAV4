<?php
// Mika's QA-infowallet
// Author: Mika Fekadu
// Website: https://www.mikafekadu.com
// Email: i@mikafekadu.com
// Copyright (c) 2025 Mika Fekadu. All rights reserved.

session_start(); // Start the session
$isAuthenticated = $_SESSION['authenticated'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QA - Info wallet</title>

    <link rel="icon" type="image/png" href="qa-fav.png">

    <!-- Core Libraries -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="viewInfo.css">
    
    
    <!-- QR Code Specific Styles -->
    <style>
        /* .qr-code-modal { max-width: 350px !important; } */
        .qr-code-modal-content { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; }
        #qrcode-popup-container { margin: 0 auto; padding: 10px; background-color: white; border-radius: 8px; }
        #qrcode-popup-container img, #qrcode-popup-container canvas { display: block; margin: 0 auto; }
        .qr-value-text { margin-top: 1rem; word-break: break-all; font-size: 0.8rem; max-width: 100%; text-align: center; }
        .qr-type-indicator { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-bottom: 0.5rem; }
    </style>

    <!-- Critical Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chroma-js/2.4.2/chroma.min.js"></script> <!-- Added Chroma.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/Flip.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/granim/2.0.0/granim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FitText.js/1.2.0/jquery.fittext.min.js" defer></script>
    <!-- <script src="https://cdn.jsdelivr.net/gh/aneeshawadhiya/magic-cursor.js/magic-cursor.js"></script> -->
   

    <!-- Library Check -->
    <script>
        document.addEventListener('DOMContentLoaded', () => console.log('QRCode library:', typeof QRCode !== 'undefined' ? 'Loaded ✅' : 'Not loaded ❌'));
        window.addEventListener('error', e => e.message.includes('is not defined') && console.error('Library error:', e.message, 'at:', e.filename, 'line:', e.lineno));
    </script>
    
    <!-- Custom JS -->
    <script type="text/javascript" src="https://aneeshawadhiya.github.io/magic-cursor.js/magic_cursor.js" defer></script>
    <script src="viewInfo.js" defer></script>    
    <script src="nfc.js" defer></script>
   
</head>
<body>
    <?php if (!$isAuthenticated): ?>
        <?php include 'login.php'; ?>
    <?php else: ?>
        <?php include 'app_content.php'; ?>
        <div id="qrcode-container" style="display: none; position: fixed; z-index: 9999;"></div>
        <div id="modal-overlay" class="modal-overlay" style="display: none;"></div> <!-- Added modal overlay -->
    <?php endif; ?>
</body>
</html>