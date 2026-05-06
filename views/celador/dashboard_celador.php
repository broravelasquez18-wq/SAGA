<?php
session_start();

// Verificar sesión de celador
if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'celador') {
    header("Location: ../../views/home.php");
    exit();
}

require_once "../../config/conexion.php";
require_once "../../config/csrf.php";
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
    // Si no tiene sede asignada, cerrar sesión
    header("Location: ../../controllers/logout.php");
    exit();
}

$hoy = date('Y-m-d');

// ⭐ ESTADÍSTICAS FILTRADAS POR SEDE
$total_hoy = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE DATE(ho.fecha_inicio) = '$hoy' AND p.sede_id = $celador_sede_id"))['total'];

$ocupados = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE DATE(ho.fecha_inicio) = '$hoy' AND ho.estado = 'ocupado' AND p.sede_id = $celador_sede_id"))['total'];

$proximos = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE DATE(ho.fecha_inicio) = '$hoy' AND ho.estado = 'proximo_a_desocupar' AND p.sede_id = $celador_sede_id"))['total'];

$finalizados = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM historial_ocupacion ho LEFT JOIN ambientes a ON ho.ambiente_id = a.id LEFT JOIN pisos p ON a.piso_id = p.id WHERE DATE(ho.fecha_inicio) = '$hoy' AND ho.estado = 'finalizado' AND p.sede_id = $celador_sede_id"))['total'];

$ambientes_disponibles = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) total FROM ambientes a LEFT JOIN pisos p ON a.piso_id = p.id WHERE a.estado = 'disponible' AND p.sede_id = $celador_sede_id"))['total'];

// ⭐ OCUPACIONES ACTIVAS DE LA SEDE
$query_activas = "SELECT ho.*, a.nombre AS ambiente_nombre, p.nombre AS piso_nombre, CONCAT(u.nombre, ' ', u.apellido) AS instructor_nombre
                  FROM historial_ocupacion ho
                  LEFT JOIN ambientes a ON ho.ambiente_id = a.id
                  LEFT JOIN pisos p ON a.piso_id = p.id
                  LEFT JOIN usuarios u ON ho.usuario_id = u.id
                  WHERE p.sede_id = $celador_sede_id
                  AND DATE(ho.fecha_inicio) = '$hoy' 
                  AND ho.estado IN ('ocupado', 'proximo_a_desocupar')
                  ORDER BY CASE ho.estado WHEN 'ocupado' THEN 1 WHEN 'proximo_a_desocupar' THEN 2 END, ho.fecha_inicio ASC 
                  LIMIT 10";
$result_activas = mysqli_query($con, $query_activas);

// Datos para el modal de nueva ocupación
$ambientes_modal = mysqli_query($con, "SELECT a.id, a.nombre, p.nombre AS piso_nombre FROM ambientes a LEFT JOIN pisos p ON a.piso_id = p.id WHERE p.sede_id = $celador_sede_id ORDER BY p.nombre, a.nombre");
$instructores_modal = mysqli_query($con, "SELECT id, nombre, apellido, cedula FROM usuarios WHERE rol = 'instructor' AND estado = 'activo' ORDER BY nombre, apellido");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/dashboard_celador.css">
    <link rel="stylesheet" href="../../assets/css/ocupaciones_admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>SAGA - Dashboard Celador</title>
    <style>
        #formOcupacion { overflow: hidden; }
        #modoSimple { overflow: hidden; max-width: 100%; }
        #fechaSimple {
            display: block;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <button class="btn-menu" id="btnMenu">
                <i class="bi bi-list"></i>
            </button>
            <img class="logo-saga" src="../../assets/img/ChatGPT_Image_6_mar_2026__12_14_37_p.m.-removebg-preview.png" alt="logo">
            <h2>SAGA</h2>
        </div>
        
        <!-- ⭐ INDICADOR DE SEDE -->
        <div class="sede-actual">
            <span class="sede-nombre"><i class="bi bi-building"></i> <?php echo $sede_nombre; ?></span>
        </div>
        
        <a class="btn-logout" href="../../controllers/logout.php">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>

    <div class="modal-menu" id="modalMenu">
        <div class="sidebar">
            <h3 class="menu-titulo">MENÚ CELADOR</h3>
            <a href="dashboard_celador.php" class="active"><i class="bi bi-bar-chart-fill"></i>Dashboard</a>
            <a href="ocupaciones_celador.php"><i class="bi bi-calendar-check"></i>Ocupaciones</a>
            <a href="calendario_celador.php"><i class="bi bi-calendar3"></i>Calendario</a>
            <a href="historial_celador.php"><i class="bi bi-clock-history"></i>Historial</a>
        </div>
    </div>

    <div class="bienvenida">
        <div class="bienvenida-contenido">
            <h1>Bienvenido, Celador</h1>
            <p>Gestiona las ocupaciones de <?php echo $sede_nombre; ?></p>
            <p class="fecha-actual"><i class="bi bi-calendar-event"></i> Hoy es <?php echo date('d/m/Y'); ?></p>
        </div>
        <button class="btn-accion-rapida" onclick="abrirModal()"><i class="bi bi-plus-lg"></i> Nueva Ocupación</button>
    </div>

    <div class="estadisticas">
        <div class="stat-card ocupado">
            <div class="stat-icono"><i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $ocupados; ?></span>
                <span class="stat-label">Ocupados Ahora</span>
            </div>
        </div>
        <div class="stat-card proximo">
            <div class="stat-icono"><i class="bi bi-circle-fill text-warning" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $proximos; ?></span>
                <span class="stat-label">Por Desocupar</span>
            </div>
        </div>
        <div class="stat-card finalizado">
            <div class="stat-icono">✅</div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $finalizados; ?></span>
                <span class="stat-label">Finalizados Hoy</span>
            </div>
        </div>
        <div class="stat-card disponible">
            <div class="stat-icono"><i class="bi bi-circle-fill text-success" style="font-size:.7rem"></i></div>
            <div class="stat-info">
                <span class="stat-numero"><?php echo $ambientes_disponibles; ?></span>
                <span class="stat-label">Ambientes Libres</span>
            </div>
        </div>
    </div>

    <div class="contenedor-principal">
        <div class="seccion-grande">
            <div class="seccion-header">
                <h2><i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i> Ocupaciones Activas de Hoy</h2>
                <a href="ocupaciones_celador.php" class="btn-ver-todas">Ver todas →</a>
            </div>
            <div class="ocupaciones-lista">
                <?php if($result_activas && mysqli_num_rows($result_activas) > 0):
                    while($ocup = mysqli_fetch_assoc($result_activas)):
                        $clase = ($ocup['estado'] == 'ocupado') ? 'ocupado' : 'proximo';
                        $texto = ($ocup['estado'] == 'ocupado') ? 'OCUPADO' : 'PRÓXIMO A DESOCUPAR';
                        $icono = ($ocup['estado'] == 'ocupado') ? '<i class="bi bi-circle-fill text-danger" style="font-size:.7rem"></i>' : '<i class="bi bi-circle-fill text-warning" style="font-size:.7rem"></i>';
                        $icono_jornada = ($ocup['jornada'] == 'mañana') ? '🌅' : (($ocup['jornada'] == 'tarde') ? '☀️' : '🌙');
                ?>
                    <div class="ocupacion-item <?php echo $clase; ?>">
                        <div class="ocupacion-info">
                            <div class="ocupacion-badge <?php echo $clase; ?>"><?php echo $icono; ?> <?php echo $texto; ?></div>
                            <h3><?php echo $ocup['piso_nombre'] . ' - ' . $ocup['ambiente_nombre']; ?></h3>
                            <p class="ocupacion-instructor">👤 <?php echo $ocup['instructor_nombre']; ?></p>
                            <p class="ocupacion-horario">🕐 <?php echo date('H:i', strtotime($ocup['fecha_inicio'])); ?> - <?php echo date('H:i', strtotime($ocup['fecha_fin'])); ?> <span class="ocupacion-jornada"><?php echo $icono_jornada; ?> <?php echo ucfirst($ocup['jornada']); ?></span></p>
                            <?php if($ocup['observaciones']): ?>
                                <p class="ocupacion-obs">📝 <?php echo htmlspecialchars($ocup['observaciones']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="ocupacion-acciones">
                            <a href="../../controllers/FinalizarOcupacion.php?id=<?php echo $ocup['id']; ?>&sede_id=<?php echo $celador_sede_id; ?>" class="btn-finalizar" onclick="return confirm('¿Finalizar esta ocupación?')"><i class="bi bi-check-circle-fill"></i> Finalizar</a>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="estado-vacio">
                        <div class="vacio-icono"><i class="bi bi-calendar-event"></i></div>
                        <h3>No hay ocupaciones activas</h3>
                        <p>Todas las ocupaciones de hoy han finalizado</p>
                        <button class="btn-registrar-vacio" onclick="abrirModal()"><i class="bi bi-plus-lg"></i> Registrar Primera Ocupación</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="seccion-lateral">
            <div class="seccion-header">
                <h2>📊 Resumen del Día</h2>
            </div>
            <div class="resumen-items">
                <div class="resumen-item">
                    <div class="resumen-icono">📋</div>
                    <div class="resumen-datos">
                        <span class="resumen-numero"><?php echo $total_hoy; ?></span>
                        <span class="resumen-texto">Total Registradas</span>
                    </div>
                </div>
                <div class="resumen-item">
                    <div class="resumen-icono">🔄</div>
                    <div class="resumen-datos">
                        <span class="resumen-numero"><?php echo $ocupados + $proximos; ?></span>
                        <span class="resumen-texto">En Proceso</span>
                    </div>
                </div>
                <div class="resumen-item">
                    <div class="resumen-icono">✅</div>
                    <div class="resumen-datos">
                        <span class="resumen-numero"><?php echo $finalizados; ?></span>
                        <span class="resumen-texto">Completadas</span>
                    </div>
                </div>
                <div class="resumen-item">
                    <div class="resumen-icono"><i class="bi bi-circle-fill text-success" style="font-size:.7rem"></i></div>
                    <div class="resumen-datos">
                        <span class="resumen-numero"><?php echo $ambientes_disponibles; ?></span>
                        <span class="resumen-texto">Ambientes Libres</span>
                    </div>
                </div>
            </div>
            <div class="acciones-rapidas">
                <h3>Acciones Rápidas</h3>
                <button class="btn-accion" onclick="abrirModal()"><i class="bi bi-plus-lg"></i> Registrar Ocupación</button>
                <a href="historial_celador.php" class="btn-accion-secundario">🕘 Ver Historial Completo</a>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVA OCUPACIÓN -->
    <div class="modal" id="modalOcupacion">
        <div class="modal-contenido">
            <button class="modal-cerrar" type="button" onclick="cerrarModal()"><i class="bi bi-x-lg"></i></button>
            <h2><i class="bi bi-plus-lg"></i> Nueva Ocupación</h2>
            <form action="../../controllers/RegistrarOcupacion.php" method="POST" id="formOcupacion">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="sede_id" value="<?php echo $celador_sede_id; ?>">
                <input type="hidden" name="return" value="calendario">

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
                        <input type="date" name="fecha" id="fechaSimple"
                               value="<?php echo date('Y-m-d'); ?>"
                               min="<?php echo date('Y-m-d'); ?>"
                               required onchange="validarJornada()">
                    </div>
                </div>

                <!-- Modo múltiple: varias fechas -->
                <div id="modoMultiple" style="display:none;">
                    <div class="form-grupo">
                        <label>Selecciona los días *</label>
                        <div class="info-multiple">💡 Selecciona múltiples días para registrar la misma ocupación</div>
                        <div id="selectorFechas" class="selector-fechas"></div>
                        <input type="hidden" name="fechas_multiples" id="fechasMultiples">
                    </div>
                </div>

                <div class="form-grupo">
                    <label>Ambiente *</label>
                    <select name="ambiente_id" required>
                        <option value="">Seleccionar ambiente...</option>
                        <?php while($amb = mysqli_fetch_assoc($ambientes_modal)): ?>
                            <option value="<?php echo $amb['id']; ?>">
                                <?php echo htmlspecialchars($amb['piso_nombre'] . ' - ' . $amb['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-grupo">
                    <label>Instructor *</label>
                    <select name="usuario_id" required>
                        <option value="">Seleccionar instructor...</option>
                        <?php while($inst = mysqli_fetch_assoc($instructores_modal)): ?>
                            <option value="<?php echo $inst['id']; ?>">
                                <?php echo htmlspecialchars($inst['nombre'] . ' ' . $inst['apellido'] . ' - ' . $inst['cedula']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-grupo">
                    <label>Jornada *</label>
                    <select name="jornada" id="jornadaSelect" required
                            onchange="toggleHorarioPersonalizado(); validarJornada();">
                        <option value="">Seleccionar jornada...</option>
                        <option value="mañana">🌅 Mañana (06:00 - 12:00)</option>
                        <option value="tarde">☀️ Tarde (12:00 - 18:00)</option>
                        <option value="noche">🌙 Noche (18:00 - 22:00)</option>
                        <option value="otro">⏰ Otro horario (personalizado)</option>
                    </select>
                    <div id="avisoJornada" style="display:none;margin-top:8px;padding:10px 14px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:8px;font-size:13px;font-weight:600;">
                        ⛔ No se puede registrar esta ocupación porque la jornada ya pasó.
                    </div>
                </div>

                <!-- Horario personalizado -->
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
                    <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-guardar" id="btnGuardar">Registrar Ocupación</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const btnMenu = document.getElementById("btnMenu");
        const modalMenu = document.getElementById("modalMenu");

        if(btnMenu && modalMenu) {
            btnMenu.addEventListener("click", () => modalMenu.classList.add("active"));
            modalMenu.addEventListener("click", (e) => {
                if(e.target === modalMenu) modalMenu.classList.remove("active");
            });
        }

        function abrirModal() {
            document.getElementById('modalOcupacion').style.display = 'flex';
        }

        function cerrarModal() {
            document.getElementById('modalOcupacion').style.display = 'none';
            document.getElementById('avisoJornada').style.display = 'none';
            document.getElementById('btnGuardar').disabled = false;
            document.getElementById('btnGuardar').style.opacity = '1';
            document.getElementById('formOcupacion').reset();
            document.getElementById('checkMultiple').checked = false;
            document.getElementById('modoSimple').style.display = 'block';
            document.getElementById('modoMultiple').style.display = 'none';
            document.getElementById('fechaSimple').setAttribute('required', 'required');
            document.getElementById('horarioPersonalizado').style.display = 'none';
            document.getElementById('horaInicio').removeAttribute('required');
            document.getElementById('horaFin').removeAttribute('required');
            fechasSeleccionadas = [];
        }

        function validarJornada() {
            const fecha    = document.getElementById('fechaSimple').value;
            const jornada  = document.getElementById('jornadaSelect').value;
            const aviso    = document.getElementById('avisoJornada');
            const btn      = document.getElementById('btnGuardar');
            const hoy      = new Date();
            const horaActual = hoy.getHours() * 60 + hoy.getMinutes();
            const fechaHoy = hoy.toISOString().split('T')[0];
            const fines    = { 'mañana': 12*60, 'tarde': 18*60, 'noche': 22*60 };
            const pasada   = fecha === fechaHoy && jornada && fines[jornada] !== undefined && horaActual >= fines[jornada];
            aviso.style.display    = pasada ? 'block' : 'none';
            btn.disabled           = pasada;
            btn.style.opacity      = pasada ? '0.5' : '1';
        }

        function toggleHorarioPersonalizado() {
            const jornada    = document.getElementById('jornadaSelect').value;
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

        let fechasSeleccionadas = [];
        const mesActual  = new Date().getMonth() + 1;
        const anioActual = new Date().getFullYear();

        function toggleModoMultiple() {
            const check      = document.getElementById('checkMultiple');
            const modoSimple = document.getElementById('modoSimple');
            const modoMult   = document.getElementById('modoMultiple');
            const fechaInput = document.getElementById('fechaSimple');
            const btn        = document.getElementById('btnGuardar');
            if(check.checked) {
                modoSimple.style.display = 'none';
                modoMult.style.display   = 'block';
                fechaInput.removeAttribute('required');
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Registrar en Días Seleccionados';
                generarSelectorFechas();
            } else {
                modoSimple.style.display = 'block';
                modoMult.style.display   = 'none';
                fechaInput.setAttribute('required', 'required');
                btn.textContent = 'Registrar Ocupación';
                fechasSeleccionadas = [];
            }
        }

        function generarSelectorFechas() {
            const container = document.getElementById('selectorFechas');
            const diasMes   = new Date(anioActual, mesActual, 0).getDate();
            const hoy       = new Date(); hoy.setHours(0,0,0,0);
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
            document.getElementById('fechasMultiples').value = fechasSeleccionadas.join(',');
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
            if(e.key === 'Escape') cerrarModal();
        });

        // Auto-refresh cada 2 minutos (solo si el modal está cerrado)
        setInterval(() => {
            if(document.getElementById('modalOcupacion').style.display !== 'flex') {
                location.reload();
            }
        }, 120000);
    </script>
</body>
</html>