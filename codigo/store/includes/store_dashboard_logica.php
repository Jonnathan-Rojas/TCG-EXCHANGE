<?php
declare(strict_types=1);

/**
 * Capa de logica del resumen del panel store (index.php).
 * KPIs operativos acotados a la tienda de sesion: envios, incidencias, consolidados y evaluaciones.
 */

require_once __DIR__ . '/store_logica.php';

// INICIO BLOQUE: CONSTANTE DE ESTADO CERRADO DE INCIDENCIA
const TCGX_STORE_DASH_INC_CERRADA = 'INCIDENCIA CERRADA';
// FIN BLOQUE: CONSTANTE DE ESTADO CERRADO DE INCIDENCIA


// INICIO BLOQUE: CONSULTAS DE RESUMEN DEL PANEL TIENDA
/**
 * Devuelve indicadores del dashboard acotados a la tienda de sesion.
 * Claves: incidencias_abiertas, envios_origen, envios_destino, consolidados_activos, evaluaciones, envios_por_estado.
 */
function tcgx_store_dashboard_resumen(PDO $pdo, int $idTienda): array
{
    $stmtInc = $pdo->prepare(
        'SELECT COUNT(*) FROM incidencias i '
        . 'INNER JOIN envios e ON e.id = i.idenvio '
        . 'WHERE i.estadoincidencia <> ? '
        . 'AND (i.idtiendareporta = ? OR e.idtiendaorigen = ? OR e.idtiendadestino = ?)'
    );
    $stmtInc->execute([TCGX_STORE_DASH_INC_CERRADA, $idTienda, $idTienda, $idTienda]);
    $incidenciasAbiertas = (int) $stmtInc->fetchColumn();

    $stmtOrigen = $pdo->prepare(
        "SELECT COUNT(*) FROM envios WHERE idtiendaorigen = ? AND estado NOT IN ('ENTREGADO', 'CANCELADO')"
    );
    $stmtOrigen->execute([$idTienda]);
    $enviosOrigen = (int) $stmtOrigen->fetchColumn();

    $stmtDestino = $pdo->prepare(
        "SELECT COUNT(*) FROM envios WHERE idtiendadestino = ? AND estado NOT IN ('ENTREGADO', 'CANCELADO')"
    );
    $stmtDestino->execute([$idTienda]);
    $enviosDestino = (int) $stmtDestino->fetchColumn();

    $stmtCons = $pdo->prepare(
        'SELECT COUNT(*) FROM consolidados c '
        . 'WHERE c.estado NOT IN (\'RECIBIDO\', \'CANCELADO\') '
        . 'AND ((c.tipotramo = ? AND c.idtiendaorigen = ?) OR (c.tipotramo = ? AND c.idtiendadestino = ?))'
    );
    $stmtCons->execute([
        'ORIGEN A CENTRO DE DISTRIBUCION',
        $idTienda,
        'CENTRO DE DISTRIBUCION A DESTINO',
        $idTienda,
    ]);
    $consolidadosActivos = (int) $stmtCons->fetchColumn();

    $stmtEval = $pdo->prepare('SELECT COUNT(*) FROM evaluaciones WHERE idtienda = ?');
    $stmtEval->execute([$idTienda]);
    $evaluaciones = (int) $stmtEval->fetchColumn();

    $stmtEstados = $pdo->prepare(
        'SELECT e.estado, COUNT(*) AS total FROM envios e '
        . 'WHERE e.idtiendaorigen = ? OR e.idtiendadestino = ? '
        . 'GROUP BY e.estado ORDER BY total DESC, e.estado ASC'
    );
    $stmtEstados->execute([$idTienda, $idTienda]);
    $enviosPorEstado = $stmtEstados->fetchAll();

    return [
        'incidencias_abiertas' => $incidenciasAbiertas,
        'envios_origen' => $enviosOrigen,
        'envios_destino' => $enviosDestino,
        'consolidados_activos' => $consolidadosActivos,
        'evaluaciones' => $evaluaciones,
        'envios_por_estado' => is_array($enviosPorEstado) ? $enviosPorEstado : [],
    ];
}
// FIN BLOQUE: CONSULTAS DE RESUMEN DEL PANEL TIENDA
