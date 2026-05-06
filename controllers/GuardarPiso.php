<?php
session_start();
require_once "../config/conexion.php";
require_once "../config/csrf.php";

if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../views/home.php");
    exit();
}

$con = conexion();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_validate();
    // ⭐ OBTENER SEDE_ID DEL FORMULARIO
    $sede_id = isset($_POST['sede_id']) ? intval($_POST['sede_id']) : 0;
    
    // Validar que sede_id sea válido
    if($sede_id <= 0) {
        header("Location: ../views/admin/index_sedes.php");
        exit();
    }
    
    $accion = $_POST['accion'];
    $nombre = mysqli_real_escape_string($con, trim($_POST['nombre']));
    $descripcion = mysqli_real_escape_string($con, trim($_POST['descripcion']));

    if($accion == 'crear') {
        // ⭐ INSERTAR CON SEDE_ID
        $query = "INSERT INTO pisos (sede_id, nombre, descripcion) 
                  VALUES ($sede_id, '$nombre', '$descripcion')";
        
        if(mysqli_query($con, $query)) {
            header("Location: ../views/admin/pisos_admin.php?sede_id=$sede_id&msg=created");
        } else {
            header("Location: ../views/admin/pisos_admin.php?sede_id=$sede_id&error=create_failed");
        }
        
    } else if($accion == 'editar') {
        // Editar piso existente
        $piso_id = intval($_POST['piso_id']);
        
        // ⭐ VALIDAR QUE EL PISO PERTENEZCA A LA SEDE
        $query = "UPDATE pisos 
                  SET nombre='$nombre', descripcion='$descripcion' 
                  WHERE id=$piso_id AND sede_id=$sede_id";
        
        if(mysqli_query($con, $query)) {
            header("Location: ../views/admin/pisos_admin.php?sede_id=$sede_id&msg=updated");
        } else {
            header("Location: ../views/admin/pisos_admin.php?sede_id=$sede_id&error=update_failed");
        }
    }
} else {
    header("Location: ../views/admin/index_sedes.php");
}

mysqli_close($con);
?>