<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del modulo de GESTION DE INCIDENCIAS (admin).
 * Una incidencia se asocia a un envio (CRE); su ciclo pasa por INCIDENCIA REPORTADA,
 * INCIDENCIA RESUELTA e INCIDENCIA CERRADA. Cada gestion registra una fila en
 * actualizaciones_incidencia con detalle, responsable y tienda. Al cerrar se completa fechacierre.
 * Solo consultas preparadas con parametros enlazados. Datos operativos en MAYUSCULAS.
 * Fuente de verdad: basedatos.sql y diseño.md (flujo tecnico de incidencias).
 */

require_once __DIR__ . '/envios_logica.php';

// INICIO BLOQUE: CONSTANTES DE ESTADO DE INCIDENCIA
// Valores alineados al CHECK chkincidenciasestado / chkactualizacionesincidenciaestado de basedatos.sql.
const TCGX_INC_ESTADO_REPORTADA = 'INCIDENCIA REPORTADA';
const TCGX_INC_ESTADO_RESUELTA = 'INCIDENCIA RESUELTA';
const TCGX_INC_ESTADO_CERRADA = 'INCIDENCIA CERRADA';
const TCGX_INC_ESTADOS = [
    TCGX_INC_ESTADO_REPORTADA,
    TCGX_INC_ESTADO_RESUELTA,
    TCGX_INC_ESTADO_CERRADA,
];
// FIN BLOQUE: CONSTANTES DE ESTADO DE INCIDENCIA


// INICIO BLOQUE: LISTADO Y DETALLE DE INCIDENCIAS
/**
 * Listado de incidencias con datos del envio, quien reporto y la tienda reportadora.
 */
function tcgx_incidencias_listar(PDO $pdo): array
{
    $sql = 'SELECT i.id, i.idenvio, i.tipoincidencia, i.estadoincidencia, i.detalleinicial, '
        . 'i.fechareporte, i.fechacierre, '
        . 'ur.nombre AS nombrereporta, tr.nombre AS nombretiendareporta, '
        . 'e.estado AS estadoenvio '
        . 'FROM incidencias i '
        . 'LEFT JOIN usuarios ur ON ur.id = i.idusuarioreporta '
        . 'LEFT JOIN tiendas tr ON tr.id = i.idtiendareporta '
        . 'LEFT JOIN envios e ON e.id = i.idenvio '
        . 'ORDER BY i.fechareporte DESC, i.id DESC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Lee una incidencia por su id con nombres resueltos; retorna la fila o null si no existe.
 */
function tcgx_incidencias_obtener(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT i.id, i.idenvio, i.tipoincidencia, i.estadoincidencia, i.detalleinicial, '
        . 'i.idusuarioreporta, i.idtiendareporta, i.fechareporte, i.fechacierre, '
        . 'ur.nombre AS nombrereporta, tr.nombre AS nombretiendareporta, '
        . 'e.estado AS estadoenvio, e.formaenvio '
        . 'FROM incidencias i '
        . 'LEFT JOIN usuarios ur ON ur.id = i.idusuarioreporta '
        . 'LEFT JOIN tiendas tr ON tr.id = i.idtiendareporta '
        . 'LEFT JOIN envios e ON e.id = i.idenvio '
        . 'WHERE i.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Historial cronologico de actualizaciones de una incidencia con nombres de usuario y tienda.
 */
function tcgx_incidencias_historial(PDO $pdo, int $idIncidencia): array
{
    $stmt = $pdo->prepare(
        'SELECT a.id, a.estadoincidencia, a.detalleactualizacion, a.fechaactualizacion, '
        . 'u.nombre AS nombreusuario, t.nombre AS nombretienda '
        . 'FROM actualizaciones_incidencia a '
        . 'LEFT JOIN usuarios u ON u.id = a.idusuarioactualiza '
        . 'LEFT JOIN tiendas t ON t.id = a.idtiendaactualiza '
        . 'WHERE a.idincidencia = ? ORDER BY a.fechaactualizacion ASC, a.id ASC'
    );
    $stmt->execute([$idIncidencia]);
    return $stmt->fetchAll();
}
// FIN BLOQUE: LISTADO Y DETALLE DE INCIDENCIAS


// INICIO BLOQUE: REGLAS DE GESTION Y ACTUALIZACION
/**
 * Indica si la incidencia admite nuevas actualizaciones (no esta INCIDENCIA CERRADA).
 */
function tcgx_incidencias_puede_actualizar(string $estado): bool
{
    return $estado !== TCGX_INC_ESTADO_CERRADA;
}

/**
 * Valida el formulario de actualizacion/seguimiento de una incidencia.
 * Retorna ['errores' => string[], 'datos' => ['estado' => ..., 'detalle' => ...]].
 */
function tcgx_incidencias_validar_actualizacion(array $post, array $incidencia): array
{
    $errores = [];

    if (!tcgx_incidencias_puede_actualizar((string) $incidencia['estadoincidencia'])) {
        $errores[] = 'LA INCIDENCIA YA ESTA CERRADA Y NO ADMITE MAS CAMBIOS.';
    }

    $estado = mb_strtoupper(trim((string) ($post['estadoincidencia'] ?? '')), 'UTF-8');
    if (!in_array($estado, TCGX_INC_ESTADOS, true)) {
        $errores[] = 'DEBE SELECCIONAR UN ESTADO VALIDO.';
    }

    $detalle = mb_strtoupper(trim((string) ($post['detalleactualizacion'] ?? '')), 'UTF-8');
    if ($detalle === '') {
        $errores[] = 'DEBE INDICAR EL DETALLE DE LA ACTUALIZACION.';
    } elseif (mb_strlen($detalle, 'UTF-8') > 255) {
        $errores[] = 'EL DETALLE NO PUEDE SUPERAR 255 CARACTERES.';
    }

    return [
        'errores' => $errores,
        'datos' => [
            'estado' => $estado,
            'detalle' => $detalle,
        ],
    ];
}

/**
 * Inserta fila de auditoria para operaciones sobre incidencias.
 */
function tcgx_incidencias_auditar(PDO $pdo, ?string $idActor, string $accion, string $idRegistro, ?array $antes, ?array $despues): void
{
    $jsonAntes = $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $jsonDespues = $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $stmt = $pdo->prepare(
        'INSERT INTO auditorias (idusuario, accion, tablaafectada, idregistro, datosantes, datosdespues) '
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idActor, $accion, 'incidencias', $idRegistro, $jsonAntes, $jsonDespues]);
}

/**
 * Registra una actualizacion de incidencia: cambia estado en cabecera, inserta historial y audita.
 * El administrador no tiene tienda propia; se usa idtiendareporta de la incidencia como contexto operativo.
 * Al pasar a INCIDENCIA CERRADA se completa fechacierre.
 * Retorna ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_incidencias_actualizar(PDO $pdo, array $incidencia, array $datos, ?string $idActor): array
{
    if (!tcgx_incidencias_puede_actualizar((string) $incidencia['estadoincidencia'])) {
        return ['ok' => false, 'error' => 'LA INCIDENCIA YA ESTA CERRADA Y NO ADMITE MAS CAMBIOS.'];
    }

    $estadoNuevo = $datos['estado'];
    $idTiendaActualiza = (int) $incidencia['idtiendareporta'];

    try {
        $pdo->beginTransaction();

        // Actualiza cabecera; si el nuevo estado es CERRADA, fija la fecha de cierre.
        if ($estadoNuevo === TCGX_INC_ESTADO_CERRADA) {
            $stmtCab = $pdo->prepare(
                'UPDATE incidencias SET estadoincidencia = :estado, fechacierre = NOW() WHERE id = :id'
            );
        } else {
            $stmtCab = $pdo->prepare('UPDATE incidencias SET estadoincidencia = :estado WHERE id = :id');
        }
        $stmtCab->execute([':estado' => $estadoNuevo, ':id' => $incidencia['id']]);

        // Historial obligatorio por cada gestion (diseño.md, seguimiento).
        $stmtHist = $pdo->prepare(
            'INSERT INTO actualizaciones_incidencia (idincidencia, estadoincidencia, detalleactualizacion, idusuarioactualiza, idtiendaactualiza) '
            . 'VALUES (:idinc, :estado, :detalle, :idusuario, :idtienda)'
        );
        $stmtHist->execute([
            ':idinc' => $incidencia['id'],
            ':estado' => $estadoNuevo,
            ':detalle' => $datos['detalle'],
            ':idusuario' => $idActor,
            ':idtienda' => $idTiendaActualiza,
        ]);

        tcgx_incidencias_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $incidencia['id'], [
            'estado' => $incidencia['estadoincidencia'],
        ], [
            'estado' => $estadoNuevo,
            'detalle' => $datos['detalle'],
        ]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR LA INCIDENCIA.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: REGLAS DE GESTION Y ACTUALIZACION


// INICIO BLOQUE: ALTA DE INCIDENCIA
/**
 * Tiendas activas (no hub) para el selector de tienda que reporta la incidencia.
 */
function tcgx_incidencias_listar_tiendas_reportadoras(PDO $pdo): array
{
    return $pdo->query(
        "SELECT id, nombre FROM tiendas WHERE estado = 'ACTIVO' AND eshub = 0 ORDER BY nombre ASC"
    )->fetchAll();
}

/**
 * Valida el formulario de registro de una incidencia nueva asociada a un envio existente.
 */
function tcgx_incidencias_validar_registro(PDO $pdo, array $post): array
{
    $errores = [];

    $idEnvio = mb_strtoupper(trim((string) ($post['idenvio'] ?? '')), 'UTF-8');
    if ($idEnvio === '' || !preg_match('/^CRE[0-9]{14}$/', $idEnvio)) {
        $errores[] = 'EL CODIGO DE ENVIO NO ES VALIDO.';
    } elseif (tcgx_envios_obtener($pdo, $idEnvio) === null) {
        $errores[] = 'EL ENVIO INDICADO NO EXISTE.';
    }

    $tipo = mb_strtoupper(trim((string) ($post['tipoincidencia'] ?? '')), 'UTF-8');
    if ($tipo === '') {
        $errores[] = 'EL TIPO DE INCIDENCIA ES OBLIGATORIO.';
    } elseif (mb_strlen($tipo, 'UTF-8') > 60) {
        $errores[] = 'EL TIPO NO PUEDE SUPERAR 60 CARACTERES.';
    }

    $detalle = mb_strtoupper(trim((string) ($post['detalleinicial'] ?? '')), 'UTF-8');
    if ($detalle === '') {
        $errores[] = 'EL DETALLE INICIAL ES OBLIGATORIO.';
    } elseif (mb_strlen($detalle, 'UTF-8') > 255) {
        $errores[] = 'EL DETALLE NO PUEDE SUPERAR 255 CARACTERES.';
    }

    $idTiendaRaw = trim((string) ($post['idtiendareporta'] ?? ''));
    $idTienda = null;
    if ($idTiendaRaw === '' || !ctype_digit($idTiendaRaw)) {
        $errores[] = 'DEBE SELECCIONAR LA TIENDA QUE REPORTA.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT id FROM tiendas WHERE id = ? AND estado = 'ACTIVO' AND eshub = 0 LIMIT 1"
        );
        $stmt->execute([(int) $idTiendaRaw]);
        if ($stmt->fetch() === false) {
            $errores[] = 'LA TIENDA SELECCIONADA NO ES VALIDA.';
        } else {
            $idTienda = (int) $idTiendaRaw;
        }
    }

    return [
        'errores' => $errores,
        'datos' => [
            'idenvio' => $idEnvio,
            'tipoincidencia' => $tipo,
            'detalleinicial' => $detalle,
            'idtiendareporta' => $idTienda,
        ],
    ];
}

/**
 * Registra una incidencia con estado INCIDENCIA REPORTADA, su primera actualizacion y auditoria CREAR.
 */
function tcgx_incidencias_crear(PDO $pdo, array $datos, ?string $idActor): array
{
    if ($idActor === null || $idActor === '') {
        return ['ok' => false, 'error' => 'NO SE PUDO IDENTIFICAR AL USUARIO QUE REPORTA.'];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO incidencias (idenvio, tipoincidencia, estadoincidencia, detalleinicial, idusuarioreporta, idtiendareporta) '
            . 'VALUES (:idenvio, :tipo, :estado, :detalle, :idusuario, :idtienda)'
        );
        $stmt->execute([
            ':idenvio' => $datos['idenvio'],
            ':tipo' => $datos['tipoincidencia'],
            ':estado' => TCGX_INC_ESTADO_REPORTADA,
            ':detalle' => $datos['detalleinicial'],
            ':idusuario' => $idActor,
            ':idtienda' => $datos['idtiendareporta'],
        ]);
        $idNuevo = (int) $pdo->lastInsertId();

        $stmtHist = $pdo->prepare(
            'INSERT INTO actualizaciones_incidencia (idincidencia, estadoincidencia, detalleactualizacion, idusuarioactualiza, idtiendaactualiza) '
            . 'VALUES (:idinc, :estado, :detalle, :idusuario, :idtienda)'
        );
        $stmtHist->execute([
            ':idinc' => $idNuevo,
            ':estado' => TCGX_INC_ESTADO_REPORTADA,
            ':detalle' => $datos['detalleinicial'],
            ':idusuario' => $idActor,
            ':idtienda' => $datos['idtiendareporta'],
        ]);

        tcgx_incidencias_auditar($pdo, $idActor, 'CREAR', (string) $idNuevo, null, [
            'idenvio' => $datos['idenvio'],
            'tipoincidencia' => $datos['tipoincidencia'],
            'estado' => TCGX_INC_ESTADO_REPORTADA,
        ]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE REGISTRAR LA INCIDENCIA.'];
    }

    return ['ok' => true, 'id' => $idNuevo];
}
// FIN BLOQUE: ALTA DE INCIDENCIA
