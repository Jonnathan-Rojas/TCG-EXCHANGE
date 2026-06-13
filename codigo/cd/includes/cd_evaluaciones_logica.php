<?php
declare(strict_types=1);

/**
 * Wrappers de logica de evaluaciones acotados al hub emisor de sesion (cd).
 */

require_once __DIR__ . '/cd_logica.php';
require_once __DIR__ . '/../../admin/includes/evaluaciones_logica.php';

// INICIO BLOQUE: LISTADO Y LECTURA ACOTADOS AL HUB
/**
 * Evaluaciones emitidas por el hub de sesion (idtienda).
 */
function tcgx_cd_evaluaciones_listar(PDO $pdo, int $idHub): array
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
    $stmt->execute([$idHub]);
    return $stmt->fetchAll();
}

/**
 * Obtiene evaluacion verificando idtienda = hub de sesion.
 */
function tcgx_cd_evaluaciones_obtener(PDO $pdo, int $id, int $idHub): ?array
{
    $evaluacion = tcgx_evaluaciones_obtener($pdo, $id);
    if ($evaluacion === null || (int) ($evaluacion['idtienda'] ?? 0) !== $idHub) {
        return null;
    }
    return $evaluacion;
}
// FIN BLOQUE: LISTADO Y LECTURA ACOTADOS AL HUB


// INICIO BLOQUE: VALIDACION CON HUB FIJO
/**
 * Valida alta o edicion forzando idtienda = hub de sesion.
 */
function tcgx_cd_evaluaciones_validar(PDO $pdo, array $post, int $idHubSesion): array
{
    $post['idtienda'] = (string) $idHubSesion;
    return tcgx_evaluaciones_validar($pdo, $post);
}
// FIN BLOQUE: VALIDACION CON HUB FIJO
