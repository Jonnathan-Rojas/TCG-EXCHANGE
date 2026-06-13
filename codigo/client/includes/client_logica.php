<?php
declare(strict_types=1);

/**
 * Utilidades transversales del modulo client: revalidacion operativa y comprobaciones de pertenencia.
 */

// INICIO BLOQUE: REVALIDACION DE USUARIO CLIENTE EN ACCIONES SENSIBLES
/**
 * Relee usuario y lista negra en BD antes de mutaciones sensibles.
 * Retorna null si todo OK, o mensaje de error en MAYUSCULAS.
 */
function tcgx_client_revalidar_operacion(PDO $pdo, string $idUsuario): ?string
{
    $stmt = $pdo->prepare(
        'SELECT u.estado AS estadousuario FROM usuarios u '
        . 'WHERE u.id = ? AND u.perfil = \'CLIENTE\' AND u.idtienda IS NULL LIMIT 1'
    );
    $stmt->execute([$idUsuario]);
    $fila = $stmt->fetch();
    if ($fila === false) {
        return 'SESION NO VALIDA PARA ESTE CLIENTE.';
    }
    if ((string) $fila['estadousuario'] !== 'ACTIVO') {
        return 'SU USUARIO NO ESTA ACTIVO.';
    }
    $stmtEv = $pdo->prepare('SELECT listanegra FROM evaluaciones WHERE idusuario = ? LIMIT 1');
    $stmtEv->execute([$idUsuario]);
    $ev = $stmtEv->fetch();
    if ($ev !== false && (int) ($ev['listanegra'] ?? 0) === 1) {
        return 'OPERACION BLOQUEADA POR LISTA NEGRA.';
    }
    return null;
}
// FIN BLOQUE: REVALIDACION DE USUARIO CLIENTE EN ACCIONES SENSIBLES
