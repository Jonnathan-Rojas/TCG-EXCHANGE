<?php
declare(strict_types=1);

/**
 * Capa de logica del resumen del panel client (index.php).
 */

require_once __DIR__ . '/client_logica.php';

// INICIO BLOQUE: CONSULTAS DE RESUMEN DEL PANEL CLIENTE
/**
 * Devuelve indicadores del dashboard acotados al usuario de sesion.
 */
function tcgx_client_dashboard_resumen(PDO $pdo, string $idUsuario): array
{
    $stmtBinders = $pdo->prepare(
        "SELECT COUNT(*) FROM binders WHERE idusuario = ? AND estado = 'ACTIVO'"
    );
    $stmtBinders->execute([$idUsuario]);
    $bindersActivos = (int) $stmtBinders->fetchColumn();

    $stmtProd = $pdo->prepare(
        'SELECT COUNT(*) FROM productos_binder p '
        . 'INNER JOIN binders b ON b.id = p.idbinder '
        . "WHERE b.idusuario = ? AND p.estado = 'ACTIVO'"
    );
    $stmtProd->execute([$idUsuario]);
    $productosActivos = (int) $stmtProd->fetchColumn();

    $stmtPub = $pdo->prepare(
        'SELECT COUNT(*) FROM productos_binder p '
        . 'INNER JOIN binders b ON b.id = p.idbinder '
        . "WHERE b.idusuario = ? AND p.publicado = 1 AND p.estado = 'ACTIVO'"
    );
    $stmtPub->execute([$idUsuario]);
    $productosPublicados = (int) $stmtPub->fetchColumn();

    $stmtEnvRem = $pdo->prepare(
        "SELECT COUNT(*) FROM envios WHERE idremitente = ? AND estado NOT IN ('ENTREGADO', 'CANCELADO')"
    );
    $stmtEnvRem->execute([$idUsuario]);
    $enviosComoRemitente = (int) $stmtEnvRem->fetchColumn();

    $stmtEnvDes = $pdo->prepare(
        "SELECT COUNT(*) FROM envios WHERE iddestinatario = ? AND estado NOT IN ('ENTREGADO', 'CANCELADO')"
    );
    $stmtEnvDes->execute([$idUsuario]);
    $enviosComoDestinatario = (int) $stmtEnvDes->fetchColumn();

    $stmtEstados = $pdo->prepare(
        'SELECT e.estado, COUNT(*) AS total FROM envios e '
        . 'WHERE e.idremitente = ? OR e.iddestinatario = ? '
        . 'GROUP BY e.estado ORDER BY total DESC, e.estado ASC'
    );
    $stmtEstados->execute([$idUsuario, $idUsuario]);
    $enviosPorEstado = $stmtEstados->fetchAll();

    return [
        'binders_activos' => $bindersActivos,
        'productos_activos' => $productosActivos,
        'productos_publicados' => $productosPublicados,
        'envios_remitente' => $enviosComoRemitente,
        'envios_destinatario' => $enviosComoDestinatario,
        'envios_por_estado' => is_array($enviosPorEstado) ? $enviosPorEstado : [],
    ];
}
// FIN BLOQUE: CONSULTAS DE RESUMEN DEL PANEL CLIENTE
