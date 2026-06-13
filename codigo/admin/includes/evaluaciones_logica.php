<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del CRUD de evaluaciones (reputacion por usuario) del modulo admin.
 * Una evaluacion es unica por usuario (UNIQUE idusuario en BD) y la emite una tienda; califica
 * rapidez, confianza, seguridad y calidad (0..5) y puede marcar lista negra con su motivo.
 * El administrador puede crear/editar evaluaciones sin importar la tienda; el usuario evaluado
 * debe ser un CLIENTE registrado.
 * Centraliza: catalogos de selects, listado, lectura, validacion, alta, edicion, eliminacion y auditoria.
 * Solo consultas preparadas con parametros enlazados.
 * Lo consumen admin/evaluaciones.php, admin/evaluacion-crear.php y admin/evaluacion-editar.php.
 */

// INICIO BLOQUE: CONSTANTES DE PUNTAJE
// Rango permitido por los CHECK de basedatos.sql para los cuatro criterios de la evaluacion.
const TCGX_EVALUACIONES_PUNTAJE_MIN = 0;
const TCGX_EVALUACIONES_PUNTAJE_MAX = 5;
// Criterios calificables, en el orden de presentacion del formulario y del listado.
const TCGX_EVALUACIONES_CRITERIOS = ['rapidez', 'confianza', 'seguridad', 'calidad'];
// FIN BLOQUE: CONSTANTES DE PUNTAJE


// INICIO BLOQUE: CATALOGOS PARA LOS SELECTS DEL FORMULARIO
/**
 * Devuelve tiendas ACTIVAS (id y nombre) para poblar el select de la tienda emisora.
 * El administrador puede seleccionar cualquier tienda (no esta limitado a una en particular).
 * El cliente evaluado NO se ofrece como select (puede haber muchos usuarios): se digita su cedula
 * y se valida contra la BD en el servidor (debe existir y ser CLIENTE).
 */
function tcgx_evaluaciones_listar_tiendas(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, nombre FROM tiendas WHERE estado = 'ACTIVO' ORDER BY nombre ASC");
    return $stmt->fetchAll();
}

/**
 * Resuelve el nombre de un cliente CLIENTE por su cedula (id) para mostrarlo en pantalla.
 * Retorna el nombre o cadena vacia si no existe o no es CLIENTE.
 */
function tcgx_evaluaciones_nombre_cliente(PDO $pdo, string $idUsuario): string
{
    $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ? AND perfil = 'CLIENTE' LIMIT 1");
    $stmt->execute([$idUsuario]);
    $fila = $stmt->fetch();
    return $fila === false ? '' : (string) $fila['nombre'];
}

/**
 * Busqueda dinamica de clientes CLIENTE SOLO POR NOMBRE para el autocompletado (Select2 AJAX).
 * Escapa los comodines LIKE del termino y limita la cantidad de filas devueltas.
 * Usa un unico parametro enlazado (compatible con prepares reales: EMULATE_PREPARES = false).
 * Retorna filas [id, nombre] ordenadas por nombre.
 */
function tcgx_evaluaciones_buscar_clientes(PDO $pdo, string $termino, int $limite = 20): array
{
    $termino = trim($termino);
    if ($termino === '') {
        return [];
    }
    // Neutraliza los comodines del usuario para que LIKE busque el texto literal.
    $escapado = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $termino);
    $patron = '%' . $escapado . '%';
    $limite = max(1, min($limite, 50));

    $sql = "SELECT id, nombre FROM usuarios "
        . "WHERE perfil = 'CLIENTE' AND nombre LIKE :patron ESCAPE '\\\\' "
        . "ORDER BY nombre ASC LIMIT " . $limite;
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':patron', $patron, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll();
}
// FIN BLOQUE: CATALOGOS PARA LOS SELECTS DEL FORMULARIO


// INICIO BLOQUE: LISTADO Y LECTURA DE EVALUACIONES
/**
 * Lista todas las evaluaciones con el nombre del usuario evaluado y de la tienda emisora.
 */
function tcgx_evaluaciones_listar(PDO $pdo): array
{
    $sql = 'SELECT e.id, e.idusuario, e.idtienda, e.rapidez, e.confianza, e.seguridad, e.calidad, '
        . 'e.listanegra, e.motivolistanegra, e.fecharegistro, '
        . 'u.nombre AS nombreusuario, ti.nombre AS nombretienda '
        . 'FROM evaluaciones e '
        . 'LEFT JOIN usuarios u ON u.id = e.idusuario '
        . 'LEFT JOIN tiendas ti ON ti.id = e.idtienda '
        . 'ORDER BY e.fecharegistro DESC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Lee una evaluacion por su clave primaria; retorna la fila o null si no existe.
 */
function tcgx_evaluaciones_obtener(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, idusuario, idtienda, rapidez, confianza, seguridad, calidad, '
        . 'listanegra, motivolistanegra, fecharegistro '
        . 'FROM evaluaciones WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Calcula la reputacion (promedio de los cuatro criterios) con un decimal.
 */
function tcgx_evaluaciones_reputacion(array $fila): float
{
    $suma = 0;
    foreach (TCGX_EVALUACIONES_CRITERIOS as $criterio) {
        $suma += (int) ($fila[$criterio] ?? 0);
    }
    $cantidad = count(TCGX_EVALUACIONES_CRITERIOS);
    return $cantidad === 0 ? 0.0 : round($suma / $cantidad, 1);
}
// FIN BLOQUE: LISTADO Y LECTURA DE EVALUACIONES


// INICIO BLOQUE: AUDITORIA DE OPERACIONES DE EVALUACIONES
/**
 * Inserta una fila en auditorias para CREAR, ACTUALIZAR o ELIMINAR sobre la tabla evaluaciones.
 */
function tcgx_evaluaciones_auditar(
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
    $stmt->execute([$idActor, $accion, 'evaluaciones', $idRegistro, $jsonAntes, $jsonDespues]);
}
// FIN BLOQUE: AUDITORIA DE OPERACIONES DE EVALUACIONES


// INICIO BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA
/**
 * Valida y normaliza la entrada del formulario de evaluacion (alta o edicion).
 * Reglas: usuario CLIENTE registrado, tienda existente, cuatro puntajes enteros 0..5,
 * y motivo de lista negra obligatorio (en MAYUSCULAS) solo cuando se marca lista negra.
 * Retorna: ['errores' => string[], 'datos' => array normalizada para persistir].
 */
function tcgx_evaluaciones_validar(PDO $pdo, array $post): array
{
    $errores = [];

    // --- Usuario evaluado (se digita la cedula; debe ser un CLIENTE registrado) ---
    $idUsuario = trim((string) ($post['idusuario'] ?? ''));
    if ($idUsuario === '') {
        $errores[] = 'DEBE INDICAR LA CEDULA DEL CLIENTE A EVALUAR.';
        $idUsuario = null;
    } else {
        $stmtU = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND perfil = 'CLIENTE' LIMIT 1");
        $stmtU->execute([$idUsuario]);
        if ($stmtU->fetch() === false) {
            $errores[] = 'EL CLIENTE A EVALUAR DEBE ESTAR REGISTRADO.';
            $idUsuario = null;
        }
    }

    // --- Tienda emisora (debe existir) ---
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

    // --- Puntajes (enteros 0..5) ---
    // Etiquetas legibles para los mensajes de error de cada criterio.
    $etiquetas = [
        'rapidez' => 'RAPIDEZ',
        'confianza' => 'CONFIANZA',
        'seguridad' => 'SEGURIDAD',
        'calidad' => 'CALIDAD',
    ];
    $puntajes = [];
    foreach (TCGX_EVALUACIONES_CRITERIOS as $criterio) {
        $valorRaw = trim((string) ($post[$criterio] ?? ''));
        if (
            $valorRaw === ''
            || !ctype_digit($valorRaw)
            || (int) $valorRaw < TCGX_EVALUACIONES_PUNTAJE_MIN
            || (int) $valorRaw > TCGX_EVALUACIONES_PUNTAJE_MAX
        ) {
            $errores[] = 'EL PUNTAJE DE ' . $etiquetas[$criterio] . ' DEBE SER UN ENTERO ENTRE '
                . TCGX_EVALUACIONES_PUNTAJE_MIN . ' Y ' . TCGX_EVALUACIONES_PUNTAJE_MAX . '.';
            $puntajes[$criterio] = null;
        } else {
            $puntajes[$criterio] = (int) $valorRaw;
        }
    }

    // --- Lista negra y su motivo ---
    // listanegra es 0/1; el motivo (MAYUSCULAS) es obligatorio solo cuando se marca lista negra.
    $listaNegraRaw = trim((string) ($post['listanegra'] ?? '0'));
    $listaNegra = $listaNegraRaw === '1' ? 1 : 0;
    $motivo = mb_strtoupper(trim((string) ($post['motivolistanegra'] ?? '')), 'UTF-8');
    if ($listaNegra === 1 && $motivo === '') {
        $errores[] = 'DEBE INDICAR EL MOTIVO DE LA LISTA NEGRA.';
    }
    // Si no esta en lista negra, el motivo no aplica y se persiste como NULL (coherente con el CHECK).
    $motivoPersistir = $listaNegra === 1 ? $motivo : null;

    $datos = [
        'idusuario' => $idUsuario,
        'idtienda' => $idTienda,
        'rapidez' => $puntajes['rapidez'],
        'confianza' => $puntajes['confianza'],
        'seguridad' => $puntajes['seguridad'],
        'calidad' => $puntajes['calidad'],
        'listanegra' => $listaNegra,
        'motivolistanegra' => $motivoPersistir,
    ];

    return ['errores' => $errores, 'datos' => $datos];
}
// FIN BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA


// INICIO BLOQUE: ALTA DE EVALUACION (TRANSACCION + AUDITORIA)
/**
 * Crea una evaluacion dentro de una transaccion atomica con su auditoria.
 * Retorna: ['ok' => true, 'id' => <id generado>] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_evaluaciones_crear(PDO $pdo, array $datos, ?string $idActor): array
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO evaluaciones (idusuario, idtienda, rapidez, confianza, seguridad, calidad, listanegra, motivolistanegra) '
            . 'VALUES (:idusuario, :idtienda, :rapidez, :confianza, :seguridad, :calidad, :listanegra, :motivolistanegra)'
        );
        $stmt->execute([
            ':idusuario' => $datos['idusuario'],
            ':idtienda' => $datos['idtienda'],
            ':rapidez' => $datos['rapidez'],
            ':confianza' => $datos['confianza'],
            ':seguridad' => $datos['seguridad'],
            ':calidad' => $datos['calidad'],
            ':listanegra' => $datos['listanegra'],
            ':motivolistanegra' => $datos['motivolistanegra'],
        ]);

        $idNuevo = (int) $pdo->lastInsertId();
        tcgx_evaluaciones_auditar($pdo, $idActor, 'CREAR', (string) $idNuevo, null, $datos);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 23000: violacion de la unicidad por usuario (ya tiene una evaluacion).
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'ESE CLIENTE YA TIENE UNA EVALUACION REGISTRADA.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CREAR LA EVALUACION.'];
    }

    return ['ok' => true, 'id' => $idNuevo];
}
// FIN BLOQUE: ALTA DE EVALUACION (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: EDICION DE EVALUACION (TRANSACCION + AUDITORIA)
/**
 * Actualiza una evaluacion existente en transaccion con auditoria UPDATE.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_evaluaciones_actualizar(PDO $pdo, int $id, array $datos, ?string $idActor, array $antes): array
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'UPDATE evaluaciones SET idusuario = :idusuario, idtienda = :idtienda, '
            . 'rapidez = :rapidez, confianza = :confianza, seguridad = :seguridad, calidad = :calidad, '
            . 'listanegra = :listanegra, motivolistanegra = :motivolistanegra WHERE id = :id'
        );
        $stmt->execute([
            ':idusuario' => $datos['idusuario'],
            ':idtienda' => $datos['idtienda'],
            ':rapidez' => $datos['rapidez'],
            ':confianza' => $datos['confianza'],
            ':seguridad' => $datos['seguridad'],
            ':calidad' => $datos['calidad'],
            ':listanegra' => $datos['listanegra'],
            ':motivolistanegra' => $datos['motivolistanegra'],
            ':id' => $id,
        ]);

        tcgx_evaluaciones_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $id, $antes, $datos);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'ESE CLIENTE YA TIENE UNA EVALUACION REGISTRADA.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR LA EVALUACION.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: EDICION DE EVALUACION (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: ELIMINACION DE EVALUACION (TRANSACCION + AUDITORIA)
/**
 * Elimina fisicamente una evaluacion en transaccion con auditoria ELIMINAR.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_evaluaciones_eliminar(PDO $pdo, int $id, ?string $idActor): array
{
    $actual = tcgx_evaluaciones_obtener($pdo, $id);
    if ($actual === null) {
        return ['ok' => false, 'error' => 'LA EVALUACION NO EXISTE.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('DELETE FROM evaluaciones WHERE id = ?');
        $stmt->execute([$id]);
        tcgx_evaluaciones_auditar($pdo, $idActor, 'ELIMINAR', (string) $id, $actual, null);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ELIMINAR LA EVALUACION.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: ELIMINACION DE EVALUACION (TRANSACCION + AUDITORIA)
