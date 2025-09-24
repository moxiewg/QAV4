<?php // app_content.php ?>
<div class="main-container">

    <!-- Category Swiper View -->
    <div id="category-swiper-view" class="category-swiper-container">
        <div class="swiper category-swiper">
            <div class="swiper-wrapper" id="category-swiper-wrapper">
                <!-- Slides will be injected here by JS -->
            </div>
            <!-- Add Pagination -->
            <div class="swiper-pagination"></div>
            <!-- Add Navigation -->
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>

    <!-- Card List View (Initially Hidden for each category) -->
    <div id="card-list-views-container">
        <!-- Card list views for each category will be injected here by JS -->
        <!-- Example Card List Structure (for reference) -->
        <!--
        <div id="card-list-profile-links" class="card-list-container" data-category-id="profile-links">
            <div class="card-list-header">
                <button class="back-button">←</button> <!- Simple back arrow ->
                <h2 class="card-list-title">Profile Links</h2>
            </div>
            <div class="card-grid">
                <!- Item cards injected here ->
            </div>
        </div>
        -->
    </div>

    <!-- Theme Toggle Button -->
    <button id="theme-toggle" class="theme-toggle">
        <!-- Icon changes with theme -->
        <svg class="w-5 h-5 light-icon" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
        <svg class="w-5 h-5 dark-icon hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
    </button>

</div>