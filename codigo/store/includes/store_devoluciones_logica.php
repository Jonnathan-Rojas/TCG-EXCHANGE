<?php
declare(strict_types=1);

/**
 * Wrappers de logica de devoluciones acotados a la tienda de sesion (store).
 */

require_once __DIR__ . '/store_logica.php';
require_once __DIR__ . '/../../admin/includes/devoluciones_logica.php';

// INICIO BLOQUE: LISTADO Y DETALLE ACOTADOS A TIENDA
/**
 * Envios en cadena DEVOLUCION% que tocan la tienda de sesion como origen o destino.
 */
function tcgx_store_devoluciones_listar(PDO $pdo, int $idTienda): array
{
    $sql = 'SELECT e.id, e.idremitente, e.iddestinatario, e.idtiendaorigen, e.idtiendadestino, '
        . 'e.formaenvio, e.estado, '
        . 'tor.nombre AS nombretiendaorigen, tde.nombre AS nombretiendadestino, '
        . 'ur.nombre AS nombreremitente, ud.nombre AS nombredestinatario, '
        . '(SELECT MAX(m.fecharegistro) FROM movimientos_envio m WHERE m.idenvio = e.id) AS ultimomovimiento '
        . 'FROM envios e '
        . 'LEFT JOIN tiendas tor ON tor.id = e.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'LEFT JOIN usuarios ur ON ur.id = e.idremitente '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
        . 'WHERE e.estado LIKE \'DEVOLUCION%\' '
        . 'AND (e.idtiendaorigen = ? OR e.idtiendadestino = ?) '
        . 'ORDER BY e.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idTienda, $idTienda]);
    return $stmt->fetchAll();
}

/**
 * Obtiene envio en devolucion verificando pertenencia a la tienda de sesion.
 */
function tcgx_store_devoluciones_obtener(PDO $pdo, string $idEnvio, int $idTienda): ?array
{
    $envio = tcgx_devoluciones_obtener($pdo, $idEnvio);
    if ($envio === null || !tcgx_store_envio_pertenece($envio, $idTienda)) {
        return null;
    }
    return $envio;
}
// FIN BLOQUE: LISTADO Y DETALLE ACOTADOS A TIENDA


// INICIO BLOQUE: PERMISO DE AVANCE POR TIENDA RESPONSABLE
/**
 * Indica si la tienda de sesion es responsable del siguiente paso en la cadena de devolucion.
 */
function tcgx_store_devoluciones_puede_avanzar_tienda(array $envio, int $idTienda): bool
{
    if (!tcgx_devoluciones_puede_avanzar((string) $envio['estado'])) {
        return false;
    }
    $siguiente = tcgx_devoluciones_siguiente_estado((string) $envio['estado']);
    if ($siguiente === null) {
        return false;
    }
    $responsable = tcgx_devoluciones_tienda_responsable($envio, $siguiente);
    return $responsable !== null && $responsable === $idTienda;
}
// FIN BLOQUE: PERMISO DE AVANCE POR TIENDA RESPONSABLE
