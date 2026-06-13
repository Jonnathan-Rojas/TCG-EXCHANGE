<?php
declare(strict_types=1);

/**
 * Capa de logica del modulo SUPERVISION DE DEVOLUCIONES (admin).
 * Un envio en devolucion recorre la cadena definida en diseño.md (regla 20): destino -> hub -> origen -> ENTREGADO.
 * Cada avance actualiza envios.estado, registra movimientos_envio con la tienda responsable del paso y audita.
 * Reutiliza lectura de envios desde envios_logica.php. Solo consultas preparadas. Datos en MAYUSCULAS.
 */

require_once __DIR__ . '/envios_logica.php';

// INICIO BLOQUE: CADENA DE ESTADOS DE DEVOLUCION
// Orden operativo inverso al envio normal: destino, hub, origen y cierre en ENTREGADO (entrega en origen).
const TCGX_DEVOLUCION_CADENA = [
    TCGX_ENVIOS_ESTADO_DEVOLUCION,
    'DEVOLUCION EN TIENDA DESTINO',
    'DEVOLUCION EN TRANSITO A CENTRO DE DISTRIBUCION',
    'DEVOLUCION EN CENTRO DE DISTRIBUCION',
    'DEVOLUCION EN TRANSITO A ORIGEN',
    'DEVOLUCION EN TIENDA DE ORIGEN',
    TCGX_ENVIOS_ESTADO_ENTREGADO,
];
// FIN BLOQUE: CADENA DE ESTADOS DE DEVOLUCION


// INICIO BLOQUE: LISTADO Y CONSULTAS DE DEVOLUCIONES
/**
 * Indica si el envio esta en la cadena activa de devolucion (estado comienza con DEVOLUCION).
 */
function tcgx_devoluciones_en_cadena(string $estado): bool
{
    return str_starts_with($estado, 'DEVOLUCION');
}

/**
 * Listado de envios en devolucion activa (estado DEVOLUCION...) para supervision transversal.
 */
function tcgx_devoluciones_listar(PDO $pdo): array
{
    $sql = 'SELECT e.id, e.idremitente, e.iddestinatario, e.idtiendaorigen, e.idtiendadestino, '
        . 'e.formaenvio, e.estado, '
        . 'tor.nombre AS nombretiendaorigen, tde.nombre AS nombretiendadestino, '
        . 'ur.nombre AS nombreremitente, ud.nombre AS nombredestinatario, '
        . '(SELECT MAX(m.fecharegistro) FROM movimientos_envio m WHERE m.idenvio = e.id) AS ultimomovimiento '
        . 'FROM envios e '
        . 'LEFT JOIN tiendas tor ON tor.id = e.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'LEFT JOIN usuarios ur ON ur.id = e.idremitente '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
        . 'WHERE e.estado LIKE \'DEVOLUCION%\' '
        . 'ORDER BY e.id DESC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Obtiene el envio por rastreo solo si esta en cadena de devolucion activa; null si no existe o no aplica.
 */
function tcgx_devoluciones_obtener(PDO $pdo, string $idEnvio): ?array
{
    $envio = tcgx_envios_obtener($pdo, $idEnvio);
    if ($envio === null || !tcgx_devoluciones_en_cadena((string) $envio['estado'])) {
        return null;
    }
    return $envio;
}
// FIN BLOQUE: LISTADO Y CONSULTAS DE DEVOLUCIONES


// INICIO BLOQUE: AVANCE EN LA CADENA DE DEVOLUCION
/**
 * Devuelve el siguiente estado de la cadena o null si el actual es el ultimo (ENTREGADO) o no pertenece a la cadena.
 */
function tcgx_devoluciones_siguiente_estado(string $estadoActual): ?string
{
    $indice = array_search($estadoActual, TCGX_DEVOLUCION_CADENA, true);
    if ($indice === false) {
        return null;
    }
    $siguiente = $indice + 1;
    return isset(TCGX_DEVOLUCION_CADENA[$siguiente]) ? TCGX_DEVOLUCION_CADENA[$siguiente] : null;
}

/**
 * Indica si el envio puede avanzar un paso mas en la cadena de devolucion.
 */
function tcgx_devoluciones_puede_avanzar(string $estadoActual): bool
{
    return tcgx_devoluciones_siguiente_estado($estadoActual) !== null;
}

/**
 * Resuelve la tienda responsable del movimiento segun el NUEVO estado de devolucion (punto logistico del paso).
 * Retorna null si falta hub en pasos que lo requieren.
 */
function tcgx_devoluciones_tienda_responsable(array $envio, string $estadoNuevo): ?int
{
    switch ($estadoNuevo) {
        case 'DEVOLUCION EN TIENDA DESTINO':
        case 'DEVOLUCION EN TRANSITO A CENTRO DE DISTRIBUCION':
            return (int) $envio['idtiendadestino'];

        case 'DEVOLUCION EN CENTRO DE DISTRIBUCION':
        case 'DEVOLUCION EN TRANSITO A ORIGEN':
            $hub = $envio['idhub'] ?? null;
            return ($hub !== null && (int) $hub > 0) ? (int) $hub : null;

        case 'DEVOLUCION EN TIENDA DE ORIGEN':
        case TCGX_ENVIOS_ESTADO_ENTREGADO:
            return (int) $envio['idtiendaorigen'];

        default:
            return (int) $envio['idtiendaorigen'];
    }
}

/**
 * Valida el avance de un paso en la cadena de devolucion.
 * Retorna ['errores' => string[], 'datos' => ['detalle' => ?string]].
 */
function tcgx_devoluciones_validar_avance(array $envio, array $post): array
{
    $errores = [];
    $estadoActual = (string) $envio['estado'];

    if (!tcgx_devoluciones_puede_avanzar($estadoActual)) {
        $errores[] = 'ESTE ENVIO YA NO PUEDE AVANZAR EN LA CADENA DE DEVOLUCION.';
    }

    $siguiente = tcgx_devoluciones_siguiente_estado($estadoActual);
    if ($siguiente !== null) {
        $idTienda = tcgx_devoluciones_tienda_responsable($envio, $siguiente);
        if ($idTienda === null) {
            $errores[] = 'NO HAY CENTRO DE DISTRIBUCION ASIGNADO PARA ESTE ENVIO; NO SE PUEDE AVANZAR ESTE PASO.';
        }
    }

    $detalle = mb_strtoupper(trim((string) ($post['detalle'] ?? '')), 'UTF-8');
    if ($detalle !== '' && mb_strlen($detalle, 'UTF-8') > 255) {
        $errores[] = 'EL DETALLE NO PUEDE SUPERAR 255 CARACTERES.';
    }

    return [
        'errores' => $errores,
        'datos' => [
            'detalle' => $detalle === '' ? null : $detalle,
        ],
    ];
}

/**
 * Avanza el envio al siguiente estado de la cadena de devolucion: actualiza cabecera, movimiento y auditoria.
 * Retorna ['ok' => true, 'nuevoestado' => ...] o ['ok' => false, 'error' => ...].
 */
function tcgx_devoluciones_avanzar(PDO $pdo, array $envio, ?string $detalle, ?string $idActor): array
{
    $estadoActual = (string) $envio['estado'];
    $estadoNuevo = tcgx_devoluciones_siguiente_estado($estadoActual);

    if ($estadoNuevo === null) {
        return ['ok' => false, 'error' => 'NO HAY UN SIGUIENTE ESTADO EN LA CADENA DE DEVOLUCION.'];
    }

    $idTienda = tcgx_devoluciones_tienda_responsable($envio, $estadoNuevo);
    if ($idTienda === null) {
        return ['ok' => false, 'error' => 'NO HAY CENTRO DE DISTRIBUCION ASIGNADO PARA ESTE ENVIO.'];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE envios SET estado = :estado WHERE id = :id');
        $stmt->execute([':estado' => $estadoNuevo, ':id' => $envio['id']]);

        $stmtM = $pdo->prepare(
            'INSERT INTO movimientos_envio (idenvio, accion, detalle, guiaexterna, idtienda, idusuario) '
            . 'VALUES (:idenvio, :accion, :detalle, NULL, :idtienda, :idusuario)'
        );
        $stmtM->execute([
            ':idenvio' => $envio['id'],
            ':accion' => $estadoNuevo,
            ':detalle' => $detalle,
            ':idtienda' => $idTienda,
            ':idusuario' => $idActor,
        ]);

        tcgx_envios_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $envio['id'], [
            'estado' => $estadoActual,
            'contexto' => 'DEVOLUCION',
        ], [
            'estado' => $estadoNuevo,
            'contexto' => 'DEVOLUCION',
        ]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE AVANZAR EL ESTADO DE DEVOLUCION.'];
    }

    return ['ok' => true, 'nuevoestado' => $estadoNuevo];
}
// FIN BLOQUE: AVANCE EN LA CADENA DE DEVOLUCION
