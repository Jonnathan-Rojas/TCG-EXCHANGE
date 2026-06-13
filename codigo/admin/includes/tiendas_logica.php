<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del CRUD de tiendas del modulo admin.
 * Centraliza: carga de catalogo geografico, listado, lectura, validacion, alta, edicion,
 * cambio de estado (baja logica) y auditoria. Solo consultas preparadas con parametros enlazados.
 * Lo consumen admin/tiendas.php, admin/tienda-crear.php y admin/tienda-editar.php.
 */

// INICIO BLOQUE: CONSTANTES Y CATALOGOS CONTROLADOS
// Valores permitidos alineados con los CHECK de basedatos.sql (sin ENUM en BD).
const TCGX_TIENDAS_ESTADOS = ['ACTIVO', 'BLOQUEADO', 'INACTIVO'];
// Ruta del catalogo geografico estatico (recurso global del proyecto), resuelta desde admin/includes.
const TCGX_TIENDAS_RUTA_CATALOGO = __DIR__ . '/../../vendor/data/ubicaciones-cr.json';
// FIN BLOQUE: CONSTANTES Y CATALOGOS CONTROLADOS


// INICIO BLOQUE: CARGA DE CATALOGO GEOGRAFICO COSTA RICA
/**
 * Lee el catalogo provincia/canton/distrito desde archivo estatico y lo normaliza a MAYUSCULAS.
 * Estructura devuelta: [ 'PROVINCIA' => [ 'CANTON' => ['DISTRITO', ...], ... ], ... ].
 * Se normaliza una sola vez por peticion mediante cache estatica en la funcion.
 */
function tcgx_tiendas_catalogo_geografico(): array
{
    static $catalogo = null;
    if ($catalogo !== null) {
        return $catalogo;
    }

    $catalogo = [];
    $contenido = @file_get_contents(TCGX_TIENDAS_RUTA_CATALOGO);
    if ($contenido === false) {
        return $catalogo;
    }

    $datos = json_decode($contenido, true);
    if (!is_array($datos) || !isset($datos['provincias']) || !is_array($datos['provincias'])) {
        return $catalogo;
    }

    foreach ($datos['provincias'] as $provincia) {
        if (!isset($provincia['title'])) {
            continue;
        }
        $nombreProvincia = mb_strtoupper(trim((string) $provincia['title']), 'UTF-8');
        $catalogo[$nombreProvincia] = [];
        $cantones = isset($provincia['cantones']) && is_array($provincia['cantones']) ? $provincia['cantones'] : [];
        foreach ($cantones as $canton) {
            if (!isset($canton['title'])) {
                continue;
            }
            $nombreCanton = mb_strtoupper(trim((string) $canton['title']), 'UTF-8');
            $catalogo[$nombreProvincia][$nombreCanton] = [];
            $distritos = isset($canton['distritos']) && is_array($canton['distritos']) ? $canton['distritos'] : [];
            foreach ($distritos as $distrito) {
                if (!isset($distrito['title'])) {
                    continue;
                }
                $catalogo[$nombreProvincia][$nombreCanton][] = mb_strtoupper(trim((string) $distrito['title']), 'UTF-8');
            }
        }
    }

    return $catalogo;
}
// FIN BLOQUE: CARGA DE CATALOGO GEOGRAFICO COSTA RICA


// INICIO BLOQUE: LISTADO Y LECTURA DE TIENDAS
/**
 * Lista todas las tiendas para el render del listado.
 */
function tcgx_tiendas_listar(PDO $pdo): array
{
    $sql = 'SELECT id, nombre, correo, telefono, provincia, canton, distrito, direccion, '
        . 'eshub, estado, fecharegistro FROM tiendas ORDER BY fecharegistro DESC, id ASC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Lee una tienda por su clave primaria; retorna la fila o null si no existe.
 */
function tcgx_tiendas_obtener(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre, correo, telefono, provincia, canton, distrito, direccion, '
        . 'eshub, estado, fecharegistro FROM tiendas WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}
// FIN BLOQUE: LISTADO Y LECTURA DE TIENDAS


// INICIO BLOQUE: AUDITORIA DE OPERACIONES DE TIENDAS
/**
 * Inserta una fila en auditorias para CREAR, ACTUALIZAR o ELIMINAR sobre la tabla tiendas.
 */
function tcgx_tiendas_auditar(
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
    $stmt->execute([$idActor, $accion, 'tiendas', $idRegistro, $jsonAntes, $jsonDespues]);
}
// FIN BLOQUE: AUDITORIA DE OPERACIONES DE TIENDAS


// INICIO BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA
/**
 * Cuenta tiendas marcadas como Centro de Distribucion (eshub = 1), excluyendo opcionalmente un id en edicion.
 */
function tcgx_tiendas_contar_hub(PDO $pdo, ?int $excluirId = null): int
{
    if ($excluirId !== null && $excluirId > 0) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM tiendas WHERE eshub = 1 AND id <> ?');
        $stmt->execute([$excluirId]);
        return (int) $stmt->fetchColumn();
    }
    return (int) $pdo->query('SELECT COUNT(*) FROM tiendas WHERE eshub = 1')->fetchColumn();
}

/**
 * Valida y normaliza la entrada del formulario de tienda (alta o edicion).
 * Reglas: tipos, longitudes, geografia obligatoria coherente con el catalogo, correo unico
 * (excepcion de minusculas), marca de centro de distribucion (eshub 0/1) y unicidad de HUB.
 * Datos operativos en MAYUSCULAS (regla del proyecto), sin alterar tildes ni la enie.
 * Retorna: ['errores' => string[], 'datos' => array normalizada para persistir].
 */
function tcgx_tiendas_validar(PDO $pdo, array $post, ?int $idExcluirHub = null): array
{
    $errores = [];
    $catalogo = tcgx_tiendas_catalogo_geografico();

    // --- Nombre ---
    $nombre = mb_strtoupper(trim((string) ($post['nombre'] ?? '')), 'UTF-8');
    if ($nombre === '') {
        $errores[] = 'EL NOMBRE ES OBLIGATORIO.';
    } elseif (mb_strlen($nombre, 'UTF-8') > 120) {
        $errores[] = 'EL NOMBRE NO PUEDE SUPERAR 120 CARACTERES.';
    }

    // --- Correo (unico; excepcion de minusculas; unicidad se valida en BD por restriccion) ---
    $correo = mb_strtolower(trim((string) ($post['correo'] ?? '')), 'UTF-8');
    if ($correo === '') {
        $errores[] = 'EL CORREO ES OBLIGATORIO.';
    } elseif (mb_strlen($correo, 'UTF-8') > 150) {
        $errores[] = 'EL CORREO NO PUEDE SUPERAR 150 CARACTERES.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'EL FORMATO DEL CORREO NO ES VALIDO.';
    }

    // --- Telefono ---
    $telefono = trim((string) ($post['telefono'] ?? ''));
    if ($telefono === '') {
        $errores[] = 'EL TELEFONO ES OBLIGATORIO.';
    } elseif (mb_strlen($telefono, 'UTF-8') > 20) {
        $errores[] = 'EL TELEFONO NO PUEDE SUPERAR 20 CARACTERES.';
    } elseif (!preg_match('/^[0-9+\-\s()]+$/', $telefono)) {
        $errores[] = 'EL TELEFONO SOLO ADMITE NUMEROS Y LOS SIMBOLOS + - ( ) Y ESPACIOS.';
    }

    // --- Geografia (obligatoria; debe ser coherente con el catalogo) ---
    $provincia = mb_strtoupper(trim((string) ($post['provincia'] ?? '')), 'UTF-8');
    $canton = mb_strtoupper(trim((string) ($post['canton'] ?? '')), 'UTF-8');
    $distrito = mb_strtoupper(trim((string) ($post['distrito'] ?? '')), 'UTF-8');
    if ($provincia === '' || !isset($catalogo[$provincia])) {
        $errores[] = 'LA PROVINCIA SELECCIONADA NO ES VALIDA.';
        $canton = '';
        $distrito = '';
    } elseif ($canton === '' || !isset($catalogo[$provincia][$canton])) {
        $errores[] = 'EL CANTON SELECCIONADO NO ES VALIDO PARA LA PROVINCIA.';
        $distrito = '';
    } elseif ($distrito === '' || !in_array($distrito, $catalogo[$provincia][$canton], true)) {
        $errores[] = 'EL DISTRITO SELECCIONADO NO ES VALIDO PARA EL CANTON.';
    }

    // --- Direccion (obligatoria) ---
    $direccion = mb_strtoupper(trim((string) ($post['direccion'] ?? '')), 'UTF-8');
    if ($direccion === '') {
        $errores[] = 'LA DIRECCION ES OBLIGATORIA.';
    } elseif (mb_strlen($direccion, 'UTF-8') > 255) {
        $errores[] = 'LA DIRECCION NO PUEDE SUPERAR 255 CARACTERES.';
    }

    // --- Centro de distribucion (eshub: solo 0 o 1) ---
    $eshubRaw = trim((string) ($post['eshub'] ?? '0'));
    if ($eshubRaw !== '0' && $eshubRaw !== '1') {
        $errores[] = 'EL VALOR DE CENTRO DE DISTRIBUCION NO ES VALIDO.';
        $eshub = 0;
    } else {
        $eshub = (int) $eshubRaw;
    }

    // --- Unicidad de Centro de Distribucion (eshub = 1): solo una tienda en todo el sistema ---
    if ($eshub === 1 && tcgx_tiendas_contar_hub($pdo, $idExcluirHub) > 0) {
        $errores[] = 'YA EXISTE UNA TIENDA COMO CENTRO DE DISTRIBUCION. SOLO PUEDE HABER UNA.';
    }

    $datos = [
        'nombre' => $nombre,
        'correo' => $correo,
        'telefono' => $telefono,
        'provincia' => $provincia !== '' ? $provincia : null,
        'canton' => $canton !== '' ? $canton : null,
        'distrito' => $distrito !== '' ? $distrito : null,
        'direccion' => $direccion !== '' ? $direccion : null,
        'eshub' => $eshub,
    ];

    return ['errores' => $errores, 'datos' => $datos];
}
// FIN BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA


// INICIO BLOQUE: ALTA DE TIENDA (TRANSACCION + AUDITORIA)
/**
 * Crea una tienda dentro de una transaccion atomica con su auditoria.
 * El id es AUTO_INCREMENT en BD; la tienda nace ACTIVO (el estado se cambia luego desde acciones).
 * Retorna: ['ok' => true, 'id' => <id generado>] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_tiendas_crear(PDO $pdo, array $datos, ?string $idActor): array
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO tiendas (nombre, provincia, canton, distrito, direccion, telefono, correo, eshub, estado) '
            . 'VALUES (:nombre, :provincia, :canton, :distrito, :direccion, :telefono, :correo, :eshub, :estado)'
        );
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':provincia' => $datos['provincia'],
            ':canton' => $datos['canton'],
            ':distrito' => $datos['distrito'],
            ':direccion' => $datos['direccion'],
            ':telefono' => $datos['telefono'],
            ':correo' => $datos['correo'],
            ':eshub' => $datos['eshub'],
            // Toda tienda nueva nace ACTIVO; no hay selector de estado en el alta.
            ':estado' => 'ACTIVO',
        ]);

        $idNuevo = (int) $pdo->lastInsertId();

        // Auditoria de creacion: copia de columnas del registro creado.
        $despues = $datos;
        $despues['estado'] = 'ACTIVO';
        tcgx_tiendas_auditar($pdo, $idActor, 'CREAR', (string) $idNuevo, null, $despues);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 23000: violacion de integridad (correo unico duplicado).
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'EL CORREO YA ESTA REGISTRADO POR OTRA TIENDA.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CREAR LA TIENDA.'];
    }

    return ['ok' => true, 'id' => $idNuevo];
}
// FIN BLOQUE: ALTA DE TIENDA (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: EDICION DE TIENDA (TRANSACCION + AUDITORIA)
/**
 * Actualiza datos de una tienda existente (sin tocar el estado) en transaccion con auditoria UPDATE.
 * Recibe $antes con la fila previa para registrar datosantes/datosdespues.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_tiendas_actualizar(PDO $pdo, int $id, array $datos, ?string $idActor, array $antes): array
{
    try {
        $pdo->beginTransaction();

        // El estado no se edita aqui; se gestiona con el boton Activar/Desactivar del listado.
        $stmt = $pdo->prepare(
            'UPDATE tiendas SET nombre = :nombre, provincia = :provincia, canton = :canton, '
            . 'distrito = :distrito, direccion = :direccion, telefono = :telefono, correo = :correo, '
            . 'eshub = :eshub WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':provincia' => $datos['provincia'],
            ':canton' => $datos['canton'],
            ':distrito' => $datos['distrito'],
            ':direccion' => $datos['direccion'],
            ':telefono' => $datos['telefono'],
            ':correo' => $datos['correo'],
            ':eshub' => $datos['eshub'],
            ':id' => $id,
        ]);

        // Auditoria con valores previos y nuevos de las columnas editables (sin estado).
        $antesAuditoria = [
            'nombre' => $antes['nombre'] ?? null,
            'correo' => $antes['correo'] ?? null,
            'telefono' => $antes['telefono'] ?? null,
            'provincia' => $antes['provincia'] ?? null,
            'canton' => $antes['canton'] ?? null,
            'distrito' => $antes['distrito'] ?? null,
            'direccion' => $antes['direccion'] ?? null,
            'eshub' => isset($antes['eshub']) ? (int) $antes['eshub'] : null,
        ];
        tcgx_tiendas_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $id, $antesAuditoria, $datos);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'EL CORREO YA ESTA REGISTRADO POR OTRA TIENDA.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR LA TIENDA.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: EDICION DE TIENDA (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: CAMBIO DE ESTADO DE TIENDA (ACTIVAR / DESACTIVAR)
/**
 * Cambia el estado de la tienda (baja logica con INACTIVO o reactivacion con ACTIVO) sin borrado fisico.
 * Conserva historial e integridad referencial. Audita como ACTUALIZAR con el estado previo y nuevo.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_tiendas_cambiar_estado(PDO $pdo, int $id, string $nuevoEstado, ?string $idActor): array
{
    if (!in_array($nuevoEstado, TCGX_TIENDAS_ESTADOS, true)) {
        return ['ok' => false, 'error' => 'ESTADO NO VALIDO.'];
    }

    $actual = tcgx_tiendas_obtener($pdo, $id);
    if ($actual === null) {
        return ['ok' => false, 'error' => 'LA TIENDA NO EXISTE.'];
    }
    if (($actual['estado'] ?? '') === $nuevoEstado) {
        return ['ok' => false, 'error' => 'LA TIENDA YA TIENE ESE ESTADO.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE tiendas SET estado = ? WHERE id = ?');
        $stmt->execute([$nuevoEstado, $id]);
        tcgx_tiendas_auditar(
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
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CAMBIAR EL ESTADO DE LA TIENDA.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: CAMBIO DE ESTADO DE TIENDA (ACTIVAR / DESACTIVAR)
