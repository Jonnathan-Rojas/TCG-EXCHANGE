<?php
declare(strict_types=1);

/**
 * Capa de logica del resumen del panel cd (index.php).
 * KPIs operativos acotados al hub de sesion.
 */

require_once __DIR__ . '/cd_logica.php';

// INICIO BLOQUE: CONSTANTE DE ESTADO CERRADO DE INCIDENCIA
const TCGX_CD_DASH_INC_CERRADA = 'INCIDENCIA CERRADA';
// FIN BLOQUE: CONSTANTE DE ESTADO CERRADO DE INCIDENCIA


// INICIO BLOQUE: CONSULTAS DE RESUMEN DEL PANEL CENTRO DE DISTRIBUCION
/**
 * Devuelve indicadores del dashboard acotados al hub de sesion.
 */
function tcgx_cd_dashboard_resumen(PDO $pdo, int $idHub): array
{
    $stmtInc = $pdo->prepare(
        'SELECT COUNT(*) FROM incidencias i '
        . 'INNER JOIN envios e ON e.id = i.idenvio '
        . 'WHERE i.estadoincidencia <> ? '
        . 'AND (i.idtiendareporta = ? OR e.idhub = ?)'
    );
    $stmtInc->execute([TCGX_CD_DASH_INC_CERRADA, $idHub, $idHub]);
    $incidenciasAbiertas = (int) $stmtInc->fetchColumn();

    $stmtEnCd = $pdo->prepare(
        "SELECT COUNT(*) FROM envios WHERE idhub = ? AND estado = 'EN CENTRO DE DISTRIBUCION'"
    );
    $stmtEnCd->execute([$idHub]);
    $enviosEnCentro = (int) $stmtEnCd->fetchColumn();

    $stmtT1Transito = $pdo->prepare(
        'SELECT COUNT(*) FROM consolidados c '
        . 'WHERE c.idcentrodistribucion = ? AND c.tipotramo = ? '
        . "AND c.estado = 'EN TRANSITO' AND c.idtiendadestino = ?"
    );
    $stmtT1Transito->execute([
        $idHub,
        'ORIGEN A CENTRO DE DISTRIBUCION',
        $idHub,
    ]);
    $consolidadosEntrantes = (int) $stmtT1Transito->fetchColumn();

    $stmtConsActivos = $pdo->prepare(
        'SELECT COUNT(*) FROM consolidados c '
        . 'WHERE c.idcentrodistribucion = ? '
        . "AND c.estado NOT IN ('RECIBIDO', 'CANCELADO')"
    );
    $stmtConsActivos->execute([$idHub]);
    $consolidadosActivos = (int) $stmtConsActivos->fetchColumn();

    $stmtEval = $pdo->prepare('SELECT COUNT(*) FROM evaluaciones WHERE idtienda = ?');
    $stmtEval->execute([$idHub]);
    $evaluaciones = (int) $stmtEval->fetchColumn();

    $stmtEstados = $pdo->prepare(
        'SELECT e.estado, COUNT(*) AS total FROM envios e '
        . 'WHERE e.idhub = ? '
        . 'GROUP BY e.estado ORDER BY total DESC, e.estado ASC'
    );
    $stmtEstados->execute([$idHub]);
    $enviosPorEstado = $stmtEstados->fetchAll();

    return [
        'incidencias_abiertas' => $incidenciasAbiertas,
        'envios_en_centro' => $enviosEnCentro,
        'consolidados_entrantes' => $consolidadosEntrantes,
        'consolidados_activos' => $consolidadosActivos,
        'evaluaciones' => $evaluaciones,
        'envios_por_estado' => is_array($enviosPorEstado) ? $enviosPorEstado : [],
    ];
}
// FIN BLOQUE: CONSULTAS DE RESUMEN DEL PANEL CENTRO DE DISTRIBUCION
