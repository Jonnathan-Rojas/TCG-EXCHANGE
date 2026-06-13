<?php
declare(strict_types=1);

/**
 * Wrappers de logica de devoluciones acotados al hub de sesion (cd).
 */

require_once __DIR__ . '/cd_logica.php';
require_once __DIR__ . '/../../admin/includes/devoluciones_logica.php';

// INICIO BLOQUE: LISTADO Y DETALLE ACOTADOS AL HUB
/**
 * Envios en cadena DEVOLUCION% cuyo hub de sesion es responsable en algun paso (idhub).
 */
function tcgx_cd_devoluciones_listar(PDO $pdo, int $idHub): array
{
    $sql = 'SELECT e.id, e.idremitente, e.iddestinatario, e.idtiendaorigen, e.idtiendadestino, '
        . 'e.formaenvio, e.estado, e.idhub, '
        . 'tor.nombre AS nombretiendaorigen, tde.nombre AS nombretiendadestino, '
        . 'ur.nombre AS nombreremitente, ud.nombre AS nombredestinatario, '
        . '(SELECT MAX(m.fecharegistro) FROM movimientos_envio m WHERE m.idenvio = e.id) AS ultimomovimiento '
        . 'FROM envios e '
        . 'LEFT JOIN tiendas tor ON tor.id = e.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'LEFT JOIN usuarios ur ON ur.id = e.idremitente '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
        . 'WHERE e.estado LIKE \'DEVOLUCION%\' AND e.idhub = ? '
        . 'ORDER BY e.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idHub]);
    return $stmt->fetchAll();
}

/**
 * Obtiene envio en devolucion verificando idhub = hub de sesion.
 */
function tcgx_cd_devoluciones_obtener(PDO $pdo, string $idEnvio, int $idHub): ?array
{
    $envio = tcgx_devoluciones_obtener($pdo, $idEnvio);
    if ($envio === null || !tcgx_cd_envio_pertenece($envio, $idHub)) {
        return null;
    }
    return $envio;
}
// FIN BLOQUE: LISTADO Y DETALLE ACOTADOS AL HUB


// INICIO BLOQUE: PERMISO DE AVANCE EN PASOS DEL HUB
/**
 * Indica si el hub de sesion es responsable del siguiente paso en la cadena de devolucion.
 */
function tcgx_cd_devoluciones_puede_avanzar_hub(array $envio, int $idHub): bool
{
    if (!tcgx_devoluciones_puede_avanzar((string) $envio['estado'])) {
        return false;
    }
    $siguiente = tcgx_devoluciones_siguiente_estado((string) $envio['estado']);
    if ($siguiente === null) {
        return false;
    }
    $responsable = tcgx_devoluciones_tienda_responsable($envio, $siguiente);
    return $responsable !== null && $responsable === $idHub;
}
// FIN BLOQUE: PERMISO DE AVANCE EN PASOS DEL HUB
