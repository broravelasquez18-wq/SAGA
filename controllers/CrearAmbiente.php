<?php
session_start();
require_once "../config/conexion.php";
require_once "../config/csrf.php";

if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../views/home.php");
    exit();
}

$con = conexion();

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../views/admin/index_sedes.php");
    exit();
}

csrf_validate();

// ⭐ OBTENER SEDE_ID
$sede_id = isset($_POST['sede_id']) ? intval($_POST['sede_id']) : 0;

if($sede_id <= 0) {
    header("Location: ../views/admin/index_sedes.php");
    exit();
}

$nombre = mysqli_real_escape_string($con, $_POST['nombre']);
$descripcion = mysqli_real_escape_string($con, $_POST['descripcion']);
$piso_id = intval($_POST['piso_id']);
$estado = mysqli_real_escape_string($con, $_POST['estado']);

// Validar que el piso pertenezca a la sede
$check_piso = mysqli_query($con, "SELECT id FROM pisos WHERE id = $piso_id AND sede_id = $sede_id");

if(mysqli_num_rows($check_piso) == 0) {
    $_SESSION['mensaje'] = "Error: El piso seleccionado no pertenece a esta sede";
    header("Location: ../views/admin/dashboard_admin.php?sede_id=$sede_id");
    exit();
}

$sql = "INSERT INTO ambientes (nombre, descripcion, piso_id, estado)
        VALUES ('$nombre', '$descripcion', '$piso_id', '$estado')";

$resultado = mysqli_query($con, $sql);

if($resultado){
    $_SESSION['mensaje'] = "Ambiente agregado correctamente";
    // ⭐ REDIRIGIR AL MÓDULO DE AMBIENTES CON SEDE_ID
    header("Location: ../views/admin/Ambientes_admin.php?sede_id=$sede_id&msg=created");
} else {
    $_SESSION['mensaje'] = "Error al registrar ambiente";
    header("Location: ../views/admin/dashboard_admin.php?sede_id=$sede_id&error=create_failed");
}

exit();
?>