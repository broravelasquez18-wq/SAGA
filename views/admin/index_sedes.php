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

// Capturar mensajes
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Obtener todas las sedes con sus estadísticas
$query_sedes = "SELECT s.*, 
                COUNT(DISTINCT p.id) as total_pisos,
                COUNT(DISTINCT a.id) as total_ambientes,
                COUNT(DISTINCT CASE WHEN u.rol = 'instructor' THEN u.id END) as total_instructores,
                COUNT(DISTINCT CASE WHEN u.rol = 'celador' THEN u.id END) as total_celadores,
                COUNT(DISTINCT CASE WHEN ho.estado IN ('ocupado', 'proximo_a_desocupar') AND DATE(ho.fecha_inicio) = CURDATE() THEN ho.id END) as ocupaciones_activas
                FROM sedes s
                LEFT JOIN pisos p ON s.id = p.sede_id
                LEFT JOIN ambientes a ON p.id = a.piso_id
                LEFT JOIN usuarios u ON s.id = u.sede_id
                LEFT JOIN historial_ocupacion ho ON a.id = ho.ambiente_id
                GROUP BY s.id
                ORDER BY s.estado DESC, s.nombre ASC";

$result_sedes = mysqli_query($con, $query_sedes);
$nombre_admin = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/index_sedes_admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>SAGA - Gestión de Sedes</title>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img class="logo-saga" src="../../assets/img/ChatGPT_Image_6_mar_2026__12_14_37_p.m.-removebg-preview.png" alt="logo">
            <h2>SAGA</h2>
        </div>
        <div class="header-info">
            <div class="usuario-info">
                <span class="usuario-nombre"><?php echo $nombre_admin; ?></span>
                <span class="usuario-tipo">Administrador</span>
            </div>
            <a class="btn-logout" href="../../controllers/logout.php">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="contenedor-principal">
        <div class="encabezado">
            <div class="bienvenida">
                <h1><i class="bi bi-building"></i> Gestión de Sedes</h1>
                <p>Administra las sedes del sistema SAGA</p>
            </div>
            <button class="btn-nueva-sede" id="btnNuevaSede">
                <i class="bi bi-plus-lg"></i> Nueva Sede
            </button>
        </div>

        <!-- Mensajes -->
        <?php if($msg): ?>
            <div class="mensaje-exito">
                <?php
                if($msg == 'sede_creada') echo '<i class="bi bi-check-circle-fill"></i> Sede creada exitosamente';
                if($msg == 'sede_actualizada') echo '<i class="bi bi-check-circle-fill"></i> Sede actualizada exitosamente';
                if($msg == 'sede_eliminada') echo '<i class="bi bi-check-circle-fill"></i> Sede eliminada exitosamente';
                ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="mensaje-error">
                <?php
                if($error == 'campos_vacios') echo '<i class="bi bi-x-circle-fill"></i> Complete todos los campos obligatorios';
                if($error == 'no_eliminar') echo '<i class="bi bi-exclamation-triangle-fill"></i> No se puede eliminar la sede porque tiene pisos o usuarios asignados';
                if($error == 'operacion_fallida') echo '<i class="bi bi-x-circle-fill"></i> Error al realizar la operación';
                ?>
            </div>
        <?php endif; ?>

        <div class="sedes-grid">
            <?php if($result_sedes && mysqli_num_rows($result_sedes) > 0):
                while($sede = mysqli_fetch_assoc($result_sedes)):
            ?>
                <div class="sede-card <?php echo $sede['estado'] == 'inactiva' ? 'inactiva' : ''; ?>">
                    <div class="sede-icono">
                        <i class="bi bi-building"></i>
                    </div>
                    
                    <div class="sede-info">
                        <h2><?php echo htmlspecialchars($sede['nombre']); ?></h2>
                        <p class="sede-ciudad">📍 <?php echo htmlspecialchars($sede['ciudad']); ?></p>
                        <?php if($sede['direccion']): ?>
                            <p class="sede-direccion"><?php echo htmlspecialchars($sede['direccion']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="sede-estado">
                        <span class="badge <?php echo $sede['estado']; ?>">
                            <?php echo $sede['estado'] == 'activa' ? '<i class="bi bi-circle-fill text-success" style="font-size:.7rem"></i> Activa' : '<i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i> Inactiva'; ?>
                        </span>
                    </div>

                    <div class="sede-estadisticas">
                        <div class="stat">
                            <span class="stat-numero"><?php echo $sede['total_pisos']; ?></span>
                            <span class="stat-label">Pisos</span>
                        </div>
                        <div class="stat">
                            <span class="stat-numero"><?php echo $sede['total_ambientes']; ?></span>
                            <span class="stat-label">Ambientes</span>
                        </div>
                        <div class="stat">
                            <span class="stat-numero"><?php echo $sede['total_instructores']; ?></span>
                            <span class="stat-label">Instructores</span>
                        </div>
                        <div class="stat">
                            <span class="stat-numero"><?php echo $sede['total_celadores']; ?></span>
                            <span class="stat-label">Celadores</span>
                        </div>
                    </div>

                    <?php if($sede['ocupaciones_activas'] > 0): ?>
                        <div class="sede-alerta">
                            <i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i> <?php echo $sede['ocupaciones_activas']; ?> ocupación(es) activa(s) hoy
                        </div>
                    <?php endif; ?>

                    <div class="sede-acciones">
                        <?php if($sede['estado'] == 'activa'): ?>
                            <a href="dashboard_admin.php?sede_id=<?php echo $sede['id']; ?>" class="btn-ingresar">
                                🚀 Ingresar
                            </a>
                        <?php endif; ?>
                        <button class="btn-editar" onclick="editarSede(<?php echo $sede['id']; ?>)">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button class="btn-eliminar" onclick="confirmarEliminar(<?php echo $sede['id']; ?>, '<?php echo htmlspecialchars($sede['nombre']); ?>')">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <div class="estado-vacio">
                    <div class="vacio-icono"><i class="bi bi-building"></i></div>
                    <h3>No hay sedes registradas</h3>
                    <p>Crea la primera sede para comenzar</p>
                    <button class="btn-crear-primera" id="btnPrimeraSede">
                        <i class="bi bi-plus-lg"></i> Crear Primera Sede
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL CREAR/EDITAR SEDE -->
    <div class="modal" id="modalSede">
        <div class="modal-contenido">
            <span class="modal-cerrar" id="cerrarModal"><i class="bi bi-x-lg"></i></span>
            <h2 id="tituloModal">Nueva Sede</h2>
            
            <form action="../../controllers/GuardarSede.php" method="POST" id="formSede">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" id="sede_id">
                
                <div class="form-grupo">
                    <label>Nombre de la Sede *</label>
                    <input type="text" name="nombre" id="nombre" required placeholder="Ej: Sede Florencia">
                </div>

                <div class="form-grupo">
                    <label>Ciudad *</label>
                    <input type="text" name="ciudad" id="ciudad" required placeholder="Ej: Florencia">
                </div>

                <div class="form-grupo">
                    <label>Dirección</label>
                    <input type="text" name="direccion" id="direccion" placeholder="Ej: Calle Principal #123">
                </div>

                <div class="form-grupo">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" id="telefono" placeholder="Ej: (608) 4351234">
                </div>

                <div class="form-grupo">
                    <label>Email</label>
                    <input type="email" name="email" id="email" placeholder="Ej: sede@sena.edu.co">
                </div>

                <div class="form-grupo">
                    <label>Estado *</label>
                    <select name="estado" id="estado" required>
                        <option value="activa">Activa</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </div>

                <div class="form-botones">
                    <button type="button" class="btn-cancelar" id="btnCancelar">Cancelar</button>
                    <button type="submit" class="btn-guardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/index_sedes_admin.js"></script>
</body>
</html>