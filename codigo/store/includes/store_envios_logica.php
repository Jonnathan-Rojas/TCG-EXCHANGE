<?php
declare(strict_types=1);

/**
 * Wrappers de logica de envios acotados a la tienda de sesion (store).
 * Reutiliza funciones de admin/includes/envios_logica.php para mutaciones y catalogos.
 */

require_once __DIR__ . '/store_logica.php';
require_once __DIR__ . '/../../admin/includes/envios_logica.php';

// INICIO BLOQUE: LISTADO Y DETALLE ACOTADOS A TIENDA
/**
 * Listado de envios donde la tienda de sesion participa como origen o destino.
 */
function tcgx_store_envios_listar(PDO $pdo, int $idTienda): array
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
        . 'WHERE e.idtiendaorigen = ? OR e.idtiendadestino = ? '
        . 'ORDER BY e.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idTienda, $idTienda]);
    return $stmt->fetchAll();
}

/**
 * Obtiene un envio y verifica pertenencia a la tienda de sesion; null si no existe o no aplica.
 */
function tcgx_store_envios_obtener(PDO $pdo, string $id, int $idTienda): ?array
{
    $envio = tcgx_envios_obtener($pdo, $id);
    if ($envio === null || !tcgx_store_envio_pertenece($envio, $idTienda)) {
        return null;
    }
    return $envio;
}

/**
 * Tiendas destino elegibles (activas no-hub, excluyendo la tienda de sesion salvo EN TIENDA).
 */
function tcgx_store_envios_listar_tiendas_destino(PDO $pdo, int $idTiendaSesion): array
{
    $stmt = $pdo->prepare(
        "SELECT id, nombre FROM tiendas WHERE estado = 'ACTIVO' AND eshub = 0 AND id <> ? ORDER BY nombre ASC"
    );
    $stmt->execute([$idTiendaSesion]);
    return $stmt->fetchAll();
}
// FIN BLOQUE: LISTADO Y DETALLE ACOTADOS A TIENDA


// INICIO BLOQUE: VALIDACION DE REGISTRO CON ORIGEN FIJO
/**
 * Valida registro forzando idtiendaorigen = tienda de sesion.
 */
function tcgx_store_envios_validar_registro(PDO $pdo, array $post, int $idTiendaSesion): array
{
    $post['idtiendaorigen'] = (string) $idTiendaSesion;
    $validacion = tcgx_envios_validar_registro($pdo, $post);
    if ((int) ($validacion['datos']['idtiendaorigen'] ?? 0) !== $idTiendaSesion) {
        $validacion['errores'][] = 'LA TIENDA DE ORIGEN DEBE SER LA TIENDA DE SU SESION.';
    }
    return $validacion;
}
// FIN BLOQUE: VALIDACION DE REGISTRO CON ORIGEN FIJO


// INICIO BLOQUE: PERMISOS DE ACCIONES POR ROL DE TIENDA
/**
 * Indica si la tienda de sesion puede ejecutar cambiar destino (solo origen).
 */
function tcgx_store_envios_puede_cambiar_destino(array $envio, int $idTienda): bool
{
    return tcgx_store_es_origen($envio, $idTienda)
        && !tcgx_envios_es_local($envio)
        && tcgx_envios_puede_cambiar_destino((string) $envio['estado']);
}

function tcgx_store_envios_puede_cambiar_receptor(array $envio, int $idTienda): bool
{
    return tcgx_store_envio_pertenece($envio, $idTienda)
        && tcgx_envios_puede_cambiar_receptor((string) $envio['estado']);
}

function tcgx_store_envios_puede_cancelar(array $envio, int $idTienda): bool
{
    return tcgx_store_es_origen($envio, $idTienda)
        && tcgx_envios_puede_cancelar((string) $envio['estado']);
}

function tcgx_store_envios_puede_devolver(array $envio, int $idTienda): bool
{
    return tcgx_store_es_origen($envio, $idTienda)
        && tcgx_envios_puede_devolver((string) $envio['estado']);
}

/**
 * Entregar: LOCAL+origen o EN DESTINO+destino (diseño store).
 */
function tcgx_store_envios_puede_entregar(array $envio, int $idTienda): bool
{
    if (!tcgx_envios_puede_entregar($envio)) {
        return false;
    }
    $esLocal = tcgx_envios_es_local($envio);
    if ($esLocal) {
        return tcgx_store_es_origen($envio, $idTienda);
    }
    return tcgx_store_es_destino($envio, $idTienda)
        && (string) $envio['estado'] === TCGX_ENVIOS_ESTADO_EN_DESTINO;
}

function tcgx_store_envios_puede_despachar_directo(array $envio, int $idTienda): bool
{
    return tcgx_store_es_origen($envio, $idTienda)
        && tcgx_envios_puede_despachar_directo($envio);
}

function tcgx_store_envios_puede_recibir_destino(array $envio, int $idTienda): bool
{
    return tcgx_store_es_destino($envio, $idTienda)
        && tcgx_envios_puede_recibir_en_destino($envio);
}
// FIN BLOQUE: PERMISOS DE ACCIONES POR ROL DE TIENDA
