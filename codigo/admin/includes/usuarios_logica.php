<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del CRUD de usuarios del modulo admin.
 * Centraliza: carga de catalogo geografico, listado, lectura, validacion, alta, edicion,
 * baja logica, generacion de clave y auditoria. Solo consultas preparadas con parametros enlazados.
 * Lo consumen admin/usuarios.php, admin/usuario-crear.php y admin/usuario-editar.php.
 */

// INICIO BLOQUE: CONSTANTES Y CATALOGOS CONTROLADOS
// Valores permitidos alineados con los CHECK de basedatos.sql (sin ENUM en BD).
const TCGX_USUARIOS_PERFILES = ['CLIENTE', 'TIENDA', 'ADMINISTRADOR'];
const TCGX_USUARIOS_ESTADOS = ['ACTIVO', 'BLOQUEADO', 'INACTIVO'];
const TCGX_USUARIOS_LONGITUD_MIN_CLAVE = 10;
// Ruta del catalogo geografico estatico (recurso global del proyecto), resuelta desde admin/includes.
const TCGX_USUARIOS_RUTA_CATALOGO = __DIR__ . '/../../vendor/data/ubicaciones-cr.json';
// FIN BLOQUE: CONSTANTES Y CATALOGOS CONTROLADOS


// INICIO BLOQUE: CARGA DE CATALOGO GEOGRAFICO COSTA RICA
/**
 * Lee el catalogo provincia/canton/distrito desde archivo estatico y lo normaliza a MAYUSCULAS.
 * Estructura devuelta: [ 'PROVINCIA' => [ 'CANTON' => ['DISTRITO', ...], ... ], ... ].
 * Se normaliza una sola vez por peticion mediante cache estatica en la funcion.
 */
function tcgx_usuarios_catalogo_geografico(): array
{
    static $catalogo = null;
    if ($catalogo !== null) {
        return $catalogo;
    }

    $catalogo = [];
    $contenido = @file_get_contents(TCGX_USUARIOS_RUTA_CATALOGO);
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


// INICIO BLOQUE: CATALOGO DE TIENDAS PARA SELECT DE PERFIL TIENDA
/**
 * Devuelve tiendas ACTIVAS (id y nombre) para poblar el select cuando el perfil es TIENDA.
 */
function tcgx_usuarios_listar_tiendas(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT id, nombre FROM tiendas WHERE estado = 'ACTIVO' ORDER BY nombre ASC");
    return $stmt->fetchAll();
}
// FIN BLOQUE: CATALOGO DE TIENDAS PARA SELECT DE PERFIL TIENDA


// INICIO BLOQUE: LISTADO Y LECTURA DE USUARIOS
/**
 * Lista todos los usuarios con el nombre de su tienda (si aplica) para el render del listado.
 */
function tcgx_usuarios_listar(PDO $pdo): array
{
    $sql = 'SELECT u.id, u.nombre, u.correo, u.telefono, u.perfil, u.idtienda, '
        . 'u.estado, u.fecharegistro, t.nombre AS nombretienda '
        . 'FROM usuarios u '
        . 'LEFT JOIN tiendas t ON t.id = u.idtienda '
        . 'ORDER BY u.fecharegistro DESC, u.id ASC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Lee un usuario por su clave primaria; retorna la fila o null si no existe.
 */
function tcgx_usuarios_obtener(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre, correo, telefono, perfil, idtienda, provincia, canton, '
        . 'distrito, direccion, estado, fecharegistro FROM usuarios WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}
// FIN BLOQUE: LISTADO Y LECTURA DE USUARIOS


// INICIO BLOQUE: GENERACION DE CLAVE AUTOMATICA SEGURA
/**
 * Genera una contrasena aleatoria que cumple la politica del proyecto:
 * minimo 10 caracteres, al menos una mayuscula, una minuscula y un numero.
 * Usa random_int (CSPRNG) y mezcla posiciones para no dejar patron fijo.
 */
function tcgx_usuarios_generar_clave(): string
{
    $mayusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minusculas = 'abcdefghijkmnpqrstuvwxyz';
    $numeros = '23456789';
    $todos = $mayusculas . $minusculas . $numeros;

    $longitud = 12;
    $caracteres = [];
    // Garantiza al menos un caracter de cada clase exigida por la politica.
    $caracteres[] = $mayusculas[random_int(0, strlen($mayusculas) - 1)];
    $caracteres[] = $minusculas[random_int(0, strlen($minusculas) - 1)];
    $caracteres[] = $numeros[random_int(0, strlen($numeros) - 1)];
    for ($i = count($caracteres); $i < $longitud; $i++) {
        $caracteres[] = $todos[random_int(0, strlen($todos) - 1)];
    }
    // Mezcla Fisher-Yates con CSPRNG para que las clases obligatorias no queden siempre al inicio.
    for ($i = count($caracteres) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        $tmp = $caracteres[$i];
        $caracteres[$i] = $caracteres[$j];
        $caracteres[$j] = $tmp;
    }
    return implode('', $caracteres);
}
// FIN BLOQUE: GENERACION DE CLAVE AUTOMATICA SEGURA


// INICIO BLOQUE: AUDITORIA DE OPERACIONES DE USUARIOS
/**
 * Inserta una fila en auditorias para CREAR, ACTUALIZAR o ELIMINAR sin datos sensibles (nunca clave/hash).
 */
function tcgx_usuarios_auditar(
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
    $stmt->execute([$idActor, $accion, 'usuarios', $idRegistro, $jsonAntes, $jsonDespues]);
}
// FIN BLOQUE: AUDITORIA DE OPERACIONES DE USUARIOS


// INICIO BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA
/**
 * Valida y normaliza la entrada del formulario de usuario (alta o edicion).
 * Reglas: tipos, longitudes, catalogos controlados, coherencia perfil/tienda y geografia segun catalogo.
 * Datos operativos en MAYUSCULAS (regla del proyecto), sin alterar tildes ni la enie.
 * Retorna: ['errores' => string[], 'datos' => array normalizada para persistir].
 */
function tcgx_usuarios_validar(PDO $pdo, array $post, bool $esEdicion): array
{
    $errores = [];
    $catalogo = tcgx_usuarios_catalogo_geografico();

    // --- Identificador (clave primaria, manual; solo en alta) ---
    $id = mb_strtoupper(trim((string) ($post['id'] ?? '')), 'UTF-8');
    if (!$esEdicion) {
        if ($id === '') {
            $errores[] = 'EL IDENTIFICADOR ES OBLIGATORIO.';
        } elseif (mb_strlen($id, 'UTF-8') > 20) {
            $errores[] = 'EL IDENTIFICADOR NO PUEDE SUPERAR 20 CARACTERES.';
        } elseif (!preg_match('/^[A-Z0-9._-]+$/', $id)) {
            $errores[] = 'EL IDENTIFICADOR SOLO ADMITE LETRAS, NUMEROS, PUNTO, GUION Y GUION BAJO.';
        }
    }

    // --- Nombre ---
    $nombre = mb_strtoupper(trim((string) ($post['nombre'] ?? '')), 'UTF-8');
    if ($nombre === '') {
        $errores[] = 'EL NOMBRE ES OBLIGATORIO.';
    } elseif (mb_strlen($nombre, 'UTF-8') > 120) {
        $errores[] = 'EL NOMBRE NO PUEDE SUPERAR 120 CARACTERES.';
    }

    // --- Correo (unico; se valida formato y luego unicidad en BD) ---
    // Excepcion a la regla de MAYUSCULAS: el correo se almacena siempre en minusculas.
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

    // --- Perfil (catalogo controlado) ---
    $perfil = mb_strtoupper(trim((string) ($post['perfil'] ?? '')), 'UTF-8');
    if (!in_array($perfil, TCGX_USUARIOS_PERFILES, true)) {
        $errores[] = 'EL PERFIL SELECCIONADO NO ES VALIDO.';
    }

    // --- Tienda (obligatoria solo para perfil TIENDA; nula para CLIENTE y ADMINISTRADOR) ---
    $idTiendaRaw = trim((string) ($post['idtienda'] ?? ''));
    $idTienda = null;
    if ($perfil === 'TIENDA') {
        if ($idTiendaRaw === '' || !ctype_digit($idTiendaRaw)) {
            $errores[] = 'EL PERFIL TIENDA REQUIERE SELECCIONAR UNA TIENDA.';
        } else {
            $idTienda = (int) $idTiendaRaw;
            // Verifica existencia de la tienda para no romper la coherencia del CHECK.
            $stmtT = $pdo->prepare('SELECT id FROM tiendas WHERE id = ? LIMIT 1');
            $stmtT->execute([$idTienda]);
            if ($stmtT->fetch() === false) {
                $errores[] = 'LA TIENDA SELECCIONADA NO EXISTE.';
                $idTienda = null;
            }
        }
    }

    // --- Geografia (opcional; si se indica provincia, exige coherencia con el catalogo) ---
    $provincia = mb_strtoupper(trim((string) ($post['provincia'] ?? '')), 'UTF-8');
    $canton = mb_strtoupper(trim((string) ($post['canton'] ?? '')), 'UTF-8');
    $distrito = mb_strtoupper(trim((string) ($post['distrito'] ?? '')), 'UTF-8');
    if ($provincia !== '') {
        if (!isset($catalogo[$provincia])) {
            $errores[] = 'LA PROVINCIA SELECCIONADA NO ES VALIDA.';
        } else {
            if ($canton === '' || !isset($catalogo[$provincia][$canton])) {
                $errores[] = 'EL CANTON SELECCIONADO NO ES VALIDO PARA LA PROVINCIA.';
            } elseif ($distrito === '' || !in_array($distrito, $catalogo[$provincia][$canton], true)) {
                $errores[] = 'EL DISTRITO SELECCIONADO NO ES VALIDO PARA EL CANTON.';
            }
        }
    } else {
        // Sin provincia no se conservan canton ni distrito sueltos.
        $canton = '';
        $distrito = '';
    }

    // --- Direccion (opcional) ---
    $direccion = mb_strtoupper(trim((string) ($post['direccion'] ?? '')), 'UTF-8');
    if (mb_strlen($direccion, 'UTF-8') > 255) {
        $errores[] = 'LA DIRECCION NO PUEDE SUPERAR 255 CARACTERES.';
    }

    $datos = [
        'id' => $id,
        'nombre' => $nombre,
        'correo' => $correo,
        'telefono' => $telefono,
        'perfil' => $perfil,
        'idtienda' => $idTienda,
        'provincia' => $provincia !== '' ? $provincia : null,
        'canton' => $canton !== '' ? $canton : null,
        'distrito' => $distrito !== '' ? $distrito : null,
        'direccion' => $direccion !== '' ? $direccion : null,
    ];

    return ['errores' => $errores, 'datos' => $datos];
}
// FIN BLOQUE: VALIDACION Y NORMALIZACION DE ENTRADA


// INICIO BLOQUE: ALTA DE USUARIO (TRANSACCION + CLAVE AUTOGENERADA + AUDITORIA)
/**
 * Crea un usuario con clave autogenerada (hash seguro) dentro de una transaccion atomica con su auditoria.
 * Retorna: ['ok' => true, 'clave' => <texto plano para mostrar una vez>] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_usuarios_crear(PDO $pdo, array $datos, ?string $idActor): array
{
    $claveTextoPlano = tcgx_usuarios_generar_clave();
    $claveHash = password_hash($claveTextoPlano, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO usuarios (id, nombre, correo, telefono, clavehash, perfil, idtienda, '
            . 'provincia, canton, distrito, direccion, estado) '
            . 'VALUES (:id, :nombre, :correo, :telefono, :clavehash, :perfil, :idtienda, '
            . ':provincia, :canton, :distrito, :direccion, :estado)'
        );
        $stmt->execute([
            ':id' => $datos['id'],
            ':nombre' => $datos['nombre'],
            ':correo' => $datos['correo'],
            ':telefono' => $datos['telefono'],
            ':clavehash' => $claveHash,
            ':perfil' => $datos['perfil'],
            ':idtienda' => $datos['idtienda'],
            ':provincia' => $datos['provincia'],
            ':canton' => $datos['canton'],
            ':distrito' => $datos['distrito'],
            ':direccion' => $datos['direccion'],
            // Todo usuario nuevo nace ACTIVO; no hay selector de estado en el alta (el cambio se hace luego desde acciones).
            ':estado' => 'ACTIVO',
        ]);

        // Auditoria de creacion: copia de columnas no sensibles (nunca clave ni hash).
        $despues = $datos;
        $despues['estado'] = 'ACTIVO';
        tcgx_usuarios_auditar($pdo, $idActor, 'CREAR', $datos['id'], null, $despues);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // 23000: violacion de integridad (clave primaria id o correo unico duplicados).
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'EL IDENTIFICADOR O EL CORREO YA ESTAN REGISTRADOS.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CREAR EL USUARIO.'];
    }

    return ['ok' => true, 'clave' => $claveTextoPlano];
}
// FIN BLOQUE: ALTA DE USUARIO (TRANSACCION + CLAVE AUTOGENERADA + AUDITORIA)


// INICIO BLOQUE: EDICION DE USUARIO (TRANSACCION + AUDITORIA)
/**
 * Actualiza datos de un usuario existente (sin tocar la clave) en transaccion con auditoria UPDATE.
 * Recibe $antes con la fila previa para registrar datosantes/datosdespues.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_usuarios_actualizar(PDO $pdo, string $id, array $datos, ?string $idActor, array $antes): array
{
    try {
        $pdo->beginTransaction();

        // El estado no se edita aqui; se gestiona con el boton Activar/Desactivar del listado.
        $stmt = $pdo->prepare(
            'UPDATE usuarios SET nombre = :nombre, correo = :correo, telefono = :telefono, '
            . 'perfil = :perfil, idtienda = :idtienda, provincia = :provincia, canton = :canton, '
            . 'distrito = :distrito, direccion = :direccion WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':correo' => $datos['correo'],
            ':telefono' => $datos['telefono'],
            ':perfil' => $datos['perfil'],
            ':idtienda' => $datos['idtienda'],
            ':provincia' => $datos['provincia'],
            ':canton' => $datos['canton'],
            ':distrito' => $datos['distrito'],
            ':direccion' => $datos['direccion'],
            ':id' => $id,
        ]);

        // Auditoria con valores previos y nuevos de las columnas editables (sin datos sensibles ni estado).
        $antesAuditoria = [
            'nombre' => $antes['nombre'] ?? null,
            'correo' => $antes['correo'] ?? null,
            'telefono' => $antes['telefono'] ?? null,
            'perfil' => $antes['perfil'] ?? null,
            'idtienda' => $antes['idtienda'] ?? null,
            'provincia' => $antes['provincia'] ?? null,
            'canton' => $antes['canton'] ?? null,
            'distrito' => $antes['distrito'] ?? null,
            'direccion' => $antes['direccion'] ?? null,
        ];
        tcgx_usuarios_auditar($pdo, $idActor, 'ACTUALIZAR', $id, $antesAuditoria, $datos);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'EL CORREO YA ESTA REGISTRADO POR OTRO USUARIO.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR EL USUARIO.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: EDICION DE USUARIO (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: CAMBIO DE ESTADO DE USUARIO (ACTIVAR / DESACTIVAR)
/**
 * Cambia el estado del usuario (baja logica con INACTIVO o reactivacion con ACTIVO) sin borrado fisico.
 * Conserva historial e integridad referencial. Audita como ACTUALIZAR con el estado previo y nuevo.
 * Retorna: ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_usuarios_cambiar_estado(PDO $pdo, string $id, string $nuevoEstado, ?string $idActor): array
{
    if (!in_array($nuevoEstado, TCGX_USUARIOS_ESTADOS, true)) {
        return ['ok' => false, 'error' => 'ESTADO NO VALIDO.'];
    }

    $actual = tcgx_usuarios_obtener($pdo, $id);
    if ($actual === null) {
        return ['ok' => false, 'error' => 'EL USUARIO NO EXISTE.'];
    }
    if (($actual['estado'] ?? '') === $nuevoEstado) {
        return ['ok' => false, 'error' => 'EL USUARIO YA TIENE ESE ESTADO.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE usuarios SET estado = ? WHERE id = ?');
        $stmt->execute([$nuevoEstado, $id]);
        tcgx_usuarios_auditar(
            $pdo,
            $idActor,
            'ACTUALIZAR',
            $id,
            ['estado' => $actual['estado']],
            ['estado' => $nuevoEstado]
        );
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CAMBIAR EL ESTADO DEL USUARIO.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: CAMBIO DE ESTADO DE USUARIO (ACTIVAR / DESACTIVAR)


// INICIO BLOQUE: REGENERACION DE CONTRASENA DE USUARIO
/**
 * Regenera la contrasena de un usuario existente: genera una nueva (politica), guarda solo el hash
 * y audita el evento como ACTUALIZAR SIN registrar el contenido de la clave (regla de seguridad).
 * Retorna: ['ok' => true, 'clave' => <texto plano para mostrar una vez>] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_usuarios_regenerar_clave(PDO $pdo, string $id, ?string $idActor): array
{
    $actual = tcgx_usuarios_obtener($pdo, $id);
    if ($actual === null) {
        return ['ok' => false, 'error' => 'EL USUARIO NO EXISTE.'];
    }

    $claveTextoPlano = tcgx_usuarios_generar_clave();
    $claveHash = password_hash($claveTextoPlano, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE usuarios SET clavehash = ? WHERE id = ?');
        $stmt->execute([$claveHash, $id]);
        // Auditoria del cambio de credencial: se registra el evento, nunca la clave ni el hash.
        tcgx_usuarios_auditar($pdo, $idActor, 'ACTUALIZAR', $id, null, ['evento' => 'REGENERACION DE CONTRASENA']);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE REGENERAR LA CONTRASENA.'];
    }

    return ['ok' => true, 'clave' => $claveTextoPlano];
}
// FIN BLOQUE: REGENERACION DE CONTRASENA DE USUARIO


// INICIO BLOQUE: VALIDACION DE PERFIL PROPIO (DATOS NO SENSIBLES DEL USUARIO EN SESION)
/**
 * Valida y normaliza solo los datos que el propio usuario puede editar en "Mi perfil":
 * correo (minusculas), telefono, geografia (provincia/canton/distrito) y direccion.
 * NO admite cambiar identificador, nombre, perfil, tienda ni estado (datos sensibles).
 * Retorna: ['errores' => string[], 'datos' => array normalizada para persistir].
 */
function tcgx_usuarios_validar_perfil_propio(PDO $pdo, array $post): array
{
    $errores = [];
    $catalogo = tcgx_usuarios_catalogo_geografico();

    // --- Correo (excepcion de minusculas; unicidad la valida el UPDATE por restriccion) ---
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

    // --- Geografia (opcional; si hay provincia exige coherencia con el catalogo) ---
    $provincia = mb_strtoupper(trim((string) ($post['provincia'] ?? '')), 'UTF-8');
    $canton = mb_strtoupper(trim((string) ($post['canton'] ?? '')), 'UTF-8');
    $distrito = mb_strtoupper(trim((string) ($post['distrito'] ?? '')), 'UTF-8');
    if ($provincia !== '') {
        if (!isset($catalogo[$provincia])) {
            $errores[] = 'LA PROVINCIA SELECCIONADA NO ES VALIDA.';
        } elseif ($canton === '' || !isset($catalogo[$provincia][$canton])) {
            $errores[] = 'EL CANTON SELECCIONADO NO ES VALIDO PARA LA PROVINCIA.';
        } elseif ($distrito === '' || !in_array($distrito, $catalogo[$provincia][$canton], true)) {
            $errores[] = 'EL DISTRITO SELECCIONADO NO ES VALIDO PARA EL CANTON.';
        }
    } else {
        $canton = '';
        $distrito = '';
    }

    // --- Direccion (opcional) ---
    $direccion = mb_strtoupper(trim((string) ($post['direccion'] ?? '')), 'UTF-8');
    if (mb_strlen($direccion, 'UTF-8') > 255) {
        $errores[] = 'LA DIRECCION NO PUEDE SUPERAR 255 CARACTERES.';
    }

    $datos = [
        'correo' => $correo,
        'telefono' => $telefono,
        'provincia' => $provincia !== '' ? $provincia : null,
        'canton' => $canton !== '' ? $canton : null,
        'distrito' => $distrito !== '' ? $distrito : null,
        'direccion' => $direccion !== '' ? $direccion : null,
    ];

    return ['errores' => $errores, 'datos' => $datos];
}
// FIN BLOQUE: VALIDACION DE PERFIL PROPIO


// INICIO BLOQUE: VALIDACION DE CONTRASENA MANUAL (POLITICA DEL PROYECTO)
/**
 * Valida una contrasena escrita por el usuario contra la politica del proyecto
 * (minimo 10 caracteres, al menos una mayuscula, una minuscula y un numero) y la confirmacion.
 * Retorna un arreglo de mensajes de error (vacio si la contrasena es valida).
 */
function tcgx_usuarios_validar_clave(string $clave, string $confirmacion): array
{
    $errores = [];

    if (mb_strlen($clave, 'UTF-8') < TCGX_USUARIOS_LONGITUD_MIN_CLAVE) {
        $errores[] = 'LA CONTRASENA DEBE TENER AL MENOS ' . TCGX_USUARIOS_LONGITUD_MIN_CLAVE . ' CARACTERES.';
    }
    if (!preg_match('/[A-Z]/', $clave)) {
        $errores[] = 'LA CONTRASENA DEBE INCLUIR AL MENOS UNA LETRA MAYUSCULA.';
    }
    if (!preg_match('/[a-z]/', $clave)) {
        $errores[] = 'LA CONTRASENA DEBE INCLUIR AL MENOS UNA LETRA MINUSCULA.';
    }
    if (!preg_match('/[0-9]/', $clave)) {
        $errores[] = 'LA CONTRASENA DEBE INCLUIR AL MENOS UN NUMERO.';
    }
    if ($clave !== $confirmacion) {
        $errores[] = 'LA CONFIRMACION NO COINCIDE CON LA CONTRASENA.';
    }

    return $errores;
}
// FIN BLOQUE: VALIDACION DE CONTRASENA MANUAL


// INICIO BLOQUE: ACTUALIZACION DE PERFIL PROPIO (DATOS NO SENSIBLES + CLAVE OPCIONAL)
/**
 * Actualiza, en transaccion y con auditoria, los datos no sensibles del propio usuario
 * (correo, telefono, geografia, direccion) y, opcionalmente, su contrasena escrita por el.
 * Si $nuevaClave es null no se toca la credencial. Nunca se persiste ni audita la clave en claro.
 * Retorna: ['ok' => true, 'clave_cambiada' => bool] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_usuarios_actualizar_perfil_propio(PDO $pdo, string $id, array $datos, ?string $nuevaClave, array $antes): array
{
    // --- Bloque seguridad: si llega clave nueva, impedir que sea igual a la actual ---
    $claveCambiada = false;
    $claveHash = null;
    if ($nuevaClave !== null) {
        $stmtHash = $pdo->prepare('SELECT clavehash FROM usuarios WHERE id = ? LIMIT 1');
        $stmtHash->execute([$id]);
        $filaHash = $stmtHash->fetch();
        if ($filaHash !== false && isset($filaHash['clavehash']) && password_verify($nuevaClave, (string) $filaHash['clavehash'])) {
            return ['ok' => false, 'error' => 'LA NUEVA CONTRASENA NO PUEDE SER IGUAL A LA ACTUAL.'];
        }
        $claveHash = password_hash($nuevaClave, PASSWORD_DEFAULT);
        $claveCambiada = true;
    }
    // --- Fin bloque seguridad ---

    try {
        $pdo->beginTransaction();

        if ($claveCambiada) {
            $stmt = $pdo->prepare(
                'UPDATE usuarios SET correo = :correo, telefono = :telefono, provincia = :provincia, '
                . 'canton = :canton, distrito = :distrito, direccion = :direccion, clavehash = :clavehash WHERE id = :id'
            );
            $stmt->execute([
                ':correo' => $datos['correo'],
                ':telefono' => $datos['telefono'],
                ':provincia' => $datos['provincia'],
                ':canton' => $datos['canton'],
                ':distrito' => $datos['distrito'],
                ':direccion' => $datos['direccion'],
                ':clavehash' => $claveHash,
                ':id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE usuarios SET correo = :correo, telefono = :telefono, provincia = :provincia, '
                . 'canton = :canton, distrito = :distrito, direccion = :direccion WHERE id = :id'
            );
            $stmt->execute([
                ':correo' => $datos['correo'],
                ':telefono' => $datos['telefono'],
                ':provincia' => $datos['provincia'],
                ':canton' => $datos['canton'],
                ':distrito' => $datos['distrito'],
                ':direccion' => $datos['direccion'],
                ':id' => $id,
            ]);
        }

        // Auditoria de los datos no sensibles; el cambio de clave se marca como evento, nunca su contenido.
        $antesAuditoria = [
            'correo' => $antes['correo'] ?? null,
            'telefono' => $antes['telefono'] ?? null,
            'provincia' => $antes['provincia'] ?? null,
            'canton' => $antes['canton'] ?? null,
            'distrito' => $antes['distrito'] ?? null,
            'direccion' => $antes['direccion'] ?? null,
        ];
        $despuesAuditoria = $datos;
        if ($claveCambiada) {
            $despuesAuditoria['evento'] = 'CAMBIO DE CONTRASENA';
        }
        tcgx_usuarios_auditar($pdo, $id, 'ACTUALIZAR', $id, $antesAuditoria, $despuesAuditoria);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'EL CORREO YA ESTA REGISTRADO POR OTRO USUARIO.'];
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR EL PERFIL.'];
    }

    return ['ok' => true, 'clave_cambiada' => $claveCambiada];
}
// FIN BLOQUE: ACTUALIZACION DE PERFIL PROPIO
