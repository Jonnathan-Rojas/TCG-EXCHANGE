<?php
declare(strict_types=1);

/**
 * Wrappers de logica de incidencias acotados al hub de sesion (cd).
 */

require_once __DIR__ . '/cd_logica.php';
require_once __DIR__ . '/../../admin/includes/incidencias_logica.php';

// INICIO BLOQUE: LISTADO Y DETALLE ACOTADOS AL HUB
/**
 * Incidencias reportadas por el hub o cuyo envio transita por el hub (idhub).
 */
function tcgx_cd_incidencias_listar(PDO $pdo, int $idHub): array
{
    $sql = 'SELECT i.id, i.idenvio, i.tipoincidencia, i.estadoincidencia, i.detalleinicial, '
        . 'i.fechareporte, i.fechacierre, '
        . 'ur.nombre AS nombrereporta, tr.nombre AS nombretiendareporta, '
        . 'e.estado AS estadoenvio '
        . 'FROM incidencias i '
        . 'LEFT JOIN usuarios ur ON ur.id = i.idusuarioreporta '
        . 'LEFT JOIN tiendas tr ON tr.id = i.idtiendareporta '
        . 'LEFT JOIN envios e ON e.id = i.idenvio '
        . 'WHERE i.idtiendareporta = ? OR e.idhub = ? '
        . 'ORDER BY i.fechareporte DESC, i.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idHub, $idHub]);
    return $stmt->fetchAll();
}

/**
 * Obtiene incidencia verificando alcance del hub.
 */
function tcgx_cd_incidencias_obtener(PDO $pdo, int $id, int $idHub): ?array
{
    $incidencia = tcgx_incidencias_obtener($pdo, $id);
    if ($incidencia === null) {
        return null;
    }
    if ((int) ($incidencia['idtiendareporta'] ?? 0) === $idHub) {
        return $incidencia;
    }
    $envio = tcgx_envios_obtener($pdo, (string) $incidencia['idenvio']);
    if ($envio !== null && tcgx_cd_envio_pertenece($envio, $idHub)) {
        return $incidencia;
    }
    return null;
}
// FIN BLOQUE: LISTADO Y DETALLE ACOTADOS AL HUB


// INICIO BLOQUE: VALIDACION DE REGISTRO CON HUB FIJO
/**
 * Valida registro forzando idtiendareporta = hub de sesion y envio con idhub coherente.
 */
function tcgx_cd_incidencias_validar_registro(PDO $pdo, array $post, int $idHubSesion, string $idUsuario): array
{
    $post['idtiendareporta'] = (string) $idHubSesion;
    $validacion = tcgx_incidencias_validar_registro($pdo, $post);
    if (!empty($validacion['errores'])) {
        return $validacion;
    }
    $idEnvio = (string) ($validacion['datos']['idenvio'] ?? '');
    $envio = tcgx_envios_obtener($pdo, $idEnvio);
    if ($envio === null || !tcgx_cd_envio_pertenece($envio, $idHubSesion)) {
        $validacion['errores'][] = 'EL ENVIO NO TRANSITA POR ESTE CENTRO DE DISTRIBUCION.';
    }
    $validacion['datos']['idusuarioreporta'] = $idUsuario;
    $validacion['datos']['idtiendareporta'] = $idHubSesion;
    return $validacion;
}
// FIN BLOQUE: VALIDACION DE REGISTRO CON HUB FIJO
