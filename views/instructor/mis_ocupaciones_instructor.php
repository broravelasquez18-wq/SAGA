<?php
session_start();

// Verificar sesión de instructor
if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'instructor') {
    header("Location: ../../views/home.php");
    exit();
}

require_once "../../config/conexion.php";
$con = conexion();

$instructor_id = $_SESSION['id'];
$instructor_nombre = $_SESSION['nombre'];

// Obtener filtro de estado
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';

// Query base
$query_base = "SELECT 
    h.id,
    h.fecha_inicio,
    h.fecha_fin,
    h.jornada,
    h.observaciones,
    h.estado,
    a.nombre as ambiente_nombre,
    p.nombre as piso_nombre,
    s.nombre as sede_nombre
FROM historial_ocupacion h
INNER JOIN ambientes a ON h.ambiente_id = a.id
INNER JOIN pisos p ON a.piso_id = p.id
INNER JOIN sedes s ON p.sede_id = s.id
WHERE h.usuario_id = $instructor_id";

// Aplicar filtro
switch($filtro) {
    case 'activos':
        $query_base .= " AND h.estado = 'ocupado' AND h.fecha_inicio <= NOW() AND h.fecha_fin >= NOW()";
        break;
    case 'proximos':
        $query_base .= " AND h.estado = 'ocupado' AND h.fecha_inicio > NOW()";
        break;
    case 'finalizados':
        $query_base .= " AND h.estado = 'finalizado'";
        break;
    case 'cancelados':
        $query_base .= " AND h.estado = 'cancelado'";
        break;
}

$query_base .= " ORDER BY h.fecha_inicio DESC";

$ocupaciones = mysqli_query($con, $query_base);
$total_registros = mysqli_num_rows($ocupaciones);

// Estadísticas
$stats_total = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion WHERE usuario_id = $instructor_id"
))['total'];

$stats_activos = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion 
     WHERE usuario_id = $instructor_id 
     AND estado = 'ocupado' 
     AND fecha_inicio <= NOW() 
     AND fecha_fin >= NOW()"
))['total'];

$stats_proximos = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion 
     WHERE usuario_id = $instructor_id 
     AND estado = 'ocupado' 
     AND fecha_inicio > NOW()"
))['total'];

$stats_finalizados = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion 
     WHERE usuario_id = $instructor_id 
     AND estado = 'finalizado'"
))['total'];

$stats_cancelados = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion 
     WHERE usuario_id = $instructor_id 
     AND estado = 'cancelado'"
))['total'];

// Mensajes
$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/dashboard_instructor.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Mis Ocupaciones - SAGA</title>
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
                <span class="usuario-nombre"><?php echo $instructor_nombre; ?></span>
                <span class="usuario-tipo">Instructor</span>
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
            <a href="dashboard_instructor.php" <?php echo $pagina_actual == 'dashboard_instructor.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-bar-chart-fill"></i>Dashboard
            </a>
            <a href="calendario_instructor.php" <?php echo $pagina_actual == 'calendario_instructor.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-calendar3"></i>Calendario
            </a>
            <a href="mis_ocupaciones_instructor.php" <?php echo $pagina_actual == 'mis_ocupaciones_instructor.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-clock-history"></i>Mis Ocupaciones
            </a>
        </div>
    </div>

    <!-- Encabezado -->
    <div class="encabezado">
        <div>
            <h1><i class="bi bi-clipboard"></i> Mis Ocupaciones</h1>
            <p>Historial completo de todas tus reservas de ambientes</p>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if($msg == 'cancelado'): ?>
        <div class="mensaje-alert mensaje-exito" id="mensajeAlert">
            <i class="bi bi-check-circle-fill"></i> Ocupación cancelada exitosamente
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="estadisticas">
        <div class="stat-card">
            <div class="stat-icono">📊</div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_total; ?></span>
                <span class="stat-label">Total Registros</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icono"><i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_activos; ?></span>
                <span class="stat-label">Activos Ahora</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icono"><i class="bi bi-circle-fill text-warning" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_proximos; ?></span>
                <span class="stat-label">Próximos</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icono"><i class="bi bi-circle-fill text-success" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_finalizados; ?></span>
                <span class="stat-label">Finalizados</span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-ocupaciones">
        <a href="mis_ocupaciones_instructor.php?filtro=todos" 
           class="filtro-btn <?php echo $filtro == 'todos' ? 'active' : ''; ?>">
            📊 Todos (<?php echo $stats_total; ?>)
        </a>
        <a href="mis_ocupaciones_instructor.php?filtro=activos" 
           class="filtro-btn <?php echo $filtro == 'activos' ? 'active' : ''; ?>">
            <i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i> Activos (<?php echo $stats_activos; ?>)
        </a>
        <a href="mis_ocupaciones_instructor.php?filtro=proximos" 
           class="filtro-btn <?php echo $filtro == 'proximos' ? 'active' : ''; ?>">
            <i class="bi bi-circle-fill text-warning" style="font-size:.7rem"></i> Próximos (<?php echo $stats_proximos; ?>)
        </a>
        <a href="mis_ocupaciones_instructor.php?filtro=finalizados" 
           class="filtro-btn <?php echo $filtro == 'finalizados' ? 'active' : ''; ?>">
            <i class="bi bi-circle-fill text-success" style="font-size:.7rem"></i> Finalizados (<?php echo $stats_finalizados; ?>)
        </a>
        <a href="mis_ocupaciones_instructor.php?filtro=cancelados" 
           class="filtro-btn <?php echo $filtro == 'cancelados' ? 'active' : ''; ?>">
            ⚫ Cancelados (<?php echo $stats_cancelados; ?>)
        </a>
    </div>

    <!-- Lista de Ocupaciones -->
    <div class="ocupaciones-lista-container">
        <div class="total-registros">
            Mostrando <?php echo $total_registros; ?> registro(s)
        </div>

        <?php if($total_registros > 0): ?>
            <div class="ocupaciones-lista">
                <?php 
                mysqli_data_seek($ocupaciones, 0);
                while($ocu = mysqli_fetch_assoc($ocupaciones)): 
                    // Determinar estado
                    $ahora = new DateTime();
                    $fecha_inicio = new DateTime($ocu['fecha_inicio']);
                    $fecha_fin = new DateTime($ocu['fecha_fin']);
                    
                    if($ocu['estado'] == 'cancelado') {
                        $estado_clase = 'cancelado';
                        $estado_texto = 'Cancelado';
                        $estado_badge = 'cancelado';
                    } elseif($ocu['estado'] == 'finalizado') {
                        $estado_clase = 'finalizado';
                        $estado_texto = 'Finalizado';
                        $estado_badge = 'finalizado';
                    } elseif($ahora >= $fecha_inicio && $ahora <= $fecha_fin) {
                        $estado_clase = 'activo';
                        $estado_texto = 'En Curso';
                        $estado_badge = 'activo';
                    } else {
                        $estado_clase = 'proximo';
                        $estado_texto = 'Próximo';
                        $estado_badge = 'proximo';
                    }

                    // Formatear fechas
                    $dia = $fecha_inicio->format('d');
                    $mes = ucfirst($fecha_inicio->format('M'));
                    $ano = $fecha_inicio->format('Y');
                    $hora_inicio = $fecha_inicio->format('H:i');
                    $hora_fin = $fecha_fin->format('H:i');
                ?>
                    <div class="ocupacion-item <?php echo $estado_clase; ?>">
                        <div class="ocupacion-fecha-box">
                            <div class="fecha-dia"><?php echo $dia; ?></div>
                            <div class="fecha-mes"><?php echo $mes; ?></div>
                            <div class="fecha-ano"><?php echo $ano; ?></div>
                        </div>

                        <div class="ocupacion-detalles">
                            <h3><?php echo $ocu['ambiente_nombre']; ?></h3>
                            <p class="ocupacion-ubicacion">
                                📍 <?php echo $ocu['sede_nombre']; ?> - <?php echo $ocu['piso_nombre']; ?>
                            </p>
                            <p class="ocupacion-horario">
                                🕐 <?php echo $hora_inicio; ?> - <?php echo $hora_fin; ?> 
                                (<?php echo ucfirst($ocu['jornada']); ?>)
                            </p>
                            <?php if(!empty($ocu['observaciones'])): ?>
                                <p class="ocupacion-obs">
                                    📝 <?php echo $ocu['observaciones']; ?>
                                </p>
                            <?php endif; ?>
                            <span class="ocupacion-estado-badge <?php echo $estado_badge; ?>">
                                <?php echo $estado_texto; ?>
                            </span>
                        </div>

                        <div class="ocupacion-acciones-lista">
                            <?php if($ocu['estado'] == 'ocupado' && $ahora < $fecha_inicio): ?>
                                <a href="../../controllers/CancelarOcupacion.php?id=<?php echo $ocu['id']; ?>&origen=mis_ocupaciones" 
                                   class="btn-cancelar-small"
                                   onclick="return confirm('¿Cancelar esta ocupación?')">
                                    <i class="bi bi-x-circle-fill"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="estado-vacio">
                <div class="vacio-icono">📋</div>
                <h3>No hay ocupaciones registradas</h3>
                <p>
                    <?php 
                    if($filtro == 'activos') echo 'No tienes ocupaciones activas en este momento';
                    elseif($filtro == 'proximos') echo 'No tienes ocupaciones próximas programadas';
                    elseif($filtro == 'finalizados') echo 'Aún no tienes ocupaciones finalizadas';
                    elseif($filtro == 'cancelados') echo 'No tienes ocupaciones canceladas';
                    else echo 'Registra tu primera ocupación en el calendario';
                    ?>
                </p>
                <a href="calendario_instructor.php" class="btn-agendar">
                    <i class="bi bi-calendar-event"></i> Ir al Calendario
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const btnMenu = document.getElementById('btnMenu');
        const modalMenu = document.getElementById('modalMenu');

        btnMenu.addEventListener('click', () => modalMenu.classList.add('active'));
        modalMenu.addEventListener('click', (e) => {
            if(e.target === modalMenu) modalMenu.classList.remove('active');
        });

        // Ocultar mensaje después de 5 segundos
        setTimeout(() => {
            const alert = document.getElementById('mensajeAlert');
            if(alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 300);
            }
        }, 5000);
    </script>
</body>
</html>