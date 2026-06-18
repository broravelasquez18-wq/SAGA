<?php
session_start();

// Verificar sesión de admin
if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../../views/home.php");
    exit();
}

require_once "../../config/conexion.php";
require_once "../../config/csrf.php";
$con = conexion();

// VALIDAR SEDE_ID
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

// OBTENER VOCEROS FILTRADOS POR SEDE
$query = "SELECT * FROM usuarios WHERE rol = 'vocero' AND sede_id = $sede_id ORDER BY nombre ASC";
$voceros = mysqli_query($con, $query);

// ESTADÍSTICAS FILTRADAS POR SEDE
$stats_total = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='vocero' AND sede_id = $sede_id"))['total'];
$stats_activos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='vocero' AND estado='activo' AND sede_id = $sede_id"))['total'];
$stats_inactivos = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) total FROM usuarios WHERE rol='vocero' AND estado='inactivo' AND sede_id = $sede_id"))['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/celadores_admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Voceros - <?php echo $sede_nombre; ?></title>
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
            <a href="voceros_admin.php?sede_id=<?php echo $sede_id; ?>" <?php echo $pagina_actual == 'voceros_admin.php' ? 'class="active"' : ''; ?>>
                <i class="bi bi-megaphone-fill"></i>Voceros
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
            <h1>Gestión de Voceros - <?php echo $sede_nombre; ?></h1>
            <p>Administra los voceros de esta sede</p>
        </div>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <button class="btn-nuevo" onclick="abrirModal()">
                <i class="bi bi-plus-lg"></i> Nuevo Vocero
            </button>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if(isset($_GET['msg'])): ?>
        <div class="mensaje-alert mensaje-exito" id="mensajeAlert">
            <?php
            switch($_GET['msg']) {
                case 'deleted':
                    echo '<i class="bi bi-check-circle-fill text-success"></i> Vocero eliminado exitosamente';
                    break;
                case 'vocero_creado':
                    echo '<i class="bi bi-check-circle-fill text-success"></i> Vocero creado exitosamente';
                    break;
                case 'updated':
                    echo '<i class="bi bi-check-circle-fill text-success"></i> Vocero actualizado exitosamente';
                    break;
                case 'activated':
                    echo '<i class="bi bi-check-circle-fill text-success"></i> Vocero activado exitosamente';
                    break;
                case 'deactivated':
                    echo '<i class="bi bi-check-circle-fill text-success"></i> Vocero desactivado exitosamente';
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="mensaje-alert mensaje-error" id="mensajeAlert">
            <?php
            switch($_GET['error']) {
                case 'cedula_exists':
                    echo '<i class="bi bi-x-circle-fill"></i> Ya existe un usuario con esa cédula';
                    break;
                case 'email_duplicado':
                    echo '<i class="bi bi-x-circle-fill"></i> Ya existe un usuario con ese email';
                    break;
                case 'email_invalido':
                    echo '<i class="bi bi-x-circle-fill"></i> El formato del email no es válido';
                    break;
                case 'email_vacio':
                    echo '<i class="bi bi-x-circle-fill"></i> El email es obligatorio';
                    break;
                case 'password_required':
                    echo '<i class="bi bi-x-circle-fill"></i> La contraseña es obligatoria al crear un vocero';
                    break;
                case 'create_failed':
                    echo '<i class="bi bi-x-circle-fill"></i> Error al crear el vocero. Intente nuevamente';
                    break;
                case 'update_failed':
                    echo '<i class="bi bi-x-circle-fill"></i> Error al actualizar el vocero. Intente nuevamente';
                    break;
                default:
                    echo '<i class="bi bi-x-circle-fill"></i> Error en la operación';
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="estadisticas">
        <div class="stat-box">
            <div class="stat-icono"><i class="bi bi-megaphone-fill"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_total; ?></span>
                <span class="stat-label">Total Voceros</span>
            </div>
        </div>

        <div class="stat-box activos">
            <div class="stat-icono">✅</div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_activos; ?></span>
                <span class="stat-label">Activos</span>
            </div>
        </div>

        <div class="stat-box inactivos">
            <div class="stat-icono">⭕</div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_inactivos; ?></span>
                <span class="stat-label">Inactivos</span>
            </div>
        </div>

    </div>

    <!-- Filtros -->
    <div class="filtros">
        <input type="text" id="buscarCelador" placeholder="🔍 Buscar vocero..." onkeyup="filtrar()">

        <select id="filtrarEstado" onchange="filtrar()">
            <option value="">Todos los estados</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>

        <select id="filtrarNivel" onchange="filtrar()">
            <option value="">Todos los niveles</option>
            <option value="tecnico">Técnico</option>
            <option value="tecnologo">Tecnólogo</option>
            <option value="complementario">Complementario</option>
        </select>

        <select id="ordenar" onchange="ordenar()">
            <option value="">Ordenar por...</option>
            <option value="nombre">Nombre (A-Z)</option>
            <option value="cedula">Cédula</option>
            <option value="estado">Estado</option>
        </select>
    </div>

    <!-- Grid de Voceros -->
    <div class="celadores-grid" id="verocerosGrid">
        <?php
        if(mysqli_num_rows($voceros) > 0) {
            while($vocero = mysqli_fetch_assoc($voceros)) {
                $estado_clase = $vocero['estado'];
                $nombre_completo = $vocero['nombre'] . ' ' . $vocero['apellido'];
                $nivel = $vocero['nivel_estudio'] ?? '';
                $nivel_textos = ['tecnico' => 'Técnico', 'tecnologo' => 'Tecnólogo', 'complementario' => 'Complementario'];
                $nivel_texto = $nivel_textos[$nivel] ?? ucfirst($nivel);
        ?>
            <div class="celador-card <?php echo $estado_clase; ?>"
                 data-nombre="<?php echo strtolower($nombre_completo); ?>"
                 data-cedula="<?php echo $vocero['cedula']; ?>"
                 data-nivel="<?php echo $nivel; ?>"
                 data-estado="<?php echo $vocero['estado']; ?>">

                <div class="card-header">
                    <div class="badges-container">
                        <?php if($nivel_texto): ?>
                        <div class="contrato-badge planta">
                            🎓 <?php echo $nivel_texto; ?>
                        </div>
                        <?php endif; ?>
                        <div class="estado-badge <?php echo $estado_clase; ?>">
                            <?php echo $vocero['estado'] == 'activo' ? '<i class="bi bi-check-circle-fill text-success"></i> Activo' : '<i class="bi bi-dash-circle text-secondary"></i> Inactivo'; ?>
                        </div>
                    </div>
                    <div class="card-acciones">
                        <?php if($vocero['estado'] == 'inactivo'): ?>
                            <button class="btn-accion activate" onclick="activar(<?php echo $vocero['id']; ?>)" title="Activar"><i class="bi bi-unlock-fill"></i></button>
                        <?php else: ?>
                            <button class="btn-accion deactivate" onclick="desactivar(<?php echo $vocero['id']; ?>)" title="Desactivar"><i class="bi bi-lock-fill"></i></button>
                        <?php endif; ?>
                        <button class="btn-accion edit" onclick="editar(<?php echo $vocero['id']; ?>)" title="Editar"><i class="bi bi-pencil"></i></button>
                        <button class="btn-accion delete" onclick="eliminar(<?php echo $vocero['id']; ?>, '<?php echo addslashes($nombre_completo); ?>')" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="celador-avatar <?php echo $estado_clase; ?>">
                        <div class="avatar-icono"><i class="bi bi-megaphone-fill"></i></div>
                    </div>
                    <h3 class="celador-nombre"><?php echo $nombre_completo; ?></h3>
                    <div class="celador-cedula">CC: <?php echo number_format($vocero['cedula'], 0, '', '.'); ?></div>

                    <div class="celador-info">
                        <div class="info-item">
                            <span class="info-icono"><i class="bi bi-mortarboard-fill"></i></span>
                            <span class="info-texto"><?php echo $nivel_texto ?: 'Sin nivel asignado'; ?></span>
                        </div>
                        <?php if(!empty($vocero['programa_estudio'])): ?>
                        <div class="info-item">
                            <span class="info-icono"><i class="bi bi-book-fill"></i></span>
                            <span class="info-texto"><?php echo htmlspecialchars($vocero['programa_estudio']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($vocero['fecha_inicio_contrato']) && !empty($vocero['fecha_fin_contrato'])): ?>
                        <div class="info-item">
                            <span class="info-icono"><i class="bi bi-calendar-range"></i></span>
                            <span class="info-texto">
                                <?php echo date('d/m/Y', strtotime($vocero['fecha_inicio_contrato'])); ?> —
                                <?php echo date('d/m/Y', strtotime($vocero['fecha_fin_contrato'])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php
            }
        } else {
        ?>
            <div class="estado-vacio">
                <div class="vacio-icono"><i class="bi bi-megaphone-fill"></i></div>
                <h3 class="vacio-titulo">No hay voceros en esta sede</h3>
                <p class="vacio-texto">Comienza agregando el primer vocero</p>
                <button class="btn-nuevo" onclick="abrirModal()"><i class="bi bi-plus-lg"></i> Crear Primer Vocero</button>
            </div>
        <?php } ?>
    </div>

    <!-- Modal -->
    <div class="modal" id="modalVocero">
        <div class="modal-contenido">
            <span class="modal-cerrar" onclick="cerrarModal()"><i class="bi bi-x-lg"></i></span>

            <h2 id="modalTitulo">Nuevo Vocero</h2>

            <form action="../../controllers/CrearVocero.php" method="POST" id="formVocero">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="accion" id="accion" value="crear">
                <input type="hidden" name="vocero_id" id="voceroId">
                <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">

                <!-- Información Personal -->
                <div class="form-seccion">
                    <h3 class="form-seccion-titulo"><i class="bi bi-clipboard"></i> Información Personal</h3>

                    <div class="form-grupo">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" id="voceroNombre" placeholder="Ej: Pedro" required>
                    </div>

                    <div class="form-grupo">
                        <label>Apellido *</label>
                        <input type="text" name="apellido" id="voceroApellido" placeholder="Ej: Ramírez Silva" required>
                    </div>

                    <div class="form-grupo">
                        <label>Cédula *</label>
                        <input type="number" name="cedula" id="voceroCedula" placeholder="Ej: 1234567890" required>
                    </div>

                    <div class="form-grupo">
                        <label>Email *</label>
                        <input type="email" name="email" id="voceroEmail" placeholder="ejemplo@correo.com" required>
                    </div>

                    <div class="form-grupo">
                        <label>Teléfono / Celular</label>
                        <input type="tel" name="telefono" id="voceroTelefono" placeholder="Ej: 3001234567">
                        <small class="form-ayuda">Sin prefijo +57. Requerido para notificaciones.</small>
                    </div>

                    <div class="form-grupo">
                        <label>Contraseña *</label>
                        <input type="password" name="contrasena" id="voceroContrasena" placeholder="Mínimo 6 caracteres" minlength="6" required>
                        <small class="form-ayuda" id="contrasenaAyuda" style="display:none">Deja vacío para mantener la contraseña actual</small>
                    </div>
                </div>

                <!-- Nivel de Estudio -->
                <div class="form-seccion">
                    <h3 class="form-seccion-titulo"><i class="bi bi-mortarboard-fill"></i> Nivel de Estudio</h3>

                    <div class="form-grupo">
                        <label>Nivel *</label>
                        <select name="nivel_estudio" id="voceroNivel" required onchange="cambiarNivel()">
                            <option value="">Seleccione...</option>
                            <option value="tecnico">Técnico</option>
                            <option value="tecnologo">Tecnólogo</option>
                            <option value="complementario">Complementario</option>
                        </select>
                    </div>

                    <div class="form-grupo">
                        <label>Programa de Estudio *</label>
                        <input type="text" name="programa_estudio" id="voceroPrograma" placeholder="Ej: Análisis y Desarrollo de Software" required>
                    </div>

                    <div id="fechasNivel" style="display:none;">
                        <div class="form-grupo">
                            <label>Fecha de Inicio *</label>
                            <input type="date" name="fecha_inicio_contrato" id="voceroFechaInicio">
                        </div>
                        <div class="form-grupo">
                            <label>Fecha de Fin *</label>
                            <input type="date" name="fecha_fin_contrato" id="voceroFechaFin">
                            <small class="form-ayuda" id="ayudaFechaFin" style="display:none"></small>
                        </div>
                    </div>
                </div>

                <div class="form-botones">
                    <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-guardar">
                        <span id="btnTexto"><i class="bi bi-floppy"></i> Guardar Vocero</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const sedeId = <?php echo $sede_id; ?>;
    </script>
    <script src="../../assets/js/voceros_admin.js"></script>
</body>
</html>
