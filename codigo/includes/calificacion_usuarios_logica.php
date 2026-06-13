<?php
declare(strict_types=1);

/**
 * Logica publica de Calificacion de Usuarios: clientes evaluados visibles en la red (sin lista negra).
 */

require_once __DIR__ . '/../admin/includes/evaluaciones_logica.php';

// INICIO BLOQUE: CONSULTA PUBLICA DE CLIENTES CALIFICADOS
/**
 * Clientes con evaluacion vigente, sin lista negra, ordenados alfabeticamente por nombre.
 *
 * @return list<array<string, mixed>>
 */
function tcgx_calificacion_usuarios_listar(PDO $pdo): array
{
    $sql = 'SELECT e.id, e.rapidez, e.confianza, e.seguridad, e.calidad, e.fecharegistro, '
        . 'u.nombre AS nombreusuario, u.provincia, u.canton '
        . 'FROM evaluaciones e '
        . 'INNER JOIN usuarios u ON u.id = e.idusuario '
        . 'WHERE e.listanegra = 0 '
        . 'AND u.estado = \'ACTIVO\' '
        . 'AND u.perfil = \'CLIENTE\' '
        . 'ORDER BY u.nombre ASC';

    $stmt = $pdo->query($sql);
    $filas = $stmt->fetchAll();
    $resultado = [];

    foreach ($filas as $fila) {
        $fila['reputacion'] = tcgx_evaluaciones_reputacion($fila);
        $fila['nombre_buscar'] = mb_strtoupper(trim((string) ($fila['nombreusuario'] ?? '')), 'UTF-8');
        $fila['provincia_buscar'] = mb_strtoupper(trim((string) ($fila['provincia'] ?? '')), 'UTF-8');
        $fila['canton_buscar'] = mb_strtoupper(trim((string) ($fila['canton'] ?? '')), 'UTF-8');
        $resultado[] = $fila;
    }

    return $resultado;
}
// FIN BLOQUE: CONSULTA PUBLICA DE CLIENTES CALIFICADOS
