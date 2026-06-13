<?php
declare(strict_types=1);

/**
 * Wrappers de logica de consolidados acotados al hub de sesion (cd).
 * Tramo 1: recibir en el centro (destino del consolidado = hub).
 * Tramo 2: armar, despachar, cancelar y sacar envios (origen del consolidado = hub).
 */

require_once __DIR__ . '/cd_logica.php';
require_once __DIR__ . '/../../admin/includes/consolidados_logica.php';

// INICIO BLOQUE: LISTADO ACOTADO AL HUB
/**
 * Listado de consolidados donde idcentrodistribucion = hub de sesion.
 */
function tcgx_cd_consolidados_listar(PDO $pdo, int $idHub): array
{
    $sql = 'SELECT c.id, c.tipotramo, c.idtiendaorigen, c.idtiendadestino, c.guiaexterna, c.estado, '
        . 'c.fechasalida, c.fecharecepcion, c.fecharegistro, '
        . 'tor.nombre AS nombreorigen, tde.nombre AS nombredestino, '
        . '(SELECT COUNT(DISTINCT d.idenvio) FROM detalle_consolidados d WHERE d.idconsolidado = c.id) AS totalenvios '
        . 'FROM consolidados c '
        . 'LEFT JOIN tiendas tor ON tor.id = c.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = c.idtiendadestino '
        . 'WHERE c.idcentrodistribucion = ? '
        . 'ORDER BY c.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idHub]);
    return $stmt->fetchAll();
}

/**
 * Obtiene consolidado verificando pertenencia al hub de sesion.
 */
function tcgx_cd_consolidados_obtener(PDO $pdo, string $id, int $idHub): ?array
{
    $consolidado = tcgx_consolidados_obtener($pdo, $id);
    if ($consolidado === null || !tcgx_cd_consolidado_pertenece($consolidado, $idHub)) {
        return null;
    }
    return $consolidado;
}
// FIN BLOQUE: LISTADO ACOTADO AL HUB


// INICIO BLOQUE: PERMISOS DE ACCIONES EN EL HUB
/**
 * Recibir consolidado tramo 1: EN TRANSITO hacia el hub.
 */
function tcgx_cd_consolidados_puede_recibir_tramo1(array $consolidado, int $idHub): bool
{
    return (string) ($consolidado['tipotramo'] ?? '') === TCGX_CONS_TRAMO_1
        && (int) ($consolidado['idtiendadestino'] ?? 0) === $idHub
        && (string) ($consolidado['estado'] ?? '') === TCGX_CONS_ESTADO_EN_TRANSITO;
}

/**
 * Despachar consolidado tramo 2: ARMADO con origen en el hub.
 */
function tcgx_cd_consolidados_puede_despachar_tramo2(array $consolidado, int $idHub): bool
{
    return (string) ($consolidado['tipotramo'] ?? '') === TCGX_CONS_TRAMO_2
        && (int) ($consolidado['idtiendaorigen'] ?? 0) === $idHub
        && (string) ($consolidado['estado'] ?? '') === TCGX_CONS_ESTADO_ARMADO;
}

function tcgx_cd_consolidados_puede_cancelar_tramo2(array $consolidado, int $idHub): bool
{
    return (string) ($consolidado['tipotramo'] ?? '') === TCGX_CONS_TRAMO_2
        && (int) ($consolidado['idtiendaorigen'] ?? 0) === $idHub
        && (string) ($consolidado['estado'] ?? '') === TCGX_CONS_ESTADO_ARMADO;
}

function tcgx_cd_consolidados_puede_sacar_tramo2(array $consolidado, int $idHub): bool
{
    return tcgx_cd_consolidados_puede_cancelar_tramo2($consolidado, $idHub);
}
// FIN BLOQUE: PERMISOS DE ACCIONES EN EL HUB
