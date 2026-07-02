$(function () {
    const $sidebar = $('#sidebar');
    const $overlay = $('#sidebarOverlay');
    const $mobileToggle = $('.sidebar-toggle-mobile');
    const MOBILE_BP = 992;

    function isMobile() {
        return window.innerWidth < MOBILE_BP;
    }

    function openSidebar() {
        $sidebar.addClass('sidebar-open');
        $overlay.addClass('is-visible');
        $('body').css('overflow', 'hidden');
        $mobileToggle.find('i').removeClass('fa-bars').addClass('fa-times');
    }

    function closeSidebar() {
        $sidebar.removeClass('sidebar-open');
        $overlay.removeClass('is-visible');
        $('body').css('overflow', '');
        $mobileToggle.find('i').removeClass('fa-times').addClass('fa-bars');
    }

    $mobileToggle.on('click', function (e) {
        e.stopPropagation();
        if ($sidebar.hasClass('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    $overlay.on('click', closeSidebar);

    $(document).on('click', function (e) {
        if (!isMobile() || !$sidebar.hasClass('sidebar-open')) return;
        if ($(e.target).closest('#sidebar, .sidebar-toggle-mobile').length) return;
        closeSidebar();
    });

    $('.club-sidebar-nav .nav-link:not(.is-disabled)').on('click', function () {
        if (isMobile()) closeSidebar();
    });

    $('#sidebarCollapse').on('click', function () {
        if (isMobile()) return;
        $sidebar.toggleClass('sidebar-collapsed');
        const $icon = $(this).find('i');
        $icon.toggleClass('fa-chevron-left fa-chevron-right');
    });

    $(window).on('resize', function () {
        if (!isMobile()) {
            closeSidebar();
        }
    });
});
