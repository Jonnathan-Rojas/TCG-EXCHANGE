<?php
declare(strict_types=1);

/**
 * Utilidades transversales del modulo cd (Centro de Distribucion): revalidacion operativa
 * y comprobaciones de pertenencia de envios/consolidados/incidencias al hub de sesion.
 */

// INICIO BLOQUE: REVALIDACION DE USUARIO Y HUB EN ACCIONES SENSIBLES
/**
 * Relee usuario y tienda hub en BD antes de operaciones sensibles.
 * Retorna null si todo OK, o mensaje de error en MAYUSCULAS si debe bloquearse la accion.
 */
function tcgx_cd_revalidar_operacion(PDO $pdo, string $idUsuario, int $idHubSesion): ?string
{
    $stmt = $pdo->prepare(
        'SELECT u.estado AS estadousuario, t.estado AS estadotienda, t.eshub, t.nombre AS nombretienda '
        . 'FROM usuarios u INNER JOIN tiendas t ON t.id = u.idtienda '
        . 'WHERE u.id = ? AND u.idtienda = ? AND u.perfil = \'TIENDA\' LIMIT 1'
    );
    $stmt->execute([$idUsuario, $idHubSesion]);
    $fila = $stmt->fetch();
    if ($fila === false) {
        return 'SESION NO VALIDA PARA ESTE CENTRO DE DISTRIBUCION.';
    }
    if ((string) $fila['estadousuario'] !== 'ACTIVO') {
        return 'SU USUARIO NO ESTA ACTIVO.';
    }
    if ((string) $fila['estadotienda'] !== 'ACTIVO') {
        return 'EL CENTRO DE DISTRIBUCION NO ESTA ACTIVO.';
    }
    if ((int) ($fila['eshub'] ?? 0) !== 1) {
        return 'ESTA TIENDA NO OPERA COMO CENTRO DE DISTRIBUCION.';
    }
    $stmtEv = $pdo->prepare('SELECT listanegra FROM evaluaciones WHERE idusuario = ? LIMIT 1');
    $stmtEv->execute([$idUsuario]);
    $ev = $stmtEv->fetch();
    if ($ev !== false && (int) ($ev['listanegra'] ?? 0) === 1) {
        return 'OPERACION BLOQUEADA POR LISTA NEGRA.';
    }
    return null;
}
// FIN BLOQUE: REVALIDACION DE USUARIO Y HUB EN ACCIONES SENSIBLES


// INICIO BLOQUE: PERTENENCIA DE REGISTROS AL HUB DE SESION
/**
 * Indica si el envio transita por el hub de sesion (idhub) o es operacion del hub como tienda reportadora.
 */
function tcgx_cd_envio_pertenece(array $envio, int $idHub): bool
{
    return (int) ($envio['idhub'] ?? 0) === $idHub;
}

/**
 * Indica si el consolidado pertenece al hub de sesion (idcentrodistribucion).
 */
function tcgx_cd_consolidado_pertenece(array $consolidado, int $idHub): bool
{
    return (int) ($consolidado['idcentrodistribucion'] ?? 0) === $idHub;
}
// FIN BLOQUE: PERTENENCIA DE REGISTROS AL HUB DE SESION
