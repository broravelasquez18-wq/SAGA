<?php
date_default_timezone_set('America/Bogota');

function conexion(){
    $host = getenv('DB_HOST') ?: '193.203.175.84';
    $user = getenv('DB_USER') ?: 'u631215701_saga';
    $pass = getenv('DB_PASS') ?: 'Saga_2026';
    $db   = getenv('DB_NAME') ?: 'u631215701_saga';

    $con = mysqli_connect($host, $user, $pass, $db);
    return $con;
}
?>