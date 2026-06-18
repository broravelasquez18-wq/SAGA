<?php
session_start();
require_once "../config/conexion.php";
require_once "../config/sms.php";
require_once "../config/csrf.php";

$con = conexion();

if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'vocero') {
    header("Location: ../views/home.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../views/vocero/calendario_vocero.php");
    exit();
}

csrf_validate();

$vocero_id    = $_SESSION['id'];
$ocupacion_id = intval($_POST['id'] ?? 0);
$mes          = isset($_POST['mes'])  ? intval($_POST['mes'])  : date('n');
$anio         = isset($_POST['anio']) ? intval($_POST['anio']) : date('Y');

$ruta_base = "../views/vocero/calendario_vocero.php";
$params    = "?mes=$mes&anio=$anio";

if($ocupacion_id <= 0) {
    header("Location: $ruta_base{$params}&error=datos_invalidos");
    exit();
}

$result = mysqli_query($con,
    "SELECT ho.id, ho.ambiente_id, ho.fecha_inicio, ho.fecha_fin, ho.estado,
            a.nombre AS ambiente_nombre,
            u.nombre AS vocero_nombre, u.apellido AS vocero_apellido, u.telefono AS vocero_telefono
     FROM historial_ocupacion ho
     LEFT JOIN ambientes a ON ho.ambiente_id = a.id
     LEFT JOIN usuarios u ON ho.usuario_id = u.id
     WHERE ho.id = $ocupacion_id
       AND ho.usuario_id = $vocero_id
       AND ho.estado IN ('ocupado', 'proximo_a_desocupar')"
);

if(mysqli_num_rows($result) == 0) {
    header("Location: $ruta_base{$params}&error=ocupacion_no_encontrada");
    exit();
}

$ocupacion        = mysqli_fetch_assoc($result);
$ambiente_id      = $ocupacion['ambiente_id'];
$fecha_fin_prog   = $ocupacion['fecha_fin'];
$vocero_telefono  = $ocupacion['vocero_telefono'] ?? '';
$ambiente_nombre  = $ocupacion['ambiente_nombre'] ?? '';
$vocero_nombre    = trim(($ocupacion['vocero_nombre'] ?? '') . ' ' . ($ocupacion['vocero_apellido'] ?? ''));

$ahora = date('Y-m-d H:i:s');
$nueva_fecha_fin = (strtotime($ahora) > strtotime($fecha_fin_prog)) ? $ahora : $fecha_fin_prog;

if(mysqli_query($con, "UPDATE historial_ocupacion SET estado='finalizado', fecha_fin='$nueva_fecha_fin' WHERE id=$ocupacion_id")) {
    $otras = mysqli_fetch_assoc(mysqli_query($con,
        "SELECT COUNT(*) as total FROM historial_ocupacion
         WHERE ambiente_id = $ambiente_id
           AND DATE(fecha_inicio) = CURDATE()
           AND estado IN ('ocupado','proximo_a_desocupar')"
    ));
    if($otras['total'] == 0) {
        mysqli_query($con, "UPDATE ambientes SET estado='disponible' WHERE id=$ambiente_id");
    }

    if(!empty($vocero_telefono)) {
        $hora_fin_real = date('H:i', strtotime($nueva_fecha_fin));
        $fecha_fmt     = date('d/m/Y', strtotime($nueva_fecha_fin));
        $numero        = '+57' . preg_replace('/\D/', '', $vocero_telefono);
        $mensaje       = "SAGA: Hola $vocero_nombre, tu ocupacion del ambiente \"$ambiente_nombre\" fue finalizada el $fecha_fmt a las $hora_fin_real.";
        enviarSMS($numero, $mensaje);
    }

    header("Location: $ruta_base{$params}&msg=finalizado");
} else {
    header("Location: $ruta_base{$params}&error=finalizar_fallido");
}

mysqli_close($con);
?>
