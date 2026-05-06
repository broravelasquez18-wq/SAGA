<?php
session_start();
require_once "../config/conexion.php";

$con = conexion();

// Verificar sesión activa
if(!isset($_SESSION['id'])) {
    header("Location: ../views/home.php");
    exit();
}

// ⭐ DETECTAR ROL DEL USUARIO
$rol = $_SESSION['rol'] ?? '';

// Obtener ID de la ocupación
$ocupacion_id = intval($_GET['id']);

// ⭐ OBTENER SEDE_ID
$sede_id = isset($_GET['sede_id']) ? intval($_GET['sede_id']) : 0;

// ⭐ NUEVO: Detectar si viene del calendario
$return = isset($_GET['return']) ? $_GET['return'] : 'ocupaciones';
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');

// ⭐ DEFINIR RUTA DE REDIRECCIÓN SEGÚN ROL Y ORIGEN
$ruta_base = '';
if($rol == 'admin') {
    if($return == 'calendario') {
        $ruta_base = "../views/admin/calendario_ocupaciones.php";
    } else {
        $ruta_base = "../views/admin/ocupaciones_admin.php";
    }
} elseif($rol == 'celador') {
    $ruta_base = "../views/celador/ocupaciones_celador.php";
} else {
    header("Location: ../views/home.php");
    exit();
}

if($sede_id <= 0 || empty($ocupacion_id)) {
    // ⭐ Redirigir con parámetros de calendario si aplica
    if($return == 'calendario' && $rol == 'admin') {
        header("Location: $ruta_base?sede_id=$sede_id&error=datos_invalidos&mes=$mes&anio=$anio");
    } else {
        header("Location: $ruta_base?sede_id=$sede_id&error=datos_invalidos");
    }
    exit();
}

// Obtener datos de la ocupación y validar que pertenezca a la sede
$get_query = "SELECT ho.ambiente_id, ho.fecha_inicio, ho.fecha_fin 
              FROM historial_ocupacion ho
              LEFT JOIN ambientes a ON ho.ambiente_id = a.id
              LEFT JOIN pisos p ON a.piso_id = p.id
              WHERE ho.id = $ocupacion_id AND p.sede_id = $sede_id";
              
$result = mysqli_query($con, $get_query);

if(mysqli_num_rows($result) == 0) {
    // ⭐ Redirigir con parámetros de calendario si aplica
    if($return == 'calendario' && $rol == 'admin') {
        header("Location: $ruta_base?sede_id=$sede_id&error=ocupacion_no_encontrada&mes=$mes&anio=$anio");
    } else {
        header("Location: $ruta_base?sede_id=$sede_id&error=ocupacion_no_encontrada");
    }
    exit();
}

$ocupacion = mysqli_fetch_assoc($result);
$ambiente_id = $ocupacion['ambiente_id'];
$fecha_inicio = $ocupacion['fecha_inicio'];
$fecha_fin_programada = $ocupacion['fecha_fin'];

// Calcular fecha_fin correcta
$ahora = date('Y-m-d H:i:s');
$nueva_fecha_fin = (strtotime($ahora) > strtotime($fecha_fin_programada)) ? $ahora : $fecha_fin_programada;

// Finalizar la ocupación
$query = "UPDATE historial_ocupacion 
          SET estado = 'finalizado', 
              fecha_fin = '$nueva_fecha_fin' 
          WHERE id = $ocupacion_id";

if(mysqli_query($con, $query)) {
    // Verificar si hay otras ocupaciones activas en este ambiente HOY
    $check_otras = "SELECT COUNT(*) as total 
                    FROM historial_ocupacion 
                    WHERE ambiente_id = $ambiente_id 
                    AND DATE(fecha_inicio) = CURDATE()
                    AND estado IN ('ocupado', 'proximo_a_desocupar')";
    
    $result_otras = mysqli_query($con, $check_otras);
    $otras = mysqli_fetch_assoc($result_otras);
    
    // Si no hay otras ocupaciones activas hoy, marcar ambiente como disponible
    if($otras['total'] == 0) {
        $update_ambiente = "UPDATE ambientes SET estado = 'disponible' WHERE id = $ambiente_id";
        mysqli_query($con, $update_ambiente);
    }
    
    // ⭐ REDIRIGIR SEGÚN ROL Y ORIGEN
    if($return == 'calendario' && $rol == 'admin') {
        header("Location: $ruta_base?sede_id=$sede_id&msg=ocupacion_finalizada&mes=$mes&anio=$anio");
    } else {
        header("Location: $ruta_base?sede_id=$sede_id&msg=ocupacion_finalizada");
    }
} else {
    // ⭐ Error al finalizar
    if($return == 'calendario' && $rol == 'admin') {
        header("Location: $ruta_base?sede_id=$sede_id&error=finalizar_fallido&mes=$mes&anio=$anio");
    } else {
        header("Location: $ruta_base?sede_id=$sede_id&error=finalizar_fallido");
    }
}

mysqli_close($con);
?>