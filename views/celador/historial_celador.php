<?php
session_start();

// Verificar sesión de celador
if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'celador') {
    header("Location: ../../views/home.php");
    exit();
}

require_once "../../config/conexion.php";
$con = conexion();

// ⭐ OBTENER SEDE DEL CELADOR
$celador_sede_id = $_SESSION['sede_id'];

// Obtener información de la sede
$sede_query = mysqli_query($con, "SELECT nombre, ciudad FROM sedes WHERE id = $celador_sede_id");

if(mysqli_num_rows($sede_query) > 0) {
    $sede_info = mysqli_fetch_assoc($sede_query);
    $sede_nombre = $sede_info['nombre'];
    $sede_ciudad = $sede_info['ciudad'];
} else {
    header("Location: ../../controllers/logout.php");
    exit();
}

$busqueda = $_GET['busqueda'] ?? '';
$filtro_fecha = $_GET['fecha'] ?? '';

// ⭐ QUERY FILTRADA POR SEDE
$query = "SELECT ho.*, a.nombre AS ambiente_nombre, p.nombre AS piso_nombre, CONCAT(u.nombre, ' ', u.apellido) AS instructor_nombre, u.cedula AS usuario_cedula
          FROM historial_ocupacion ho
          LEFT JOIN ambientes a ON ho.ambiente_id = a.id
          LEFT JOIN pisos p ON a.piso_id = p.id
          LEFT JOIN usuarios u ON ho.usuario_id = u.id 
          WHERE p.sede_id = $celador_sede_id";

if($busqueda) {
    $b = mysqli_real_escape_string($con, $busqueda);
    $query .= " AND (a.nombre LIKE '%$b%' OR u.nombre LIKE '%$b%' OR u.apellido LIKE '%$b%' OR u.cedula LIKE '%$b%' OR ho.observaciones LIKE '%$b%')";
}
if($filtro_fecha) {
    $query .= " AND DATE(ho.fecha_inicio) = '" . mysqli_real_escape_string($con, $filtro_fecha) . "'";
}
$query .= " ORDER BY ho.fecha_inicio DESC, ho.id DESC LIMIT 100";
$result = mysqli_query($con, $query);

// ⭐ ESTADÍSTICAS FILTRADAS POR SEDE
$total = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE p.sede_id = $celador_sede_id"))['total'];

$hoy = date('Y-m-d');
$ocupaciones_hoy = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE DATE(ho.fecha_inicio) = '$hoy' AND p.sede_id = $celador_sede_id"))['total'];

$activas = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE ho.estado = 'ocupado' AND p.sede_id = $celador_sede_id"))['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/historial_admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>SAGA - Historial Celador</title>
</head>
<body>
    <div class="header">
        <div class="logo">
            <button class="btn-menu" id="btnMenu"><i class="bi bi-list"></i></button>
            <img class="logo-saga" src="../../assets/img/ChatGPT_Image_6_mar_2026__12_14_37_p.m.-removebg-preview.png" alt="logo">
            <h2>SAGA</h2>
        </div>
        
        <!-- ⭐ INDICADOR DE SEDE -->
        <div class="sede-actual">
            <div class="sede-info-header">
                <span class="sede-nombre-header"><i class="bi bi-building"></i> <?php echo $sede_nombre; ?></span>
            </div>
        </div>
        
        <a class="btn-logout" href="../../controllers/logout.php">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>

    <div class="modal-menu" id="modalMenu">
        <div class="sidebar">
            <h3 class="menu-titulo">MENÚ CELADOR</h3>
            <a href="dashboard_celador.php"><i class="bi bi-bar-chart-fill"></i>Dashboard</a>
            <a href="ocupaciones_celador.php"><i class="bi bi-calendar-check"></i>Ocupaciones</a>
            <a href="calendario_celador.php"><i class="bi bi-calendar3"></i>Calendario</a>
            <a href="historial_celador.php" class="active"><i class="bi bi-clock-history"></i>Historial</a>
        </div>
    </div>

    <div class="encabezado">
        <div class="encabezado-contenido">
            <h1>🕘 Historial - <?php echo $sede_nombre; ?></h1>
            <p>Registro completo de ocupaciones de esta sede</p>
        </div>
    </div>

    <div class="estadisticas">
        <div class="stat-box"><div class="stat-icono">📋</div><div class="stat-info"><span class="stat-numero"><?php echo $total; ?></span><span class="stat-label">Total Ocupaciones</span></div></div>
        <div class="stat-box activos"><div class="stat-icono"><i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i></div><div class="stat-info"><span class="stat-numero"><?php echo $activas; ?></span><span class="stat-label">Activas Ahora</span></div></div>
        <div class="stat-box modulos"><div class="stat-icono"><i class="bi bi-calendar-event"></i></div><div class="stat-info"><span class="stat-numero"><?php echo $ocupaciones_hoy; ?></span><span class="stat-label">Hoy</span></div></div>
    </div>

    <div class="filtros">
        <form method="GET" action="" id="filtrosForm">
            <input type="text" name="busqueda" placeholder="🔍 Buscar instructor, ambiente, cédula..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($filtro_fecha); ?>" onchange="document.getElementById('filtrosForm').submit()">
            <?php if($busqueda || $filtro_fecha): ?>
                <a href="historial_celador.php" class="btn-limpiar">✕ Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="historial-contenedor">
        <div class="timeline">
            <?php if($result && mysqli_num_rows($result) > 0):
                $fecha_anterior = '';
                while($reg = mysqli_fetch_assoc($result)):
                    $fecha_actual = date('Y-m-d', strtotime($reg['fecha_inicio']));
                    if($fecha_actual != $fecha_anterior):
                        $fecha_anterior = $fecha_actual;
                        $hoy_fecha = date('Y-m-d');
                        $ayer = date('Y-m-d', strtotime('-1 day'));
                        $texto_fecha = ($fecha_actual == $hoy_fecha) ? '<i class="bi bi-calendar-event"></i> Hoy - ' . date('d/m/Y', strtotime($fecha_actual)) : (($fecha_actual == $ayer) ? '<i class="bi bi-calendar-event"></i> Ayer - ' . date('d/m/Y', strtotime($fecha_actual)) : '<i class="bi bi-calendar-event"></i> ' . date('d/m/Y', strtotime($fecha_actual)));
            ?>
                <div class="fecha-separador"><span><?php echo $texto_fecha; ?></span></div>
            <?php endif;
                    $clase = $reg['estado'] == 'ocupado' ? 'activo' : 'finalizado';
                    $icono = $reg['estado'] == 'ocupado' ? '<i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i>' : '✅';
                    $texto = $reg['estado'] == 'ocupado' ? 'ACTIVO' : 'FINALIZADO';
                    $icono_jornada = ($reg['jornada'] == 'mañana') ? '🌅' : (($reg['jornada'] == 'tarde') ? '☀️' : '🌙');
            ?>
                <div class="timeline-item <?php echo $clase; ?>">
                    <div class="timeline-marker"><div class="timeline-icono"><?php echo $icono; ?></div></div>
                    <div class="timeline-contenido">
                        <div class="timeline-header">
                            <div class="timeline-info">
                                <span class="timeline-accion"><?php echo $texto; ?></span>
                                <span class="timeline-modulo"><?php echo ($reg['piso_nombre'] ?? 'Sin piso') . ' - ' . ($reg['ambiente_nombre'] ?? 'Sin nombre'); ?></span>
                            </div>
                            <span class="timeline-hora"><?php echo $icono_jornada; ?> <?php echo ucfirst($reg['jornada']); ?></span>
                        </div>
                        <div class="timeline-descripcion">
                            <strong>👤 Instructor:</strong> <?php echo $reg['instructor_nombre']; ?><br>
                            <strong>🆔 Cédula:</strong> <?php echo $reg['usuario_cedula']; ?><br>
                            <strong>🕐 Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($reg['fecha_inicio'])); ?><br>
                            <strong>🕐 Fin:</strong> <?php echo date('d/m/Y H:i', strtotime($reg['fecha_fin'])); ?>
                            <?php if($reg['observaciones']): ?><br><strong>📝 Observaciones:</strong> <?php echo htmlspecialchars($reg['observaciones']); ?><?php endif; ?>
                        </div>
                        <div class="timeline-footer">
                            <span class="timeline-usuario">👤 <?php echo $reg['instructor_nombre']; ?></span>
                            <span class="timeline-ip">🚪 <?php echo ($reg['piso_nombre'] ?? 'Sin piso') . ' - ' . ($reg['ambiente_nombre'] ?? 'Sin nombre'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div class="estado-vacio">
                    <div class="vacio-icono">🕘</div>
                    <h3 class="vacio-titulo">No hay registros en el historial</h3>
                    <p class="vacio-texto">Los registros de ocupaciones de esta sede aparecerán aquí</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const btnMenu = document.getElementById("btnMenu");
        const modalMenu = document.getElementById("modalMenu");

        if(btnMenu && modalMenu) {
            btnMenu.addEventListener("click", () => {
                modalMenu.classList.add("active");
            });

            modalMenu.addEventListener("click", (e) => {
                if(e.target === modalMenu) {
                    modalMenu.classList.remove("active");
                }
            });
        }
    </script>
</body>
</html>