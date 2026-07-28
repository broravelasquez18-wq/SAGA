<?php
// ── Credenciales Twilio (vienen de .env / variables de entorno) ────
require_once __DIR__ . '/env.php';
cargarEnv();

define('TWILIO_ACCOUNT_SID', getenv('TWILIO_ACCOUNT_SID') ?: 'ACXXXXXXXX');
define('TWILIO_AUTH_TOKEN',  getenv('TWILIO_AUTH_TOKEN') ?: '');
define('TWILIO_FROM',        getenv('TWILIO_FROM') ?: '');

/**
 * Envía un SMS usando la API REST de Twilio.
 * @param string $to      Número destino con prefijo país (ej: +573001234567)
 * @param string $mensaje Texto del SMS
 * @return bool
 */
function enviarSMS(string $to, string $mensaje): bool {
    if(empty($to) || empty(TWILIO_FROM) || TWILIO_ACCOUNT_SID === 'ACXXXXXXXX') {
        return false;
    }

    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Messages.json';

    $data = http_build_query([
        'From' => TWILIO_FROM,
        'To'   => preg_replace('/[^0-9+]/', '', $to),
        'Body' => $mensaje,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERPWD        => TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN,
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    return $http_code === 201 && isset($result['sid']);
}
?>
