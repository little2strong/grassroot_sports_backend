$(document).ready(function() {
    // Function to check if screen width is less than 991px
    function isMobileView() {
        return $(window).width() < 991;
    }

    // Create overlay element
    const overlay = $('<div class="sidebar-overlay"></div>');
    overlay.css({
        'position': 'fixed',
        'top': 0,
        'left': 0,
        'width': '100%',
        'height': '100%',
        'background-color': 'rgba(0, 0, 0, 0.5)',
        'z-index': 1040,
        'display': 'none'
    });
    $('body').append(overlay);

    // Sidebar toggle functionality for mobile
    $('.sidebar-toggle-mobile.d-lg-none').on('click', function(e) {
        e.stopPropagation();
        const sidebar = $('#sidebar');
        const icon = $(this).find('i');

        if (isMobileView()) {
            sidebar.toggleClass('sidebar-open');

            if (sidebar.hasClass('sidebar-open')) {
                // Show overlay when sidebar is open
                overlay.fadeIn(300);
                // Change icon to X
                icon.removeClass('fa-bars');
                icon.addClass('fa-times');
            } else {
                // Hide overlay when sidebar is closed
                overlay.fadeOut(300);
                // Change icon back to bars
                icon.removeClass('fa-times');
                icon.addClass('fa-bars');
            }
        }
    });

    // Close sidebar when clicking on overlay
    overlay.on('click', function() {
        const sidebar = $('#sidebar');
        const icon = $('.sidebar-toggle-mobile.d-lg-none').find('i');

        sidebar.removeClass('sidebar-open');
        overlay.fadeOut(300);
        icon.removeClass('fa-times');
        icon.addClass('fa-bars');
    });

    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(event) {
        const sidebar = $('#sidebar');
        const isClickInsideSidebar = sidebar.has(event.target).length > 0;
        const isClickOnToggle = $(event.target).closest('.sidebar-toggle-mobile').length > 0;

        if (isMobileView() && !isClickInsideSidebar && !isClickOnToggle && sidebar.hasClass('sidebar-open')) {
            const icon = $('.sidebar-toggle-mobile.d-lg-none').find('i');

            sidebar.removeClass('sidebar-open');
            overlay.fadeOut(300);
            icon.removeClass('fa-times');
            icon.addClass('fa-bars');
        }
    });

    // Handle window resize
    $(window).on('resize', function() {
        if (!isMobileView()) {
            // Hide overlay if window is resized above 991px
            overlay.fadeOut(300);
            $('#sidebar').removeClass('sidebar-open');

            // Reset icon for mobile toggle button
            const mobileIcon = $('.sidebar-toggle-mobile.d-lg-none').find('i');
            mobileIcon.removeClass('fa-times');
            mobileIcon.addClass('fa-bars');
        }
    });

    // Desktop sidebar toggle functionality (Collapse button now in top bar)
    $('.sidebar-toggle-top.d-none.d-lg-block').on('click', function() {
        const sidebar = $('#sidebar');
        const icon = $(this).find('i');

        sidebar.toggleClass('sidebar-collapsed');

        if (sidebar.hasClass('sidebar-collapsed')) {
            icon.removeClass('fa-chevron-left');
            icon.addClass('fa-chevron-right');
        } else {
            icon.removeClass('fa-chevron-right');
            icon.addClass('fa-chevron-left');
        }
    });

    // Set active nav link
    $('.sidebar-nav .nav-link').on('click', function() {
        $('.sidebar-nav .nav-link').removeClass('active');
        $(this).addClass('active');
    });

    // Initialize Charts
    initializeCharts();
});
