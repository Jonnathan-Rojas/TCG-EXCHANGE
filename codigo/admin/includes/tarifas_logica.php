<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del CRUD de tarifas de envio del modulo admin.
 * Una tarifa define el costo de envio por tienda y por tipo de ruta (FK a rutas) para calcular montoapagar.
 * Centraliza: listado, lectura, validacion, alta, edicion, eliminacion y auditoria.
 * Solo consultas preparadas con parametros enlazados.
 * Lo consumen admin/tarifas.php, admin/tarifa-crear.php y admin/tarifa-editar.php.
 */

// INICIO BLOQUE: CATALOGOS PARA LOS SELECTS DEL FORMULARIO
/**
 * Devuelve tiendas ACTIVAS (id y nombre) para poblar el select de la tarifa.
 */
function tcgx_tarifas_listar_tiendas(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, nombre FROM tiendas WHERE estado = 'ACTIVO' ORDER BY nombre ASC");
    return $stmt->fetchAll();
}

/**
 * Devuelve rutas ACTIVAS (id y nombre) del catalogo para poblar el select de la tarifa.
 */
function tcgx_tarifas_listar_rutas(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, nombre FROM rutas WHERE estado = 'ACTIVO' ORDER BY nombre ASC");
    return $stmt->fetchAll();
}
// FIN BLOQUE: CATALOGOS PARA LOS SELECTS DEL FORMULARIO


// INICIO BLOQUE: LISTADO Y LECTURA DE TARIFAS
/**
 * Lista todas las tarifas con el nombre de su tienda y de su ruta para el render del listado.
 */
function tcgx_tarifas_listar(PDO $pdo): array
{
    $sql = 'SELECT t.id, t.idtienda, t.idruta, t.preciobase, t.precioporpaquete, t.fecharegistro, '
        . 'ti.nombre AS nombretienda, r.nombre AS nombreruta '
        . 'FROM tarifas t '
        . 'LEFT JOIN tiendas ti ON ti.id = t.idtienda '
        . 'LEFT JOIN rutas r ON r.id = t.idruta '
        . 'ORDER BY ti.nombre ASC, r.nombre ASC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Lee una tarifa por su clave primaria; retorna la fila o null si no existe.
 */
function tcgx_tarifas_obtener(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, idtienda, idruta, preciobase, precioporpaquete, fecharegistro '
        . 'FROM tarifas WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}
// FIN BLOQUE: LISTADO Y LECTURA DE TARIFAS


// INICIO BLOQUE: AUDITORIA DE OPERACIONES DE TARIFAS
/**
 * Inserta una fila en auditorias para CREAR, ACTUALIZAR o ELIMINAR sobre la tabla tarifas.
 */
function tcgx_tarifas_auditar(
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
    $stmt->execute([$idActor, $accion, 'tarifas', $idRegistro, $jsonAntes, $jsonDespues]);
}
// FIN BLOQUE: AUDITORIA DE OPERACIONES DE TARIFAS


// INICIO BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA
/**
 * Valida y normaliza la entrada del formulario de tarifa (alta o edicion).
 * Reglas: tienda existente, ruta existente y precio unico numerico no negativo.
 * NUNCA USAR PRECIO BASE: la columna preciobase se persiste fija en 0.00 y se ignora en todos los procesos;
 * el unico precio operativo se guarda en precioporpaquete.
 * Retorna: ['errores' => string[], 'datos' => array normalizada para persistir].
 */
function tcgx_tarifas_validar(PDO $pdo, array $post): array
{
    $errores = [];

    // --- Tienda (debe existir) ---
    $idTiendaRaw = trim((string) ($post['idtienda'] ?? ''));
    $idTienda = null;
    if ($idTiendaRaw === '' || !ctype_digit($idTiendaRaw)) {
        $errores[] = 'DEBE SELECCIONAR UNA TIENDA.';
    } else {
        $idTienda = (int) $idTiendaRaw;
        $stmtT = $pdo->prepare('SELECT id FROM tiendas WHERE id = ? LIMIT 1');
        $stmtT->execute([$idTienda]);
        if ($stmtT->fetch() === false) {
            $errores[] = 'LA TIENDA SELECCIONADA NO EXISTE.';
            $idTienda = null;
        }
    }

    // --- Ruta (debe existir en el catalogo) ---
    $idRutaRaw = trim((string) ($post['idruta'] ?? ''));
    $idRuta = null;
    if ($idRutaRaw === '' || !ctype_digit($idRutaRaw)) {
        $errores[] = 'DEBE SELECCIONAR UNA RUTA.';
    } else {
        $idRuta = (int) $idRutaRaw;
        $stmtR = $pdo->prepare('SELECT id FROM rutas WHERE id = ? LIMIT 1');
        $stmtR->execute([$idRuta]);
        if ($stmtR->fetch() === false) {
            $errores[] = 'LA RUTA SELECCIONADA NO EXISTE.';
            $idRuta = null;
        }
    }

    // --- Precio unico (numerico, no negativo) ---
    // El formulario envia un solo campo "Precio" sobre el name=precioporpaquete; preciobase no se solicita ni se usa.
    $precioPaqueteRaw = trim((string) ($post['precioporpaquete'] ?? ''));
    $precioPaquete = null;
    if ($precioPaqueteRaw === '' || !is_numeric($precioPaqueteRaw) || (float) $precioPaqueteRaw < 0) {
        $errores[] = 'EL PRECIO DEBE SER UN NUMERO MAYOR O IGUAL A CERO.';
    } else {
        $precioPaquete = number_format((float) $precioPaqueteRaw, 2, '.', '');
    }

    // NUNCA USAR PRECIO BASE: se fija en 0.00 solo para satisfacer la columna NOT NULL de la tabla; no es operativo.
    $datos = [
        'idtienda' => $idTienda,
        'idruta' => $idRuta,
        'preciobase' => '0.00',
        'precioporpaquete' => $precioPaquete,
    ];

    return ['errores' => $errores, 'datos' => $datos];
}
// FIN BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA


// INICIO BLOQUE: ALTA DE TARIFA (TRANSACCION + AUDITORIA)
/**
 * Crea una tarifa dentro de una transaccion atomica con su auditoria.
 * Retorna: ['ok' => true, 'id' => <id generado>] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_tarifas_crear(PDO $pdo, array $datos, ?string $idActor): array
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO tarifas (idtienda, idruta, preciobase, precioporpaquete) '
            . 'VALUES (:idtienda, :idruta, :preciobase, :precioporpaquete)'
        );
        $stmt->execute([
            ':idtienda' => $datos['idtienda'],
            ':idruta' => $datos['idruta'],
            ':preciobase' => $datos['preciobase'],
            ':precioporpaquete' => $datos['precioporpaquete'],
        ]);

        $idNuevo = (int) $pdo->lastInsertId();
        tcgx_tarifas_auditar($pdo, $idActor, 'CREAR', (string) $idNuevo, null, $datos);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 23000: violacion de la unicidad (idtienda + idruta).
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'YA EXISTE UNA TARIFA PARA ESA TIENDA Y RUTA.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CREAR LA TARIFA.'];
    }

    return ['ok' => true, 'id' => $idNuevo];
}
// FIN BLOQUE: ALTA DE TARIFA (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: EDICION DE TARIFA (TRANSACCION + AUDITORIA)
/**
 * Actualiza una tarifa existente en transaccion con auditoria UPDATE.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_tarifas_actualizar(PDO $pdo, int $id, array $datos, ?string $idActor, array $antes): array
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'UPDATE tarifas SET idtienda = :idtienda, idruta = :idruta, '
            . 'preciobase = :preciobase, precioporpaquete = :precioporpaquete WHERE id = :id'
        );
        $stmt->execute([
            ':idtienda' => $datos['idtienda'],
            ':idruta' => $datos['idruta'],
            ':preciobase' => $datos['preciobase'],
            ':precioporpaquete' => $datos['precioporpaquete'],
            ':id' => $id,
        ]);

        $antesAuditoria = [
            'idtienda' => isset($antes['idtienda']) ? (int) $antes['idtienda'] : null,
            'idruta' => isset($antes['idruta']) ? (int) $antes['idruta'] : null,
            'preciobase' => $antes['preciobase'] ?? null,
            'precioporpaquete' => $antes['precioporpaquete'] ?? null,
        ];
        tcgx_tarifas_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $id, $antesAuditoria, $datos);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'YA EXISTE UNA TARIFA PARA ESA TIENDA Y RUTA.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR LA TARIFA.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: EDICION DE TARIFA (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: ELIMINACION DE TARIFA (TRANSACCION + AUDITORIA)
/**
 * Elimina fisicamente una tarifa (dato de configuracion sin estado) en transaccion con auditoria ELIMINAR.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_tarifas_eliminar(PDO $pdo, int $id, ?string $idActor): array
{
    $actual = tcgx_tarifas_obtener($pdo, $id);
    if ($actual === null) {
        return ['ok' => false, 'error' => 'LA TARIFA NO EXISTE.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM tarifas WHERE id = ?');
        $stmt->execute([$id]);
        tcgx_tarifas_auditar($pdo, $idActor, 'ELIMINAR', (string) $id, $actual, null);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ELIMINAR LA TARIFA.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: ELIMINACION DE TARIFA (TRANSACCION + AUDITORIA)
