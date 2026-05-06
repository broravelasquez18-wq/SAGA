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

// Verificar si existe la tabla
$check_table = mysqli_query($con, "SHOW TABLES LIKE 'historial_ocupacion'");
$tabla_existe = mysqli_num_rows($check_table) > 0;

if(!$tabla_existe) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../../assets/css/historial_admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <title>SAGA - Historial</title>
    </head>
    <body>
        <div class="alerta-instalacion">
            <div class="alerta-icono">⚠️</div>
            <div class="alerta-contenido">
                <h3>Tabla de Historial no Creada</h3>
                <p>Para usar el módulo de historial, ejecuta el archivo SQL en phpMyAdmin</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// FILTROS
$filtro_ambiente = $_GET['ambiente'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';
$filtro_jornada = $_GET['jornada'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// ⭐ QUERY PRINCIPAL - FILTRADO POR SEDE
$query = "SELECT ho.*, 
          a.nombre AS ambiente_nombre, 
          p.nombre AS piso_nombre,
          u.nombre AS usuario_nombre, 
          u.apellido AS usuario_apellido,
          u.cedula AS usuario_cedula
          FROM historial_ocupacion ho
          LEFT JOIN ambientes a ON ho.ambiente_id = a.id
          LEFT JOIN pisos p ON a.piso_id = p.id
          LEFT JOIN usuarios u ON ho.usuario_id = u.id
          WHERE p.sede_id = $sede_id";

if($filtro_ambiente) {
    $query .= " AND ho.ambiente_id = " . intval($filtro_ambiente);
}
if($filtro_estado) {
    $query .= " AND ho.estado = '" . mysqli_real_escape_string($con, $filtro_estado) . "'";
}
if($filtro_fecha) {
    $query .= " AND DATE(ho.fecha_inicio) = '" . mysqli_real_escape_string($con, $filtro_fecha) . "'";
}
if($filtro_jornada) {
    $query .= " AND ho.jornada = '" . mysqli_real_escape_string($con, $filtro_jornada) . "'";
}
if($busqueda) {
    $busqueda_clean = mysqli_real_escape_string($con, $busqueda);
    $query .= " AND (a.nombre LIKE '%$busqueda_clean%' 
                OR u.nombre LIKE '%$busqueda_clean%' 
                OR u.apellido LIKE '%$busqueda_clean%'
                OR u.cedula LIKE '%$busqueda_clean%'
                OR ho.observaciones LIKE '%$busqueda_clean%')";
}

$query .= " ORDER BY ho.fecha_inicio DESC, ho.id DESC LIMIT 100";

$result = mysqli_query($con, $query);

// ⭐ ESTADÍSTICAS FILTRADAS POR SEDE
$total_ocupaciones = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE p.sede_id = $sede_id"))['total'];
$hoy = date('Y-m-d');
$ocupaciones_hoy = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE DATE(ho.fecha_inicio) = '$hoy' AND p.sede_id = $sede_id"))['total'];
$activas = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE ho.estado IN ('ocupado', 'proximo_a_desocupar') AND p.sede_id = $sede_id"))['total'];
$ambientes_usados = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(DISTINCT ho.ambiente_id) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE p.sede_id = $sede_id"))['total'];

// ⭐ LISTA DE AMBIENTES DE ESTA SEDE
$ambientes_query = "SELECT DISTINCT a.id, a.nombre, p.nombre AS piso_nombre 
                    FROM ambientes a 
                    LEFT JOIN pisos p ON a.piso_id = p.id 
                    WHERE p.sede_id = $sede_id
                    ORDER BY p.nombre, a.nombre";
$ambientes_result = mysqli_query($con, $ambientes_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/historial_admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Historial - <?php echo $sede_nombre; ?></title>
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
            <img class="logo-saga" src="../../assets/img/ChatGPT_Image_6_mar_2026__12_14_37_p.m.-removebg-preview.png" alt="logo">
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
            <h1>Historial - <?php echo $sede_nombre; ?></h1>
            <p>Registro completo de ocupaciones de esta sede</p>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="estadisticas">
        <div class="stat-box">
            <div class="stat-icono">📋</div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $total_ocupaciones; ?></span>
                <span class="stat-label">Total Ocupaciones</span>
            </div>
        </div>

        <div class="stat-box activos">
            <div class="stat-icono"><i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $activas; ?></span>
                <span class="stat-label">Activas Ahora</span>
            </div>
        </div>

        <div class="stat-box modulos">
            <div class="stat-icono"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $ocupaciones_hoy; ?></span>
                <span class="stat-label">Hoy</span>
            </div>
        </div>

        <div class="stat-box tiempo">
            <div class="stat-icono">🚪</div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $ambientes_usados; ?></span>
                <span class="stat-label">Ambientes Usados</span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros">
        <form method="GET" action="" id="filtrosForm">
            <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
            <input type="text" name="busqueda" placeholder="🔍 Buscar instructor, ambiente, cédula..." value="<?php echo htmlspecialchars($busqueda); ?>">
            
            <select name="ambiente" onchange="document.getElementById('filtrosForm').submit()">
                <option value="">Todos los ambientes</option>
                <?php 
                if($ambientes_result):
                    mysqli_data_seek($ambientes_result, 0);
                    while($amb = mysqli_fetch_assoc($ambientes_result)): 
                ?>
                    <option value="<?php echo $amb['id']; ?>" <?php echo $filtro_ambiente == $amb['id'] ? 'selected' : ''; ?>>
                        <?php echo $amb['piso_nombre'] . ' - ' . $amb['nombre']; ?>
                    </option>
                <?php 
                    endwhile;
                endif;
                ?>
            </select>

            <select name="estado" onchange="document.getElementById('filtrosForm').submit()">
                <option value="">Todos los estados</option>
                <option value="ocupado" <?php echo $filtro_estado == 'ocupado' ? 'selected' : ''; ?>><i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i> Ocupado</option>
                <option value="proximo_a_desocupar" <?php echo $filtro_estado == 'proximo_a_desocupar' ? 'selected' : ''; ?>><i class="bi bi-circle-fill text-warning" style="font-size:.7rem"></i> Próximo</option>
                <option value="disponible" <?php echo $filtro_estado == 'disponible' ? 'selected' : ''; ?>><i class="bi bi-circle-fill text-success" style="font-size:.7rem"></i> Disponible</option>
                <option value="finalizado" <?php echo $filtro_estado == 'finalizado' ? 'selected' : ''; ?>><i class="bi bi-check-circle-fill"></i> Finalizado</option>
            </select>

            <select name="jornada" onchange="document.getElementById('filtrosForm').submit()">
                <option value="">Todas las jornadas</option>
                <option value="mañana" <?php echo $filtro_jornada == 'mañana' ? 'selected' : ''; ?>>🌅 Mañana</option>
                <option value="tarde" <?php echo $filtro_jornada == 'tarde' ? 'selected' : ''; ?>>☀️ Tarde</option>
                <option value="noche" <?php echo $filtro_jornada == 'noche' ? 'selected' : ''; ?>>🌙 Noche</option>
            </select>

            <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>" onchange="document.getElementById('filtrosForm').submit()">

            <?php if($filtro_ambiente || $filtro_estado || $filtro_fecha || $filtro_jornada || $busqueda): ?>
                <a href="historial_admin.php?sede_id=<?php echo $sede_id; ?>" class="btn-limpiar">✕ Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Timeline -->
    <div class="historial-contenedor">
        <div class="timeline">
            <?php 
            if($result && mysqli_num_rows($result) > 0):
                $fecha_anterior = '';
                while($registro = mysqli_fetch_assoc($result)):
                    $fecha_actual = date('Y-m-d', strtotime($registro['fecha_inicio']));
                    
                    if($fecha_actual != $fecha_anterior):
                        $fecha_anterior = $fecha_actual;
                        $hoy = date('Y-m-d');
                        $ayer = date('Y-m-d', strtotime('-1 day'));
                        
                        if($fecha_actual == $hoy) {
                            $texto_fecha = '<i class="bi bi-calendar-event"></i> Hoy - ' . date('d/m/Y', strtotime($fecha_actual));
                        } elseif($fecha_actual == $ayer) {
                            $texto_fecha = '<i class="bi bi-calendar-event"></i> Ayer - ' . date('d/m/Y', strtotime($fecha_actual));
                        } else {
                            $texto_fecha = '<i class="bi bi-calendar-event"></i> ' . date('d/m/Y', strtotime($fecha_actual));
                        }
            ?>
                <div class="fecha-separador">
                    <span><?php echo $texto_fecha; ?></span>
                </div>
            <?php 
                    endif;
                    
                    $clase_estado = '';
                    $icono_estado = '';
                    $texto_estado = '';
                    
                    switch($registro['estado']) {
                        case 'ocupado':
                            $clase_estado = 'activo';
                            $icono_estado = '<i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i>';
                            $texto_estado = 'OCUPADO';
                            break;
                        case 'proximo_a_desocupar':
                            $clase_estado = 'proximo';
                            $icono_estado = '<i class="bi bi-circle-fill text-warning" style="font-size:.7rem"></i>';
                            $texto_estado = 'PRÓXIMO A DESOCUPAR';
                            break;
                        case 'disponible':
                            $clase_estado = 'disponible';
                            $icono_estado = '<i class="bi bi-circle-fill text-success" style="font-size:.7rem"></i>';
                            $texto_estado = 'DISPONIBLE';
                            break;
                        case 'finalizado':
                            $clase_estado = 'finalizado';
                            $icono_estado = '✅';
                            $texto_estado = 'FINALIZADO';
                            break;
                    }
                    
                    $icono_jornada = '<i class="bi bi-calendar-event"></i>';
                    if($registro['jornada'] == 'mañana') $icono_jornada = '🌅';
                    if($registro['jornada'] == 'tarde') $icono_jornada = '☀️';
                    if($registro['jornada'] == 'noche') $icono_jornada = '🌙';
                    
                    $usuario_nombre = $registro['usuario_nombre'] . ' ' . $registro['usuario_apellido'];
                    $ambiente_completo = ($registro['piso_nombre'] ?? 'Sin piso') . ' - ' . ($registro['ambiente_nombre'] ?? 'Sin nombre');
            ?>
                <div class="timeline-item <?php echo $clase_estado; ?>">
                    <div class="timeline-marker">
                        <div class="timeline-icono"><?php echo $icono_estado; ?></div>
                    </div>
                    <div class="timeline-contenido">
                        <div class="timeline-header">
                            <div class="timeline-info">
                                <span class="timeline-accion"><?php echo $texto_estado; ?></span>
                                <span class="timeline-modulo"><?php echo $ambiente_completo; ?></span>
                            </div>
                            <span class="timeline-hora"><?php echo $icono_jornada; ?> <?php echo ucfirst($registro['jornada']); ?></span>
                        </div>
                        <div class="timeline-descripcion">
                            <strong>👤 Instructor:</strong> <?php echo $usuario_nombre; ?><br>
                            <strong>🆔 Cédula:</strong> <?php echo $registro['usuario_cedula']; ?><br>
                            <strong>🕐 Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($registro['fecha_inicio'])); ?><br>
                            <strong>🕐 Fin:</strong> <?php echo date('d/m/Y H:i', strtotime($registro['fecha_fin'])); ?>
                            <?php if($registro['observaciones']): ?>
                                <br><strong>📝 Observaciones:</strong> <?php echo htmlspecialchars($registro['observaciones']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-footer">
                            <span class="timeline-usuario">👤 <?php echo $usuario_nombre; ?></span>
                            <span class="timeline-ip">🚪 <?php echo $ambiente_completo; ?></span>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="estado-vacio">
                    <div class="vacio-icono">🕘</div>
                    <h3 class="vacio-titulo">No hay registros en el historial</h3>
                    <p class="vacio-texto">Los registros de ocupaciones de esta sede aparecerán aquí</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const sedeId = <?php echo $sede_id; ?>;
    </script>
    <script src="../../assets/js/historial_admin.js"></script>
</body>
</html>