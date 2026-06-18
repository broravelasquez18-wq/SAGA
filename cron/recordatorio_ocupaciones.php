<?php
/**
 * Script de recordatorio de ocupaciones — ejecutar cada 30 minutos via cron/Task Scheduler.
 *
 * Windows Task Scheduler:
 *   Programa : C:\xampp\php\php.exe
 *   Argumentos: C:\xampp\htdocs\SAGA\cron\recordatorio_ocupaciones.php
 *   Repetir cada: 30 minutos
 *
 * Linux cron (si se despliega en Linux):
 *   * /30 * * * * php /var/www/html/SAGA/cron/recordatorio_ocupaciones.php >> /var/log/saga_recordatorio.log 2>&1
 *
 * Requiere ejecutar migrate_recordatorio.php UNA vez para crear la tabla de control.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/sendgrid.php';

$con = conexion();

// Enviar recordatorio a ocupaciones que empiezan en los próximos 30 minutos
// y que aún no han recibido recordatorio.
$ahora     = date('Y-m-d H:i:s');
$en30min   = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$query = "SELECT ho.id, ho.fecha_inicio, ho.fecha_fin, ho.jornada,
                 a.nombre AS ambiente_nombre,
                 u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
          FROM historial_ocupacion ho
          LEFT JOIN ambientes a ON ho.ambiente_id = a.id
          LEFT JOIN usuarios u  ON ho.usuario_id  = u.id
          WHERE ho.estado NOT IN ('finalizado', 'cancelado')
            AND ho.fecha_inicio BETWEEN '$ahora' AND '$en30min'
            AND ho.id NOT IN (SELECT ocupacion_id FROM recordatorios_enviados)";

$result = mysqli_query($con, $query);

if(!$result) {
    echo "[ERROR] Query fallida: " . mysqli_error($con) . "\n";
    mysqli_close($con);
    exit(1);
}

$enviados = 0;
$fallidos = 0;

while($row = mysqli_fetch_assoc($result)) {
    $email   = $row['usuario_email']    ?? '';
    $nombre  = $row['usuario_nombre']   ?? '';
    $apellido= $row['usuario_apellido'] ?? '';
    $ambiente= $row['ambiente_nombre']  ?? '';
    $jornada = $row['jornada']          ?? '';
    $fecha   = date('Y-m-d', strtotime($row['fecha_inicio']));
    $hIni    = date('H:i',   strtotime($row['fecha_inicio']));
    $hFin    = date('H:i',   strtotime($row['fecha_fin']));

    if(empty($email)) {
        $fallidos++;
        continue;
    }

    $ok = enviarEmail(
        $email,
        "$nombre $apellido",
        'Recordatorio de ocupación - SAGA',
        emailRecordatorio($nombre, $apellido, $ambiente, $fecha, $jornada, $hIni, $hFin)
    );

    if($ok) {
        mysqli_query($con, "INSERT IGNORE INTO recordatorios_enviados (ocupacion_id) VALUES ({$row['id']})");
        $enviados++;
        echo "[OK] Recordatorio enviado a $email — ocupación #{$row['id']}\n";
    } else {
        $fallidos++;
        echo "[FAIL] No se pudo enviar a $email — ocupación #{$row['id']}\n";
    }
}

echo "Proceso finalizado: $enviados enviados, $fallidos fallidos.\n";
mysqli_close($con);
?>
