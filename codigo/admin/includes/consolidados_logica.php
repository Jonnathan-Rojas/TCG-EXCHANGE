<?php
declare(strict_types=1);

/**
 * Capa de logica y datos del modulo de CONSOLIDADOS (admin) — PROCESO 2: ENVIO AL HUB POR LA TIENDA.
 * Un consolidado agrupa varios envios (sus paquetes) para moverlos JUNTOS por un tramo logistico.
 * Es independiente del registro individual del cliente: aqui los estados se mueven EN BLOQUE.
 *
 * Tramos (consolidados.tipotramo, CHECK de basedatos.sql):
 *   - ORIGEN A CENTRO DE DISTRIBUCION (tramo 1): la tienda agrupa lo que esta EN TIENDA DE ORIGEN y lo manda al HUB.
 *   - CENTRO DE DISTRIBUCION A DESTINO (tramo 2): el HUB desconsolida y arma envios por destino.
 *
 * Reutiliza helpers del envio individual (HUB unico, tiendas punto, etc.).
 * Solo consultas preparadas con parametros enlazados. Datos operativos en MAYUSCULAS.
 * Fuente de verdad: basedatos.sql y diseño.md (flujo tecnico de envios, gestion separada).
 */

require_once __DIR__ . '/envios_logica.php';

// INICIO BLOQUE: CONSTANTES CONTROLADAS DEL CONSOLIDADO
// Tramos validos (alineados al CHECK chkconsolidadostipotramo).
const TCGX_CONS_TRAMO_1 = 'ORIGEN A CENTRO DE DISTRIBUCION';
const TCGX_CONS_TRAMO_2 = 'CENTRO DE DISTRIBUCION A DESTINO';
const TCGX_CONS_TRAMOS = [TCGX_CONS_TRAMO_1, TCGX_CONS_TRAMO_2];

// Estados del consolidado (la columna consolidados.estado no tiene CHECK fijo; la aplicacion los controla).
const TCGX_CONS_ESTADO_ARMADO = 'ARMADO';
const TCGX_CONS_ESTADO_EN_TRANSITO = 'EN TRANSITO';
const TCGX_CONS_ESTADO_RECIBIDO = 'RECIBIDO';
const TCGX_CONS_ESTADO_CANCELADO = 'CANCELADO';

// INICIO SUBBLOQUE: MAPA DE ESTADOS DE ENVIO POR TRAMO Y ACCION
// Define a que estado pasa CADA envio del consolidado al armar/despachar/recibir, segun el tramo.
const TCGX_CONS_MAPA_ESTADOS = [
    TCGX_CONS_TRAMO_1 => [
        'origen_requerido' => 'EN TIENDA DE ORIGEN',
        'armar' => 'PREPARANDO PARA ENVIO',
        'despachar' => 'EN TRANSITO A CENTRO DE DISTRIBUCION',
        'recibir' => 'EN CENTRO DE DISTRIBUCION',
    ],
    TCGX_CONS_TRAMO_2 => [
        'origen_requerido' => 'EN CENTRO DE DISTRIBUCION',
        // En tramo 2 el armado no cambia el estado del envio (sigue EN CENTRO DE DISTRIBUCION hasta el despacho).
        'armar' => 'EN CENTRO DE DISTRIBUCION',
        'despachar' => 'EN TRANSITO A DESTINO',
        'recibir' => 'EN DESTINO',
    ],
];
// FIN SUBBLOQUE: MAPA DE ESTADOS DE ENVIO POR TRAMO Y ACCION
// FIN BLOQUE: CONSTANTES CONTROLADAS DEL CONSOLIDADO


// INICIO BLOQUE: LISTADO Y DETALLE DE CONSOLIDADOS
/**
 * Listado de consolidados con nombres de tiendas y cantidad de envios incluidos.
 */
function tcgx_consolidados_listar(PDO $pdo): array
{
    $sql = 'SELECT c.id, c.tipotramo, c.idtiendaorigen, c.idtiendadestino, c.guiaexterna, c.estado, '
        . 'c.fechasalida, c.fecharecepcion, c.fecharegistro, '
        . 'tor.nombre AS nombreorigen, tde.nombre AS nombredestino, '
        . '(SELECT COUNT(DISTINCT d.idenvio) FROM detalle_consolidados d WHERE d.idconsolidado = c.id) AS totalenvios '
        . 'FROM consolidados c '
        . 'LEFT JOIN tiendas tor ON tor.id = c.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = c.idtiendadestino '
        . 'ORDER BY c.id DESC';
    return $pdo->query($sql)->fetchAll();
}

/**
 * Lee un consolidado por su codigo (CRC) con nombres resueltos; retorna la fila o null si no existe.
 */
function tcgx_consolidados_obtener(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT c.id, c.tipotramo, c.idtiendaorigen, c.idtiendadestino, c.idcentrodistribucion, '
        . 'c.guiaexterna, c.estado, c.fechasalida, c.fecharecepcion, c.fecharegistro, '
        . 'tor.nombre AS nombreorigen, tde.nombre AS nombredestino, thu.nombre AS nombrehub '
        . 'FROM consolidados c '
        . 'LEFT JOIN tiendas tor ON tor.id = c.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = c.idtiendadestino '
        . 'LEFT JOIN tiendas thu ON thu.id = c.idcentrodistribucion '
        . 'WHERE c.id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $fila = $stmt->fetch();
    return $fila === false ? null : $fila;
}

/**
 * Lineas de detalle del consolidado (paquete por paquete) con datos del envio y del paquete.
 */
function tcgx_consolidados_detalle(PDO $pdo, string $idConsolidado): array
{
    $stmt = $pdo->prepare(
        'SELECT d.id, d.idenvio, d.idpaquete, d.recibidocorrecto, d.observacionrecepcion, d.fecharecepcion, '
        . 'e.estado AS estadoenvio, e.formaenvio, '
        . 'p.tipo AS tipopaquete, p.descripcion AS descripcionpaquete, p.cantidad, '
        . 'ud.nombre AS nombredestinatario, tde.nombre AS nombredestinoenvio '
        . 'FROM detalle_consolidados d '
        . 'INNER JOIN envios e ON e.id = d.idenvio '
        . 'LEFT JOIN paquetes p ON p.id = d.idpaquete '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'WHERE d.idconsolidado = ? ORDER BY d.idenvio ASC, d.idpaquete ASC'
    );
    $stmt->execute([$idConsolidado]);
    return $stmt->fetchAll();
}

/**
 * Envios ELEGIBLES para armar un consolidado de un tramo y una tienda dada.
 *   - Tramo 1: envios EN TIENDA DE ORIGEN, que pasan por el HUB (no EN TIENDA), de esa tienda de ORIGEN.
 *   - Tramo 2: envios EN CENTRO DE DISTRIBUCION cuyo DESTINO final es esa tienda.
 * En ambos casos se excluyen los que ya esten en un consolidado activo (no cancelado) del mismo tramo.
 * Retorna filas de envio con su cantidad de paquetes.
 */
function tcgx_consolidados_envios_elegibles(PDO $pdo, string $tramo, int $idTienda): array
{
    if ($tramo === TCGX_CONS_TRAMO_1) {
        $sql = 'SELECT e.id, e.iddestinatario, e.idtiendadestino, e.formaenvio, e.estado, '
            . 'ud.nombre AS nombredestinatario, tde.nombre AS nombredestino, '
            . '(SELECT COUNT(*) FROM paquetes p WHERE p.idenvio = e.id) AS totalpaquetes '
            . 'FROM envios e '
            . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
            . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'WHERE e.estado = :estado AND e.idtiendaorigen = :idtienda AND e.formaenvio <> :entienda '
        . 'AND e.idhub IS NOT NULL '
        . 'AND NOT EXISTS (SELECT 1 FROM detalle_consolidados d INNER JOIN consolidados c ON c.id = d.idconsolidado '
            . 'WHERE d.idenvio = e.id AND c.tipotramo = :tramo AND c.estado <> :cancelado) '
            . 'ORDER BY e.id ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':estado' => TCGX_CONS_MAPA_ESTADOS[TCGX_CONS_TRAMO_1]['origen_requerido'],
            ':idtienda' => $idTienda,
            ':entienda' => TCGX_ENVIOS_RUTA_EN_TIENDA,
            ':tramo' => TCGX_CONS_TRAMO_1,
            ':cancelado' => TCGX_CONS_ESTADO_CANCELADO,
        ]);
        return $stmt->fetchAll();
    }

    $sql = 'SELECT e.id, e.iddestinatario, e.idtiendadestino, e.formaenvio, e.estado, '
        . 'ud.nombre AS nombredestinatario, tde.nombre AS nombredestino, '
        . '(SELECT COUNT(*) FROM paquetes p WHERE p.idenvio = e.id) AS totalpaquetes '
        . 'FROM envios e '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'WHERE e.estado = :estado AND e.idtiendadestino = :idtienda '
        . 'AND NOT EXISTS (SELECT 1 FROM detalle_consolidados d INNER JOIN consolidados c ON c.id = d.idconsolidado '
        . 'WHERE d.idenvio = e.id AND c.tipotramo = :tramo AND c.estado <> :cancelado) '
        . 'ORDER BY e.id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':estado' => TCGX_CONS_MAPA_ESTADOS[TCGX_CONS_TRAMO_2]['origen_requerido'],
        ':idtienda' => $idTienda,
        ':tramo' => TCGX_CONS_TRAMO_2,
        ':cancelado' => TCGX_CONS_ESTADO_CANCELADO,
    ]);
    return $stmt->fetchAll();
}
// FIN BLOQUE: LISTADO Y DETALLE DE CONSOLIDADOS


// INICIO BLOQUE: AUDITORIA Y CODIGO DE RASTREO DEL CONSOLIDADO
/**
 * Inserta una fila en auditorias para operaciones sobre la tabla consolidados.
 */
function tcgx_consolidados_auditar(PDO $pdo, ?string $idActor, string $accion, string $idRegistro, ?array $antes, ?array $despues): void
{
    $jsonAntes = $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $jsonDespues = $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $stmt = $pdo->prepare(
        'INSERT INTO auditorias (idusuario, accion, tablaafectada, idregistro, datosantes, datosdespues) '
        . 'VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idActor, $accion, 'consolidados', $idRegistro, $jsonAntes, $jsonDespues]);
}

/**
 * Genera un codigo de rastreo de consolidado con formato CRC + AAAAMMDDHHMMSS (17 caracteres).
 */
function tcgx_consolidados_generar_id(): string
{
    return 'CRC' . date('YmdHis');
}

/**
 * Registra un movimiento en CADA envio del consolidado (actualizacion en bloque de trazabilidad).
 * Si $nuevoEstado no es null, ademas actualiza envios.estado de cada envio incluido.
 */
function tcgx_consolidados_mover_envios(PDO $pdo, string $idConsolidado, ?string $nuevoEstado, string $accionMov, string $detalle, int $idTiendaResponsable, ?string $idActor): void
{
    // Envios distintos incluidos en el consolidado (un envio puede tener varios paquetes/detalles).
    $stmtSel = $pdo->prepare('SELECT DISTINCT idenvio FROM detalle_consolidados WHERE idconsolidado = ?');
    $stmtSel->execute([$idConsolidado]);
    $envios = $stmtSel->fetchAll();

    $stmtEstado = $pdo->prepare('UPDATE envios SET estado = :estado WHERE id = :id');
    $stmtMov = $pdo->prepare(
        'INSERT INTO movimientos_envio (idenvio, accion, detalle, guiaexterna, idtienda, idusuario) '
        . 'VALUES (:idenvio, :accion, :detalle, NULL, :idtienda, :idusuario)'
    );

    foreach ($envios as $fila) {
        $idEnvio = (string) $fila['idenvio'];
        if ($nuevoEstado !== null) {
            $stmtEstado->execute([':estado' => $nuevoEstado, ':id' => $idEnvio]);
        }
        $stmtMov->execute([
            ':idenvio' => $idEnvio,
            ':accion' => $nuevoEstado ?? $accionMov,
            ':detalle' => $detalle,
            ':idtienda' => $idTiendaResponsable,
            ':idusuario' => $idActor,
        ]);
    }
}
// FIN BLOQUE: AUDITORIA Y CODIGO DE RASTREO DEL CONSOLIDADO


// INICIO BLOQUE: ARMADO DE CONSOLIDADO (TRANSACCION + MOVIMIENTO EN BLOQUE)
/**
 * Valida y arma un consolidado de un tramo a partir de una lista de envios elegibles.
 * Crea la cabecera (CRC), inserta el detalle paquete por paquete y mueve en bloque los envios incluidos.
 * Retorna ['ok' => true, 'id' => <CRC...>] o ['ok' => false, 'error' => <mensaje>].
 */
function tcgx_consolidados_armar(PDO $pdo, string $tramo, string $idTiendaRaw, array $idEnvios, ?string $idActor): array
{
    // --- Tramo valido ---
    if (!in_array($tramo, TCGX_CONS_TRAMOS, true)) {
        return ['ok' => false, 'error' => 'TRAMO NO VALIDO.'];
    }
    $mapa = TCGX_CONS_MAPA_ESTADOS[$tramo];

    // --- Centro de distribucion unico (intermediario obligatorio del consolidado) ---
    $hub = tcgx_envios_hub_unico($pdo);
    if ($hub === null) {
        return ['ok' => false, 'error' => 'NO HAY UN CENTRO DE DISTRIBUCION ACTIVO.'];
    }
    $idHub = (int) $hub['id'];

    // --- Tienda del tramo (origen en tramo 1, destino en tramo 2): activa y no hub ---
    $errores = [];
    $idTienda = tcgx_envios_validar_tienda($pdo, $idTiendaRaw, $tramo === TCGX_CONS_TRAMO_1 ? 'ORIGEN' : 'DESTINO', $errores);
    if ($idTienda === null) {
        return ['ok' => false, 'error' => $errores[0] ?? 'TIENDA NO VALIDA.'];
    }

    // --- Envios seleccionados (al menos uno) y validados contra la lista de elegibles del tramo ---
    $idEnvios = array_values(array_unique(array_filter(array_map('strval', $idEnvios), static fn ($v) => $v !== '')));
    if (empty($idEnvios)) {
        return ['ok' => false, 'error' => 'DEBE SELECCIONAR AL MENOS UN ENVIO PARA CONSOLIDAR.'];
    }
    $elegibles = [];
    foreach (tcgx_consolidados_envios_elegibles($pdo, $tramo, $idTienda) as $e) {
        $elegibles[(string) $e['id']] = $e;
    }
    foreach ($idEnvios as $idEnvio) {
        if (!isset($elegibles[$idEnvio])) {
            return ['ok' => false, 'error' => 'UNO DE LOS ENVIOS SELECCIONADOS YA NO ES ELEGIBLE PARA ESTE CONSOLIDADO.'];
        }
    }

    // Origen y destino de la CABECERA segun el tramo (el HUB es el extremo intermedio).
    $idOrigenCab = $tramo === TCGX_CONS_TRAMO_1 ? $idTienda : $idHub;
    $idDestinoCab = $tramo === TCGX_CONS_TRAMO_1 ? $idHub : $idTienda;
    // Responsable del movimiento en bloque: el origen del tramo (tienda en T1, HUB en T2).
    $idResponsable = $idOrigenCab;

    $intentos = 0;
    do {
        $intentos++;
        $idConsolidado = tcgx_consolidados_generar_id();
        try {
            $pdo->beginTransaction();

            $stmtC = $pdo->prepare(
                'INSERT INTO consolidados (id, tipotramo, idtiendaorigen, idtiendadestino, idcentrodistribucion, guiaexterna, estado) '
                . 'VALUES (:id, :tramo, :origen, :destino, :centro, NULL, :estado)'
            );
            $stmtC->execute([
                ':id' => $idConsolidado,
                ':tramo' => $tramo,
                ':origen' => $idOrigenCab,
                ':destino' => $idDestinoCab,
                ':centro' => $idHub,
                ':estado' => TCGX_CONS_ESTADO_ARMADO,
            ]);

            // Detalle: una fila por CADA paquete de cada envio incluido.
            $stmtPaq = $pdo->prepare('SELECT id FROM paquetes WHERE idenvio = ?');
            $stmtDet = $pdo->prepare(
                'INSERT INTO detalle_consolidados (idconsolidado, idenvio, idpaquete) VALUES (:idc, :ide, :idp)'
            );
            foreach ($idEnvios as $idEnvio) {
                $stmtPaq->execute([$idEnvio]);
                foreach ($stmtPaq->fetchAll() as $paq) {
                    $stmtDet->execute([':idc' => $idConsolidado, ':ide' => $idEnvio, ':idp' => (int) $paq['id']]);
                }
            }

            // Movimiento en bloque: actualiza el estado de cada envio segun el tramo (armar).
            tcgx_consolidados_mover_envios(
                $pdo,
                $idConsolidado,
                $mapa['armar'],
                $mapa['armar'],
                'INCLUIDO EN CONSOLIDADO ' . $idConsolidado,
                $idResponsable,
                $idActor
            );

            tcgx_consolidados_auditar($pdo, $idActor, 'CREAR', $idConsolidado, null, [
                'tipotramo' => $tramo,
                'totalenvios' => count($idEnvios),
                'estado' => TCGX_CONS_ESTADO_ARMADO,
            ]);

            $pdo->commit();
            return ['ok' => true, 'id' => $idConsolidado];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Colision del codigo (PK) en el mismo segundo: reintenta una vez.
            if ($e->getCode() === '23000' && $intentos < 2) {
                sleep(1);
                continue;
            }
            return ['ok' => false, 'error' => 'NO FUE POSIBLE ARMAR EL CONSOLIDADO.'];
        }
    } while ($intentos < 2);

    return ['ok' => false, 'error' => 'NO FUE POSIBLE ARMAR EL CONSOLIDADO.'];
}
// FIN BLOQUE: ARMADO DE CONSOLIDADO


// INICIO BLOQUE: DESPACHO Y RECEPCION (MOVIMIENTO EN BLOQUE)
/**
 * Despacha un consolidado ARMADO: marca EN TRANSITO con fecha de salida y mueve en bloque los envios.
 * La guia externa es opcional (p. ej. Correos de Costa Rica en rutas remotas).
 */
function tcgx_consolidados_despachar(PDO $pdo, array $consolidado, string $guiaRaw, ?string $idActor): array
{
    if ((string) $consolidado['estado'] !== TCGX_CONS_ESTADO_ARMADO) {
        return ['ok' => false, 'error' => 'SOLO SE PUEDE DESPACHAR UN CONSOLIDADO EN ESTADO ARMADO.'];
    }
    $tramo = (string) $consolidado['tipotramo'];
    $mapa = TCGX_CONS_MAPA_ESTADOS[$tramo] ?? null;
    if ($mapa === null) {
        return ['ok' => false, 'error' => 'TRAMO NO VALIDO.'];
    }

    $guia = mb_strtoupper(trim($guiaRaw), 'UTF-8');
    if (mb_strlen($guia, 'UTF-8') > 80) {
        return ['ok' => false, 'error' => 'LA GUIA EXTERNA NO PUEDE SUPERAR 80 CARACTERES.'];
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('UPDATE consolidados SET estado = :estado, guiaexterna = :guia, fechasalida = NOW() WHERE id = :id');
        $stmt->execute([
            ':estado' => TCGX_CONS_ESTADO_EN_TRANSITO,
            ':guia' => $guia === '' ? null : $guia,
            ':id' => $consolidado['id'],
        ]);

        tcgx_consolidados_mover_envios(
            $pdo,
            (string) $consolidado['id'],
            $mapa['despachar'],
            $mapa['despachar'],
            'DESPACHADO EN CONSOLIDADO ' . $consolidado['id'],
            (int) $consolidado['idtiendaorigen'],
            $idActor
        );

        tcgx_consolidados_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $consolidado['id'], ['estado' => TCGX_CONS_ESTADO_ARMADO], ['estado' => TCGX_CONS_ESTADO_EN_TRANSITO]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE DESPACHAR EL CONSOLIDADO.'];
    }

    return ['ok' => true];
}

/**
 * Recibe un consolidado EN TRANSITO: registra recepcion paquete por paquete (recibidocorrecto + observacion),
 * marca el consolidado RECIBIDO con fecha de recepcion y mueve en bloque los envios al estado del tramo.
 * $recepcion es un mapa [idDetalle => ['recibido' => bool, 'observacion' => string]].
 */
function tcgx_consolidados_recibir(PDO $pdo, array $consolidado, array $recepcion, ?string $idActor): array
{
    if ((string) $consolidado['estado'] !== TCGX_CONS_ESTADO_EN_TRANSITO) {
        return ['ok' => false, 'error' => 'SOLO SE PUEDE RECIBIR UN CONSOLIDADO EN TRANSITO.'];
    }
    $tramo = (string) $consolidado['tipotramo'];
    $mapa = TCGX_CONS_MAPA_ESTADOS[$tramo] ?? null;
    if ($mapa === null) {
        return ['ok' => false, 'error' => 'TRAMO NO VALIDO.'];
    }

    try {
        $pdo->beginTransaction();

        // Recepcion por paquete: actualiza cada linea de detalle del consolidado.
        $stmtDet = $pdo->prepare(
            'UPDATE detalle_consolidados SET recibidocorrecto = :ok, observacionrecepcion = :obs, fecharecepcion = NOW() '
            . 'WHERE id = :id AND idconsolidado = :idc'
        );
        $stmtIds = $pdo->prepare('SELECT id FROM detalle_consolidados WHERE idconsolidado = ?');
        $stmtIds->execute([$consolidado['id']]);
        foreach ($stmtIds->fetchAll() as $fila) {
            $idDet = (int) $fila['id'];
            $info = $recepcion[$idDet] ?? ['recibido' => true, 'observacion' => ''];
            $obs = mb_strtoupper(trim((string) ($info['observacion'] ?? '')), 'UTF-8');
            $stmtDet->execute([
                ':ok' => !empty($info['recibido']) ? 1 : 0,
                ':obs' => $obs === '' ? null : mb_substr($obs, 0, 255, 'UTF-8'),
                ':id' => $idDet,
                ':idc' => $consolidado['id'],
            ]);
        }

        $stmt = $pdo->prepare('UPDATE consolidados SET estado = :estado, fecharecepcion = NOW() WHERE id = :id');
        $stmt->execute([':estado' => TCGX_CONS_ESTADO_RECIBIDO, ':id' => $consolidado['id']]);

        tcgx_consolidados_mover_envios(
            $pdo,
            (string) $consolidado['id'],
            $mapa['recibir'],
            $mapa['recibir'],
            'RECIBIDO DEL CONSOLIDADO ' . $consolidado['id'],
            (int) $consolidado['idtiendadestino'],
            $idActor
        );

        tcgx_consolidados_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $consolidado['id'], ['estado' => TCGX_CONS_ESTADO_EN_TRANSITO], ['estado' => TCGX_CONS_ESTADO_RECIBIDO]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE RECIBIR EL CONSOLIDADO.'];
    }

    return ['ok' => true];
}
// FIN BLOQUE: DESPACHO Y RECEPCION


// INICIO BLOQUE: CANCELACION Y EXTRACCION DE ENVIOS (SOLO ANTES DEL DESPACHO)
/**
 * Cancela un consolidado ARMADO (aun no despachado): revierte en bloque los envios al estado de origen
 * del tramo, elimina el detalle, marca el consolidado CANCELADO y audita.
 */
function tcgx_consolidados_cancelar(PDO $pdo, array $consolidado, ?string $idActor): array
{
    if ((string) $consolidado['estado'] !== TCGX_CONS_ESTADO_ARMADO) {
        return ['ok' => false, 'error' => 'SOLO SE PUEDE CANCELAR UN CONSOLIDADO QUE AUN NO HA SIDO DESPACHADO.'];
    }
    $tramo = (string) $consolidado['tipotramo'];
    $mapa = TCGX_CONS_MAPA_ESTADOS[$tramo] ?? null;
    if ($mapa === null) {
        return ['ok' => false, 'error' => 'TRAMO NO VALIDO.'];
    }

    try {
        $pdo->beginTransaction();

        // Revierte cada envio al estado previo al armado (estado de origen del tramo) y deja constancia.
        tcgx_consolidados_mover_envios(
            $pdo,
            (string) $consolidado['id'],
            $mapa['origen_requerido'],
            $mapa['origen_requerido'],
            'CONSOLIDADO ' . $consolidado['id'] . ' CANCELADO',
            (int) $consolidado['idtiendaorigen'],
            $idActor
        );

        $pdo->prepare('DELETE FROM detalle_consolidados WHERE idconsolidado = ?')->execute([$consolidado['id']]);
        $pdo->prepare('UPDATE consolidados SET estado = ? WHERE id = ?')->execute([TCGX_CONS_ESTADO_CANCELADO, $consolidado['id']]);

        tcgx_consolidados_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $consolidado['id'], ['estado' => TCGX_CONS_ESTADO_ARMADO], ['estado' => TCGX_CONS_ESTADO_CANCELADO]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE CANCELAR EL CONSOLIDADO.'];
    }

    return ['ok' => true];
}

/**
 * Saca un envio de un consolidado ARMADO (aun no despachado): elimina su detalle, revierte ese envio
 * al estado de origen del tramo y registra su movimiento. Si el consolidado queda vacio, se cancela.
 */
function tcgx_consolidados_sacar_envio(PDO $pdo, array $consolidado, string $idEnvio, ?string $idActor): array
{
    if ((string) $consolidado['estado'] !== TCGX_CONS_ESTADO_ARMADO) {
        return ['ok' => false, 'error' => 'SOLO SE PUEDE SACAR ENVIOS DE UN CONSOLIDADO QUE AUN NO HA SIDO DESPACHADO.'];
    }
    $tramo = (string) $consolidado['tipotramo'];
    $mapa = TCGX_CONS_MAPA_ESTADOS[$tramo] ?? null;
    if ($mapa === null) {
        return ['ok' => false, 'error' => 'TRAMO NO VALIDO.'];
    }

    // El envio debe pertenecer al consolidado.
    $stmtChk = $pdo->prepare('SELECT 1 FROM detalle_consolidados WHERE idconsolidado = ? AND idenvio = ? LIMIT 1');
    $stmtChk->execute([$consolidado['id'], $idEnvio]);
    if ($stmtChk->fetch() === false) {
        return ['ok' => false, 'error' => 'EL ENVIO NO PERTENECE A ESTE CONSOLIDADO.'];
    }

    try {
        $pdo->beginTransaction();

        // Revierte el envio extraido y registra su movimiento individual.
        $pdo->prepare('UPDATE envios SET estado = :estado WHERE id = :id')
            ->execute([':estado' => $mapa['origen_requerido'], ':id' => $idEnvio]);
        $pdo->prepare(
            'INSERT INTO movimientos_envio (idenvio, accion, detalle, guiaexterna, idtienda, idusuario) '
            . 'VALUES (:idenvio, :accion, :detalle, NULL, :idtienda, :idusuario)'
        )->execute([
            ':idenvio' => $idEnvio,
            ':accion' => $mapa['origen_requerido'],
            ':detalle' => 'EXTRAIDO DEL CONSOLIDADO ' . $consolidado['id'],
            ':idtienda' => (int) $consolidado['idtiendaorigen'],
            ':idusuario' => $idActor,
        ]);

        $pdo->prepare('DELETE FROM detalle_consolidados WHERE idconsolidado = ? AND idenvio = ?')
            ->execute([$consolidado['id'], $idEnvio]);

        tcgx_consolidados_auditar($pdo, $idActor, 'ACTUALIZAR', (string) $consolidado['id'], ['envioextraido' => $idEnvio], null);

        // Si el consolidado quedo sin envios, se cancela para no dejar cabeceras vacias.
        $stmtCount = $pdo->prepare('SELECT COUNT(*) AS total FROM detalle_consolidados WHERE idconsolidado = ?');
        $stmtCount->execute([$consolidado['id']]);
        $restantes = (int) ($stmtCount->fetch()['total'] ?? 0);
        if ($restantes === 0) {
            $pdo->prepare('UPDATE consolidados SET estado = ? WHERE id = ?')->execute([TCGX_CONS_ESTADO_CANCELADO, $consolidado['id']]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['ok' => false, 'error' => 'NO FUE POSIBLE SACAR EL ENVIO DEL CONSOLIDADO.'];
    }

    return ['ok' => true, 'vacio' => isset($restantes) && $restantes === 0];
}
// FIN BLOQUE: CANCELACION Y EXTRACCION DE ENVIOS
