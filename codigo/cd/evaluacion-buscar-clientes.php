<?php
declare(strict_types=1);

/**
 * Endpoint AJAX (solo POST) que alimenta el autocompletado Select2 del cliente a evaluar (modulo cd).
 * Busca clientes CLIENTE por nombre o cedula y devuelve JSON con el formato que espera Select2.
 * Seguridad: requiere sesion TIENDA (carga_sesion_cd) y valida el token CSRF del modulo de evaluaciones.
 */

// INICIO BLOQUE: ARRANQUE Y DEPENDENCIAS
require __DIR__ . '/includes/carga_sesion_cd.php';
require __DIR__ . '/includes/cd_evaluaciones_logica.php';

header('Content-Type: application/json; charset=utf-8');
// FIN BLOQUE: ARRANQUE Y DEPENDENCIAS


// INICIO BLOQUE: VALIDACION DE METODO Y CSRF
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['results' => []]);
    exit;
}

$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
$tokenSesion = (string) ($_SESSION['tcgx_evaluaciones_csrf'] ?? '');
if ($tokenSesion === '' || $tokenPost === '' || !hash_equals($tokenSesion, $tokenPost)) {
    http_response_code(403);
    echo json_encode(['results' => []]);
    exit;
}
// FIN BLOQUE: VALIDACION DE METODO Y CSRF


// INICIO BLOQUE: BUSQUEDA Y RESPUESTA JSON (FORMATO SELECT2)
$termino = (string) ($_POST['q'] ?? '');
$filas = tcgx_evaluaciones_buscar_clientes(Bd::getPdo(), $termino, 20);

$resultados = [];
foreach ($filas as $fila) {
    $cedula = (string) $fila['id'];
    $nombre = (string) $fila['nombre'];
    $resultados[] = [
        'id' => $cedula,
        'text' => $nombre . ' (' . $cedula . ')',
        'nombre' => $nombre,
        'cedula' => $cedula,
    ];
}

echo json_encode(['results' => $resultados], JSON_UNESCAPED_UNICODE);
// FIN BLOQUE: BUSQUEDA Y RESPUESTA JSON (FORMATO SELECT2)
