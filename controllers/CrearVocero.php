<?php
session_start();
require_once "../config/conexion.php";
require_once "../config/csrf.php";
require_once "../config/sms.php";
require_once "../config/sendgrid.php";

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

$sede_id = intval($_POST['sede_id'] ?? 0);
$accion  = $_POST['accion'] ?? 'crear';

if($sede_id <= 0) {
    header("Location: ../views/admin/index_sedes.php");
    exit();
}

$cedula        = mysqli_real_escape_string($con, trim($_POST['cedula']));
$nombre        = mysqli_real_escape_string($con, trim($_POST['nombre']));
$apellido      = mysqli_real_escape_string($con, trim($_POST['apellido']));
$email         = mysqli_real_escape_string($con, trim($_POST['email']));
$telefono      = mysqli_real_escape_string($con, trim($_POST['telefono'] ?? ''));
$password      = isset($_POST['contrasena']) ? mysqli_real_escape_string($con, $_POST['contrasena']) : '';
$nivel_estudio    = mysqli_real_escape_string($con, trim($_POST['nivel_estudio']    ?? ''));
$programa_estudio = mysqli_real_escape_string($con, trim($_POST['programa_estudio'] ?? ''));
$fecha_inicio   = !empty($_POST['fecha_inicio_contrato']) ? mysqli_real_escape_string($con, $_POST['fecha_inicio_contrato']) : null;
$fecha_fin      = !empty($_POST['fecha_fin_contrato'])    ? mysqli_real_escape_string($con, $_POST['fecha_fin_contrato'])    : null;
$fecha_inicio_sql = $fecha_inicio ? "'$fecha_inicio'" : 'NULL';
$fecha_fin_sql    = $fecha_fin    ? "'$fecha_fin'"    : 'NULL';

if(empty($email)) {
    header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=email_vacio");
    exit();
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=email_invalido");
    exit();
}

// ── CREAR ────────────────────────────────────────────────────────
if($accion == 'crear') {
    $dup_email = mysqli_query($con, "SELECT id FROM usuarios WHERE email='$email'");
    if(mysqli_num_rows($dup_email) > 0) {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=email_duplicado");
        exit();
    }
    $dup_cedula = mysqli_query($con, "SELECT id FROM usuarios WHERE cedula='$cedula'");
    if(mysqli_num_rows($dup_cedula) > 0) {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=cedula_exists");
        exit();
    }
    if(empty($password)) {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=password_required");
        exit();
    }
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (cedula,email,nombre,apellido,contraseña,telefono,rol,nivel_estudio,programa_estudio,tipo_contrato,fecha_inicio_contrato,fecha_fin_contrato,estado,sede_id)
            VALUES ('$cedula','$email','$nombre','$apellido','$password_hash','$telefono','vocero','$nivel_estudio','$programa_estudio','planta',$fecha_inicio_sql,$fecha_fin_sql,'activo',$sede_id)";

    if(mysqli_query($con, $sql)) {
        if(!empty($telefono)) {
            $numero  = '+57' . preg_replace('/\D/', '', $telefono);
            $mensaje = "Bienvenido/a a SAGA, $nombre $apellido. Tu cuenta de Vocero ha sido creada. Ya puedes iniciar sesion con tu email.";
            enviarSMS($numero, $mensaje);
        }
        enviarEmail($email, "$nombre $apellido", 'Bienvenido/a a SAGA', emailBienvenida($nombre, $apellido, 'vocero'));
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&msg=vocero_creado");
    } else {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=create_failed");
    }

// ── EDITAR ───────────────────────────────────────────────────────
} else {
    $vocero_id = intval($_POST['vocero_id'] ?? 0);
    if($vocero_id <= 0) {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=invalid_id");
        exit();
    }

    $dup_email = mysqli_query($con, "SELECT id FROM usuarios WHERE email='$email' AND id != $vocero_id");
    if(mysqli_num_rows($dup_email) > 0) {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=email_duplicado");
        exit();
    }
    $dup_cedula = mysqli_query($con, "SELECT id FROM usuarios WHERE cedula='$cedula' AND id != $vocero_id");
    if(mysqli_num_rows($dup_cedula) > 0) {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=cedula_exists");
        exit();
    }

    $sql = "UPDATE usuarios SET cedula='$cedula',email='$email',nombre='$nombre',apellido='$apellido',
            telefono='$telefono',nivel_estudio='$nivel_estudio',programa_estudio='$programa_estudio',
            tipo_contrato='planta',fecha_inicio_contrato=$fecha_inicio_sql,fecha_fin_contrato=$fecha_fin_sql
            WHERE id=$vocero_id";
    if(!empty($password)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = str_replace("WHERE id", ", contraseña='$password_hash' WHERE id", $sql);
    }

    if(mysqli_query($con, $sql)) {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&msg=updated");
    } else {
        header("Location: ../views/admin/voceros_admin.php?sede_id=$sede_id&error=update_failed");
    }
}

mysqli_close($con);
exit();
?>
