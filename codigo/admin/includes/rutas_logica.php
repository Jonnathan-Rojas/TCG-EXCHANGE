<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del CRUD de rutas (catalogo de tipos de ruta) del modulo admin.
 * Cada ruta define: nombre del tramo segun cercania al HUB (LOCAL/REMOTO/...),
 * el medio de envio asociado y si exige numero de guia externa.
 * Centraliza: listado, lectura, validacion, alta, edicion, cambio de estado y auditoria.
 * Solo consultas preparadas con parametros enlazados.
 * Lo consumen admin/rutas.php, admin/ruta-crear.php y admin/ruta-editar.php.
 */

// INICIO BLOQUE: CONSTANTES Y CATALOGOS CONTROLADOS
// Estados validos alineados con el CHECK de la tabla rutas (sin ENUM); baja logica via estado.
const TCGX_RUTAS_ESTADOS = ['ACTIVO', 'INACTIVO'];
// FIN BLOQUE: CONSTANTES Y CATALOGOS CONTROLADOS


// INICIO BLOQUE: LISTADO Y LECTURA DE RUTAS
/**
 * Lista todas las rutas del catalogo para el render del listado.
 */
function tcgx_rutas_listar(PDO $pdo): array
{
    $sql = 'SELECT id, nombre, medioenvio, exigeguiaexterna, estado, fecharegistro '
        . 'FROM rutas ORDER BY nombre ASC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Lee una ruta por su clave primaria; retorna la fila o null si no existe.
 */
function tcgx_rutas_obtener(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre, medioenvio, exigeguiaexterna, estado, fecharegistro '
        . 'FROM rutas WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}
// FIN BLOQUE: LISTADO Y LECTURA DE RUTAS


// INICIO BLOQUE: AUDITORIA DE OPERACIONES DE RUTAS
/**
 * Inserta una fila en auditorias para CREAR, ACTUALIZAR o cambios de estado sobre la tabla rutas.
 */
function tcgx_rutas_auditar(
    PDO $pdo,
    ?string $idActor,
    string $accion,
    string $idRegistro,
    ?array $antes,
    ?array $despues
): void {
    $jsonAntes = $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $jsonDespues = $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $stmt = $pdo->prepare(
        'INSERT INTO auditorias (idusuario, accion, tablaafectada, idregistro, datosantes, datosdespues) '
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idActor, $accion, 'rutas', $idRegistro, $jsonAntes, $jsonDespues]);
}
// FIN BLOQUE: AUDITORIA DE OPERACIONES DE RUTAS


// INICIO BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA
/**
 * Valida y normaliza la entrada del formulario de ruta (alta o edicion).
 * Reglas: nombre y medio de envio obligatorios (MAYUSCULAS) e indicador de guia externa booleano.
 * Retorna: ['errores' => string[], 'datos' => array normalizada para persistir].
 */
function tcgx_rutas_validar(array $post): array
{
    $errores = [];

    // --- Nombre del tipo de ruta (catalogo en MAYUSCULAS) ---
    $nombre = mb_strtoupper(trim((string) ($post['nombre'] ?? '')), 'UTF-8');
    if ($nombre === '') {
        $errores[] = 'EL NOMBRE DE LA RUTA ES OBLIGATORIO.';
    } elseif (mb_strlen($nombre) > 40) {
        $errores[] = 'EL NOMBRE DE LA RUTA NO PUEDE SUPERAR 40 CARACTERES.';
    }

    // --- Medio de envio asociado (texto libre en MAYUSCULAS) ---
    $medioEnvio = mb_strtoupper(trim((string) ($post['medioenvio'] ?? '')), 'UTF-8');
    if ($medioEnvio === '') {
        $errores[] = 'EL MEDIO DE ENVIO ES OBLIGATORIO.';
    } elseif (mb_strlen($medioEnvio) > 80) {
        $errores[] = 'EL MEDIO DE ENVIO NO PUEDE SUPERAR 80 CARACTERES.';
    }

    // --- Indicador de guia externa (0/1) ---
    $exigeGuiaRaw = trim((string) ($post['exigeguiaexterna'] ?? '0'));
    $exigeGuia = $exigeGuiaRaw === '1' ? 1 : 0;

    $datos = [
        'nombre' => $nombre,
        'medioenvio' => $medioEnvio,
        'exigeguiaexterna' => $exigeGuia,
    ];

    return ['errores' => $errores, 'datos' => $datos];
}
// FIN BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA


// INICIO BLOQUE: ALTA DE RUTA (TRANSACCION + AUDITORIA)
/**
 * Crea una ruta del catalogo dentro de una transaccion atomica con su auditoria.
 * Retorna: ['ok' => true, 'id' => <id generado>] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_rutas_crear(PDO $pdo, array $datos, ?string $idActor): array
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO rutas (nombre, medioenvio, exigeguiaexterna) '
            . 'VALUES (:nombre, :medioenvio, :exigeguiaexterna)'
        );
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':medioenvio' => $datos['medioenvio'],
            ':exigeguiaexterna' => $datos['exigeguiaexterna'],
        ]);

        $idNuevo = (int) $pdo->lastInsertId();
        tcgx_rutas_auditar($pdo, $idActor, 'CREAR', (string) $idNuevo, null, $datos);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 23000: violacion de la unicidad del nombre del tipo de ruta.
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'YA EXISTE UNA RUTA CON ESE NOMBRE.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CREAR LA RUTA.'];
    }

    return ['ok' => true, 'id' => $idNuevo];
}
// FIN BLOQUE: ALTA DE RUTA (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: EDICION DE RUTA (TRANSACCION + AUDITORIA)
/**
 * Actualiza una ruta existente en transaccion con auditoria UPDATE.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_rutas_actualizar(PDO $pdo, int $id, array $datos, ?string $idActor, array $antes): array
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'UPDATE rutas SET nombre = :nombre, medioenvio = :medioenvio, '
            . 'exigeguiaexterna = :exigeguiaexterna WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':medioenvio' => $datos['medioenvio'],
            ':exigeguiaexterna' => $datos['exigeguiaexterna'],
            ':id' => $id,
        ]);

        $antesAuditoria = [
            'nombre' => $antes['nombre'] ?? null,
            'medioenvio' => $antes['medioenvio'] ?? null,
            'exigeguiaexterna' => isset($antes['exigeguiaexterna']) ? (int) $antes['exigeguiaexterna'] : null,
        ];
        tcgx_rutas_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $id, $antesAuditoria, $datos);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'YA EXISTE UNA RUTA CON ESE NOMBRE.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR LA RUTA.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: EDICION DE RUTA (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: CAMBIO DE ESTADO (ACTIVAR / DESACTIVAR) CON AUDITORIA
/**
 * Cambia el estado de la ruta (ACTIVO/INACTIVO) como baja/alta logica, en transaccion con auditoria.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_rutas_cambiar_estado(PDO $pdo, int $id, string $nuevoEstado, ?string $idActor): array
{
    if (!in_array($nuevoEstado, TCGX_RUTAS_ESTADOS, true)) {
        return ['ok' => false, 'error' => 'ESTADO NO VALIDO.'];
    }

    $actual = tcgx_rutas_obtener($pdo, $id);
    if ($actual === null) {
        return ['ok' => false, 'error' => 'LA RUTA NO EXISTE.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE rutas SET estado = :estado WHERE id = :id');
        $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);
        tcgx_rutas_auditar(
            $pdo,
            $idActor,
            'ACTUALIZAR',
            (string) $id,
            ['estado' => $actual['estado']],
            ['estado' => $nuevoEstado]
        );
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CAMBIAR EL ESTADO DE LA RUTA.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: CAMBIO DE ESTADO (ACTIVAR / DESACTIVAR) CON AUDITORIA
