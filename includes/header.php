<?php
require_once __DIR__ . '/../auth/auth_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inventario</title>
    
    <!-- jQuery primero -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Bootstrap JS Bundle (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    body {
        min-height: 100vh;
        background: #f8f9fa;
        margin: 0;
        padding: 0;
        display: flex;
    }

    .sidebar {
        width: 250px;
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 1030;
        background: #343a40;
        transition: transform 0.3s ease;
        overflow-y: auto;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .main-content {
        flex: 1;
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        width: calc(100% - 250px);
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        .sidebar.show {
            transform: translateX(0);
        }
        .main-content {
            margin-left: 0;
            width: 100%;
        }
    }

    /* Estilos para elementos específicos */
    .card {
        background: white;
        border-radius: 8px;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        padding: 20px;
    }

    .table-responsive {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-top: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    /* Estilos para el menú */
    .nav-group {
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding-bottom: 0.5rem;
    }

    .nav-group-title {
        color: #adb5bd;
        font-size: 0.75rem;
        text-transform: uppercase;
        padding: 0.5rem 1.5rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .nav-item .nav-link {
        padding-left: 2.5rem;
    }

    .nav-item .nav-link i {
        width: 1.25rem;
        text-align: center;
        margin-right: 0.5rem;
    }

    .nav-link:hover {
        background-color: rgba(255,255,255,0.1);
        border-radius: 4px;
        margin: 2px 10px;
    }

    .nav-link.active {
        background-color: #0d6efd;
        border-radius: 4px;
        margin: 2px 10px;
    }

    /* Ajustes para botones */
    .btn {
        border-radius: 6px;
        padding: 8px 16px;
    }

    .btn-group {
        display: inline-flex;
        gap: 0.5rem;
    }

    /* Botón de toggle */
    #sidebarCollapse {
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1040;
        display: none;
        background-color: #343a40;
        border: none;
        color: white;
        padding: 10px 12px;
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    @media (max-width: 768px) {
        #sidebarCollapse {
            display: block;
        }
    }

    /* Estilos para headers y alertas */
    h2, .h2 {
        color: #2c3e50;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    .alert {
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    </style>
</head>
<body>
    <!-- Botón Toggle Sidebar -->
    <button type="button" id="sidebarCollapse" class="btn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-3 bg-primary">
            <h3 class="h5 mb-0"><i class="fas fa-box-open"></i> Inventario</h3>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="p-3 bg-dark">
            <div class="text-white">
                <i class="fas fa-user"></i> 
                <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?>
            </div>
            <small class="text-white-50">
                <?php echo ucfirst($_SESSION['rol']); ?>
            </small>
        </div>
        <?php endif; ?>

        <ul class="nav flex-column mt-3">
            <!-- Dashboard -->
            <div class="nav-group">
                <div class="nav-group-title">Principal</div>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                       href="/index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
            </div>
            
            <!-- Gestión de Préstamos -->
            <div class="nav-group">
                <div class="nav-group-title">Préstamos</div>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'prestamos.php' ? 'active' : ''; ?>" 
                       href="/prestamos.php">
                        <i class="fas fa-handshake"></i> Préstamos Activos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'historial_prestamos.php' ? 'active' : ''; ?>" 
                       href="/historial_prestamos.php">
                        <i class="fas fa-history"></i> Historial
                    </a>
                </li>
            </div>

            <!-- Gestión de Inventario -->
            <div class="nav-group">
                <div class="nav-group-title">Inventario</div>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : ''; ?>" 
                       href="/productos.php">
                        <i class="fas fa-box"></i> Productos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'entradas.php' ? 'active' : ''; ?>" 
                       href="/entradas.php">
                        <i class="fas fa-truck-loading"></i> Entradas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'proveedores.php' ? 'active' : ''; ?>" 
                       href="/proveedores.php">
                        <i class="fas fa-truck"></i> Proveedores
                    </a>
                </li>
            </div>

            <!-- Gestión de Usuarios -->
            <div class="nav-group">
                <div class="nav-group-title">Usuarios</div>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active' : ''; ?>" 
                       href="/clientes.php">
                        <i class="fas fa-users"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios_productos.php' ? 'active' : ''; ?>" 
                       href="/usuarios_productos.php">
                        <i class="fas fa-user-tag"></i> Usuarios Productos
                    </a>
                </li>
                <?php if (checkPermission('admin')): ?>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" 
                       href="/usuarios.php">
                        <i class="fas fa-user-shield"></i> Usuarios Sistema
                    </a>
                </li>
                <?php endif; ?>
            </div>

            <!-- Reportes -->
            <?php if (checkPermission('admin')): ?>
            <div class="nav-group">
                <div class="nav-group-title">Análisis</div>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>" 
                       href="/reportes.php">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                </li>
            </div>
            <?php endif; ?>

            <!-- Cerrar Sesión -->
            <div class="nav-group">
                <li class="nav-item">
                    <a class="nav-link text-danger" href="/auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </div>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- El contenido específico de cada página irá aquí -->
    </main>

    <script>
    $(document).ready(function() {
        // Toggle sidebar
        $('#sidebarCollapse').click(function() {
            $('.sidebar').toggleClass('show');
        });

        // Auto-hide sidebar on mobile when clicking outside
        $(document).click(function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#sidebarCollapse').length) {
                    $('.sidebar').removeClass('show');
                }
            }
        });

        // Handle window resize
        $(window).resize(function() {
            if ($(window).width() > 768) {
                $('.sidebar').removeClass('show');
            }
        });

        // Highlight current section in menu
        const currentPath = window.location.pathname;
        $('.nav-link').each(function() {
            const linkPath = $(this).attr('href');
            if (currentPath === linkPath) {
                $(this).addClass('active');
                // Highlight parent nav-group
                $(this).closest('.nav-group').addClass('active-group');
            }
        });

        // Add hover effect for menu items
        $('.nav-link').hover(
            function() { $(this).addClass('hover'); },
            function() { $(this).removeClass('hover'); }
        );
    });
    </script>
</body>
</html> 