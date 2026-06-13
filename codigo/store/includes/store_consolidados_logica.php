<?php
declare(strict_types=1);

/**
 * Wrappers de logica de consolidados acotados a la tienda de sesion (store).
 * Tramo 1: origen = tienda sesion (armar, despachar, cancelar, sacar).
 * Tramo 2: destino = tienda sesion (solo recibir).
 */

require_once __DIR__ . '/store_logica.php';
require_once __DIR__ . '/../../admin/includes/consolidados_logica.php';

// INICIO BLOQUE: LISTADO ACOTADO A TIENDA
/**
 * Listado de consolidados donde la tienda participa en tramo 1 (origen) o tramo 2 (destino).
 */
function tcgx_store_consolidados_listar(PDO $pdo, int $idTienda): array
{
    $sql = 'SELECT c.id, c.tipotramo, c.idtiendaorigen, c.idtiendadestino, c.guiaexterna, c.estado, '
        . 'c.fechasalida, c.fecharecepcion, c.fecharegistro, '
        . 'tor.nombre AS nombreorigen, tde.nombre AS nombredestino, '
        . '(SELECT COUNT(DISTINCT d.idenvio) FROM detalle_consolidados d WHERE d.idconsolidado = c.id) AS totalenvios '
        . 'FROM consolidados c '
        . 'LEFT JOIN tiendas tor ON tor.id = c.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = c.idtiendadestino '
        . 'WHERE (c.tipotramo = ? AND c.idtiendaorigen = ?) '
        . 'OR (c.tipotramo = ? AND c.idtiendadestino = ?) '
        . 'ORDER BY c.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        TCGX_CONS_TRAMO_1,
        $idTienda,
        TCGX_CONS_TRAMO_2,
        $idTienda,
    ]);
    return $stmt->fetchAll();
}

/**
 * Obtiene consolidado verificando pertenencia a la tienda de sesion.
 */
function tcgx_store_consolidados_obtener(PDO $pdo, string $id, int $idTienda): ?array
{
    $consolidado = tcgx_consolidados_obtener($pdo, $id);
    if ($consolidado === null || !tcgx_store_consolidado_pertenece($consolidado, $idTienda)) {
        return null;
    }
    return $consolidado;
}
// FIN BLOQUE: LISTADO ACOTADO A TIENDA


// INICIO BLOQUE: PERMISOS DE ACCIONES POR TRAMO
/**
 * Despachar, cancelar y sacar envio: solo tramo 1 en tienda origen.
 */
function tcgx_store_consolidados_puede_despachar(array $consolidado, int $idTienda): bool
{
    return (string) ($consolidado['tipotramo'] ?? '') === TCGX_CONS_TRAMO_1
        && (int) ($consolidado['idtiendaorigen'] ?? 0) === $idTienda
        && (string) ($consolidado['estado'] ?? '') === TCGX_CONS_ESTADO_ARMADO;
}

function tcgx_store_consolidados_puede_cancelar(array $consolidado, int $idTienda): bool
{
    return (string) ($consolidado['tipotramo'] ?? '') === TCGX_CONS_TRAMO_1
        && (int) ($consolidado['idtiendaorigen'] ?? 0) === $idTienda
        && (string) ($consolidado['estado'] ?? '') === TCGX_CONS_ESTADO_ARMADO;
}

function tcgx_store_consolidados_puede_sacar(array $consolidado, int $idTienda): bool
{
    return (string) ($consolidado['tipotramo'] ?? '') === TCGX_CONS_TRAMO_1
        && (int) ($consolidado['idtiendaorigen'] ?? 0) === $idTienda
        && (string) ($consolidado['estado'] ?? '') === TCGX_CONS_ESTADO_ARMADO;
}

/**
 * Recibir consolidado: tramo 2 en tienda destino.
 */
function tcgx_store_consolidados_puede_recibir(array $consolidado, int $idTienda): bool
{
    return (string) ($consolidado['tipotramo'] ?? '') === TCGX_CONS_TRAMO_2
        && (int) ($consolidado['idtiendadestino'] ?? 0) === $idTienda
        && (string) ($consolidado['estado'] ?? '') === TCGX_CONS_ESTADO_EN_TRANSITO;
}
// FIN BLOQUE: PERMISOS DE ACCIONES POR TRAMO
