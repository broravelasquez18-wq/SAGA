<?php
session_start();
require_once "../config/conexion.php";
require_once "../config/csrf.php";

$con = conexion();

// Verificar método POST
if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../views/home.php");
    exit();
}

csrf_validate();

// ⭐ DETECTAR ROL DEL USUARIO
$rol = $_SESSION['rol'] ?? '';
$usuario_id = $_SESSION['id'] ?? 0;

// Recibir datos del formulario
$sede_id = isset($_POST['sede_id']) ? intval($_POST['sede_id']) : 0;
$ambiente_id = isset($_POST['ambiente_id']) ? intval($_POST['ambiente_id']) : 0;
$fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
$jornada = isset($_POST['jornada']) ? trim($_POST['jornada']) : '';
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';

// ⭐ PARA ADMIN Y CELADOR: usuario_id viene del formulario
// ⭐ PARA INSTRUCTOR: usuario_id es el instructor logueado
if($rol == 'admin' || $rol == 'celador') {
    $instructor_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
} elseif($rol == 'instructor') {
    $instructor_id = $usuario_id;
} else {
    header("Location: ../views/home.php");
    exit();
}

// ⭐ DEFINIR RUTA DE REDIRECCIÓN SEGÚN ROL
$ruta_error = '';
$ruta_exito = '';

if($rol == 'admin') {
    $ruta_error = "../views/admin/ocupaciones_admin.php?sede_id=$sede_id&";
    $ruta_exito = "../views/admin/ocupaciones_admin.php?sede_id=$sede_id&msg=agendado";
} elseif($rol == 'celador') {
    $ruta_error = "../views/celador/ocupaciones_celador.php?";
    $ruta_exito = "../views/celador/ocupaciones_celador.php?msg=agendado";
} elseif($rol == 'instructor') {
    $ruta_error = "../views/instructor/dashboard_instructor.php?";
    $ruta_exito = "../views/instructor/dashboard_instructor.php?msg=agendado";
} else {
    header("Location: ../views/home.php");
    exit();
}

// Validar campos obligatorios
if(!$sede_id || !$ambiente_id || !$fecha || !$jornada || !$instructor_id) {
    header("Location: {$ruta_error}error=campos_vacios");
    exit();
}

// VALIDACIÓN: Fecha no puede ser pasada
$hoy = date('Y-m-d');
if($fecha < $hoy) {
    header("Location: {$ruta_error}error=fecha_pasada");
    exit();
}

// VALIDACIÓN: Verificar que el ambiente pertenece a la sede seleccionada
$verify_ambiente = mysqli_query($con, "SELECT a.id FROM ambientes a LEFT JOIN pisos p ON a.piso_id = p.id WHERE a.id = $ambiente_id AND p.sede_id = $sede_id");
if(mysqli_num_rows($verify_ambiente) == 0) {
    header("Location: {$ruta_error}error=ambiente_invalido");
    exit();
}

// Determinar horarios según jornada
$fecha_inicio = '';
$fecha_fin = '';

switch($jornada) {
    case 'mañana':
        $fecha_inicio = $fecha . ' 06:00:00';
        $fecha_fin = $fecha . ' 12:00:00';
        break;
    case 'tarde':
        $fecha_inicio = $fecha . ' 12:00:00';
        $fecha_fin = $fecha . ' 18:00:00';
        break;
    case 'noche':
        $fecha_inicio = $fecha . ' 18:00:00';
        $fecha_fin = $fecha . ' 22:00:00';
        break;
    default:
        header("Location: {$ruta_error}error=jornada_invalida");
        exit();
}

// VALIDACIÓN: Verificar que el AMBIENTE no esté ocupado en esa jornada
$check_ambiente_ocupado = "SELECT * FROM historial_ocupacion 
                           WHERE ambiente_id = $ambiente_id 
                           AND DATE(fecha_inicio) = '$fecha'
                           AND jornada = '$jornada'
                           AND estado != 'finalizado'";

$result_ambiente = mysqli_query($con, $check_ambiente_ocupado);

if(mysqli_num_rows($result_ambiente) > 0) {
    header("Location: {$ruta_error}error=ambiente_ocupado");
    exit();
}

// VALIDACIÓN: Verificar que el INSTRUCTOR no esté ocupado en esa fecha/jornada
$check_instructor_ocupado = "SELECT ho.id, a.nombre as ambiente_nombre, p.nombre as piso_nombre, s.nombre as sede_nombre
                             FROM historial_ocupacion ho
                             LEFT JOIN ambientes a ON ho.ambiente_id = a.id
                             LEFT JOIN pisos p ON a.piso_id = p.id
                             LEFT JOIN sedes s ON p.sede_id = s.id
                             WHERE ho.usuario_id = $instructor_id
                             AND DATE(ho.fecha_inicio) = '$fecha'
                             AND ho.jornada = '$jornada'
                             AND ho.estado != 'finalizado'";

$result_instructor = mysqli_query($con, $check_instructor_ocupado);

if(mysqli_num_rows($result_instructor) > 0) {
    $ocupacion_existente = mysqli_fetch_assoc($result_instructor);
    $ubicacion = $ocupacion_existente['sede_nombre'] . ' - ' . $ocupacion_existente['piso_nombre'] . ' - ' . $ocupacion_existente['ambiente_nombre'];
    header("Location: {$ruta_error}error=instructor_ocupado&ubicacion=" . urlencode($ubicacion));
    exit();
}

// Determinar estado inicial
$hora_actual = date('H:i:s');
$estado_inicial = 'ocupado';
$es_hoy = (date('Y-m-d') == $fecha);

if($es_hoy) {
    switch($jornada) {
        case 'mañana':
            if($hora_actual >= '11:30:00' && $hora_actual < '12:00:00') {
                $estado_inicial = 'proximo_a_desocupar';
            } elseif($hora_actual >= '12:00:00') {
                $estado_inicial = 'disponible';
            }
            break;
        case 'tarde':
            if($hora_actual >= '17:30:00' && $hora_actual < '18:00:00') {
                $estado_inicial = 'proximo_a_desocupar';
            } elseif($hora_actual >= '18:00:00') {
                $estado_inicial = 'disponible';
            }
            break;
        case 'noche':
            if($hora_actual >= '21:30:00' && $hora_actual < '22:00:00') {
                $estado_inicial = 'proximo_a_desocupar';
            } elseif($hora_actual >= '22:00:00') {
                $estado_inicial = 'disponible';
            }
            break;
    }
}

// Insertar ocupación en historial
$observaciones_clean = mysqli_real_escape_string($con, $observaciones);

$query = "INSERT INTO historial_ocupacion 
          (ambiente_id, usuario_id, fecha_inicio, fecha_fin, estado, observaciones, jornada) 
          VALUES 
          ($ambiente_id, $instructor_id, '$fecha_inicio', '$fecha_fin', '$estado_inicial', '$observaciones_clean', '$jornada')";

if(mysqli_query($con, $query)) {
    // Actualizar estado del ambiente solo si está ocupado
    if($estado_inicial == 'ocupado') {
        $update_ambiente = "UPDATE ambientes SET estado = 'ocupado' WHERE id = $ambiente_id";
        mysqli_query($con, $update_ambiente);
    }
    
    // ⭐ REDIRIGIR SEGÚN ROL
    header("Location: $ruta_exito");
} else {
    header("Location: {$ruta_error}error=error_sistema");
}

mysqli_close($con);
?>