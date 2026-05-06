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
    header("Location: ../views/admin/carga_masiva_ocupaciones.php?sede_id=1&error=sin_sede");
    exit();
}

if(!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../views/admin/carga_masiva_ocupaciones.php?sede_id=$sede_id&error=sin_archivo");
    exit();
}

$archivo = $_FILES['archivo'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if(!in_array($extension, ['xlsx', 'xls', 'csv'])) {
    header("Location: ../views/admin/carga_masiva_ocupaciones.php?sede_id=$sede_id&error=formato_invalido");
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $spreadsheet = IOFactory::load($archivo['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    if($highestRow < 2) {
        header("Location: ../views/admin/carga_masiva_ocupaciones.php?sede_id=$sede_id&error=archivo_vacio");
        exit();
    }

    // Leer headers
    $headers = [];
    for($col = 'A'; $col <= 'H'; $col++) {
        $headers[$col] = trim(strtolower($sheet->getCell($col . '1')->getValue()));
    }

    // Columnas obligatorias
    $requeridas = ['piso', 'ambiente', 'cedula_instructor', 'fecha', 'jornada'];
    foreach($requeridas as $r) {
        if(!in_array($r, $headers)) {
            header("Location: ../views/admin/carga_masiva_ocupaciones.php?sede_id=$sede_id&error=columnas_faltantes");
            exit();
        }
    }

    // Mapear columnas
    $col_piso        = array_search('piso', $headers);
    $col_ambiente    = array_search('ambiente', $headers);
    $col_cedula      = array_search('cedula_instructor', $headers);
    $col_fecha       = array_search('fecha', $headers);
    $col_jornada     = array_search('jornada', $headers);
    $col_hora_inicio = array_search('hora_inicio', $headers);
    $col_hora_fin    = array_search('hora_fin', $headers);
    $col_obs         = array_search('observaciones', $headers);

    $creados       = 0;
    $fallidos      = 0;
    $primera_fecha = null;
    $hoy = date('Y-m-d');

    // Procesar filas (máximo 200)
    for($row = 2; $row <= min($highestRow, 202); $row++) {
        $piso_nombre     = trim($sheet->getCell($col_piso . $row)->getValue());
        $ambiente_nombre = trim($sheet->getCell($col_ambiente . $row)->getValue());
        $cedula          = trim($sheet->getCell($col_cedula . $row)->getValue());
        $fecha           = trim($sheet->getCell($col_fecha . $row)->getFormattedValue());
        $jornada         = trim(strtolower($sheet->getCell($col_jornada . $row)->getValue()));
        $hora_inicio_raw = $col_hora_inicio ? trim($sheet->getCell($col_hora_inicio . $row)->getValue()) : '';
        $hora_fin_raw    = $col_hora_fin    ? trim($sheet->getCell($col_hora_fin . $row)->getValue()) : '';
        $observaciones   = $col_obs ? trim($sheet->getCell($col_obs . $row)->getValue()) : '';

        // Validar campos obligatorios vacíos
        if(empty($piso_nombre) || empty($ambiente_nombre) || empty($cedula) || empty($fecha) || empty($jornada)) {
            $fallidos++;
            continue;
        }

        // Normalizar fecha (puede venir como número serial de Excel)
        if(is_numeric($fecha) && strlen((string)$fecha) <= 5) {
            try {
                $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$fecha)->format('Y-m-d');
            } catch(Exception $e) {
                $fallidos++;
                continue;
            }
        }

        // Validar formato de fecha
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha);
        if(!$fecha_obj || $fecha_obj->format('Y-m-d') !== $fecha) {
            $fallidos++;
            continue;
        }

        // No permitir fechas pasadas
        if($fecha < $hoy) {
            $fallidos++;
            continue;
        }

        // Validar jornada
        $jornadas_validas = ['mañana', 'tarde', 'noche', 'personalizado'];
        if(!in_array($jornada, $jornadas_validas)) {
            $fallidos++;
            continue;
        }

        // Determinar horarios
        $hora_inicio = '';
        $hora_fin    = '';
        $jornada_bd  = $jornada;

        if($jornada == 'personalizado') {
            $hora_inicio = $hora_inicio_raw;
            $hora_fin    = $hora_fin_raw;

            if(empty($hora_inicio) || empty($hora_fin)) {
                $fallidos++;
                continue;
            }
            if($hora_fin <= $hora_inicio) {
                $fallidos++;
                continue;
            }
            if($hora_inicio < '06:00' || $hora_fin > '22:00') {
                $fallidos++;
                continue;
            }
        } else {
            switch($jornada) {
                case 'mañana': $hora_inicio = '06:00'; $hora_fin = '12:00'; break;
                case 'tarde':  $hora_inicio = '12:00'; $hora_fin = '18:00'; break;
                case 'noche':  $hora_inicio = '18:00'; $hora_fin = '22:00'; break;
            }
        }

        // Validar que la jornada no haya terminado si es hoy
        if($fecha == $hoy && $hora_fin <= date('H:i')) {
            $fallidos++;
            continue;
        }

        // Buscar piso en la sede
        $piso_sql = "SELECT id FROM pisos
                     WHERE LOWER(nombre) = '" . mysqli_real_escape_string($con, strtolower($piso_nombre)) . "'
                     AND sede_id = $sede_id LIMIT 1";
        $piso_result = mysqli_query($con, $piso_sql);
        if(mysqli_num_rows($piso_result) == 0) {
            $fallidos++;
            continue;
        }
        $piso_id = mysqli_fetch_assoc($piso_result)['id'];

        // Buscar ambiente en el piso
        $amb_sql = "SELECT id FROM ambientes
                    WHERE LOWER(nombre) = '" . mysqli_real_escape_string($con, strtolower($ambiente_nombre)) . "'
                    AND piso_id = $piso_id LIMIT 1";
        $amb_result = mysqli_query($con, $amb_sql);
        if(mysqli_num_rows($amb_result) == 0) {
            $fallidos++;
            continue;
        }
        $ambiente_id = mysqli_fetch_assoc($amb_result)['id'];

        // Buscar instructor por cédula
        $cedula_esc = mysqli_real_escape_string($con, $cedula);
        $inst_sql = "SELECT id FROM usuarios
                     WHERE cedula = '$cedula_esc' AND rol = 'instructor' AND estado = 'activo' LIMIT 1";
        $inst_result = mysqli_query($con, $inst_sql);
        if(mysqli_num_rows($inst_result) == 0) {
            $fallidos++;
            continue;
        }
        $usuario_id = mysqli_fetch_assoc($inst_result)['id'];

        $fecha_inicio_dt = $fecha . ' ' . $hora_inicio . ':00';
        $fecha_fin_dt    = $fecha . ' ' . $hora_fin    . ':00';

        // Verificar conflicto de ambiente
        $check_amb = "SELECT id FROM historial_ocupacion
                      WHERE ambiente_id = $ambiente_id
                      AND DATE(fecha_inicio) = '$fecha'
                      AND estado NOT IN ('finalizado','cancelado')
                      AND (fecha_inicio < '$fecha_fin_dt' AND fecha_fin > '$fecha_inicio_dt')";
        if(mysqli_num_rows(mysqli_query($con, $check_amb)) > 0) {
            $fallidos++;
            continue;
        }

        // Verificar conflicto de instructor
        $check_inst = "SELECT id FROM historial_ocupacion
                       WHERE usuario_id = $usuario_id
                       AND DATE(fecha_inicio) = '$fecha'
                       AND estado NOT IN ('finalizado','cancelado')
                       AND (fecha_inicio < '$fecha_fin_dt' AND fecha_fin > '$fecha_inicio_dt')";
        if(mysqli_num_rows(mysqli_query($con, $check_inst)) > 0) {
            $fallidos++;
            continue;
        }

        // Determinar estado inicial
        $estado_inicial = 'ocupado';
        if($fecha == $hoy) {
            $ahora   = new DateTime();
            $fin_obj = new DateTime($fecha_fin_dt);
            $ini_obj = new DateTime($fecha_inicio_dt);
            $antes30 = clone $fin_obj;
            $antes30->modify('-30 minutes');

            if($ahora >= $fin_obj)     $estado_inicial = 'disponible';
            elseif($ahora >= $antes30) $estado_inicial = 'proximo_a_desocupar';
            else                       $estado_inicial = 'ocupado';
        }

        $obs_esc = mysqli_real_escape_string($con, $observaciones);

        // Insertar ocupación
        $insert_sql = "INSERT INTO historial_ocupacion
                       (ambiente_id, usuario_id, fecha_inicio, fecha_fin, estado, observaciones, jornada)
                       VALUES
                       ($ambiente_id, $usuario_id, '$fecha_inicio_dt', '$fecha_fin_dt', '$estado_inicial', '$obs_esc', '$jornada_bd')";

        if(mysqli_query($con, $insert_sql)) {
            $creados++;
            if($primera_fecha === null) $primera_fecha = $fecha;
            if($estado_inicial == 'ocupado') {
                mysqli_query($con, "UPDATE ambientes SET estado='ocupado' WHERE id=$ambiente_id");
            }
        } else {
            $fallidos++;
            error_log("Error SQL carga ocupaciones: " . mysqli_error($con));
        }
    }

    if($creados > 0 && $primera_fecha !== null) {
        $mes_cal  = intval(date('n', strtotime($primera_fecha)));
        $anio_cal = intval(date('Y', strtotime($primera_fecha)));
        header("Location: ../views/admin/calendario_ocupaciones.php?sede_id=$sede_id&mes=$mes_cal&anio=$anio_cal&msg=carga_exitosa&creados=$creados&fallidos=$fallidos");
    } else {
        header("Location: ../views/admin/carga_masiva_ocupaciones.php?sede_id=$sede_id&msg=carga_exitosa&creados=$creados&fallidos=$fallidos");
    }
    exit();

} catch(Exception $e) {
    error_log("Error en carga masiva ocupaciones: " . $e->getMessage());
    header("Location: ../views/admin/carga_masiva_ocupaciones.php?sede_id=$sede_id&error=error_lectura&detalle=" . urlencode($e->getMessage()));
    exit();
}
?>
