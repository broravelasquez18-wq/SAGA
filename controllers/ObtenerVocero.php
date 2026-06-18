<?php
session_start();
require_once "../config/conexion.php";
header('Content-Type: application/json');
$con = conexion();
if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $r  = mysqli_query($con, "SELECT * FROM usuarios WHERE id=$id AND rol='vocero'");
    if($r && mysqli_num_rows($r) > 0) {
        $v = mysqli_fetch_assoc($r);
        unset($v['contraseña']);
        echo json_encode(['success' => true, 'vocero' => $v]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
mysqli_close($con);
?>
