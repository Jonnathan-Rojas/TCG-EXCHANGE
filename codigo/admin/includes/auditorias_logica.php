<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del modulo de AUDITORIAS (admin).
 * Visor de solo lectura sobre la tabla auditorias: listado filtrable y detalle de eventos
 * (ACCESO, LOGOUT, CREAR, LEER, ACTUALIZAR, ELIMINAR). Solo consultas preparadas.
 * Fuente de verdad: basedatos.sql (chkauditoriasaccion) y diseño.md (trazabilidad).
 */

// INICIO BLOQUE: CONSTANTES DE ACCIONES DE AUDITORIA
// Valores alineados al CHECK chkauditoriasaccion de basedatos.sql (sin ENUM en BD).
const TCGX_AUD_ACCIONES = [
    'ACCESO',
    'LOGOUT',
    'CREAR',
    'LEER',
    'ACTUALIZAR',
    'ELIMINAR',
];
// FIN BLOQUE: CONSTANTES DE ACCIONES DE AUDITORIA


// INICIO BLOQUE: CATALOGOS PARA FILTROS
/**
 * Tablas distintas registradas en auditorias para el selector de filtro.
 */
function tcgx_auditorias_catalogo_tablas(PDO $pdo): array
{
    $filas = $pdo->query(
        'SELECT DISTINCT tablaafectada FROM auditorias '
        . 'WHERE tablaafectada IS NOT NULL AND tablaafectada <> \'\' '
        . 'ORDER BY tablaafectada ASC'
    )->fetchAll(PDO::FETCH_COLUMN);
    return is_array($filas) ? $filas : [];
}

/**
 * Usuarios que aparecen en auditorias (id, nombre) para filtro por actor.
 */
function tcgx_auditorias_catalogo_usuarios(PDO $pdo): array
{
    $sql = 'SELECT DISTINCT u.id, u.nombre FROM auditorias a '
        . 'INNER JOIN usuarios u ON u.id = a.idusuario '
        . 'WHERE a.idusuario IS NOT NULL AND a.idusuario <> \'\' '
        . 'ORDER BY u.nombre ASC';
    return $pdo->query($sql)->fetchAll();
}
// FIN BLOQUE: CATALOGOS PARA FILTROS


// INICIO BLOQUE: NORMALIZACION Y VALIDACION DE FILTROS
/**
 * Normaliza la entrada del formulario de filtros y descarta valores no permitidos.
 * Retorna claves: accion, tablaafectada, idusuario, fechadesde, fechahasta (cadenas vacias = sin filtro).
 */
function tcgx_auditorias_normalizar_filtros(array $entrada): array
{
    $accion = mb_strtoupper(trim((string) ($entrada['accion'] ?? '')), 'UTF-8');
    if ($accion !== '' && !in_array($accion, TCGX_AUD_ACCIONES, true)) {
        $accion = '';
    }

    $tabla = trim((string) ($entrada['tablaafectada'] ?? ''));
    if ($tabla !== '' && mb_strlen($tabla, 'UTF-8') > 100) {
        $tabla = '';
    }

    $idUsuario = trim((string) ($entrada['idusuario'] ?? ''));
    if ($idUsuario !== '' && mb_strlen($idUsuario, 'UTF-8') > 20) {
        $idUsuario = '';
    }

    $fechaDesde = trim((string) ($entrada['fechadesde'] ?? ''));
    $fechaHasta = trim((string) ($entrada['fechahasta'] ?? ''));

    if ($fechaDesde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
        $fechaDesde = '';
    }
    if ($fechaHasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
        $fechaHasta = '';
    }
    if ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta) {
        $tmp = $fechaDesde;
        $fechaDesde = $fechaHasta;
        $fechaHasta = $tmp;
    }

    return [
        'accion' => $accion,
        'tablaafectada' => $tabla,
        'idusuario' => $idUsuario,
        'fechadesde' => $fechaDesde,
        'fechahasta' => $fechaHasta,
    ];
}
// FIN BLOQUE: NORMALIZACION Y VALIDACION DE FILTROS


// INICIO BLOQUE: LISTADO Y DETALLE DE AUDITORIAS
/**
 * Lista eventos de auditoria aplicando filtros opcionales; limite de seguridad para no saturar la vista.
 */
function tcgx_auditorias_listar(PDO $pdo, array $filtros): array
{
    $where = [];
    $params = [];

    if (($filtros['accion'] ?? '') !== '') {
        $where[] = 'a.accion = ?';
        $params[] = $filtros['accion'];
    }
    if (($filtros['tablaafectada'] ?? '') !== '') {
        $where[] = 'a.tablaafectada = ?';
        $params[] = $filtros['tablaafectada'];
    }
    if (($filtros['idusuario'] ?? '') !== '') {
        $where[] = 'a.idusuario = ?';
        $params[] = $filtros['idusuario'];
    }
    if (($filtros['fechadesde'] ?? '') !== '') {
        $where[] = 'DATE(a.fechaevento) >= ?';
        $params[] = $filtros['fechadesde'];
    }
    if (($filtros['fechahasta'] ?? '') !== '') {
        $where[] = 'DATE(a.fechaevento) <= ?';
        $params[] = $filtros['fechahasta'];
    }

    $sql = 'SELECT a.id, a.fechaevento, a.idusuario, u.nombre AS nombreusuario, '
        . 'a.accion, a.tablaafectada, a.idregistro '
        . 'FROM auditorias a '
        . 'LEFT JOIN usuarios u ON u.id = a.idusuario ';
    if ($where !== []) {
        $sql .= 'WHERE ' . implode(' AND ', $where) . ' ';
    }
    $sql .= 'ORDER BY a.fechaevento DESC, a.id DESC LIMIT 2000';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Lee un evento de auditoria por id con JSON de datos; retorna la fila o null si no existe.
 */
function tcgx_auditorias_obtener(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT a.id, a.fechaevento, a.idusuario, u.nombre AS nombreusuario, '
        . 'a.accion, a.tablaafectada, a.idregistro, a.datosantes, a.datosdespues '
        . 'FROM auditorias a '
        . 'LEFT JOIN usuarios u ON u.id = a.idusuario '
        . 'WHERE a.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}
// FIN BLOQUE: LISTADO Y DETALLE DE AUDITORIAS


// INICIO BLOQUE: REGISTRO DE LECTURAS EN VISTAS DE DETALLE
/**
 * Inserta evento LEER en auditorias al abrir una vista de solo consulta (detalle de envio, incidencia, etc.).
 * Fallos silenciosos para no bloquear la navegacion del administrador.
 */
function tcgx_auditorias_registrar_lectura(PDO $pdo, ?string $idActor, string $tablaAfectada, string $idRegistro): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO auditorias (idusuario, accion, tablaafectada, idregistro, datosantes, datosdespues) '
            . 'VALUES (?, ?, ?, ?, NULL, NULL)'
        );
        $stmt->execute([$idActor, 'LEER', $tablaAfectada, $idRegistro]);
    } catch (Throwable) {
    }
}
// FIN BLOQUE: REGISTRO DE LECTURAS EN VISTAS DE DETALLE
