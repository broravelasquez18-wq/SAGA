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

$nombre = mysqli_real_escape_string($con, trim($_POST['nombre']));
$descripcion = mysqli_real_escape_string($con, trim($_POST['descripcion']));

// Validar campos vacíos
if(empty($nombre) || empty($descripcion)) {
    header("Location: ../views/admin/dashboard_admin.php?sede_id=$sede_id&error=campos_vacios");
    exit();
}

// Validar que no exista un piso con el mismo nombre en esta sede
$check = mysqli_query($con, "SELECT id FROM pisos WHERE nombre = '$nombre' AND sede_id = $sede_id");

if(mysqli_num_rows($check) > 0) {
    header("Location: ../views/admin/dashboard_admin.php?sede_id=$sede_id&error=piso_duplicado");
    exit();
}

// ⭐ INSERTAR CON SEDE_ID
$query = "INSERT INTO pisos (sede_id, nombre, descripcion) 
          VALUES ($sede_id, '$nombre', '$descripcion')";

if(mysqli_query($con, $query)) {
    // ⭐ REDIRIGIR AL MÓDULO DE PISOS
    header("Location: ../views/admin/pisos_admin.php?sede_id=$sede_id&msg=created");
} else {
    header("Location: ../views/admin/dashboard_admin.php?sede_id=$sede_id&error=error_crear_piso");
}

mysqli_close($con);
exit();
?>