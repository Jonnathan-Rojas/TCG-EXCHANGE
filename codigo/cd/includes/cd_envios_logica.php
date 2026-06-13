<?php
declare(strict_types=1);

/**
 * Wrappers de logica de envios acotados al hub de sesion (cd).
 * Listado y acciones sobre envios con idhub = hub de sesion.
 */

require_once __DIR__ . '/cd_logica.php';
require_once __DIR__ . '/../../admin/includes/envios_logica.php';

// INICIO BLOQUE: LISTADO Y DETALLE ACOTADOS AL HUB
/**
 * Listado de envios que transitan por el hub de sesion (idhub).
 */
function tcgx_cd_envios_listar(PDO $pdo, int $idHub): array
{
    $sql = 'SELECT e.id, e.idremitente, e.iddestinatario, e.idtiendaorigen, e.idtiendadestino, '
        . 'e.formaenvio, e.montoapagar, e.estado, '
        . 'tor.nombre AS nombretiendaorigen, tde.nombre AS nombretiendadestino, '
        . 'ur.nombre AS nombreremitente, ud.nombre AS nombredestinatario '
        . 'FROM envios e '
        . 'LEFT JOIN tiendas tor ON tor.id = e.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'LEFT JOIN usuarios ur ON ur.id = e.idremitente '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
        . 'WHERE e.idhub = ? '
        . 'ORDER BY e.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idHub]);
    return $stmt->fetchAll();
}

/**
 * Obtiene un envio verificando idhub = hub de sesion.
 */
function tcgx_cd_envios_obtener(PDO $pdo, string $id, int $idHub): ?array
{
    $envio = tcgx_envios_obtener($pdo, $id);
    if ($envio === null || !tcgx_cd_envio_pertenece($envio, $idHub)) {
        return null;
    }
    return $envio;
}

/**
 * Tiendas destino activas (no hub) para cambio de destino desde el centro.
 */
function tcgx_cd_envios_listar_tiendas_destino(PDO $pdo): array
{
    return $pdo->query(
        "SELECT id, nombre FROM tiendas WHERE estado = 'ACTIVO' AND eshub = 0 ORDER BY nombre ASC"
    )->fetchAll();
}
// FIN BLOQUE: LISTADO Y DETALLE ACOTADOS AL HUB


// INICIO BLOQUE: PERMISOS DE ACCIONES INDIVIDUALES EN EL HUB
/**
 * Cambiar destino: envio aun no ha salido del centro de distribucion hacia destino.
 */
function tcgx_cd_envios_puede_cambiar_destino(array $envio, int $idHub): bool
{
    return tcgx_cd_envio_pertenece($envio, $idHub)
        && !tcgx_envios_es_local($envio)
        && tcgx_envios_puede_cambiar_destino((string) $envio['estado']);
}

function tcgx_cd_envios_puede_cambiar_receptor(array $envio, int $idHub): bool
{
    return tcgx_cd_envio_pertenece($envio, $idHub)
        && tcgx_envios_puede_cambiar_receptor((string) $envio['estado']);
}
// FIN BLOQUE: PERMISOS DE ACCIONES INDIVIDUALES EN EL HUB
