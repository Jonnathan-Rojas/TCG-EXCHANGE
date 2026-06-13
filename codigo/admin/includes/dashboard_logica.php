<?php
declare(strict_types=1);

/**
 * Capa de logica del resumen del panel admin (index.php).
 * Agrega KPIs operativos: envios por estado, incidencias abiertas, usuarios y tiendas activas.
 * Solo consultas de lectura con parametros enlazados donde aplica.
 */

// INICIO BLOQUE: CONSTANTE DE ESTADO CERRADO DE INCIDENCIA
// Reutiliza el valor definido en incidencias_logica cuando ya esta cargada; si no, valor canonico de BD.
const TCGX_DASH_INC_CERRADA = 'INCIDENCIA CERRADA';
// FIN BLOQUE: CONSTANTE DE ESTADO CERRADO DE INCIDENCIA


// INICIO BLOQUE: CONSULTAS DE RESUMEN DEL PANEL
/**
 * Devuelve el conjunto de indicadores para el dashboard del administrador.
 * Claves: incidencias_abiertas, usuarios_activos, tiendas_activas, envios_por_estado (lista estado/total).
 */
function tcgx_dashboard_resumen(PDO $pdo): array
{
    $stmtInc = $pdo->prepare(
        'SELECT COUNT(*) FROM incidencias WHERE estadoincidencia <> ?'
    );
    $stmtInc->execute([TCGX_DASH_INC_CERRADA]);
    $incidenciasAbiertas = (int) $stmtInc->fetchColumn();

    $usuariosActivos = (int) $pdo->query(
        "SELECT COUNT(*) FROM usuarios WHERE estado = 'ACTIVO'"
    )->fetchColumn();

    $tiendasActivas = (int) $pdo->query(
        "SELECT COUNT(*) FROM tiendas WHERE estado = 'ACTIVO'"
    )->fetchColumn();

    $enviosPorEstado = $pdo->query(
        'SELECT estado, COUNT(*) AS total FROM envios GROUP BY estado ORDER BY total DESC, estado ASC'
    )->fetchAll();

    return [
        'incidencias_abiertas' => $incidenciasAbiertas,
        'usuarios_activos' => $usuariosActivos,
        'tiendas_activas' => $tiendasActivas,
        'envios_por_estado' => is_array($enviosPorEstado) ? $enviosPorEstado : [],
    ];
}
// FIN BLOQUE: CONSULTAS DE RESUMEN DEL PANEL
