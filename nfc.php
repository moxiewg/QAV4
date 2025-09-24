<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mika Fekadu - Links & Info</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>

    <!-- SweetAlert2 CSS & JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- GSAP Core & ScrollTrigger Plugin via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js"></script>
    
    <!-- Font Awesome (for NFC icon) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Optional: Add custom styles or overrides here */
        body {
            background-color: #f0f2f5; /* Light grey background */
        }
        /* Add a subtle transition for hover effects */
        .item-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }
        /* Hide elements initially for GSAP animation */
        .category-section, .item-card {
            opacity: 0;
            visibility: hidden;
        }
    </style>
</head>
<body class="font-sans antialiased">

<?php
// --- PHP Data Loading ---
$jsonFilePath = 'data.json';
$data = null;
$error_message = null;

if (file_exists($jsonFilePath)) {
    $jsonContent = file_get_contents($jsonFilePath);
    $data = json_decode($jsonContent, true); // Decode as associative array

    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = "Error decoding JSON: " . json_last_error_msg();
        $data = null; // Ensure data is null on error
    } elseif (empty($data) || !isset($data['categories'])) {
        $error_message = "JSON data is empty or missing 'categories' key.";
        $data = null;
    }
} else {
    $error_message = "Error: data.json file not found.";
}
// --- End PHP Data Loading ---
?>

<div class="container mx-auto max-w-6xl p-4 md:p-8">

    <header class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">Mika Fekadu</h1>
        <p class="text-lg text-gray-600">Links, Contact & Payment Information</p>
    </header>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($data && isset($data['categories'])): ?>
        <?php foreach ($data['categories'] as $category): ?>
            <section class="mb-10 category-section" id="<?= htmlspecialchars($category['id'] ?? '') ?>">
                <h2 class="text-2xl font-semibold text-gray-700 mb-5 border-l-4 border-blue-500 pl-3">
                    <?= htmlspecialchars($category['name'] ?? $category['title'] ?? 'Untitled Category') ?>
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php if (isset($category['items']) && is_array($category['items'])): ?>
                        <?php foreach ($category['items'] as $item): ?>
                            <?php
                                // Resolve a copy value from several possible keys
                                $copyValue = '';
                                foreach (['value','url','text','number','code','link'] as $k) {
                                    if (!empty($item[$k])) { $copyValue = $item[$k]; break; }
                                }
                                $name = $item['title'] ?? $item['name'] ?? $item['label'] ?? $copyValue ?? 'N/A';
                                $description = $item['description'] ?? '';
                                $image = $item['icon'] ?? ($item['image'] ?? '');
                                $color = $item['color'] ?? '#cccccc';
                                $allow_share = $item['allow_share'] ?? false;
                            ?>
                            <div class="item-card bg-white rounded-lg shadow-md overflow-hidden flex flex-col border-l-4" style="border-color: <?= htmlspecialchars($color) ?>;">
                                <div class="p-5 flex-grow">
                                    <?php if ($image): ?>
                                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?> icon" class="h-8 w-8 mb-3 object-contain inline-block" style="background-color: <?= htmlspecialchars($color) ?>; border-radius: 50%; padding: 4px;" onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-1"><?= htmlspecialchars($name) ?></h3>
                                    <div class="text-sm text-gray-600 mb-3 prose prose-sm max-w-none">
                                        <?= $description /* Output raw HTML from description if present */ ?>
                                    </div>
                                    <p class="text-xs text-gray-500 break-all mb-4">
                                        <span class="font-medium">Value:</span> <?= htmlspecialchars((string)$copyValue, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <div class="bg-gray-50 px-5 py-3">
                                    <?php if ($allow_share): ?>
                                        <button
                                            class="nfc-share-button inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                            data-value="<?= htmlspecialchars((string)$copyValue, ENT_QUOTES, 'UTF-8') ?>"
                                            data-name="<?= htmlspecialchars($name) ?>"
                                            aria-label="Share <?= htmlspecialchars($name) ?> via NFC">
                                            <i class="fas fa-wifi mr-2"></i> <!-- NFC Icon -->
                                            Share NFC
                                        </button>
                                    <?php else: ?>
                                         <span class="text-xs text-gray-400 italic">NFC sharing disabled</span>
                                    <?php endif; ?>
                                    <!-- Always show NFC action icon button -->
                                    <button
                                        class="nfc-action-button ml-2 p-2 rounded-full bg-indigo-100 hover:bg-indigo-200 text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                        data-value="<?= htmlspecialchars((string)$copyValue, ENT_QUOTES, 'UTF-8') ?>"
                                        data-name="<?= htmlspecialchars($name) ?>"
                                        aria-label="NFC Share <?= htmlspecialchars($name) ?> (icon)">
                                        <i class="fas fa-wifi"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php elseif (!$error_message): ?>
        <div class="text-center text-gray-500">No data available to display.</div>
    <?php endif; ?>

</div>
<script src="nfc_manager.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- GSAP Animations ---
        // Apply initial states slightly differently for GSAP 'to'
        gsap.set(".category-section", { y: 30, opacity: 0, visibility:'hidden' });
        gsap.set(".item-card", { scale: 0.95, opacity: 0, visibility:'hidden'});

        gsap.to(".category-section", { // Use 'to' because we start with opacity 0
            duration: 0.6,
            opacity: 1,
            visibility: 'visible',
            y: 0, // Animate from initial y: 30
            stagger: 0.2,
            ease: "power2.out",
            delay: 0.2 // Small delay for page elements to settle
        });

        gsap.to(".item-card", { // Use 'to'
            duration: 0.5,
            opacity: 1,
            visibility: 'visible',
            scale: 1, // Animate from initial scale: 0.95
            stagger: 0.07,
            ease: "back.out(1.7)", // A nice bouncy effect
            delay: 0.5 // Start after categories start appearing
        });


        // --- WebNFC Logic ---
        const nfcManager = new EnhancedNFCManager();
        nfcManager.initNFCButtons();
    });
</script>

</body>
</html>