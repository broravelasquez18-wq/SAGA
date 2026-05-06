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

// Obtener datos del formulario
$id = isset($_POST['id']) && $_POST['id'] != '' ? intval($_POST['id']) : 0;
$nombre = mysqli_real_escape_string($con, trim($_POST['nombre']));
$ciudad = mysqli_real_escape_string($con, trim($_POST['ciudad']));
$direccion = mysqli_real_escape_string($con, trim($_POST['direccion']));
$telefono = mysqli_real_escape_string($con, trim($_POST['telefono']));
$email = mysqli_real_escape_string($con, trim($_POST['email']));
$estado = mysqli_real_escape_string($con, $_POST['estado']);

// Validar campos obligatorios
if(empty($nombre) || empty($ciudad)) {
    header("Location: ../views/admin/index_sedes.php?error=campos_vacios");
    exit();
}

if($id > 0) {
    // EDITAR sede existente
    $query = "UPDATE sedes SET 
              nombre = '$nombre',
              ciudad = '$ciudad',
              direccion = '$direccion',
              telefono = '$telefono',
              email = '$email',
              estado = '$estado'
              WHERE id = $id";
    
    if(mysqli_query($con, $query)) {
        header("Location: ../views/admin/index_sedes.php?msg=sede_actualizada");
    } else {
        header("Location: ../views/admin/index_sedes.php?error=operacion_fallida");
    }
    
} else {
    // CREAR nueva sede
    $query = "INSERT INTO sedes (nombre, ciudad, direccion, telefono, email, estado) 
              VALUES ('$nombre', '$ciudad', '$direccion', '$telefono', '$email', '$estado')";
    
    if(mysqli_query($con, $query)) {
        header("Location: ../views/admin/index_sedes.php?msg=sede_creada");
    } else {
        header("Location: ../views/admin/index_sedes.php?error=operacion_fallida");
    }
}

mysqli_close($con);
exit();
?>