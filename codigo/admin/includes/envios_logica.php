<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del modulo de ENVIOS (admin) — PROCESO 1: REGISTRO DE ENVIO INDIVIDUAL.
 * Un envio individual es el registro del paquete que un cliente deja en una tienda para enviar a
 * otro cliente. Nace en estado EN TIENDA DE ORIGEN y es INDEPENDIENTE de cualquier consolidado.
 * Este archivo cubre: catalogos de selects, busqueda de clientes (Select2), listado y detalle,
 * registro (cabecera CRE + paquetes + primer movimiento en transaccion), acciones individuales
 * (cambiar destino, cambiar receptor, cancelar, devolver) y auditoria.
 * La gestion de consolidados (PROCESO 2) vive en su propia capa, separada de esta.
 * Solo consultas preparadas con parametros enlazados. Datos operativos en MAYUSCULAS (correos aparte).
 * Fuente de verdad: basedatos.sql y diseño.md (flujo tecnico de envios).
 */

// INICIO BLOQUE: CONSTANTES CONTROLADAS DEL ENVIO INDIVIDUAL
// Tipos de paquete permitidos por el CHECK chkpaquetestipo de basedatos.sql.
const TCGX_ENVIOS_TIPOS_PAQUETE = ['SOBRE', 'CAJA', 'BOLSA'];

// Estado con el que nace todo envio recien registrado (queda disponible para consolidar).
const TCGX_ENVIOS_ESTADO_INICIAL = 'EN TIENDA DE ORIGEN';

// Estado de cancelacion (la columna envios.estado no tiene CHECK fijo; el valor lo controla la aplicacion).
const TCGX_ENVIOS_ESTADO_CANCELADO = 'CANCELADO';

// Primer estado de la cadena de devolucion (regla de negocio de devoluciones).
const TCGX_ENVIOS_ESTADO_DEVOLUCION = 'DEVOLUCION SOLICITADA';

// Estado de entrega final (frontera para "cambiar receptor": solo antes de entregar).
const TCGX_ENVIOS_ESTADO_ENTREGADO = 'ENTREGADO';

// La forma de envio es la RUTA elegida del catalogo rutas. Esta constante identifica la ruta de "misma tienda".
const TCGX_ENVIOS_RUTA_EN_TIENDA = 'EN TIENDA';

// Estado intermedio y final de entrega en tienda de destino (flujo normal y directo).
const TCGX_ENVIOS_ESTADO_EN_DESTINO = 'EN DESTINO';

// Transito directo origen-destino sin Centro de Distribucion (modalidad DIRECTO en rutas.medioenvio).
const TCGX_ENVIOS_ESTADO_EN_TRANSITO_DESTINO = 'EN TRANSITO A DESTINO';

// Valor de rutas.medioenvio que marca modalidad DIRECTO (idhub nulo; diseño.md regla 12).
const TCGX_ENVIOS_MODALIDAD_DIRECTO = 'DIRECTO';

// INICIO SUBBLOQUE: RESTRICCIONES DE IMAGENES DE EVIDENCIA (imagenes_envio)
// Evidencia opcional por paquete, cargada SOLO al registrar el envio (diseño.md, Paso 5).
// Tope de peso por imagen (5 MB) y cantidad por paquete; formatos permitidos (mime => extension segura).
const TCGX_ENVIOS_IMG_MAX_BYTES = 5242880;
const TCGX_ENVIOS_IMG_MAX_POR_PAQUETE = 5;
const TCGX_ENVIOS_IMG_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

/**
 * Carpeta fisica de almacenamiento de imagenes de envio: /uploads/envios en la raiz del proyecto.
 * (No es constante porque depende de una llamada a dirname()).
 */
function tcgx_envios_img_dir(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'envios';
}
// FIN SUBBLOQUE: RESTRICCIONES DE IMAGENES DE EVIDENCIA

// INICIO SUBBLOQUE: FRONTERAS DE ESTADO PARA ACCIONES INDIVIDUALES
// Estados en los que el paquete AUN no ha sido despachado por el Centro de Distribucion hacia el destino.
// Mientras el envio este en uno de estos estados se permite CAMBIAR EL DESTINO (no ha salido del hub).
const TCGX_ENVIOS_ESTADOS_ANTES_DE_HUB = [
    'EN TIENDA DE ORIGEN',
    'PREPARANDO PARA ENVIO',
    'EN TRANSITO A CENTRO DE DISTRIBUCION',
    'EN CENTRO DE DISTRIBUCION',
];

// Estados finales/terminales: ni se cancela ni se reabren acciones operativas sobre el envio.
const TCGX_ENVIOS_ESTADOS_TERMINALES = [
    'ENTREGADO',
    'CANCELADO',
];
// FIN SUBBLOQUE: FRONTERAS DE ESTADO PARA ACCIONES INDIVIDUALES
// FIN BLOQUE: CONSTANTES CONTROLADAS DEL ENVIO INDIVIDUAL


// INICIO BLOQUE: CATALOGOS PARA SELECTS (TIENDAS, HUB, RUTAS) Y BUSQUEDA DE CLIENTES
/**
 * Tiendas ACTIVAS que NO son Centro de Distribucion (eshub = 0), para los selects de ORIGEN y DESTINO.
 * El hub es intermediario logistico: nunca es origen ni destino de un envio, solo punto de paso.
 */
function tcgx_envios_listar_tiendas_punto(PDO $pdo): array
{
    return $pdo->query("SELECT id, nombre FROM tiendas WHERE estado = 'ACTIVO' AND eshub = 0 ORDER BY nombre ASC")->fetchAll();
}

/**
 * Centro de Distribucion UNICO (eshub = 1 y ACTIVO). Solo existe uno; el sistema lo asigna
 * automaticamente al envio que no sea EN TIENDA. Retorna [id, nombre] o null si no hay HUB activo.
 */
function tcgx_envios_hub_unico(PDO $pdo): ?array
{
    $fila = $pdo->query("SELECT id, nombre FROM tiendas WHERE estado = 'ACTIVO' AND eshub = 1 ORDER BY id ASC LIMIT 1")->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Rutas ACTIVAS del catalogo (id, nombre, medioenvio, exigeguiaexterna) para el selector de forma de envio.
 */
function tcgx_envios_listar_rutas(PDO $pdo): array
{
    return $pdo->query(
        'SELECT id, nombre, medioenvio, exigeguiaexterna FROM rutas WHERE estado = \'ACTIVO\' ORDER BY nombre ASC'
    )->fetchAll();
}

/**
 * Lee una ruta ACTIVA por nombre (forma de envio) con sus metadatos de modalidad.
 */
function tcgx_envios_ruta_obtener(PDO $pdo, string $nombre): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre, medioenvio, exigeguiaexterna FROM rutas WHERE nombre = ? AND estado = \'ACTIVO\' LIMIT 1'
    );
    $stmt->execute([$nombre]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Indica si la ruta exige paso por Centro de Distribucion (modalidad NORMAL u otras con hub).
 * EN TIENDA y DIRECTO no usan hub.
 */
function tcgx_envios_ruta_usa_hub(?array $ruta): bool
{
    if ($ruta === null) {
        return true;
    }
    $medio = mb_strtoupper(trim((string) ($ruta['medioenvio'] ?? '')), 'UTF-8');
    return $medio !== TCGX_ENVIOS_MODALIDAD_DIRECTO;
}

/**
 * Devuelve el id de una ruta ACTIVA por su nombre (forma de envio) o null si no existe/activa.
 */
function tcgx_envios_ruta_id(PDO $pdo, string $nombre): ?int
{
    $ruta = tcgx_envios_ruta_obtener($pdo, $nombre);
    return $ruta === null ? null : (int) $ruta['id'];
}

/**
 * Precio unico de la tarifa (columna precioporpaquete) para una tienda y ruta. NUNCA usa precio base.
 * Retorna el monto como cadena con dos decimales o null si no hay tarifa registrada.
 */
function tcgx_envios_precio_tarifa(PDO $pdo, int $idTienda, int $idRuta): ?string
{
    $stmt = $pdo->prepare('SELECT precioporpaquete FROM tarifas WHERE idtienda = ? AND idruta = ? LIMIT 1');
    $stmt->execute([$idTienda, $idRuta]);
    $fila = $stmt->fetch();
    return $fila === false ? null : number_format((float) $fila['precioporpaquete'], 2, '.', '');
}

/**
 * Busqueda dinamica de clientes (perfil CLIENTE) SOLO POR NOMBRE para el autocompletado (Select2 AJAX).
 * Escapa los comodines LIKE y usa un unico parametro enlazado (compatible con EMULATE_PREPARES = false).
 */
function tcgx_envios_buscar_clientes(PDO $pdo, string $termino, int $limite = 20): array
{
    $termino = trim($termino);
    if ($termino === '') {
        return [];
    }
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

/**
 * Resuelve el nombre de un cliente (perfil CLIENTE) por su cedula (id); cadena vacia si no aplica.
 */
function tcgx_envios_nombre_cliente(PDO $pdo, string $idUsuario): string
{
    $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ? AND perfil = 'CLIENTE' LIMIT 1");
    $stmt->execute([$idUsuario]);
    $fila = $stmt->fetch();
    return $fila === false ? '' : (string) $fila['nombre'];
}
// FIN BLOQUE: CATALOGOS PARA SELECTS Y BUSQUEDA DE CLIENTES


// INICIO BLOQUE: LISTADO Y DETALLE DE ENVIOS INDIVIDUALES
/**
 * Listado transversal de envios con nombres de tiendas y de las personas (remitente/destinatario).
 */
function tcgx_envios_listar(PDO $pdo): array
{
    $sql = 'SELECT e.id, e.idremitente, e.iddestinatario, e.idtiendaorigen, e.idtiendadestino, '
        . 'e.formaenvio, e.montoapagar, e.estado, '
        . 'tor.nombre AS nombretiendaorigen, tde.nombre AS nombretiendadestino, '
        . 'ur.nombre AS nombreremitente, ud.nombre AS nombredestinatario '
        . 'FROM envios e '
        . 'LEFT JOIN tiendas tor ON tor.id = e.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'LEFT JOIN usuarios ur ON ur.id = e.idremitente '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
        . 'ORDER BY e.id DESC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Lee un envio por su codigo de rastreo con nombres resueltos; retorna la fila o null si no existe.
 */
function tcgx_envios_obtener(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT e.id, e.idremitente, e.iddestinatario, e.idtiendaorigen, e.idtiendadestino, e.idhub, '
        . 'e.formaenvio, e.montoapagar, e.estado, '
        . 'tor.nombre AS nombretiendaorigen, tde.nombre AS nombretiendadestino, thu.nombre AS nombrehub, '
        . 'ur.nombre AS nombreremitente, ud.nombre AS nombredestinatario '
        . 'FROM envios e '
        . 'LEFT JOIN tiendas tor ON tor.id = e.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'LEFT JOIN tiendas thu ON thu.id = e.idhub '
        . 'LEFT JOIN usuarios ur ON ur.id = e.idremitente '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
        . 'WHERE e.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Paquetes que componen un envio.
 */
function tcgx_envios_paquetes(PDO $pdo, string $idEnvio): array
{
    $stmt = $pdo->prepare('SELECT id, tipo, descripcion, cantidad, valordeclarado FROM paquetes WHERE idenvio = ? ORDER BY id ASC');
    $stmt->execute([$idEnvio]);
    return $stmt->fetchAll();
}

/**
 * Imagenes de evidencia de un envio (por paquete), para consulta en el detalle del envio.
 * Retorna filas con idpaquete, nombreimagen y fecharegistro ordenadas por paquete.
 */
function tcgx_envios_imagenes(PDO $pdo, string $idEnvio): array
{
    $stmt = $pdo->prepare('SELECT id, idpaquete, nombreimagen, fecharegistro FROM imagenes_envio WHERE idenvio = ? ORDER BY idpaquete ASC, id ASC');
    $stmt->execute([$idEnvio]);
    return $stmt->fetchAll();
}

/**
 * Historial cronologico de movimientos de un envio con nombres de tienda y usuario responsables.
 */
function tcgx_envios_movimientos(PDO $pdo, string $idEnvio): array
{
    $stmt = $pdo->prepare(
        'SELECT m.id, m.accion, m.detalle, m.guiaexterna, m.fecharegistro, '
        . 'ti.nombre AS nombretienda, u.nombre AS nombreusuario '
        . 'FROM movimientos_envio m '
        . 'LEFT JOIN tiendas ti ON ti.id = m.idtienda '
        . 'LEFT JOIN usuarios u ON u.id = m.idusuario '
        . 'WHERE m.idenvio = ? ORDER BY m.fecharegistro ASC, m.id ASC'
    );
    $stmt->execute([$idEnvio]);
    return $stmt->fetchAll();
}
// FIN BLOQUE: LISTADO Y DETALLE DE ENVIOS INDIVIDUALES


// INICIO BLOQUE: AUDITORIA Y CODIGO DE RASTREO
/**
 * Inserta una fila en auditorias para CREAR/ACTUALIZAR/ELIMINAR sobre la tabla envios.
 */
function tcgx_envios_auditar(PDO $pdo, ?string $idActor, string $accion, string $idRegistro, ?array $antes, ?array $despues): void
{
    $jsonAntes = $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $jsonDespues = $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $stmt = $pdo->prepare(
        'INSERT INTO auditorias (idusuario, accion, tablaafectada, idregistro, datosantes, datosdespues) '
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idActor, $accion, 'envios', $idRegistro, $jsonAntes, $jsonDespues]);
}

/**
 * Genera un codigo de rastreo de envio con formato CRE + AAAAMMDDHHMMSS (17 caracteres).
 */
function tcgx_envios_generar_id(): string
{
    return 'CRE' . date('YmdHis');
}
// FIN BLOQUE: AUDITORIA Y CODIGO DE RASTREO


// INICIO BLOQUE: VALIDACIONES AUXILIARES
/**
 * Valida una tienda de punto (origen/destino): existente, ACTIVA y NO Centro de Distribucion.
 * Acumula error con la etiqueta dada. Retorna el id entero o null si invalida.
 */
function tcgx_envios_validar_tienda(PDO $pdo, string $idRaw, string $etiqueta, array &$errores): ?int
{
    $idRaw = trim($idRaw);
    if ($idRaw === '' || !ctype_digit($idRaw)) {
        $errores[] = 'DEBE SELECCIONAR LA TIENDA DE ' . $etiqueta . '.';
        return null;
    }
    $id = (int) $idRaw;
    $stmt = $pdo->prepare("SELECT eshub FROM tiendas WHERE id = ? AND estado = 'ACTIVO' LIMIT 1");
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    if ($fila === false) {
        $errores[] = 'LA TIENDA DE ' . $etiqueta . ' NO EXISTE O NO ESTA ACTIVA.';
        return null;
    }
    if ((int) $fila['eshub'] === 1) {
        $errores[] = 'LA TIENDA DE ' . $etiqueta . ' NO PUEDE SER UN CENTRO DE DISTRIBUCION (ES INTERMEDIARIO).';
        return null;
    }
    return $id;
}

/**
 * Valida que un id corresponda a un usuario CLIENTE registrado. Acumula error con la etiqueta dada.
 * Retorna la cedula (string) o null si invalida.
 */
function tcgx_envios_validar_cliente(PDO $pdo, string $idRaw, string $etiqueta, array &$errores): ?string
{
    $idRaw = trim($idRaw);
    if ($idRaw === '') {
        $errores[] = 'DEBE INDICAR EL ' . $etiqueta . ' (CLIENTE REGISTRADO).';
        return null;
    }
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND perfil = 'CLIENTE' LIMIT 1");
    $stmt->execute([$idRaw]);
    if ($stmt->fetch() === false) {
        $errores[] = 'EL ' . $etiqueta . ' DEBE SER UN CLIENTE REGISTRADO.';
        return null;
    }
    return $idRaw;
}

/**
 * Valida las filas de paquetes enviadas (arreglos paralelos tipo/descripcion/cantidad/valor).
 * Exige al menos un paquete valido. Normaliza textos a MAYUSCULAS. Retorna lista de paquetes.
 */
function tcgx_envios_validar_paquetes(array $post, array &$errores): array
{
    $tipos = (array) ($post['paquete_tipo'] ?? []);
    $descripciones = (array) ($post['paquete_descripcion'] ?? []);
    $cantidades = (array) ($post['paquete_cantidad'] ?? []);
    $valores = (array) ($post['paquete_valor'] ?? []);

    $paquetes = [];
    $total = count($tipos);
    for ($i = 0; $i < $total; $i++) {
        $tipo = mb_strtoupper(trim((string) ($tipos[$i] ?? '')), 'UTF-8');
        $descripcion = mb_strtoupper(trim((string) ($descripciones[$i] ?? '')), 'UTF-8');
        $cantidadRaw = trim((string) ($cantidades[$i] ?? ''));
        $valorRaw = trim((string) ($valores[$i] ?? ''));

        // Fila totalmente vacia: se ignora (no es un paquete).
        if ($tipo === '' && $descripcion === '' && $cantidadRaw === '' && $valorRaw === '') {
            continue;
        }

        if (!in_array($tipo, TCGX_ENVIOS_TIPOS_PAQUETE, true)) {
            $errores[] = 'CADA PAQUETE DEBE TENER UN TIPO VALIDO (SOBRE, CAJA O BOLSA).';
            continue;
        }
        if ($cantidadRaw === '' || !ctype_digit($cantidadRaw) || (int) $cantidadRaw < 1) {
            $errores[] = 'LA CANTIDAD DE CADA PAQUETE DEBE SER UN ENTERO MAYOR O IGUAL A 1.';
            continue;
        }
        if ($valorRaw === '' || !is_numeric($valorRaw) || (float) $valorRaw < 0) {
            $errores[] = 'EL VALOR DECLARADO DE CADA PAQUETE DEBE SER UN NUMERO MAYOR O IGUAL A CERO.';
            continue;
        }

        $paquetes[] = [
            // 'indice' conserva la posicion original de la fila (DOM) para asociar sus imagenes por paquete.
            'indice' => $i,
            'tipo' => $tipo,
            'descripcion' => $descripcion === '' ? null : $descripcion,
            'cantidad' => (int) $cantidadRaw,
            'valordeclarado' => number_format((float) $valorRaw, 2, '.', ''),
        ];
    }

    if (empty($paquetes)) {
        $errores[] = 'DEBE REGISTRAR AL MENOS UN PAQUETE.';
    }

    return $paquetes;
}

/**
 * Valida las imagenes de evidencia subidas por paquete (campo de archivo paquete_imagenes[INDICE][]).
 * Solo se procesan las de los paquetes validos (por su indice de fila). Reglas: formato JPG/PNG/WEBP
 * (verificado por contenido con finfo), peso por imagen <= 5 MB y como maximo 5 imagenes por paquete.
 * Acumula errores en $errores y retorna un mapa [indice => [ ['tmp_name' => ..., 'extension' => ...], ... ]].
 */
function tcgx_envios_validar_imagenes(array $files, array $indicesValidos, array &$errores): array
{
    $resultado = [];

    // Sin campo de archivos o estructura no esperada: no hay imagenes que procesar.
    if (!isset($files['paquete_imagenes']) || !is_array($files['paquete_imagenes']['name'] ?? null)) {
        return $resultado;
    }
    $grupo = $files['paquete_imagenes'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($indicesValidos as $indice) {
        if (!isset($grupo['name'][$indice]) || !is_array($grupo['name'][$indice])) {
            continue;
        }

        $items = [];
        $total = count($grupo['name'][$indice]);
        for ($k = 0; $k < $total; $k++) {
            $codigoError = (int) ($grupo['error'][$indice][$k] ?? UPLOAD_ERR_NO_FILE);

            // Casilla sin archivo: se ignora (la evidencia es opcional).
            if ($codigoError === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($codigoError === UPLOAD_ERR_INI_SIZE || $codigoError === UPLOAD_ERR_FORM_SIZE) {
                $errores[] = 'UNA IMAGEN SUPERA EL TAMAÑO MAXIMO PERMITIDO (5 MB).';
                continue;
            }
            if ($codigoError !== UPLOAD_ERR_OK) {
                $errores[] = 'OCURRIO UN ERROR AL SUBIR UNA IMAGEN DEL PAQUETE.';
                continue;
            }

            $tmp = (string) ($grupo['tmp_name'][$indice][$k] ?? '');
            $size = (int) ($grupo['size'][$indice][$k] ?? 0);

            // Defensa: el archivo debe provenir realmente de una subida HTTP.
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                $errores[] = 'ARCHIVO DE IMAGEN NO VALIDO.';
                continue;
            }
            if ($size <= 0 || $size > TCGX_ENVIOS_IMG_MAX_BYTES) {
                $errores[] = 'CADA IMAGEN DEBE PESAR COMO MAXIMO 5 MB.';
                continue;
            }

            // Verificacion por contenido (no por extension declarada) contra los tipos permitidos.
            $mime = (string) $finfo->file($tmp);
            if (!isset(TCGX_ENVIOS_IMG_MIMES[$mime])) {
                $errores[] = 'SOLO SE PERMITEN IMAGENES JPG, PNG O WEBP.';
                continue;
            }

            if (count($items) >= TCGX_ENVIOS_IMG_MAX_POR_PAQUETE) {
                $errores[] = 'CADA PAQUETE ADMITE COMO MAXIMO 5 IMAGENES.';
                break;
            }

            $items[] = ['tmp_name' => $tmp, 'extension' => TCGX_ENVIOS_IMG_MIMES[$mime]];
        }

        if (!empty($items)) {
            $resultado[$indice] = $items;
        }
    }

    return $resultado;
}
// FIN BLOQUE: VALIDACIONES AUXILIARES


// INICIO BLOQUE: VALIDACION DE REGISTRO DE ENVIO INDIVIDUAL
/**
 * Valida y normaliza el formulario de registro de un envio individual.
 * Reglas: forma de envio = ruta ACTIVA; tiendas origen/destino validas (no hub); EN TIENDA implica
 * mismo origen y destino y sin hub; remitente y destinatario CLIENTE registrados; al menos un paquete;
 * monto AUTOMATICO desde la tarifa de la tienda de origen para la ruta (nunca manual, nunca precio base).
 * Retorna ['errores' => string[], 'datos' => array lista para persistir].
 */
function tcgx_envios_validar_registro(PDO $pdo, array $post): array
{
    $errores = [];

    // --- Forma de envio = RUTA del catalogo (data-driven; validada contra rutas ACTIVAS) ---
    $forma = mb_strtoupper(trim((string) ($post['formaenvio'] ?? '')), 'UTF-8');
    $ruta = $forma === '' ? null : tcgx_envios_ruta_obtener($pdo, $forma);
    $idRuta = $ruta === null ? null : (int) $ruta['id'];
    if ($idRuta === null) {
        $errores[] = 'DEBE SELECCIONAR UNA FORMA DE ENVIO (RUTA) VALIDA DEL CATALOGO.';
        $forma = null;
    }
    $esEnTienda = ($forma === TCGX_ENVIOS_RUTA_EN_TIENDA);

    // --- Tienda de origen (activa y no hub) ---
    $idOrigen = tcgx_envios_validar_tienda($pdo, (string) ($post['idtiendaorigen'] ?? ''), 'ORIGEN', $errores);

    // --- Tienda de destino: en EN TIENDA es la misma de origen; en el resto debe ser distinta ---
    $idDestino = null;
    if ($esEnTienda) {
        $idDestino = $idOrigen;
    } else {
        $idDestino = tcgx_envios_validar_tienda($pdo, (string) ($post['idtiendadestino'] ?? ''), 'DESTINO', $errores);
        if ($idOrigen !== null && $idDestino !== null && $idOrigen === $idDestino) {
            $errores[] = 'LA TIENDA DE ORIGEN Y DESTINO DEBEN SER DISTINTAS.';
        }
    }

    // --- Centro de distribucion (hub): obligatorio solo en rutas que lo usan (NORMAL); DIRECTO y EN TIENDA van sin hub ---
    $idHub = null;
    if (!$esEnTienda && tcgx_envios_ruta_usa_hub($ruta)) {
        $hub = tcgx_envios_hub_unico($pdo);
        if ($hub === null) {
            $errores[] = 'NO HAY UN CENTRO DE DISTRIBUCION ACTIVO. REGISTRELO ANTES DE CREAR ENVIOS.';
        } else {
            $idHub = (int) $hub['id'];
        }
    }

    // --- Remitente y destinatario (CLIENTE registrado) ---
    $idRemitente = tcgx_envios_validar_cliente($pdo, (string) ($post['idremitente'] ?? ''), 'REMITENTE', $errores);
    $idDestinatario = tcgx_envios_validar_cliente($pdo, (string) ($post['iddestinatario'] ?? ''), 'DESTINATARIO', $errores);

    // --- Monto AUTOMATICO desde la tarifa de la tienda de ORIGEN para la ruta (fuente de verdad: servidor) ---
    $monto = null;
    if ($idOrigen !== null && $idRuta !== null) {
        $monto = tcgx_envios_precio_tarifa($pdo, $idOrigen, $idRuta);
        if ($monto === null) {
            $errores[] = 'NO HAY TARIFA REGISTRADA PARA LA TIENDA DE ORIGEN Y LA FORMA DE ENVIO SELECCIONADA. REGISTRE LA TARIFA PRIMERO.';
        }
    }

    // --- Paquetes (al menos uno valido) ---
    $paquetes = tcgx_envios_validar_paquetes($post, $errores);

    $datos = [
        'idremitente' => $idRemitente,
        'iddestinatario' => $idDestinatario,
        'idtiendaorigen' => $idOrigen,
        'idtiendadestino' => $idDestino,
        'idhub' => $idHub,
        'formaenvio' => $forma,
        'montoapagar' => $monto,
        'estado' => TCGX_ENVIOS_ESTADO_INICIAL,
        'paquetes' => $paquetes,
    ];

    return ['errores' => $errores, 'datos' => $datos];
}
// FIN BLOQUE: VALIDACION DE REGISTRO DE ENVIO INDIVIDUAL


// INICIO BLOQUE: REGISTRO DE ENVIO INDIVIDUAL (TRANSACCION + AUDITORIA)
/**
 * Crea un envio individual: cabecera (CRE) + paquetes + primer movimiento + imagenes de evidencia, en transaccion.
 * $imagenes es el mapa [indice de paquete => [ ['tmp_name' => ..., 'extension' => ...], ... ]] ya validado.
 * Reintenta una vez si colisiona el codigo de rastreo generado en el mismo segundo.
 * Retorna ['ok' => true, 'id' => <CRE...>] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_envios_crear(PDO $pdo, array $datos, array $imagenes, ?string $idActor): array
{
    // INICIO BLOQUE: TRASLADO FISICO DE IMAGENES (ANTES DE LA TRANSACCION)
    // Se mueven los archivos a su destino final ANTES de la transaccion para no depender de los
    // temporales en un reintento por colision de PK. Los nombres finales son aleatorios y unicos.
    $archivosMovidos = []; // rutas absolutas ya movidas (para limpiar ante un fallo definitivo).
    $mapaImagenes = [];    // [indice => [nombreArchivoFinal, ...]] para insertar en imagenes_envio.

    if (!empty($imagenes)) {
        $dir = tcgx_envios_img_dir();
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => 'NO FUE POSIBLE PREPARAR EL ALMACENAMIENTO DE IMAGENES.'];
        }
        foreach ($imagenes as $indice => $items) {
            foreach ($items as $img) {
                try {
                    $nombreFinal = bin2hex(random_bytes(16)) . '.' . $img['extension'];
                } catch (Exception $e) {
                    foreach ($archivosMovidos as $a) { @unlink($a); }
                    return ['ok' => false, 'error' => 'NO FUE POSIBLE GUARDAR LAS IMAGENES DEL ENVIO.'];
                }
                $destino = $dir . DIRECTORY_SEPARATOR . $nombreFinal;
                if (!move_uploaded_file($img['tmp_name'], $destino)) {
                    foreach ($archivosMovidos as $a) { @unlink($a); }
                    return ['ok' => false, 'error' => 'NO FUE POSIBLE GUARDAR LAS IMAGENES DEL ENVIO.'];
                }
                $archivosMovidos[] = $destino;
                $mapaImagenes[$indice][] = $nombreFinal;
            }
        }
    }
    // FIN BLOQUE: TRASLADO FISICO DE IMAGENES

    $intentos = 0;
    do {
        $intentos++;
        $idEnvio = tcgx_envios_generar_id();
        try {
            $pdo->beginTransaction();

            $stmtE = $pdo->prepare(
                'INSERT INTO envios (id, idremitente, iddestinatario, idtiendaorigen, idtiendadestino, idhub, formaenvio, montoapagar, estado) '
                . 'VALUES (:id, :idremitente, :iddestinatario, :idorigen, :iddestino, :idhub, :forma, :monto, :estado)'
            );
            $stmtE->execute([
                ':id' => $idEnvio,
                ':idremitente' => $datos['idremitente'],
                ':iddestinatario' => $datos['iddestinatario'],
                ':idorigen' => $datos['idtiendaorigen'],
                ':iddestino' => $datos['idtiendadestino'],
                ':idhub' => $datos['idhub'],
                ':forma' => $datos['formaenvio'],
                ':monto' => $datos['montoapagar'],
                ':estado' => $datos['estado'],
            ]);

            $stmtP = $pdo->prepare(
                'INSERT INTO paquetes (idenvio, tipo, descripcion, cantidad, valordeclarado) '
                . 'VALUES (:idenvio, :tipo, :descripcion, :cantidad, :valor)'
            );
            // Insercion de imagenes de evidencia asociadas a cada paquete recien creado.
            $stmtImg = $pdo->prepare(
                'INSERT INTO imagenes_envio (idenvio, idpaquete, nombreimagen) VALUES (:idenvio, :idpaquete, :nombre)'
            );
            foreach ($datos['paquetes'] as $paquete) {
                $stmtP->execute([
                    ':idenvio' => $idEnvio,
                    ':tipo' => $paquete['tipo'],
                    ':descripcion' => $paquete['descripcion'],
                    ':cantidad' => $paquete['cantidad'],
                    ':valor' => $paquete['valordeclarado'],
                ]);

                // Asocia las imagenes del paquete (por su indice de fila) al id recien generado.
                $indicePaquete = $paquete['indice'] ?? null;
                if ($indicePaquete !== null && !empty($mapaImagenes[$indicePaquete])) {
                    $idPaquete = (int) $pdo->lastInsertId();
                    foreach ($mapaImagenes[$indicePaquete] as $nombreImg) {
                        $stmtImg->execute([
                            ':idenvio' => $idEnvio,
                            ':idpaquete' => $idPaquete,
                            ':nombre' => $nombreImg,
                        ]);
                    }
                }
            }

            // Primer movimiento: apertura operativa en la tienda de origen.
            $stmtM = $pdo->prepare(
                'INSERT INTO movimientos_envio (idenvio, accion, detalle, guiaexterna, idtienda, idusuario) '
                . 'VALUES (:idenvio, :accion, :detalle, NULL, :idtienda, :idusuario)'
            );
            $stmtM->execute([
                ':idenvio' => $idEnvio,
                ':accion' => $datos['estado'],
                ':detalle' => 'ENVIO REGISTRADO EN TIENDA DE ORIGEN',
                ':idtienda' => $datos['idtiendaorigen'],
                ':idusuario' => $idActor,
            ]);

            tcgx_envios_auditar($pdo, $idActor, 'CREAR', $idEnvio, null, [
                'estado' => $datos['estado'],
                'formaenvio' => $datos['formaenvio'],
                'montoapagar' => $datos['montoapagar'],
            ]);

            $pdo->commit();
            return ['ok' => true, 'id' => $idEnvio];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // 23000 con reintento disponible: probable colision del codigo de rastreo (PK) en el mismo segundo.
            // (Los archivos ya movidos se reutilizan en el reintento; no se borran aqui.)
            if ($e->getCode() === '23000' && $intentos < 2) {
                sleep(1);
                continue;
            }
            // Fallo definitivo: limpiar archivos fisicos ya movidos para no dejar huerfanos.
            foreach ($archivosMovidos as $a) { @unlink($a); }
            return ['ok' => false, 'error' => 'NO FUE POSIBLE REGISTRAR EL ENVIO.'];
        }
    } while ($intentos < 2);

    // Salida defensiva: limpiar archivos si por alguna razon no se completo el alta.
    foreach ($archivosMovidos as $a) { @unlink($a); }
    return ['ok' => false, 'error' => 'NO FUE POSIBLE REGISTRAR EL ENVIO.'];
}
// FIN BLOQUE: REGISTRO DE ENVIO INDIVIDUAL (TRANSACCION + AUDITORIA)


// INICIO BLOQUE: ACCIONES INDIVIDUALES SOBRE EL ENVIO
/**
 * Indica si el envio aun no ha salido del Centro de Distribucion hacia el destino (permite cambiar destino).
 */
function tcgx_envios_puede_cambiar_destino(string $estado): bool
{
    return in_array($estado, TCGX_ENVIOS_ESTADOS_ANTES_DE_HUB, true);
}

/**
 * Indica si el envio aun no ha sido entregado (permite cambiar el receptor/destinatario).
 */
function tcgx_envios_puede_cambiar_receptor(string $estado): bool
{
    return !in_array($estado, TCGX_ENVIOS_ESTADOS_TERMINALES, true)
        && !str_starts_with($estado, 'DEVOLUCION');
}

/**
 * Indica si el envio puede cancelarse (no esta en un estado terminal ni en cadena de devolucion).
 */
function tcgx_envios_puede_cancelar(string $estado): bool
{
    return !in_array($estado, TCGX_ENVIOS_ESTADOS_TERMINALES, true)
        && !str_starts_with($estado, 'DEVOLUCION');
}

/**
 * Indica si el envio puede devolverse (ya fue entregado o esta en destino y no esta cancelado).
 */
function tcgx_envios_puede_devolver(string $estado): bool
{
    return $estado !== TCGX_ENVIOS_ESTADO_CANCELADO && !str_starts_with($estado, 'DEVOLUCION');
}

/**
 * Indica si la forma de envio es LOCAL (ruta EN TIENDA, mismo origen y destino).
 */
function tcgx_envios_es_local(array $envio): bool
{
    return (string) ($envio['formaenvio'] ?? '') === TCGX_ENVIOS_RUTA_EN_TIENDA;
}

/**
 * Indica si el envio es modalidad DIRECTO (sin idhub y no es EN TIENDA).
 */
function tcgx_envios_es_directo(array $envio): bool
{
    $hub = $envio['idhub'] ?? null;
    return ($hub === null || (int) $hub === 0) && !tcgx_envios_es_local($envio);
}

/**
 * Indica si la ruta del envio exige guia externa al despachar (campo exigeguiaexterna en rutas).
 */
function tcgx_envios_ruta_exige_guia(PDO $pdo, array $envio): bool
{
    $ruta = tcgx_envios_ruta_obtener($pdo, (string) ($envio['formaenvio'] ?? ''));
    return $ruta !== null && (int) ($ruta['exigeguiaexterna'] ?? 0) === 1;
}

/**
 * Indica si el envio puede marcarse ENTREGADO: LOCAL desde EN TIENDA DE ORIGEN; resto desde EN DESTINO.
 */
function tcgx_envios_puede_entregar(array $envio): bool
{
    $estado = (string) $envio['estado'];
    if (in_array($estado, TCGX_ENVIOS_ESTADOS_TERMINALES, true) || str_starts_with($estado, 'DEVOLUCION')) {
        return false;
    }
    if (tcgx_envios_es_local($envio)) {
        return $estado === TCGX_ENVIOS_ESTADO_INICIAL;
    }
    return $estado === TCGX_ENVIOS_ESTADO_EN_DESTINO;
}

/**
 * Marca el envio como ENTREGADO al destinatario (o retiro LOCAL en la misma tienda).
 */
function tcgx_envios_entregar(PDO $pdo, array $envio, ?string $idActor): array
{
    if (!tcgx_envios_puede_entregar($envio)) {
        return ['ok' => false, 'error' => 'NO SE PUEDE MARCAR COMO ENTREGADO EN EL ESTADO ACTUAL.'];
    }
    $idTienda = tcgx_envios_es_local($envio)
        ? (int) $envio['idtiendaorigen']
        : (int) $envio['idtiendadestino'];
    return tcgx_envios_aplicar_estado(
        $pdo,
        $envio,
        TCGX_ENVIOS_ESTADO_ENTREGADO,
        'ENVIO ENTREGADO AL DESTINATARIO',
        $idActor,
        $idTienda
    );
}

/**
 * Indica si un envio DIRECTO puede despacharse desde la tienda de origen.
 */
function tcgx_envios_puede_despachar_directo(array $envio): bool
{
    return tcgx_envios_es_directo($envio)
        && (string) $envio['estado'] === TCGX_ENVIOS_ESTADO_INICIAL;
}

/**
 * Despacha un envio DIRECTO: EN TIENDA DE ORIGEN -> EN TRANSITO A DESTINO (guia externa si la ruta la exige).
 */
function tcgx_envios_despachar_directo(PDO $pdo, array $envio, string $guiaRaw, ?string $idActor): array
{
    if (!tcgx_envios_puede_despachar_directo($envio)) {
        return ['ok' => false, 'error' => 'NO SE PUEDE DESPACHAR ESTE ENVIO EN SU ESTADO ACTUAL.'];
    }
    $guia = mb_strtoupper(trim($guiaRaw), 'UTF-8');
    if (tcgx_envios_ruta_exige_guia($pdo, $envio) && $guia === '') {
        return ['ok' => false, 'error' => 'DEBE INDICAR LA GUIA EXTERNA PARA ESTA RUTA.'];
    }
    if ($guia !== '' && mb_strlen($guia, 'UTF-8') > 80) {
        return ['ok' => false, 'error' => 'LA GUIA EXTERNA NO PUEDE SUPERAR 80 CARACTERES.'];
    }
    return tcgx_envios_aplicar_estado(
        $pdo,
        $envio,
        TCGX_ENVIOS_ESTADO_EN_TRANSITO_DESTINO,
        'DESPACHO DIRECTO A TIENDA DE DESTINO',
        $idActor,
        (int) $envio['idtiendaorigen'],
        $guia !== '' ? $guia : null
    );
}

/**
 * Indica si un envio DIRECTO puede recibirse en la tienda de destino.
 */
function tcgx_envios_puede_recibir_en_destino(array $envio): bool
{
    return tcgx_envios_es_directo($envio)
        && (string) $envio['estado'] === TCGX_ENVIOS_ESTADO_EN_TRANSITO_DESTINO;
}

/**
 * Recibe un envio DIRECTO en destino: EN TRANSITO A DESTINO -> EN DESTINO.
 */
function tcgx_envios_recibir_en_destino(PDO $pdo, array $envio, ?string $idActor): array
{
    if (!tcgx_envios_puede_recibir_en_destino($envio)) {
        return ['ok' => false, 'error' => 'NO SE PUEDE RECIBIR ESTE ENVIO EN DESTINO EN SU ESTADO ACTUAL.'];
    }
    return tcgx_envios_aplicar_estado(
        $pdo,
        $envio,
        TCGX_ENVIOS_ESTADO_EN_DESTINO,
        'RECEPCION EN TIENDA DE DESTINO',
        $idActor,
        (int) $envio['idtiendadestino']
    );
}

/**
 * Cambia la tienda de destino de un envio individual (solo si no ha salido del hub) y registra movimiento.
 * Retorna ['ok' => true] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_envios_cambiar_destino(PDO $pdo, array $envio, string $idNuevoDestinoRaw, ?string $idActor): array
{
    $estado = (string) $envio['estado'];
    if (!tcgx_envios_puede_cambiar_destino($estado)) {
        return ['ok' => false, 'error' => 'NO SE PUEDE CAMBIAR EL DESTINO: EL ENVIO YA SALIO DEL CENTRO DE DISTRIBUCION.'];
    }
    if ((string) $envio['formaenvio'] === TCGX_ENVIOS_RUTA_EN_TIENDA) {
        return ['ok' => false, 'error' => 'UN ENVIO EN TIENDA NO TIENE DESTINO EXTERNO.'];
    }

    $errores = [];
    $idNuevo = tcgx_envios_validar_tienda($pdo, $idNuevoDestinoRaw, 'DESTINO', $errores);
    if ($idNuevo === null) {
        return ['ok' => false, 'error' => $errores[0] ?? 'DESTINO NO VALIDO.'];
    }
    if ($idNuevo === (int) $envio['idtiendaorigen']) {
        return ['ok' => false, 'error' => 'LA TIENDA DE ORIGEN Y DESTINO DEBEN SER DISTINTAS.'];
    }
    if ($idNuevo === (int) $envio['idtiendadestino']) {
        return ['ok' => false, 'error' => 'EL DESTINO SELECCIONADO ES EL MISMO ACTUAL.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE envios SET idtiendadestino = :destino WHERE id = :id');
        $stmt->execute([':destino' => $idNuevo, ':id' => $envio['id']]);

        $stmtM = $pdo->prepare(
            'INSERT INTO movimientos_envio (idenvio, accion, detalle, guiaexterna, idtienda, idusuario) '
            . 'VALUES (:idenvio, :accion, :detalle, NULL, :idtienda, :idusuario)'
        );
        $stmtM->execute([
            ':idenvio' => $envio['id'],
            ':accion' => $estado,
            ':detalle' => 'CAMBIO DE TIENDA DE DESTINO',
            ':idtienda' => $envio['idtiendaorigen'],
            ':idusuario' => $idActor,
        ]);

        tcgx_envios_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $envio['id'], ['idtiendadestino' => (int) $envio['idtiendadestino']], ['idtiendadestino' => $idNuevo]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CAMBIAR EL DESTINO.'];
    }

    return ['ok' => true];
}

/**
 * Cambia el receptor/destinatario de un envio individual (solo si no ha sido entregado) y registra movimiento.
 */
function tcgx_envios_cambiar_receptor(PDO $pdo, array $envio, string $idNuevoReceptorRaw, ?string $idActor): array
{
    $estado = (string) $envio['estado'];
    if (!tcgx_envios_puede_cambiar_receptor($estado)) {
        return ['ok' => false, 'error' => 'NO SE PUEDE CAMBIAR EL RECEPTOR: EL ENVIO YA FUE ENTREGADO O ESTA CANCELADO.'];
    }

    $errores = [];
    $idNuevo = tcgx_envios_validar_cliente($pdo, $idNuevoReceptorRaw, 'DESTINATARIO', $errores);
    if ($idNuevo === null) {
        return ['ok' => false, 'error' => $errores[0] ?? 'DESTINATARIO NO VALIDO.'];
    }
    if ($idNuevo === (string) $envio['iddestinatario']) {
        return ['ok' => false, 'error' => 'EL DESTINATARIO SELECCIONADO ES EL MISMO ACTUAL.'];
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE envios SET iddestinatario = :dest WHERE id = :id');
        $stmt->execute([':dest' => $idNuevo, ':id' => $envio['id']]);

        $stmtM = $pdo->prepare(
            'INSERT INTO movimientos_envio (idenvio, accion, detalle, guiaexterna, idtienda, idusuario) '
            . 'VALUES (:idenvio, :accion, :detalle, NULL, :idtienda, :idusuario)'
        );
        $stmtM->execute([
            ':idenvio' => $envio['id'],
            ':accion' => $estado,
            ':detalle' => 'CAMBIO DE RECEPTOR (DESTINATARIO)',
            ':idtienda' => $envio['idtiendaorigen'],
            ':idusuario' => $idActor,
        ]);

        tcgx_envios_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $envio['id'], ['iddestinatario' => (string) $envio['iddestinatario']], ['iddestinatario' => $idNuevo]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CAMBIAR EL RECEPTOR.'];
    }

    return ['ok' => true];
}

/**
 * Cancela un envio individual (estado CANCELADO) si aun no es terminal ni esta en devolucion.
 */
function tcgx_envios_cancelar(PDO $pdo, array $envio, ?string $idActor): array
{
    $estado = (string) $envio['estado'];
    if (!tcgx_envios_puede_cancelar($estado)) {
        return ['ok' => false, 'error' => 'NO SE PUEDE CANCELAR ESTE ENVIO EN SU ESTADO ACTUAL.'];
    }
    return tcgx_envios_aplicar_estado($pdo, $envio, TCGX_ENVIOS_ESTADO_CANCELADO, 'ENVIO CANCELADO', $idActor);
}

/**
 * Inicia la devolucion de un envio individual (estado DEVOLUCION SOLICITADA).
 */
function tcgx_envios_devolver(PDO $pdo, array $envio, ?string $idActor): array
{
    $estado = (string) $envio['estado'];
    if (!tcgx_envios_puede_devolver($estado)) {
        return ['ok' => false, 'error' => 'NO SE PUEDE DEVOLVER ESTE ENVIO EN SU ESTADO ACTUAL.'];
    }
    return tcgx_envios_aplicar_estado($pdo, $envio, TCGX_ENVIOS_ESTADO_DEVOLUCION, 'DEVOLUCION SOLICITADA', $idActor);
}

/**
 * Aplica un cambio de estado simple a un envio: actualiza estado, registra movimiento y audita (transaccion).
 * $idTiendaResponsable define la tienda del movimiento; $guiaExterna opcional para tramos con guia.
 */
function tcgx_envios_aplicar_estado(
    PDO $pdo,
    array $envio,
    string $nuevoEstado,
    string $detalle,
    ?string $idActor,
    ?int $idTiendaResponsable = null,
    ?string $guiaExterna = null
): array {
    $idTienda = $idTiendaResponsable ?? (int) $envio['idtiendaorigen'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE envios SET estado = :estado WHERE id = :id');
        $stmt->execute([':estado' => $nuevoEstado, ':id' => $envio['id']]);

        $stmtM = $pdo->prepare(
            'INSERT INTO movimientos_envio (idenvio, accion, detalle, guiaexterna, idtienda, idusuario) '
            . 'VALUES (:idenvio, :accion, :detalle, :guia, :idtienda, :idusuario)'
        );
        $stmtM->execute([
            ':idenvio' => $envio['id'],
            ':accion' => $nuevoEstado,
            ':detalle' => $detalle,
            ':guia' => $guiaExterna,
            ':idtienda' => $idTienda,
            ':idusuario' => $idActor,
        ]);

        tcgx_envios_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $envio['id'], ['estado' => (string) $envio['estado']], ['estado' => $nuevoEstado]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE ACTUALIZAR EL ENVIO.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: ACCIONES INDIVIDUALES SOBRE EL ENVIO
