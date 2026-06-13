<?php
declare(strict_types=1);

/**
 * Endpoint AJAX (solo POST) para el autocompletado Select2 del remitente/destinatario del envio.
 * Busca clientes (perfil CLIENTE) SOLO POR NOMBRE. Seguridad: sesion ADMINISTRADOR + token CSRF del modulo envios.
 */

// INICIO BLOQUE: ARRANQUE Y DEPENDENCIAS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/envios_logica.php';

header('Content-Type: application/json; charset=utf-8');
// FIN BLOQUE: ARRANQUE Y DEPENDENCIAS


// INICIO BLOQUE: VALIDACION DE METODO Y CSRF
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['results' => []]);
    exit;
}

$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
$tokenSesion = (string) ($_SESSION['tcgx_envios_csrf'] ?? '');
if ($tokenSesion === '' || $tokenPost === '' || !hash_equals($tokenSesion, $tokenPost)) {
    http_response_code(403);
    echo json_encode(['results' => []]);
    exit;
}
// FIN BLOQUE: VALIDACION DE METODO Y CSRF


// INICIO BLOQUE: BUSQUEDA Y RESPUESTA EN FORMATO SELECT2
$termino = (string) ($_POST['q'] ?? '');
$clientes = tcgx_envios_buscar_clientes(Bd::getPdo(), $termino);

$resultados = [];
foreach ($clientes as $cliente) {
    // text combina nombre y cedula para que el usuario identifique al cliente en el desplegable.
    $resultados[] = [
        'id' => (string) $cliente['id'],
        'text' => (string) $cliente['nombre'] . ' (' . (string) $cliente['id'] . ')',
    ];
}

echo json_encode(['results' => $resultados], JSON_UNESCAPED_UNICODE);
// FIN BLOQUE: BUSQUEDA Y RESPUESTA EN FORMATO SELECT2
