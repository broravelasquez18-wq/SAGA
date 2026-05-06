<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

if(!isset($_SESSION['id']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../views/home.php");
    exit();
}

require_once "../config/conexion.php";
require_once "../config/csrf.php";
$con = conexion();

csrf_validate();

$sede_id = isset($_POST['sede_id']) ? intval($_POST['sede_id']) : 0;

if($sede_id <= 0) {
    header("Location: ../views/admin/carga_masiva_ambientes.php?sede_id=1&error=sin_sede");
    exit();
}

if(!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../views/admin/carga_masiva_ambientes.php?sede_id=$sede_id&error=sin_archivo");
    exit();
}

$archivo = $_FILES['archivo'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if(!in_array($extension, ['xlsx', 'xls', 'csv'])) {
    header("Location: ../views/admin/carga_masiva_ambientes.php?sede_id=$sede_id&error=formato_invalido");
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $spreadsheet = IOFactory::load($archivo['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();
    
    if($highestRow < 2) {
        header("Location: ../views/admin/carga_masiva_ambientes.php?sede_id=$sede_id&error=archivo_vacio");
        exit();
    }
    
    // Leer headers
    $headers = [];
    $headers['A'] = trim(strtolower($sheet->getCell('A1')->getValue()));
    $headers['B'] = trim(strtolower($sheet->getCell('B1')->getValue()));
    $headers['C'] = trim(strtolower($sheet->getCell('C1')->getValue()));
    
    // Validar columnas mínimas (solo piso y nombre)
    if(!in_array('piso', $headers) || !in_array('nombre', $headers)) {
        header("Location: ../views/admin/carga_masiva_ambientes.php?sede_id=$sede_id&error=columnas_faltantes");
        exit();
    }
    
    // Mapear columnas
    $col_piso = array_search('piso', $headers);
    $col_nombre = array_search('nombre', $headers);
    $col_descripcion = array_search('descripcion', $headers);
    
    $ambientes_creados = 0;
    $ambientes_fallidos = 0;
    
    // Procesar filas
    for($row = 2; $row <= min($highestRow, 202); $row++) {
        $piso_nombre = trim($sheet->getCell($col_piso . $row)->getValue());
        $ambiente_nombre = trim($sheet->getCell($col_nombre . $row)->getValue());
        $descripcion = $col_descripcion ? trim($sheet->getCell($col_descripcion . $row)->getValue()) : '';
        
        // Validar campos vacíos
        if(empty($piso_nombre) || empty($ambiente_nombre)) {
            $ambientes_fallidos++;
            continue;
        }
        
        // Buscar piso
        $piso_sql = "SELECT id FROM pisos 
                     WHERE LOWER(nombre) = '" . mysqli_real_escape_string($con, strtolower($piso_nombre)) . "' 
                     AND sede_id = $sede_id LIMIT 1";
        $piso_result = mysqli_query($con, $piso_sql);
        
        if(mysqli_num_rows($piso_result) == 0) {
            $ambientes_fallidos++;
            continue;
        }
        
        $piso_id = mysqli_fetch_assoc($piso_result)['id'];
        
        // Verificar si existe
        $existe_sql = "SELECT id FROM ambientes 
                       WHERE LOWER(nombre) = '" . mysqli_real_escape_string($con, strtolower($ambiente_nombre)) . "' 
                       AND piso_id = $piso_id";
        $existe_result = mysqli_query($con, $existe_sql);
        
        if(mysqli_num_rows($existe_result) > 0) {
            $ambientes_fallidos++;
            continue;
        }
        
        // Insertar ambiente (SIN capacidad ni tipo)
        $insert_sql = "INSERT INTO ambientes (nombre, descripcion, piso_id, estado) 
                       VALUES (
                           '" . mysqli_real_escape_string($con, $ambiente_nombre) . "', 
                           '" . mysqli_real_escape_string($con, $descripcion) . "', 
                           $piso_id, 
                           'disponible'
                       )";
        
        if(mysqli_query($con, $insert_sql)) {
            $ambientes_creados++;
        } else {
            $ambientes_fallidos++;
            error_log("Error SQL: " . mysqli_error($con));
        }
    }
    
    header("Location: ../views/admin/carga_masiva_ambientes.php?sede_id=$sede_id&msg=carga_exitosa&creados=$ambientes_creados&fallidos=$ambientes_fallidos");
    exit();
    
} catch(Exception $e) {
    error_log("Error en carga masiva: " . $e->getMessage());
    header("Location: ../views/admin/carga_masiva_ambientes.php?sede_id=$sede_id&error=error_lectura&detalle=" . urlencode($e->getMessage()));
    exit();
}
?>