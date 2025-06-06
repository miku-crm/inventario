$(document).ready(function() {
    const sidebar = $('.sidebar');
    const mainContent = $('.main-content');
    const sidebarToggle = $('#sidebarToggle');
    const isMobile = () => window.innerWidth < 992;

    // Función para colapsar el sidebar
    function collapseSidebar() {
        sidebar.addClass('collapsed');
        mainContent.addClass('expanded');
    }

    // Función para expandir el sidebar
    function expandSidebar() {
        sidebar.removeClass('collapsed');
        mainContent.removeClass('expanded');
    }

    // Inicialización
    if (isMobile()) {
        collapseSidebar();
    }

    // Toggle sidebar al hacer clic en el botón
    sidebarToggle.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (sidebar.hasClass('collapsed')) {
            expandSidebar();
        } else {
            collapseSidebar();
        }
    });

    // Cerrar sidebar al hacer clic en un enlace (en móvil)
    $('.sidebar .nav-link').on('click', function() {
        if (isMobile()) {
            collapseSidebar();
        }
    });

    // Cerrar sidebar al hacer clic fuera
    $(document).on('click', function(e) {
        if (isMobile() && 
            !$(e.target).closest('.sidebar').length && 
            !$(e.target).closest('#sidebarToggle').length && 
            !sidebar.hasClass('collapsed')) {
            collapseSidebar();
        }
    });

    // Manejar redimensionamiento de ventana
    $(window).on('resize', function() {
        if (isMobile()) {
            collapseSidebar();
        }
        
        // Ajustar tablas si existen
        if ($.fn.dataTable && $.fn.dataTable.tables) {
            $('table.dataTable').DataTable().columns.adjust();
        }
    });

    // Prevenir que los clics dentro del sidebar se propaguen
    sidebar.on('click', function(e) {
        if (isMobile()) {
            e.stopPropagation();
        }
    });
}); 