<?php
date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/env.php';
cargarEnv();

function conexion(){
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    $db   = getenv('DB_NAME');

    if ($host === false || $user === false || $db === false) {
        error_log('Faltan variables de entorno DB_HOST/DB_USER/DB_NAME');
        die('Error de configuración del servidor.');
    }

    $con = mysqli_connect($host, $user, $pass, $db);
    return $con;
}
?>