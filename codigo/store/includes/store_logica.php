<?php
declare(strict_types=1);

/**
 * Utilidades transversales del modulo store: alcance por tienda de sesion, revalidacion operativa
 * y comprobaciones de pertenencia de envios/consolidados/incidencias a la tienda del usuario.
 */

// INICIO BLOQUE: REVALIDACION DE USUARIO Y TIENDA EN ACCIONES SENSIBLES
/**
 * Relee usuario y tienda en BD antes de operaciones sensibles (diseño.md: no confiar solo en sesion).
 * Retorna null si todo OK, o mensaje de error en MAYUSCULAS si debe bloquearse la accion.
 */
function tcgx_store_revalidar_operacion(PDO $pdo, string $idUsuario, int $idTiendaSesion): ?string
{
    $stmt = $pdo->prepare(
        'SELECT u.estado AS estadousuario, t.estado AS estadotienda, t.eshub, t.nombre AS nombretienda '
        . 'FROM usuarios u INNER JOIN tiendas t ON t.id = u.idtienda '
        . 'WHERE u.id = ? AND u.idtienda = ? AND u.perfil = \'TIENDA\' LIMIT 1'
    );
    $stmt->execute([$idUsuario, $idTiendaSesion]);
    $fila = $stmt->fetch();
    if ($fila === false) {
        return 'SESION NO VALIDA PARA ESTA TIENDA.';
    }
    if ((string) $fila['estadousuario'] !== 'ACTIVO') {
        return 'SU USUARIO NO ESTA ACTIVO.';
    }
    if ((string) $fila['estadotienda'] !== 'ACTIVO') {
        return 'LA TIENDA NO ESTA ACTIVA.';
    }
    if ((int) ($fila['eshub'] ?? 0) === 1) {
        return 'ESTA TIENDA OPERA DESDE EL MODULO DE CENTRO DE DISTRIBUCION.';
    }
    $stmtEv = $pdo->prepare('SELECT listanegra FROM evaluaciones WHERE idusuario = ? LIMIT 1');
    $stmtEv->execute([$idUsuario]);
    $ev = $stmtEv->fetch();
    if ($ev !== false && (int) ($ev['listanegra'] ?? 0) === 1) {
        return 'OPERACION BLOQUEADA POR LISTA NEGRA.';
    }
    return null;
}
// FIN BLOQUE: REVALIDACION DE USUARIO Y TIENDA EN ACCIONES SENSIBLES


// INICIO BLOQUE: PERTENENCIA DE REGISTROS A LA TIENDA DE SESION
/**
 * Indica si la tienda de sesion participa en el envio como origen o destino.
 */
function tcgx_store_envio_pertenece(array $envio, int $idTienda): bool
{
    return (int) ($envio['idtiendaorigen'] ?? 0) === $idTienda
        || (int) ($envio['idtiendadestino'] ?? 0) === $idTienda;
}

/**
 * Indica si la tienda de sesion es la de origen del envio.
 */
function tcgx_store_es_origen(array $envio, int $idTienda): bool
{
    return (int) ($envio['idtiendaorigen'] ?? 0) === $idTienda;
}

/**
 * Indica si la tienda de sesion es la de destino del envio.
 */
function tcgx_store_es_destino(array $envio, int $idTienda): bool
{
    return (int) ($envio['idtiendadestino'] ?? 0) === $idTienda;
}

/**
 * Indica si la tienda puede gestionar el consolidado (salida tramo 1 en origen o recepcion tramo 2 en destino).
 */
function tcgx_store_consolidado_pertenece(array $consolidado, int $idTienda): bool
{
    $tramo = (string) ($consolidado['tipotramo'] ?? '');
    if ($tramo === 'ORIGEN A CENTRO DE DISTRIBUCION') {
        return (int) ($consolidado['idtiendaorigen'] ?? 0) === $idTienda;
    }
    if ($tramo === 'CENTRO DE DISTRIBUCION A DESTINO') {
        return (int) ($consolidado['idtiendadestino'] ?? 0) === $idTienda;
    }
    return false;
}
// FIN BLOQUE: PERTENENCIA DE REGISTROS A LA TIENDA DE SESION
