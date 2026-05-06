<?php
session_start();

// Verificar sesión de instructor
if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'instructor') {
    header("Location: ../../views/home.php");
    exit();
}

require_once "../../config/conexion.php";
require_once "../../config/csrf.php";
$con = conexion();

$instructor_id = $_SESSION['id'];
$instructor_nombre = $_SESSION['nombre'];
$instructor_apellido = $_SESSION['apellido'];

// Datos para el modal de nueva ocupación
$instructor_tipo_contrato = $_SESSION['tipo_contrato'] ?? 'planta';
$instructor_sede_id       = $_SESSION['sede_id'] ?? 0;
if($instructor_tipo_contrato == 'contratista') {
    $sedes_result     = mysqli_query($con, "SELECT id, nombre, ciudad FROM sedes ORDER BY nombre ASC");
    $ambientes_result = null;
} else {
    $sedes_result     = null;
    $ambientes_result = mysqli_query($con, "SELECT a.id, a.nombre, p.nombre AS piso_nombre
                                            FROM ambientes a
                                            LEFT JOIN pisos p ON a.piso_id = p.id
                                            WHERE p.sede_id = $instructor_sede_id
                                            ORDER BY p.nombre, a.nombre");
}

// Estadísticas
$stats_total = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion WHERE usuario_id = $instructor_id"
))['total'];

$stats_activas = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion 
     WHERE usuario_id = $instructor_id 
     AND estado = 'ocupado' 
     AND fecha_inicio <= NOW() 
     AND fecha_fin >= NOW()"
))['total'];

$stats_proximas = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion 
     WHERE usuario_id = $instructor_id 
     AND estado = 'ocupado' 
     AND fecha_inicio > NOW()"
))['total'];

$stats_finalizadas = mysqli_fetch_assoc(mysqli_query($con, 
    "SELECT COUNT(*) as total FROM historial_ocupacion 
     WHERE usuario_id = $instructor_id 
     AND estado = 'finalizado'"
))['total'];

// Ocupación actual (si existe)
$ocupacion_actual = mysqli_query($con, 
    "SELECT h.*, a.nombre as ambiente, p.nombre as piso, s.nombre as sede
     FROM historial_ocupacion h
     INNER JOIN ambientes a ON h.ambiente_id = a.id
     INNER JOIN pisos p ON a.piso_id = p.id
     INNER JOIN sedes s ON p.sede_id = s.id
     WHERE h.usuario_id = $instructor_id 
     AND h.estado = 'ocupado'
     AND h.fecha_inicio <= NOW() 
     AND h.fecha_fin >= NOW()
     LIMIT 1"
);

$tiene_ocupacion_actual = mysqli_num_rows($ocupacion_actual) > 0;
if($tiene_ocupacion_actual) {
    $ocu_actual = mysqli_fetch_assoc($ocupacion_actual);
}

// Próximas ocupaciones (las 6 más cercanas)
$proximas_ocupaciones = mysqli_query($con, 
    "SELECT h.*, a.nombre as ambiente, p.nombre as piso, s.nombre as sede
     FROM historial_ocupacion h
     INNER JOIN ambientes a ON h.ambiente_id = a.id
     INNER JOIN pisos p ON a.piso_id = p.id
     INNER JOIN sedes s ON p.sede_id = s.id
     WHERE h.usuario_id = $instructor_id 
     AND h.estado = 'ocupado'
     AND h.fecha_inicio > NOW()
     ORDER BY h.fecha_inicio ASC
     LIMIT 6"
);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/dashboard_instructor.css">
    <link rel="stylesheet" href="../../assets/css/calendario_ocupaciones.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>Dashboard Instructor - SAGA</title>
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
                <span class="usuario-nombre"><?php echo $instructor_nombre . ' ' . $instructor_apellido; ?></span>
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

    <!-- Bienvenida -->
    <div class="bienvenida">
        <div class="bienvenida-contenido">
            <h1>¡Hola, <?php echo $instructor_nombre; ?>! 👋</h1>
            <p>Gestiona tus ocupaciones de ambientes</p>
        </div>
        <div class="bienvenida-botones">
            <a href="calendario_instructor.php" class="btn-calendario">
                <i class="bi bi-calendar-event"></i> Ver Calendario
            </a>
            <button class="btn-agendar-grande" onclick="abrirModalNuevaOcupacion()">
                <i class="bi bi-plus-lg"></i> Nueva Ocupación
            </button>
        </div>
    </div>

    <!-- Ocupación Actual -->
    <?php if($tiene_ocupacion_actual): ?>
        <div class="ocupacion-actual-banner">
            <div>
                <div class="ocupacion-actual-icono"><i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i></div>
                <div class="ocupacion-actual-info">
                    <h3>Ocupación Activa en este momento</h3>
                    <p>
                        <?php echo $ocu_actual['ambiente']; ?> - 
                        <?php echo $ocu_actual['sede']; ?> (<?php echo $ocu_actual['piso']; ?>)
                        • <?php echo date('H:i', strtotime($ocu_actual['fecha_inicio'])); ?> - 
                        <?php echo date('H:i', strtotime($ocu_actual['fecha_fin'])); ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="estadisticas">
        <div class="stat-card">
            <div class="stat-icono">📊</div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_total; ?></span>
                <span class="stat-label">Total</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icono"><i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_activas; ?></span>
                <span class="stat-label">Activas</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icono"><i class="bi bi-circle-fill text-warning" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_proximas; ?></span>
                <span class="stat-label">Próximas</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icono"><i class="bi bi-circle-fill text-success" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $stats_finalizadas; ?></span>
                <span class="stat-label">Finalizadas</span>
            </div>
        </div>
    </div>

    <!-- Próximas Ocupaciones -->
    <div class="proximas-ocupaciones">
        <div>
            <h2 class="seccion-titulo">Próximas Ocupaciones</h2>

            <?php if(mysqli_num_rows($proximas_ocupaciones) > 0): ?>
                <div class="ocupaciones-grid">
                    <?php while($ocu = mysqli_fetch_assoc($proximas_ocupaciones)): ?>
                        <div class="ocupacion-card">
                            <div class="ocupacion-info">
                                <h3><?php echo $ocu['ambiente']; ?></h3>
                                <p class="ocupacion-fecha-texto">
                                    <i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y', strtotime($ocu['fecha_inicio'])); ?>
                                </p>
                                <p class="ocupacion-ubicacion">
                                    📍 <?php echo $ocu['sede']; ?> - <?php echo $ocu['piso']; ?>
                                </p>
                                <p class="ocupacion-horario">
                                    🕐 <?php echo date('H:i', strtotime($ocu['fecha_inicio'])); ?> - 
                                    <?php echo date('H:i', strtotime($ocu['fecha_fin'])); ?>
                                    (<?php echo ucfirst($ocu['jornada']); ?>)
                                </p>
                                <?php if(!empty($ocu['observaciones'])): ?>
                                    <p class="ocupacion-obs">
                                        📝 <?php echo $ocu['observaciones']; ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="ocupacion-acciones">
                                <a href="../../controllers/CancelarOcupacion.php?id=<?php echo $ocu['id']; ?>&origen=dashboard" 
                                   class="btn-cancelar-ocupacion"
                                   onclick="return confirm('¿Cancelar esta ocupación?')">
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="mis_ocupaciones_instructor.php" class="btn-agendar">
                        <i class="bi bi-clipboard"></i> Ver Todas mis Ocupaciones
                    </a>
                </div>
            <?php else: ?>
                <div class="estado-vacio">
                    <div class="vacio-icono"><i class="bi bi-calendar-event"></i></div>
                    <h3>No tienes ocupaciones próximas</h3>
                    <p>Agenda un nuevo ambiente para tus clases</p>
                    <button class="btn-agendar" onclick="abrirModalNuevaOcupacion()">
                        <i class="bi bi-plus-lg"></i> Agendar Ahora
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast -->
    <div id="toastNotificacion" style="
        position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(100px);
        background: #059669; color: #fff; padding: 14px 24px; border-radius: 12px;
        font-weight: 600; font-size: 15px; opacity: 0; transition: all 0.4s ease;
        z-index: 9999; white-space: nowrap; pointer-events: none;
    "></div>

    <!-- MODAL NUEVA OCUPACIÓN -->
    <div class="modal" id="modalNuevaOcupacion">
        <div class="modal-contenido">
            <div class="modal-encabezado">
                <h2><i class="bi bi-plus-lg"></i> Nueva Ocupación</h2>
                <button class="modal-cerrar" type="button" onclick="cerrarModalNuevaOcupacion()"><i class="bi bi-x-lg"></i></button>
            </div>

            <form action="../../controllers/RegistrarOcupacionMultiple.php" method="POST" id="formOcupacion">
                <?php echo csrf_field(); ?>

                <!-- Checkbox múltiples días -->
                <div class="form-grupo">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox" id="checkMultiple" onchange="toggleModoMultiple()" style="width:auto;cursor:pointer;">
                        <span><i class="bi bi-calendar-event"></i> Registrar en múltiples días</span>
                    </label>
                </div>

                <!-- Modo simple: una fecha -->
                <div id="modoSimple">
                    <div class="form-grupo">
                        <label>Fecha *</label>
                        <input type="date" name="fecha" id="fechaOcupacion" required>
                    </div>
                </div>

                <!-- Modo múltiple: varias fechas -->
                <div id="modoMultiple" style="display:none;">
                    <div class="form-grupo">
                        <label>Selecciona los días *</label>
                        <div class="info-multiple">💡 Selecciona múltiples días para registrar la misma ocupación</div>
                        <div id="selectorFechas" class="selector-fechas"></div>
                        <input type="hidden" name="fechas_seleccionadas" id="fechasMultiples">
                    </div>
                </div>

                <?php if($instructor_tipo_contrato == 'contratista'): ?>
                <div class="form-grupo">
                    <label>Sede *</label>
                    <select id="sedeSelect" onchange="cargarAmbientesPorSede(this.value)">
                        <option value="">Seleccionar sede...</option>
                        <?php while($sede = mysqli_fetch_assoc($sedes_result)): ?>
                            <option value="<?php echo $sede['id']; ?>">
                                <?php echo htmlspecialchars($sede['nombre'] . ' — ' . $sede['ciudad']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-grupo">
                    <label>Ambiente *</label>
                    <select name="ambiente_id" required id="ambienteSelect" disabled>
                        <option value="">Primero selecciona una sede...</option>
                    </select>
                </div>
                <?php else: ?>
                <div class="form-grupo">
                    <label>Ambiente *</label>
                    <select name="ambiente_id" required id="ambienteSelect">
                        <option value="">Seleccionar ambiente...</option>
                        <?php while($amb = mysqli_fetch_assoc($ambientes_result)): ?>
                            <option value="<?php echo $amb['id']; ?>">
                                <?php echo htmlspecialchars($amb['piso_nombre'] . ' - ' . $amb['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-grupo">
                    <label>Jornada *</label>
                    <select name="jornada" id="jornadaSelect" required onchange="toggleHorarioPersonalizado()">
                        <option value="">Seleccionar jornada...</option>
                        <option value="mañana">🌅 Mañana (06:00 - 12:00)</option>
                        <option value="tarde">☀️ Tarde (12:00 - 18:00)</option>
                        <option value="noche">🌙 Noche (18:00 - 22:00)</option>
                        <option value="otro">⏰ Otro horario (personalizado)</option>
                    </select>
                </div>

                <div id="horarioPersonalizado" style="display:none;">
                    <div class="form-row" style="display:flex;gap:15px;">
                        <div class="form-grupo" style="flex:1;">
                            <label>Hora Inicio *</label>
                            <input type="time" name="hora_inicio" id="horaInicio" min="06:00" max="22:00">
                            <small>Rango: 06:00 - 22:00</small>
                        </div>
                        <div class="form-grupo" style="flex:1;">
                            <label>Hora Fin *</label>
                            <input type="time" name="hora_fin" id="horaFin" min="06:00" max="22:00">
                            <small>Rango: 06:00 - 22:00</small>
                        </div>
                    </div>
                </div>

                <div class="form-grupo">
                    <label>Observaciones</label>
                    <textarea name="observaciones" rows="3" placeholder="Observaciones opcionales..."></textarea>
                </div>

                <div class="form-botones">
                    <button type="button" class="btn-cancelar" onclick="cerrarModalNuevaOcupacion()">Cancelar</button>
                    <button type="submit" class="btn-guardar" id="btnGuardar">Registrar Ocupación</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const btnMenu = document.getElementById('btnMenu');
        const modalMenu = document.getElementById('modalMenu');

        btnMenu.addEventListener('click', () => modalMenu.classList.add('active'));
        modalMenu.addEventListener('click', (e) => {
            if(e.target === modalMenu) modalMenu.classList.remove('active');
        });

        let fechasSeleccionadas = [];
        const mesActual  = <?php echo date('n'); ?>;
        const anioActual = <?php echo date('Y'); ?>;

        function abrirModalNuevaOcupacion() {
            const hoy = new Date();
            const y = hoy.getFullYear();
            const m = String(hoy.getMonth() + 1).padStart(2, '0');
            const d = String(hoy.getDate()).padStart(2, '0');
            document.getElementById('fechaOcupacion').value = `${y}-${m}-${d}`;
            const minFecha = `${y}-${m}-${d}`;
            document.getElementById('fechaOcupacion').min = minFecha;
            document.getElementById('modalNuevaOcupacion').style.display = 'flex';
        }

        function cerrarModalNuevaOcupacion() {
            document.getElementById('modalNuevaOcupacion').style.display = 'none';
            document.getElementById('formOcupacion').reset();
            document.getElementById('checkMultiple').checked = false;
            document.getElementById('modoSimple').style.display = 'block';
            document.getElementById('modoMultiple').style.display = 'none';
            document.getElementById('horarioPersonalizado').style.display = 'none';
            document.getElementById('btnGuardar').textContent = 'Registrar Ocupación';
            fechasSeleccionadas = [];
            const sedeSelect = document.getElementById('sedeSelect');
            const ambienteSelect = document.getElementById('ambienteSelect');
            if(sedeSelect) sedeSelect.value = '';
            if(ambienteSelect && sedeSelect) {
                ambienteSelect.innerHTML = '<option value="">Primero selecciona una sede...</option>';
                ambienteSelect.disabled = true;
            }
        }

        function cargarAmbientesPorSede(sedeId) {
            const select = document.getElementById('ambienteSelect');
            if(!sedeId) {
                select.innerHTML = '<option value="">Primero selecciona una sede...</option>';
                select.disabled = true;
                return;
            }
            select.innerHTML = '<option value="">Cargando ambientes...</option>';
            select.disabled = true;
            fetch(`../../controllers/ObtenerAmbiente.php?sede_id=${sedeId}`)
                .then(r => r.json())
                .then(ambientes => {
                    if(!Array.isArray(ambientes) || ambientes.length === 0) {
                        select.innerHTML = '<option value="">No hay ambientes en esta sede</option>';
                        return;
                    }
                    select.innerHTML = '<option value="">Seleccionar ambiente...</option>';
                    ambientes.forEach(a => {
                        const opt = document.createElement('option');
                        opt.value = a.id;
                        opt.textContent = a.piso_nombre + ' - ' + a.nombre;
                        select.appendChild(opt);
                    });
                    select.disabled = false;
                })
                .catch(() => {
                    select.innerHTML = '<option value="">Error al cargar ambientes</option>';
                });
        }

        function toggleHorarioPersonalizado() {
            const jornada = document.getElementById('jornadaSelect').value;
            const horarioDiv = document.getElementById('horarioPersonalizado');
            const horaInicio = document.getElementById('horaInicio');
            const horaFin    = document.getElementById('horaFin');
            if(jornada === 'otro') {
                horarioDiv.style.display = 'block';
                horaInicio.setAttribute('required', 'required');
                horaFin.setAttribute('required', 'required');
            } else {
                horarioDiv.style.display = 'none';
                horaInicio.removeAttribute('required');
                horaFin.removeAttribute('required');
            }
        }

        function toggleModoMultiple() {
            const check      = document.getElementById('checkMultiple');
            const modoSimple = document.getElementById('modoSimple');
            const modoMult   = document.getElementById('modoMultiple');
            const fechaInput = document.getElementById('fechaOcupacion');
            const btnGuardar = document.getElementById('btnGuardar');
            if(check.checked) {
                modoSimple.style.display = 'none';
                modoMult.style.display   = 'block';
                fechaInput.removeAttribute('required');
                btnGuardar.innerHTML = '<i class="bi bi-check-circle-fill"></i> Registrar en Días Seleccionados';
                generarSelectorFechas();
            } else {
                modoSimple.style.display = 'block';
                modoMult.style.display   = 'none';
                fechaInput.setAttribute('required', 'required');
                btnGuardar.textContent   = 'Registrar Ocupación';
                fechasSeleccionadas      = [];
            }
        }

        function generarSelectorFechas() {
            const container = document.getElementById('selectorFechas');
            const diasMes   = new Date(anioActual, mesActual, 0).getDate();
            const hoy       = new Date();
            hoy.setHours(0, 0, 0, 0);
            let html = '<div class="dias-grid">';
            for(let dia = 1; dia <= diasMes; dia++) {
                const fecha    = new Date(anioActual, mesActual - 1, dia);
                const fechaStr = `${anioActual}-${String(mesActual).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
                if(fecha >= hoy) {
                    html += `<div class="dia-selector" data-fecha="${fechaStr}" onclick="toggleFecha('${fechaStr}')">
                        <div class="dia-num">${dia}</div>
                        <div class="dia-mes">${obtenerNombreMes(mesActual)}</div>
                    </div>`;
                }
            }
            html += '</div><div class="contador-seleccion" id="contadorSeleccion">0 días seleccionados</div>';
            container.innerHTML = html;
        }

        function toggleFecha(fecha) {
            const idx = fechasSeleccionadas.indexOf(fecha);
            const el  = document.querySelector(`[data-fecha="${fecha}"]`);
            if(idx > -1) { fechasSeleccionadas.splice(idx, 1); el.classList.remove('seleccionado'); }
            else         { fechasSeleccionadas.push(fecha);    el.classList.add('seleccionado'); }
            document.getElementById('fechasMultiples').value = JSON.stringify(fechasSeleccionadas);
            const n = fechasSeleccionadas.length;
            document.getElementById('contadorSeleccion').textContent =
                `${n} día${n !== 1 ? 's' : ''} seleccionado${n !== 1 ? 's' : ''}`;
        }

        function obtenerNombreMes(mes) {
            return ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][mes - 1];
        }

        document.getElementById('formOcupacion').addEventListener('submit', function(e) {
            if(document.getElementById('checkMultiple').checked && fechasSeleccionadas.length === 0) {
                e.preventDefault();
                alert('Debes seleccionar al menos un día');
            }
        });

        document.addEventListener('keydown', (e) => {
            if(e.key === 'Escape') cerrarModalNuevaOcupacion();
        });
    </script>
</body>
</html>