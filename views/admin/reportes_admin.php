<?php
session_start();

// Verificar sesión de admin
if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../../views/home.php");
    exit();
}

require_once "../../config/conexion.php";
$con = conexion();

// ⭐ VALIDAR SEDE_ID
$sede_id = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : 0;
if($sede_id <= 0) {
    header("Location: index_sedes.php");
    exit();
}

// Obtener información de la sede
$sede_query = mysqli_query($con, "SELECT nombre, ciudad FROM sedes WHERE id = $sede_id");
if(mysqli_num_rows($sede_query) > 0) {
    $sede_info = mysqli_fetch_assoc($sede_query);
    $sede_nombre = $sede_info['nombre'];
    $sede_ciudad = $sede_info['ciudad'];
} else {
    header("Location: index_sedes.php");
    exit();
}

// ⭐ ESTADÍSTICAS FILTRADAS POR SEDE
// Instructores: planta de esta sede + contratistas (todos)
$total_instructores = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='instructor' AND (sede_id = $sede_id OR (tipo_contrato='contratista' AND sede_id IS NULL))"))['total'];
$instructores_activos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='instructor' AND estado='activo' AND (sede_id = $sede_id OR (tipo_contrato='contratista' AND sede_id IS NULL))"))['total'];
$instructores_inactivos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='instructor' AND estado='inactivo' AND (sede_id = $sede_id OR (tipo_contrato='contratista' AND sede_id IS NULL))"))['total'];
$fecha_limite = date('Y-m-d', strtotime('+30 days'));
$instructores_vencer = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='instructor' AND tipo_contrato='contratista' AND estado='activo' AND fecha_fin_contrato <= '$fecha_limite' AND fecha_fin_contrato >= CURDATE() AND (sede_id = $sede_id OR sede_id IS NULL)"))['total'];

// Celadores: solo de esta sede
$total_celadores = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='celador' AND sede_id = $sede_id"))['total'];
$celadores_activos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='celador' AND estado='activo' AND sede_id = $sede_id"))['total'];
$celadores_inactivos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='celador' AND estado='inactivo' AND sede_id = $sede_id"))['total'];
$celadores_vencer = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='celador' AND tipo_contrato='contratista' AND estado='activo' AND fecha_fin_contrato <= '$fecha_limite' AND fecha_fin_contrato >= CURDATE() AND sede_id = $sede_id"))['total'];

// Ocupaciones: filtradas por sede (a través de ambiente → piso → sede)
$total_ocupaciones = 0;
$ocupaciones_hoy = 0;
$check_table = mysqli_query($con, "SHOW TABLES LIKE 'historial_ocupacion'");
if(mysqli_num_rows($check_table) > 0) {
    $total_ocupaciones = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE p.sede_id = $sede_id"))['total'];
    $hoy = date('Y-m-d');
    $ocupaciones_hoy = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE DATE(ho.fecha_inicio) = '$hoy' AND p.sede_id = $sede_id"))['total'];
}

// Personal Total
$total_personal = $total_instructores + $total_celadores;
$total_activos = $instructores_activos + $celadores_activos;
$total_inactivos = $instructores_inactivos + $celadores_inactivos;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/reportes_admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Reportes - <?php echo $sede_nombre; ?></title>
    <style>
        .header-info { display:flex; align-items:center; gap:12px; }
        .usuario-info { display:flex; flex-direction:column; align-items:flex-end; line-height:1.3; }
        .usuario-nombre { font-size:15px; font-weight:700; color:var(--azul-oscuro); }
        .usuario-tipo { font-size:13px; font-weight:600; color:var(--verde-acento); }
        .btn-logout { padding:12px !important; width:46px; height:46px; justify-content:center; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <button class="btn-menu" id="btnMenu">
                <i class="bi bi-list"></i>
            </button>
            <img class="logo-saga" src="../../assets/img/ChatGPT_Image_6_mar_2026__12_14_37_p.m.-removebg-preview.png" alt="logo saga">
            <h2>SAGA</h2>
        </div>

        <div class="header-info">
            <div class="usuario-info">
                <span class="usuario-nombre"><?php echo $_SESSION['nombre']; ?></span>
                <span class="usuario-tipo">Administrador</span>
            </div>
            <a class="btn-logout" href="../../controllers/logout.php">
                <i class="bi bi-box-arrow-right"></i>
            </a>

        </div>
    </div>

    <!-- Sidebar -->
    <?php $pagina_actual = basename($_SERVER['PHP_SELF']); ?>
    <div class="modal-menu" id="modalMenu">
        <div class="sidebar">
            <h3 class="menu-titulo">PRINCIPAL</h3>
            <a href="dashboard_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'dashboard_admin.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-bar-chart-fill"></i>Dashboard
            </a>
            <a href="index_sedes.php">
                <i class="bi bi-building"></i>Sedes
            </a>
            <a href="pisos_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'pisos_admin.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-layers"></i>Pisos
            </a>
            <a href="Ambientes_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo ($pagina_actual == 'Ambientes_admin.php' || $pagina_actual == 'Ambientes_piso.php' || $pagina_actual == 'carga_masiva_ambientes.php') ? 'class="active"' : ''; ?>>
                <i class="bi bi-grid-3x3-gap"></i>Ambientes
            </a>

            <h3 class="menu-titulo">GESTIÓN</h3>
            <a href="Celadores_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'Celadores_admin.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-shield-check"></i>Celadores
            </a>
            <a href="instructores_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'instructores_admin.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-person-workspace"></i>Instructores
            </a>
            <a href="ocupaciones_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'ocupaciones_admin.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-calendar-check"></i>Ocupaciones
            </a>
            <a href="calendario_ocupaciones.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'calendario_ocupaciones.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-calendar3"></i>Calendario
            </a>
            <a href="reportes_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'reportes_admin.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-file-earmark-bar-graph"></i>Reportes
            </a>
            <a href="historial_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'historial_admin.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-clock-history"></i>Historial
            </a>

            <h3 class="menu-titulo">SISTEMA</h3>
            <a href="index_sedes.php">
                <i class="bi bi-gear-fill"></i>Gestionar Sedes
            </a>

        </div>
    </div>

    <!-- Encabezado -->
    <div class="encabezado">
        <div class="encabezado-contenido">
            <h1>Reportes - <?php echo $sede_nombre; ?></h1>
            <p>Genera y visualiza reportes detallados de esta sede</p>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="estadisticas-rapidas">
        <h2 class="seccion-titulo">📊 Resumen General</h2>
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icono">👥</div>
                <div class="stat-info">
                    <span class="stat-numero"><?php echo $total_personal; ?></span>
                    <span class="stat-label">Total Personal</span>
                </div>
            </div>

            <div class="stat-card activos">
                <div class="stat-icono">✅</div>
                <div class="stat-info">
                    <span class="stat-numero"><?php echo $total_activos; ?></span>
                    <span class="stat-label">Personal Activo</span>
                </div>
            </div>

            <div class="stat-card inactivos">
                <div class="stat-icono">⭕</div>
                <div class="stat-info">
                    <span class="stat-numero"><?php echo $total_inactivos; ?></span>
                    <span class="stat-label">Personal Inactivo</span>
                </div>
            </div>

            <div class="stat-card alerta">
                <div class="stat-icono">⚠️</div>
                <div class="stat-info">
                    <span class="stat-numero"><?php echo ($instructores_vencer + $celadores_vencer); ?></span>
                    <span class="stat-label">Contratos por Vencer</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Reportes Disponibles -->
    <div class="reportes-section">
        <h2 class="seccion-titulo"><i class="bi bi-clipboard"></i> Reportes Disponibles</h2>
        
        <div class="reportes-grid">
            
            <!-- 1. Reporte de Instructores -->
            <div class="reporte-card">
                <div class="reporte-header instructores">
                    <div class="reporte-icono"><i class="bi bi-person-workspace"></i></div>
                    <h3>Reporte de Instructores</h3>
                </div>
                
                <div class="reporte-body">
                    <p class="reporte-descripcion">
                        Instructores de planta de esta sede + contratistas (todas las sedes)
                    </p>
                    
                    <div class="reporte-stats">
                        <div class="mini-stat">
                            <span class="mini-numero"><?php echo $total_instructores; ?></span>
                            <span class="mini-label">Total</span>
                        </div>
                        <div class="mini-stat activo">
                            <span class="mini-numero"><?php echo $instructores_activos; ?></span>
                            <span class="mini-label">Activos</span>
                        </div>
                        <div class="mini-stat inactivo">
                            <span class="mini-numero"><?php echo $instructores_inactivos; ?></span>
                            <span class="mini-label">Inactivos</span>
                        </div>
                        <div class="mini-stat vencer">
                            <span class="mini-numero"><?php echo $instructores_vencer; ?></span>
                            <span class="mini-label">Por Vencer</span>
                        </div>
                    </div>
                </div>
                
                <div class="reporte-footer">
                    <a href="../../controllers/ReporteInstructores.php?formato=excel&sede_id=<?php echo $sede_id; ?>" class="btn-generar excel">
                        📊 Excel
                    </a>
                    <a href="../../controllers/ReporteInstructores.php?formato=pdf&sede_id=<?php echo $sede_id; ?>" class="btn-generar pdf">
                        <i class="bi bi-file-earmark-text"></i> PDF
                    </a>
                </div>
            </div>

            <!-- 2. Reporte de Celadores -->
            <div class="reporte-card">
                <div class="reporte-header celadores">
                    <div class="reporte-icono"><i class="bi bi-shield-check"></i></div>
                    <h3>Reporte de Celadores</h3>
                </div>
                
                <div class="reporte-body">
                    <p class="reporte-descripcion">
                        Celadores asignados a esta sede
                    </p>
                    
                    <div class="reporte-stats">
                        <div class="mini-stat">
                            <span class="mini-numero"><?php echo $total_celadores; ?></span>
                            <span class="mini-label">Total</span>
                        </div>
                        <div class="mini-stat activo">
                            <span class="mini-numero"><?php echo $celadores_activos; ?></span>
                            <span class="mini-label">Activos</span>
                        </div>
                        <div class="mini-stat inactivo">
                            <span class="mini-numero"><?php echo $celadores_inactivos; ?></span>
                            <span class="mini-label">Inactivos</span>
                        </div>
                        <div class="mini-stat vencer">
                            <span class="mini-numero"><?php echo $celadores_vencer; ?></span>
                            <span class="mini-label">Por Vencer</span>
                        </div>
                    </div>
                </div>
                
                <div class="reporte-footer">
                    <a href="../../controllers/ReporteCeladores.php?formato=excel&sede_id=<?php echo $sede_id; ?>" class="btn-generar excel">
                        📊 Excel
                    </a>
                    <a href="../../controllers/ReporteCeladores.php?formato=pdf&sede_id=<?php echo $sede_id; ?>" class="btn-generar pdf">
                        <i class="bi bi-file-earmark-text"></i> PDF
                    </a>
                </div>
            </div>

            <!-- 3. Reporte de Ocupaciones -->
            <div class="reporte-card">
                <div class="reporte-header ocupaciones">
                    <div class="reporte-icono"><i class="bi bi-calendar-event"></i></div>
                    <h3>Reporte de Ocupaciones</h3>
                </div>
                
                <div class="reporte-body">
                    <p class="reporte-descripcion">
                        Ocupaciones de ambientes de esta sede
                    </p>
                    
                    <div class="reporte-stats">
                        <div class="mini-stat">
                            <span class="mini-numero"><?php echo $total_ocupaciones; ?></span>
                            <span class="mini-label">Total</span>
                        </div>
                        <div class="mini-stat activo">
                            <span class="mini-numero"><?php echo $ocupaciones_hoy; ?></span>
                            <span class="mini-label">Hoy</span>
                        </div>
                    </div>
                    
                    <div class="filtros-reporte">
                        <label>Filtrar por fecha:</label>
                        <div class="fecha-filtros">
                            <input type="date" id="fechaInicio" class="input-fecha" value="<?php echo date('Y-m-01'); ?>">
                            <span>hasta</span>
                            <input type="date" id="fechaFin" class="input-fecha" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="reporte-footer">
                    <button onclick="generarReporteOcupaciones('excel')" class="btn-generar excel">
                        📊 Excel
                    </button>
                    <button onclick="generarReporteOcupaciones('pdf')" class="btn-generar pdf">
                        <i class="bi bi-file-earmark-text"></i> PDF
                    </button>
                </div>
            </div>

            <!-- 4. Reporte General -->
            <div class="reporte-card destacado">
                <div class="reporte-header general">
                    <div class="reporte-icono">📈</div>
                    <h3>Reporte General de la Sede</h3>
                </div>
                
                <div class="reporte-body">
                    <p class="reporte-descripcion">
                        Reporte completo con estadísticas, personal, contratos y ocupaciones de esta sede
                    </p>
                    
                    <div class="reporte-incluye">
                        <h4>Incluye:</h4>
                        <ul>
                            <li><i class="bi bi-check-circle-fill"></i> Estadísticas de Instructores</li>
                            <li><i class="bi bi-check-circle-fill"></i> Estadísticas de Celadores</li>
                            <li><i class="bi bi-check-circle-fill"></i> Estado de Contratos</li>
                            <li><i class="bi bi-check-circle-fill"></i> Personal por Vencer</li>
                            <li><i class="bi bi-check-circle-fill"></i> Análisis Completo</li>
                        </ul>
                    </div>
                </div>
                
                <div class="reporte-footer">
                    <a href="../../controllers/ReporteGeneral.php?formato=excel&sede_id=<?php echo $sede_id; ?>" class="btn-generar excel grande">
                        📊 Generar Excel Completo
                    </a>
                    <a href="../../controllers/ReporteGeneral.php?formato=pdf&sede_id=<?php echo $sede_id; ?>" class="btn-generar pdf grande">
                        <i class="bi bi-file-earmark-text"></i> Generar PDF Completo
                    </a>
                </div>
            </div>

        </div>
    </div>

    <script>
        const sedeId = <?php echo $sede_id; ?>;
        
        const btnMenu = document.getElementById("btnMenu");
        const modalMenu = document.getElementById("modalMenu");

        btnMenu.addEventListener("click", () => modalMenu.classList.add("active"));
        modalMenu.addEventListener("click", (e) => {
            if(e.target === modalMenu) modalMenu.classList.remove("active");
        });

        function generarReporteOcupaciones(formato) {
            const inicio = document.getElementById('fechaInicio').value;
            const fin = document.getElementById('fechaFin').value;
            
            if(!inicio || !fin) {
                alert('Por favor selecciona ambas fechas');
                return;
            }
            
            window.location.href = `../../controllers/ReporteOcupaciones.php?formato=${formato}&inicio=${inicio}&fin=${fin}&sede_id=${sedeId}`;
        }
    </script>
</body>
</html>