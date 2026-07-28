<?php
/**
 * Carga variables desde .env (si existe) hacia el entorno del proceso.
 * En producción, en vez de .env se pueden setear las variables de entorno
 * reales del sitio (panel de hosting / systemd), y este loader simplemente
 * no encuentra el archivo y no hace nada.
 */
function cargarEnv(): void {
    static $cargado = false;
    if ($cargado) return;
    $cargado = true;

    $rutaEnv = __DIR__ . '/../.env';
    if (!is_readable($rutaEnv)) return;

    foreach (file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) continue;

        [$clave, $valor] = explode('=', $linea, 2);
        $clave = trim($clave);
        $valor = trim($valor, " \t\n\r\0\x0B\"'");

        if (getenv($clave) === false) {
            putenv("$clave=$valor");
        }
    }
}
