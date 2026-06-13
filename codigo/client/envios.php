<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE Y LISTADO DE ENVIOS DEL CLIENTE (REMITENTE)
require __DIR__ . '/includes/carga_sesion_client.php';
require_once __DIR__ . '/includes/client_envios_logica.php';

if (empty($_SESSION['tcgx_envios_csrf'])) {
    $_SESSION['tcgx_envios_csrf'] = bin2hex(random_bytes(32));
}
$tcgxEnviosCsrf = $_SESSION['tcgx_envios_csrf'];

$tcgxEnviosFlash = null;
if (isset($_SESSION['tcgx_envios_flash']) && is_array($_SESSION['tcgx_envios_flash'])) {
    $tcgxEnviosFlash = $_SESSION['tcgx_envios_flash'];
    unset($_SESSION['tcgx_envios_flash']);
}

$tcgxEnviosListadoTitulo = 'Envíos';
$tcgxEnviosListadoOrigen = 'envios';
$tcgxPageTitle = 'Envíos | TCG EXCHANGE';
$tcgxListaEnvios = tcgx_client_envios_listar(Bd::getPdo(), $idUsuarioVista, 'remitente');

require __DIR__ . '/includes/client_envios_listado_plantilla.php';
// FIN BLOQUE: ARRANQUE Y LISTADO DE ENVIOS DEL CLIENTE (REMITENTE)
