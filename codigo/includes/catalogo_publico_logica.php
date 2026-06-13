<?php
declare(strict_types=1);

/**
 * Logica del catalogo publico (Buscar Cartas): productos publicados de todos los binders
 * excepto usuarios en lista negra o con cuenta no operativa.
 */

require_once __DIR__ . '/rutas_assets.php';

// INICIO BLOQUE: RUTAS DE IMAGEN DEL CATALOGO PUBLICO
/**
 * Directorio fisico de imagenes de un binder en la raiz del proyecto.
 */
function tcgx_catalogo_publico_img_dir(int $idBinder): string
{
    return dirname(__DIR__) . '/images/binders/' . $idBinder;
}

/**
 * URL publica hacia una imagen de producto almacenada bajo images/binders/.
 */
function tcgx_catalogo_publico_img_url(int $idBinder, string $nombreArchivo): string
{
    return tcgexchange_url_recurso_proyecto('images/binders/' . $idBinder . '/' . rawurlencode($nombreArchivo));
}
// FIN BLOQUE: RUTAS DE IMAGEN DEL CATALOGO PUBLICO


// INICIO BLOQUE: CONSULTA DEL CATALOGO PUBLICO
/**
 * Fragmento SQL compartido: productos visibles en catalogo publico (sin lista negra).
 */
function tcgx_catalogo_publico_sql_base(): string
{
    return 'FROM productos_binder p '
        . 'INNER JOIN binders b ON b.id = p.idbinder '
        . 'INNER JOIN usuarios u ON u.id = b.idusuario '
        . 'LEFT JOIN evaluaciones e ON e.idusuario = u.id '
        . 'WHERE p.publicado = 1 '
        . 'AND p.estado = \'ACTIVO\' '
        . 'AND b.estado = \'ACTIVO\' '
        . 'AND u.estado = \'ACTIVO\' '
        . 'AND (e.listanegra IS NULL OR e.listanegra = 0)';
}

/**
 * Lista productos publicados de binders activos cuyo propietario no esta en lista negra.
 *
 * @return list<array<string, mixed>>
 */
function tcgx_catalogo_publico_listar(PDO $pdo): array
{
    $sql = 'SELECT p.id, p.idbinder, p.nombrecarta, p.expansion, p.numerocarta, p.rareza, p.idioma, '
        . 'p.condicion, p.cantidad, p.precioventa, p.tipomoneda, p.fecharegistro, '
        . 'b.juego, u.telefono, '
        . '(SELECT i.nombreimagen FROM imagenes_producto i WHERE i.idproducto = p.id ORDER BY i.id ASC LIMIT 1) AS nombreimagen '
        . tcgx_catalogo_publico_sql_base()
        . ' ORDER BY p.fecharegistro DESC, p.id DESC';

    $stmt = $pdo->query($sql);
    $filas = $stmt->fetchAll();
    $resultado = [];

    foreach ($filas as $fila) {
        $idBinder = (int) ($fila['idbinder'] ?? 0);
        $nombreImagen = trim((string) ($fila['nombreimagen'] ?? ''));
        $fila['url_imagen'] = null;
        if ($idBinder > 0 && $nombreImagen !== '') {
            $fila['url_imagen'] = tcgx_catalogo_publico_img_url($idBinder, $nombreImagen);
        }
        unset($fila['nombreimagen']);
        $resultado[] = $fila;
    }

    return $resultado;
}

/**
 * Obtiene un producto publico por ID con sus imagenes; null si no es visible en catalogo.
 *
 * @return array<string, mixed>|null
 */
function tcgx_catalogo_publico_obtener(PDO $pdo, int $idProducto): ?array
{
    if ($idProducto <= 0) {
        return null;
    }

    $sql = 'SELECT p.id, p.idbinder, p.nombrecarta, p.expansion, p.numerocarta, p.rareza, p.idioma, '
        . 'p.condicion, p.cantidad, p.precioventa, p.tipomoneda, p.fecharegistro, '
        . 'b.juego, u.telefono '
        . tcgx_catalogo_publico_sql_base()
        . ' AND p.id = ? LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idProducto]);
    $fila = $stmt->fetch();
    if ($fila === false) {
        return null;
    }

    $idBinder = (int) ($fila['idbinder'] ?? 0);
    $fila['url_imagen'] = null;
    $fila['imagenes'] = [];

    $stmtImg = $pdo->prepare(
        'SELECT nombreimagen FROM imagenes_producto WHERE idproducto = ? ORDER BY id ASC'
    );
    $stmtImg->execute([$idProducto]);
    foreach ($stmtImg->fetchAll() as $imgFila) {
        $nombreImagen = trim((string) ($imgFila['nombreimagen'] ?? ''));
        if ($idBinder > 0 && $nombreImagen !== '') {
            $fila['imagenes'][] = tcgx_catalogo_publico_img_url($idBinder, $nombreImagen);
        }
    }
    if ($fila['imagenes'] !== []) {
        $fila['url_imagen'] = $fila['imagenes'][0];
    }

    return $fila;
}

/**
 * TCG (columna juego) distintos presentes en el catalogo publico actual.
 *
 * @return list<string>
 */
function tcgx_catalogo_publico_tcgs_disponibles(PDO $pdo): array
{
    $sql = 'SELECT DISTINCT b.juego ' . tcgx_catalogo_publico_sql_base() . ' ORDER BY b.juego ASC';
    $stmt = $pdo->query($sql);
    $tcgs = [];
    foreach ($stmt->fetchAll() as $fila) {
        $txt = mb_strtoupper(trim((string) ($fila['juego'] ?? '')), 'UTF-8');
        if ($txt !== '' && !in_array($txt, $tcgs, true)) {
            $tcgs[] = $txt;
        }
    }

    return $tcgs;
}

/**
 * Mensaje prefijado para contacto WhatsApp sobre una carta del catalogo publico.
 */
function tcgx_catalogo_publico_mensaje_whatsapp(array $prod): string
{
    $nombre = mb_strtoupper(trim((string) ($prod['nombrecarta'] ?? '')), 'UTF-8');
    $id = trim((string) ($prod['id'] ?? ''));

    return 'HOLA, ME INTERESA LA CARTA "' . $nombre . '" (ID ' . $id . ') VISTA EN TCG EXCHANGE.';
}

/**
 * Enlace wa.me hacia el telefono del vendedor; null si no hay numero valido.
 */
function tcgx_catalogo_publico_whatsapp_enlace(?string $telefono, string $mensaje): ?string
{
    $tel = trim((string) $telefono);
    if ($tel === '') {
        return null;
    }
    $digitos = preg_replace('/\D/', '', $tel);
    if ($digitos === '') {
        return null;
    }
    if (strlen($digitos) === 8) {
        $digitos = '506' . $digitos;
    }

    return 'https://wa.me/' . $digitos . '?text=' . rawurlencode($mensaje);
}

/**
 * Simbolo de moneda para catalogo publico segun tipomoneda almacenado (COLONES o DOLARES).
 */
function tcgx_catalogo_publico_simbolo_moneda(string $tipomoneda): string
{
    $moneda = mb_strtoupper(trim($tipomoneda), 'UTF-8');
    if ($moneda === 'COLONES') {
        return '₡';
    }
    if ($moneda === 'DOLARES') {
        return '$';
    }

    return '';
}

/**
 * Precio publico con simbolo de moneda y separadores de miles (sin texto COLONES/DOLARES).
 */
function tcgx_catalogo_publico_precio_formateado(float $precio, string $tipomoneda): string
{
    $simbolo = tcgx_catalogo_publico_simbolo_moneda($tipomoneda);
    $monto = number_format($precio, 2, '.', ',');

    return $simbolo !== '' ? $simbolo . $monto : $monto;
}
// FIN BLOQUE: CONSULTA DEL CATALOGO PUBLICO
