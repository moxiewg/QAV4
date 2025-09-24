<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scroll-Based Layout Animation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Basic page setup */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            color: #fff;
            background: #111;
            /* Prevents horizontal scrollbar during Flip animation */
            overflow-x: hidden; 
        }

        /* A container to center content and provide padding */
        .container {
            width: 100%;
            max-width: 1200px; /* Limits the width on very large screens */
            padding: 0 2rem;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* Simple header for context */
        .header {
            padding: 2rem 0;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        /* Dummy content to create scrollable space */
        .dummy-content {
            height: 50vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 1.5rem;
            color: #555;
        }

        /* The main gallery container */
        .gallery {
            display: grid;
            width: 100%;
            position: relative;
        }

        /* Styles for individual gallery items */
        .gallery__item {
            position: relative;
            overflow: hidden;
            width: 100%;
            height: auto;
            /* aspect-ratio is defined inline as a CSS variable for flexibility */
            aspect-ratio: var(--aspect-ratio);
        }

        /* The image element within a gallery item */
        .gallery__item-img {
            position: absolute;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: 50% 50%;
        }

        /* STATE 1: The default grid layout */
        .gallery--grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem; /* Space between grid items */
            align-items: start;
        }

        /* STATE 2: The single-item, pinned layout */
        .gallery--single {
            /* All items are stacked in the same grid cell */
            grid-template-columns: 1fr;
            grid-template-rows: 1fr;
            place-items: center;
        }
        
        .gallery--single .gallery__item {
            /* Forces all items into the same cell */
            grid-area: 1 / 1 / 2 / 2; 
            width: 50%; /* Make the pinned item smaller than the full viewport */
            max-width: 500px;
        }

        /* In the single layout, hide all items that are not the target */
        .gallery--single .gallery__item:not(.gallery__item--current) {
            opacity: 0;
            pointer-events: none;
        }

    </style>
</head>
<body>

    <main class="container">
        <header class="header">
            <h1>Scroll To Animate Layout</h1>
        </header>

        <div class="dummy-content">
            <p>Scroll down to see the magic happen.</p>
        </div>

        <!-- The gallery starts in the 'gallery--grid' state -->
        <div class="gallery gallery--grid">
            <!-- 
              Each item has an inline style for --aspect-ratio to control its shape.
              The first item will be the one that remains visible in the single layout.
            -->
            <div class="gallery__item" style="--aspect-ratio: 1/1.5;">
                <div class="gallery__item-img" style="background-image:url(https://tympanus.net/codrops/wp-content/uploads/2023/07/scroll-layout-1.jpg)"></div>
            </div>
            <div class="gallery__item" style="--aspect-ratio: 1.5/1;">
                <div class="gallery__item-img" style="background-image:url(https://tympanus.net/codrops/wp-content/uploads/2023/07/scroll-layout-2.jpg)"></div>
            </div>
            <div class="gallery__item" style="--aspect-ratio: 1/1.5;">
                <div class="gallery__item-img" style="background-image:url(https://tympanus.net/codrops/wp-content/uploads/2023/07/scroll-layout-3.jpg)"></div>
            </div>
            <div class="gallery__item" style="--aspect-ratio: 1.5/1;">
                <div class="gallery__item-img" style="background-image:url(https://tympanus.net/codrops/wp-content/uploads/2023/07/scroll-layout-4.jpg)"></div>
            </div>
             <div class="gallery__item" style="--aspect-ratio: 1.5/1;">
                <div class="gallery__item-img" style="background-image:url(https://tympanus.net/codrops/wp-content/uploads/2023/07/scroll-layout-5.jpg)"></div>
            </div>
            <div class="gallery__item" style="--aspect-ratio: 1/1.5;">
                <div class="gallery__item-img" style="background-image:url(https://tympanus.net/codrops/wp-content/uploads/2023/07/scroll-layout-6.jpg)"></div>
            </div>
            <div class="gallery__item" style="--aspect-ratio: 1.5/1;">
                <div class="gallery__item-img" style="background-image:url(https://tympanus.net/codrops/wp-content/uploads/2023/07/scroll-layout-7.jpg)"></div>
            </div>
            <div class="gallery__item" style="--aspect-ratio: 1/1.5;">
                <div class="gallery__item-img" style="background-image:url(https://tympanus.net/codrops/wp-content/uploads/2023/07/scroll-layout-8.jpg)"></div>
            </div>
        </div>

        <div class="dummy-content" style="height: 100vh;">
            <p>The layout is now pinned.<br>Scroll further to release.</p>
        </div>

    </main>

    <!-- GSAP Libraries from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/Flip.min.js"></script>

    <script>
        // Ensure the DOM is fully loaded before running the script
        document.addEventListener('DOMContentLoaded', () => {

            // Register GSAP plugins
            gsap.registerPlugin(ScrollTrigger, Flip);

            // Select DOM elements
            const gallery = document.querySelector('.gallery');
            // Convert NodeList to an Array to easily use array methods
            const galleryItems = Array.from(gallery.querySelectorAll('.gallery__item'));
            // The first item is designated as the 'current' one for the single layout
            const firstItem = galleryItems[0];

            // Add the class that marks which item should remain visible
            gsap.set(firstItem, {
                className: 'gallery__item gallery__item--current'
            });

            // The main animation function
            const animateGallery = (isEntering) => {
                // Get the current state of all gallery items using GSAP Flip
                // We're tracking position, size, and aspect-ratio
                const state = Flip.getState(galleryItems, { props: 'transform, aspectRatio' });

                // Toggle the layout classes on the main gallery container
                gallery.classList.toggle('gallery--single', isEntering);
                gallery.classList.toggle('gallery--grid', !isEntering);

                // Animate from the captured state to the new state
                Flip.from(state, {
                    duration: 0.8,
                    ease: 'power2.inOut',
                    stagger: 0.04, // Small delay between each item's animation
                    absolute: true, // Takes items out of document flow for smooth transition
                    // Set a higher z-index on the target item to ensure it's on top
                    onEnter: (elements) => {
                        return gsap.to(elements, { 
                            zIndex: 1,
                            duration: 0
                        });
                    },
                    onLeave: (elements) => {
                        return gsap.to(elements, { 
                            zIndex: 0,
                            duration: 0
                        });
                    }
                });
            };

            // Create the ScrollTrigger to control the animation
            ScrollTrigger.create({
                trigger: gallery,
                start: 'center center', // When the center of the gallery hits the center of the viewport
                end: '+=200%', // Pin for a duration of 200% of the viewport height
                pin: true, // Pin the trigger element during the animation
                scrub: 1,  // Smoothly links the animation to the scrollbar
                onEnter: () => animateGallery(true),
                onLeaveBack: () => animateGallery(false),
            });
        });
    </script>
</body>
</html>