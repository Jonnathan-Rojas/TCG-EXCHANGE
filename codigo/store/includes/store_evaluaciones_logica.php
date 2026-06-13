<?php
declare(strict_types=1);

/**
 * Wrappers de logica de evaluaciones acotados a la tienda emisora de sesion (store).
 */

require_once __DIR__ . '/store_logica.php';
require_once __DIR__ . '/../../admin/includes/evaluaciones_logica.php';

// INICIO BLOQUE: LISTADO Y LECTURA ACOTADOS A TIENDA
/**
 * Evaluaciones emitidas por la tienda de sesion.
 */
function tcgx_store_evaluaciones_listar(PDO $pdo, int $idTienda): array
{
    $sql = 'SELECT e.id, e.idusuario, e.idtienda, e.rapidez, e.confianza, e.seguridad, e.calidad, '
        . 'e.listanegra, e.motivolistanegra, e.fecharegistro, '
        . 'u.nombre AS nombreusuario, ti.nombre AS nombretienda '
        . 'FROM evaluaciones e '
        . 'LEFT JOIN usuarios u ON u.id = e.idusuario '
        . 'LEFT JOIN tiendas ti ON ti.id = e.idtienda '
        . 'WHERE e.idtienda = ? '
        . 'ORDER BY e.fecharegistro DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idTienda]);
    return $stmt->fetchAll();
}

/**
 * Obtiene evaluacion verificando idtienda = tienda de sesion.
 */
function tcgx_store_evaluaciones_obtener(PDO $pdo, int $id, int $idTienda): ?array
{
    $evaluacion = tcgx_evaluaciones_obtener($pdo, $id);
    if ($evaluacion === null || (int) ($evaluacion['idtienda'] ?? 0) !== $idTienda) {
        return null;
    }
    return $evaluacion;
}
// FIN BLOQUE: LISTADO Y LECTURA ACOTADOS A TIENDA


// INICIO BLOQUE: VALIDACION CON TIENDA FIJA
/**
 * Valida alta/edición forzando idtienda = tienda de sesion.
 */
function tcgx_store_evaluaciones_validar(PDO $pdo, array $post, int $idTiendaSesion): array
{
    $post['idtienda'] = (string) $idTiendaSesion;
    return tcgx_evaluaciones_validar($pdo, $post);
}
// FIN BLOQUE: VALIDACION CON TIENDA FIJA
