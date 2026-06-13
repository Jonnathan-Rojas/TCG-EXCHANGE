<?php
declare(strict_types=1);

/**
 * Wrappers de logica de incidencias acotados a la tienda de sesion (store).
 */

require_once __DIR__ . '/store_logica.php';
require_once __DIR__ . '/../../admin/includes/incidencias_logica.php';

// INICIO BLOQUE: LISTADO Y DETALLE ACOTADOS A TIENDA
/**
 * Incidencias reportadas por la tienda o cuyo envio toca la tienda de sesion.
 */
function tcgx_store_incidencias_listar(PDO $pdo, int $idTienda): array
{
    $sql = 'SELECT i.id, i.idenvio, i.tipoincidencia, i.estadoincidencia, i.detalleinicial, '
        . 'i.fechareporte, i.fechacierre, '
        . 'ur.nombre AS nombrereporta, tr.nombre AS nombretiendareporta, '
        . 'e.estado AS estadoenvio '
        . 'FROM incidencias i '
        . 'LEFT JOIN usuarios ur ON ur.id = i.idusuarioreporta '
        . 'LEFT JOIN tiendas tr ON tr.id = i.idtiendareporta '
        . 'LEFT JOIN envios e ON e.id = i.idenvio '
        . 'WHERE i.idtiendareporta = ? '
        . 'OR e.idtiendaorigen = ? OR e.idtiendadestino = ? '
        . 'ORDER BY i.fechareporte DESC, i.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idTienda, $idTienda, $idTienda]);
    return $stmt->fetchAll();
}

/**
 * Obtiene incidencia verificando alcance de tienda (reporta o envio relacionado).
 */
function tcgx_store_incidencias_obtener(PDO $pdo, int $id, int $idTienda): ?array
{
    $incidencia = tcgx_incidencias_obtener($pdo, $id);
    if ($incidencia === null) {
        return null;
    }
    if ((int) ($incidencia['idtiendareporta'] ?? 0) === $idTienda) {
        return $incidencia;
    }
    $envio = tcgx_envios_obtener($pdo, (string) $incidencia['idenvio']);
    if ($envio !== null && tcgx_store_envio_pertenece($envio, $idTienda)) {
        return $incidencia;
    }
    return null;
}
// FIN BLOQUE: LISTADO Y DETALLE ACOTADOS A TIENDA


// INICIO BLOQUE: VALIDACION DE REGISTRO CON TIENDA FIJA
/**
 * Valida registro forzando idtiendareporta = tienda de sesion y envio perteneciente.
 */
function tcgx_store_incidencias_validar_registro(PDO $pdo, array $post, int $idTiendaSesion, string $idUsuario): array
{
    $post['idtiendareporta'] = (string) $idTiendaSesion;
    $validacion = tcgx_incidencias_validar_registro($pdo, $post);
    if (!empty($validacion['errores'])) {
        return $validacion;
    }
    $idEnvio = (string) ($validacion['datos']['idenvio'] ?? '');
    $envio = tcgx_envios_obtener($pdo, $idEnvio);
    if ($envio === null || !tcgx_store_envio_pertenece($envio, $idTiendaSesion)) {
        $validacion['errores'][] = 'EL ENVIO NO PERTENECE A SU TIENDA.';
    }
    $validacion['datos']['idusuarioreporta'] = $idUsuario;
    $validacion['datos']['idtiendareporta'] = $idTiendaSesion;
    return $validacion;
}
// FIN BLOQUE: VALIDACION DE REGISTRO CON TIENDA FIJA
