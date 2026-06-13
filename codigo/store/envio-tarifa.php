<?php
declare(strict_types=1);

/**
 * Endpoint AJAX (solo POST) que devuelve el precio unico de la tarifa para la tienda de sesion y forma de envio (ruta).
 * Vista previa del montoapagar en el registro de envios; el servidor recalcula al guardar.
 * Seguridad: sesion TIENDA (no hub) + token CSRF del modulo envios + idtienda acotado a la tienda de sesion.
 */

// INICIO BLOQUE: ARRANQUE Y DEPENDENCIAS
require __DIR__ . '/includes/carga_sesion_store.php';
require_once __DIR__ . '/includes/store_envios_logica.php';

header('Content-Type: application/json; charset=utf-8');
// FIN BLOQUE: ARRANQUE Y DEPENDENCIAS


// INICIO BLOQUE: VALIDACION DE METODO Y CSRF
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'precio' => null]);
    exit;
}

$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
$tokenSesion = (string) ($_SESSION['tcgx_envios_csrf'] ?? '');
if ($tokenSesion === '' || $tokenPost === '' || !hash_equals($tokenSesion, $tokenPost)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'precio' => null]);
    exit;
}
// FIN BLOQUE: VALIDACION DE METODO Y CSRF


// INICIO BLOQUE: RESOLUCION DE RUTA Y CONSULTA DE PRECIO (SOLO TIENDA DE SESION)
$idTiendaRaw = trim((string) ($_POST['idtienda'] ?? ''));
$forma = mb_strtoupper(trim((string) ($_POST['formaenvio'] ?? '')), 'UTF-8');

if (!ctype_digit($idTiendaRaw) || $forma === '') {
    echo json_encode(['ok' => false, 'precio' => null]);
    exit;
}

$idTiendaSolicitada = (int) $idTiendaRaw;
// La tienda solo puede consultar tarifas de su propia sesion (origen fijo en registro store).
if ($idTiendaSolicitada !== $idTiendaSesion) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'precio' => null]);
    exit;
}

$idRuta = tcgx_envios_ruta_id(Bd::getPdo(), $forma);
if ($idRuta === null) {
    echo json_encode(['ok' => false, 'precio' => null]);
    exit;
}

$precio = tcgx_envios_precio_tarifa(Bd::getPdo(), $idTiendaSesion, $idRuta);
echo json_encode(['ok' => $precio !== null, 'precio' => $precio]);
// FIN BLOQUE: RESOLUCION DE RUTA Y CONSULTA DE PRECIO (SOLO TIENDA DE SESION)
