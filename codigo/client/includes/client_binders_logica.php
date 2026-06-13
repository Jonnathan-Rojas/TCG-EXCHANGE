<?php
declare(strict_types=1);

/**
 * Capa de logica de binders y productos del modulo client.
 * CRUD acotado al idusuario de sesion; imagenes en images/binders/{idbinder}/.
 */

require_once __DIR__ . '/client_logica.php';

// INICIO BLOQUE: CONSTANTES CONTROLADAS DE BINDERS Y PRODUCTOS
const TCGX_CLIENT_BINDER_ESTADOS = ['ACTIVO', 'BLOQUEADO', 'INACTIVO'];
const TCGX_CLIENT_PRODUCTO_MONEDAS = ['COLONES', 'DOLARES'];
const TCGX_CLIENT_PRODUCTO_IDIOMAS = ['ESPAÑOL', 'INGLÉS', 'JAPONÉS', 'CHINO', 'OTRO'];
const TCGX_CLIENT_PRODUCTO_CONDICIONES = [
    'NEAR MINT',
    'LIGHTLY PLAYED',
    'MODERATELY PLAYED',
    'HEAVILY PLAYED',
    'DAMAGED',
];
const TCGX_CLIENT_IMG_MAX_BYTES = 5242880;
const TCGX_CLIENT_IMG_MAX_POR_PRODUCTO = 5;
const TCGX_CLIENT_IMG_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
// FIN BLOQUE: CONSTANTES CONTROLADAS DE BINDERS Y PRODUCTOS


// INICIO BLOQUE: RUTAS Y AUDITORIA
/**
 * Directorio fisico del binder: images/binders/{idbinder}/ en la raiz del proyecto.
 */
function tcgx_client_binder_img_dir(int $idBinder): string
{
    return dirname(__DIR__, 2) . '/images/binders/' . $idBinder;
}

/**
 * URL publica hacia una imagen de producto almacenada bajo images/binders/.
 */
function tcgx_client_binder_img_url(int $idBinder, string $nombreArchivo): string
{
    return tcgexchange_url_recurso_proyecto('images/binders/' . $idBinder . '/' . rawurlencode($nombreArchivo));
}

/**
 * Inserta fila en auditorias para operaciones sobre binders, productos o imagenes.
 */
function tcgx_client_binders_auditar(PDO $pdo, ?string $idActor, string $accion, string $tabla, string $idRegistro, ?array $antes, ?array $despues): void
{
    $jsonAntes = $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $jsonDespues = $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $stmt = $pdo->prepare(
        'INSERT INTO auditorias (idusuario, accion, tablaafectada, idregistro, datosantes, datosdespues) '
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idActor, $accion, $tabla, $idRegistro, $jsonAntes, $jsonDespues]);
}
// FIN BLOQUE: RUTAS Y AUDITORIA


// INICIO BLOQUE: CONSULTAS DE BINDERS
/**
 * Listado de binders del usuario de sesion.
 */
function tcgx_client_binders_listar(PDO $pdo, string $idUsuario): array
{
    $sql = 'SELECT b.id, b.juego, b.nombre, b.descripcion, b.estado, b.fecharegistro, '
        . '(SELECT COUNT(*) FROM productos_binder p WHERE p.idbinder = b.id AND p.estado = \'ACTIVO\') AS totalproductos '
        . 'FROM binders b WHERE b.idusuario = ? ORDER BY b.fecharegistro DESC, b.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);
    return $stmt->fetchAll();
}

/**
 * Obtiene binder verificando propiedad del usuario de sesion.
 */
function tcgx_client_binders_obtener(PDO $pdo, int $idBinder, string $idUsuario): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, idusuario, juego, nombre, descripcion, estado, fecharegistro '
        . 'FROM binders WHERE id = ? AND idusuario = ? LIMIT 1'
    );
    $stmt->execute([$idBinder, $idUsuario]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}
// FIN BLOQUE: CONSULTAS DE BINDERS


// INICIO BLOQUE: VALIDACION Y MUTACION DE BINDERS
/**
 * Valida alta o edicion de binder desde POST.
 */
function tcgx_client_binders_validar(array $post): array
{
    $errores = [];
    $juego = mb_strtoupper(trim((string) ($post['juego'] ?? '')), 'UTF-8');
    $nombre = mb_strtoupper(trim((string) ($post['nombre'] ?? '')), 'UTF-8');
    $descripcion = mb_strtoupper(trim((string) ($post['descripcion'] ?? '')), 'UTF-8');

    if ($juego === '' || mb_strlen($juego, 'UTF-8') > 50) {
        $errores[] = 'EL TCG ES OBLIGATORIO (MAXIMO 50 CARACTERES).';
    }
    if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 120) {
        $errores[] = 'EL NOMBRE ES OBLIGATORIO (MAXIMO 120 CARACTERES).';
    }
    if (mb_strlen($descripcion, 'UTF-8') > 255) {
        $errores[] = 'LA DESCRIPCION NO PUEDE SUPERAR 255 CARACTERES.';
    }

    return [
        'errores' => $errores,
        'datos' => [
            'juego' => $juego,
            'nombre' => $nombre,
            'descripcion' => $descripcion === '' ? null : $descripcion,
        ],
    ];
}

/**
 * Crea binder del usuario de sesion.
 */
function tcgx_client_binders_crear(PDO $pdo, array $datos, string $idUsuario, ?string $idActor): array
{
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO binders (idusuario, juego, nombre, descripcion, estado) '
            . 'VALUES (?, ?, ?, ?, \'ACTIVO\')'
        );
        $stmt->execute([
            $idUsuario,
            $datos['juego'],
            $datos['nombre'],
            $datos['descripcion'],
        ]);
        $idBinder = (int) $pdo->lastInsertId();
        $dir = tcgx_client_binder_img_dir($idBinder);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('NO FUE POSIBLE CREAR EL DIRECTORIO DEL BINDER.');
        }
        tcgx_client_binders_auditar($pdo, $idActor, 'CREAR', 'binders', (string) $idBinder, null, [
            'juego' => $datos['juego'],
            'nombre' => $datos['nombre'],
        ]);
        $pdo->commit();
        return ['ok' => true, 'id' => $idBinder];
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CREAR EL BINDER.'];
    }
}

/**
 * Actualiza binder del usuario de sesion.
 */
function tcgx_client_binders_actualizar(PDO $pdo, int $idBinder, array $datos, string $idUsuario, ?string $idActor): array
{
    $antes = tcgx_client_binders_obtener($pdo, $idBinder, $idUsuario);
    if ($antes === null) {
        return ['ok' => false, 'error' => 'EL BINDER NO EXISTE O NO LE PERTENECE.'];
    }
    try {
        $stmt = $pdo->prepare(
            'UPDATE binders SET juego = ?, nombre = ?, descripcion = ? WHERE id = ? AND idusuario = ?'
        );
        $stmt->execute([
            $datos['juego'],
            $datos['nombre'],
            $datos['descripcion'],
            $idBinder,
            $idUsuario,
        ]);
        tcgx_client_binders_auditar($pdo, $idActor, 'ACTUALIZAR', 'binders', (string) $idBinder, $antes, $datos);
        return ['ok' => true];
    } catch (Throwable) {
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR EL BINDER.'];
    }
}

/**
 * Elimina binder, sus productos, imagenes fisicas y registros en transaccion.
 */
function tcgx_client_binders_eliminar(PDO $pdo, int $idBinder, string $idUsuario, ?string $idActor): array
{
    $binder = tcgx_client_binders_obtener($pdo, $idBinder, $idUsuario);
    if ($binder === null) {
        return ['ok' => false, 'error' => 'EL BINDER NO EXISTE O NO LE PERTENECE.'];
    }
    try {
        $pdo->beginTransaction();
        $productos = tcgx_client_productos_listar($pdo, $idBinder, $idUsuario);
        foreach ($productos as $prod) {
            tcgx_client_productos_eliminar_interno($pdo, (int) $prod['id'], $idBinder, $idActor, false);
        }
        $stmt = $pdo->prepare('DELETE FROM binders WHERE id = ? AND idusuario = ?');
        $stmt->execute([$idBinder, $idUsuario]);
        $dir = tcgx_client_binder_img_dir($idBinder);
        if (is_dir($dir)) {
            $archivos = glob($dir . '/*') ?: [];
            foreach ($archivos as $archivo) {
                if (is_file($archivo)) {
                    unlink($archivo);
                }
            }
            rmdir($dir);
        }
        tcgx_client_binders_auditar($pdo, $idActor, 'ELIMINAR', 'binders', (string) $idBinder, $binder, null);
        $pdo->commit();
        return ['ok' => true];
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ELIMINAR EL BINDER.'];
    }
}
// FIN BLOQUE: VALIDACION Y MUTACION DE BINDERS


// INICIO BLOQUE: CONSULTAS DE PRODUCTOS
/**
 * Productos activos de un binder del usuario de sesion.
 */
function tcgx_client_productos_listar(PDO $pdo, int $idBinder, string $idUsuario): array
{
    if (tcgx_client_binders_obtener($pdo, $idBinder, $idUsuario) === null) {
        return [];
    }
    $sql = 'SELECT p.id, p.idbinder, p.nombrecarta, p.expansion, p.numerocarta, p.rareza, p.idioma, '
        . 'p.condicion, p.cantidad, p.precioventa, p.tipomoneda, p.publicado, p.estado, p.fecharegistro, '
        . '(SELECT COUNT(*) FROM imagenes_producto i WHERE i.idproducto = p.id) AS totalimagenes '
        . 'FROM productos_binder p WHERE p.idbinder = ? AND p.estado = \'ACTIVO\' ORDER BY p.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idBinder]);
    return $stmt->fetchAll();
}

/**
 * Obtiene producto verificando propiedad via binder del usuario.
 */
function tcgx_client_productos_obtener(PDO $pdo, int $idProducto, string $idUsuario): ?array
{
    $stmt = $pdo->prepare(
        'SELECT p.id, p.idbinder, p.nombrecarta, p.expansion, p.numerocarta, p.rareza, p.idioma, '
        . 'p.condicion, p.cantidad, p.precioventa, p.tipomoneda, p.publicado, p.estado, p.fecharegistro, '
        . 'b.idusuario '
        . 'FROM productos_binder p INNER JOIN binders b ON b.id = p.idbinder '
        . 'WHERE p.id = ? AND b.idusuario = ? LIMIT 1'
    );
    $stmt->execute([$idProducto, $idUsuario]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Imagenes de un producto del usuario de sesion.
 */
function tcgx_client_productos_imagenes(PDO $pdo, int $idProducto, string $idUsuario): array
{
    if (tcgx_client_productos_obtener($pdo, $idProducto, $idUsuario) === null) {
        return [];
    }
    $stmt = $pdo->prepare(
        'SELECT id, idproducto, nombreimagen, fecharegistro FROM imagenes_producto WHERE idproducto = ? ORDER BY id ASC'
    );
    $stmt->execute([$idProducto]);
    return $stmt->fetchAll();
}
// FIN BLOQUE: CONSULTAS DE PRODUCTOS


// INICIO BLOQUE: VALIDACION Y MUTACION DE PRODUCTOS
/**
 * Valida alta o edicion de producto desde POST.
 */
function tcgx_client_productos_validar(array $post): array
{
    $errores = [];
    $nombrecarta = mb_strtoupper(trim((string) ($post['nombrecarta'] ?? '')), 'UTF-8');
    $expansion = mb_strtoupper(trim((string) ($post['expansion'] ?? '')), 'UTF-8');
    $numerocarta = mb_strtoupper(trim((string) ($post['numerocarta'] ?? '')), 'UTF-8');
    $rareza = mb_strtoupper(trim((string) ($post['rareza'] ?? '')), 'UTF-8');
    $idioma = mb_strtoupper(trim((string) ($post['idioma'] ?? '')), 'UTF-8');
    $condicion = mb_strtoupper(trim((string) ($post['condicion'] ?? '')), 'UTF-8');
    $cantidadRaw = trim((string) ($post['cantidad'] ?? ''));
    $precioRaw = trim((string) ($post['precioventa'] ?? ''));
    $moneda = mb_strtoupper(trim((string) ($post['tipomoneda'] ?? '')), 'UTF-8');
    $publicadoRaw = trim((string) ($post['publicado'] ?? '0'));

    if ($nombrecarta === '' || mb_strlen($nombrecarta, 'UTF-8') > 150) {
        $errores[] = 'EL NOMBRE DE LA CARTA ES OBLIGATORIO (MAXIMO 150 CARACTERES).';
    }
    if (mb_strlen($expansion, 'UTF-8') > 100) {
        $errores[] = 'LA EXPANSION NO PUEDE SUPERAR 100 CARACTERES.';
    }
    if (mb_strlen($numerocarta, 'UTF-8') > 40) {
        $errores[] = 'EL NUMERO DE CARTA NO PUEDE SUPERAR 40 CARACTERES.';
    }
    if (mb_strlen($rareza, 'UTF-8') > 50) {
        $errores[] = 'LA RAREZA NO PUEDE SUPERAR 50 CARACTERES.';
    }
    if ($idioma === '' || !in_array($idioma, TCGX_CLIENT_PRODUCTO_IDIOMAS, true)) {
        $errores[] = 'EL IDIOMA DEBE SER ESPAÑOL, INGLÉS, JAPONÉS, CHINO U OTRO.';
    }
    if ($condicion === '' || !in_array($condicion, TCGX_CLIENT_PRODUCTO_CONDICIONES, true)) {
        $errores[] = 'LA CONDICION DEBE SER NEAR MINT, LIGHTLY PLAYED, MODERATELY PLAYED, HEAVILY PLAYED O DAMAGED.';
    }
    if ($cantidadRaw === '' || !ctype_digit($cantidadRaw) || (int) $cantidadRaw < 1) {
        $errores[] = 'LA CANTIDAD DEBE SER UN ENTERO MAYOR O IGUAL A 1.';
    }
    if ($precioRaw === '' || !is_numeric($precioRaw) || (float) $precioRaw < 0) {
        $errores[] = 'EL PRECIO DE VENTA DEBE SER UN NUMERO MAYOR O IGUAL A 0.';
    }
    if (!in_array($moneda, TCGX_CLIENT_PRODUCTO_MONEDAS, true)) {
        $errores[] = 'LA MONEDA DEBE SER COLONES O DOLARES.';
    }
    $publicado = ($publicadoRaw === '1') ? 1 : 0;

    return [
        'errores' => $errores,
        'datos' => [
            'nombrecarta' => $nombrecarta,
            'expansion' => $expansion === '' ? null : $expansion,
            'numerocarta' => $numerocarta === '' ? null : $numerocarta,
            'rareza' => $rareza === '' ? null : $rareza,
            'idioma' => $idioma,
            'condicion' => $condicion,
            'cantidad' => (int) $cantidadRaw,
            'precioventa' => number_format((float) $precioRaw, 2, '.', ''),
            'tipomoneda' => $moneda,
            'publicado' => $publicado,
        ],
    ];
}

/**
 * Crea producto en binder del usuario de sesion.
 */
function tcgx_client_productos_crear(PDO $pdo, int $idBinder, array $datos, string $idUsuario, ?string $idActor): array
{
    if (tcgx_client_binders_obtener($pdo, $idBinder, $idUsuario) === null) {
        return ['ok' => false, 'error' => 'EL BINDER NO EXISTE O NO LE PERTENECE.'];
    }
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO productos_binder (idbinder, nombrecarta, expansion, numerocarta, rareza, idioma, condicion, '
            . 'cantidad, precioventa, tipomoneda, publicado, estado) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'ACTIVO\')'
        );
        $stmt->execute([
            $idBinder,
            $datos['nombrecarta'],
            $datos['expansion'],
            $datos['numerocarta'],
            $datos['rareza'],
            $datos['idioma'],
            $datos['condicion'],
            $datos['cantidad'],
            $datos['precioventa'],
            $datos['tipomoneda'],
            $datos['publicado'],
        ]);
        $idProducto = (int) $pdo->lastInsertId();
        tcgx_client_binders_auditar($pdo, $idActor, 'CREAR', 'productos_binder', (string) $idProducto, null, [
            'idbinder' => $idBinder,
            'nombrecarta' => $datos['nombrecarta'],
        ]);
        $pdo->commit();
        return ['ok' => true, 'id' => $idProducto];
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CREAR EL PRODUCTO.'];
    }
}

/**
 * Actualiza producto del usuario de sesion.
 */
function tcgx_client_productos_actualizar(PDO $pdo, int $idProducto, array $datos, string $idUsuario, ?string $idActor): array
{
    $antes = tcgx_client_productos_obtener($pdo, $idProducto, $idUsuario);
    if ($antes === null) {
        return ['ok' => false, 'error' => 'EL PRODUCTO NO EXISTE O NO LE PERTENECE.'];
    }
    try {
        $stmt = $pdo->prepare(
            'UPDATE productos_binder SET nombrecarta = ?, expansion = ?, numerocarta = ?, rareza = ?, idioma = ?, '
            . 'condicion = ?, cantidad = ?, precioventa = ?, tipomoneda = ?, publicado = ? WHERE id = ?'
        );
        $stmt->execute([
            $datos['nombrecarta'],
            $datos['expansion'],
            $datos['numerocarta'],
            $datos['rareza'],
            $datos['idioma'],
            $datos['condicion'],
            $datos['cantidad'],
            $datos['precioventa'],
            $datos['tipomoneda'],
            $datos['publicado'],
            $idProducto,
        ]);
        tcgx_client_binders_auditar($pdo, $idActor, 'ACTUALIZAR', 'productos_binder', (string) $idProducto, $antes, $datos);
        return ['ok' => true];
    } catch (Throwable) {
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR EL PRODUCTO.'];
    }
}

/**
 * Alterna publicacion de producto (0/1).
 */
function tcgx_client_productos_toggle_publicado(PDO $pdo, int $idProducto, string $idUsuario, ?string $idActor): array
{
    $prod = tcgx_client_productos_obtener($pdo, $idProducto, $idUsuario);
    if ($prod === null) {
        return ['ok' => false, 'error' => 'EL PRODUCTO NO EXISTE O NO LE PERTENECE.'];
    }
    $nuevo = ((int) ($prod['publicado'] ?? 0) === 1) ? 0 : 1;
    try {
        $stmt = $pdo->prepare('UPDATE productos_binder SET publicado = ? WHERE id = ?');
        $stmt->execute([$nuevo, $idProducto]);
        tcgx_client_binders_auditar($pdo, $idActor, 'ACTUALIZAR', 'productos_binder', (string) $idProducto, ['publicado' => (int) $prod['publicado']], ['publicado' => $nuevo]);
        return ['ok' => true, 'publicado' => $nuevo];
    } catch (Throwable) {
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CAMBIAR LA PUBLICACION.'];
    }
}

/**
 * Elimina producto e imagenes asociadas (uso interno y publico).
 */
function tcgx_client_productos_eliminar(PDO $pdo, int $idProducto, string $idUsuario, ?string $idActor): array
{
    $prod = tcgx_client_productos_obtener($pdo, $idProducto, $idUsuario);
    if ($prod === null) {
        return ['ok' => false, 'error' => 'EL PRODUCTO NO EXISTE O NO LE PERTENECE.'];
    }
    try {
        $pdo->beginTransaction();
        tcgx_client_productos_eliminar_interno($pdo, $idProducto, (int) $prod['idbinder'], $idActor, true);
        $pdo->commit();
        return ['ok' => true];
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ELIMINAR EL PRODUCTO.'];
    }
}

/**
 * Borrado interno de producto: archivos fisicos, imagenes_producto y fila productos_binder.
 */
function tcgx_client_productos_eliminar_interno(PDO $pdo, int $idProducto, int $idBinder, ?string $idActor, bool $auditar): void
{
    $imagenes = tcgx_client_productos_imagenes_sin_auth($pdo, $idProducto);
    $dir = tcgx_client_binder_img_dir($idBinder);
    foreach ($imagenes as $img) {
        $ruta = $dir . '/' . (string) $img['nombreimagen'];
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
    $stmtImg = $pdo->prepare('DELETE FROM imagenes_producto WHERE idproducto = ?');
    $stmtImg->execute([$idProducto]);
    $stmtProd = $pdo->prepare('DELETE FROM productos_binder WHERE id = ?');
    $stmtProd->execute([$idProducto]);
    if ($auditar) {
        tcgx_client_binders_auditar($pdo, $idActor, 'ELIMINAR', 'productos_binder', (string) $idProducto, ['id' => $idProducto], null);
    }
}

/**
 * Lectura de imagenes sin verificar usuario (solo uso interno en transacciones del binder).
 */
function tcgx_client_productos_imagenes_sin_auth(PDO $pdo, int $idProducto): array
{
    $stmt = $pdo->prepare('SELECT id, nombreimagen FROM imagenes_producto WHERE idproducto = ?');
    $stmt->execute([$idProducto]);
    return $stmt->fetchAll();
}
// FIN BLOQUE: VALIDACION Y MUTACION DE PRODUCTOS


// INICIO BLOQUE: IMAGENES DE PRODUCTO
/**
 * Valida archivos de imagen subidos para un producto.
 */
function tcgx_client_productos_validar_imagenes(array $files, array &$errores): array
{
    $resultado = [];
    $nombres = (array) ($files['name'] ?? []);
    $tipos = (array) ($files['type'] ?? []);
    $tmp = (array) ($files['tmp_name'] ?? []);
    $erroresUpload = (array) ($files['error'] ?? []);
    $tamanos = (array) ($files['size'] ?? []);
    $total = count($nombres);

    for ($i = 0; $i < $total; $i++) {
        if ((int) ($erroresUpload[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ((int) ($erroresUpload[$i] ?? 0) !== UPLOAD_ERR_OK) {
            $errores[] = 'ERROR AL SUBIR UNA DE LAS IMAGENES.';
            continue;
        }
        $mime = (string) ($tipos[$i] ?? '');
        if (!isset(TCGX_CLIENT_IMG_MIMES[$mime])) {
            $errores[] = 'FORMATO DE IMAGEN NO PERMITIDO (SOLO JPG, PNG O WEBP).';
            continue;
        }
        if ((int) ($tamanos[$i] ?? 0) > TCGX_CLIENT_IMG_MAX_BYTES) {
            $errores[] = 'CADA IMAGEN NO PUEDE SUPERAR 5 MB.';
            continue;
        }
        $resultado[] = [
            'tmp_name' => (string) ($tmp[$i] ?? ''),
            'extension' => TCGX_CLIENT_IMG_MIMES[$mime],
        ];
    }

    if (count($resultado) > TCGX_CLIENT_IMG_MAX_POR_PRODUCTO) {
        $errores[] = 'MAXIMO ' . TCGX_CLIENT_IMG_MAX_POR_PRODUCTO . ' IMAGENES POR PRODUCTO.';
        return [];
    }

    return $resultado;
}

/**
 * Guarda imagenes validadas en disco y registra metadatos en imagenes_producto.
 */
function tcgx_client_productos_guardar_imagenes(PDO $pdo, int $idProducto, int $idBinder, string $nombrecarta, array $imagenes, string $idUsuario, ?string $idActor): array
{
    if (tcgx_client_productos_obtener($pdo, $idProducto, $idUsuario) === null) {
        return ['ok' => false, 'error' => 'EL PRODUCTO NO EXISTE O NO LE PERTENECE.'];
    }
    if ($imagenes === []) {
        return ['ok' => true];
    }

    $existentes = tcgx_client_productos_imagenes($pdo, $idProducto, $idUsuario);
    if (count($existentes) + count($imagenes) > TCGX_CLIENT_IMG_MAX_POR_PRODUCTO) {
        return ['ok' => false, 'error' => 'MAXIMO ' . TCGX_CLIENT_IMG_MAX_POR_PRODUCTO . ' IMAGENES POR PRODUCTO.'];
    }

    $dir = tcgx_client_binder_img_dir($idBinder);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CREAR EL DIRECTORIO DE IMAGENES.'];
    }

    $baseNombre = (string) $idProducto . preg_replace('/\s+/', '', mb_strtoupper($nombrecarta, 'UTF-8'));
    $consecutivo = count($existentes) + 1;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare(
            'INSERT INTO imagenes_producto (idproducto, nombreimagen) VALUES (?, ?)'
        );
        foreach ($imagenes as $img) {
            $nombreArchivo = $baseNombre . $consecutivo . '.' . $img['extension'];
            $rutaDestino = $dir . '/' . $nombreArchivo;
            if (!move_uploaded_file($img['tmp_name'], $rutaDestino)) {
                throw new RuntimeException('FALLO AL MOVER IMAGEN.');
            }
            $stmt->execute([$idProducto, $nombreArchivo]);
            tcgx_client_binders_auditar($pdo, $idActor, 'CREAR', 'imagenes_producto', (string) $pdo->lastInsertId(), null, [
                'idproducto' => $idProducto,
                'nombreimagen' => $nombreArchivo,
            ]);
            $consecutivo++;
        }
        $pdo->commit();
        return ['ok' => true];
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE GUARDAR LAS IMAGENES.'];
    }
}

/**
 * Elimina una imagen de producto (archivo fisico y registro).
 */
function tcgx_client_productos_eliminar_imagen(PDO $pdo, int $idImagen, int $idProducto, string $idUsuario, ?string $idActor): array
{
    $prod = tcgx_client_productos_obtener($pdo, $idProducto, $idUsuario);
    if ($prod === null) {
        return ['ok' => false, 'error' => 'EL PRODUCTO NO EXISTE O NO LE PERTENECE.'];
    }
    $stmt = $pdo->prepare(
        'SELECT id, nombreimagen FROM imagenes_producto WHERE id = ? AND idproducto = ? LIMIT 1'
    );
    $stmt->execute([$idImagen, $idProducto]);
    $img = $stmt->fetch();
    if ($img === false) {
        return ['ok' => false, 'error' => 'LA IMAGEN NO EXISTE.'];
    }
    $ruta = tcgx_client_binder_img_dir((int) $prod['idbinder']) . '/' . (string) $img['nombreimagen'];
    if (is_file($ruta)) {
        unlink($ruta);
    }
    $stmtDel = $pdo->prepare('DELETE FROM imagenes_producto WHERE id = ? AND idproducto = ?');
    $stmtDel->execute([$idImagen, $idProducto]);
    tcgx_client_binders_auditar($pdo, $idActor, 'ELIMINAR', 'imagenes_producto', (string) $idImagen, $img, null);
    return ['ok' => true];
}
// FIN BLOQUE: IMAGENES DE PRODUCTO
