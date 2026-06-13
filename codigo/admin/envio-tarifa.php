<?php
declare(strict_types=1);

/**
 * Endpoint AJAX (solo POST) que devuelve el precio unico de la tarifa para una tienda y forma de envio (ruta).
 * Es la vista previa del montoapagar (campo de solo lectura) del registro de envios; el servidor recalcula
 * el monto al guardar desde la tarifa de la tienda de origen, por lo que es la fuente de verdad.
 * NUNCA usa precio base. Seguridad: sesion ADMINISTRADOR + token CSRF del modulo envios.
 */

// INICIO BLOQUE: ARRANQUE Y DEPENDENCIAS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/envios_logica.php';

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


// INICIO BLOQUE: RESOLUCION DE RUTA Y CONSULTA DE PRECIO
$idTiendaRaw = trim((string) ($_POST['idtienda'] ?? ''));
$forma = mb_strtoupper(trim((string) ($_POST['formaenvio'] ?? '')), 'UTF-8');

if (!ctype_digit($idTiendaRaw) || $forma === '') {
    echo json_encode(['ok' => false, 'precio' => null]);
    exit;
}

$idRuta = tcgx_envios_ruta_id(Bd::getPdo(), $forma);
if ($idRuta === null) {
    echo json_encode(['ok' => false, 'precio' => null]);
    exit;
}

$precio = tcgx_envios_precio_tarifa(Bd::getPdo(), (int) $idTiendaRaw, $idRuta);
echo json_encode(['ok' => $precio !== null, 'precio' => $precio]);
// FIN BLOQUE: RESOLUCION DE RUTA Y CONSULTA DE PRECIO
