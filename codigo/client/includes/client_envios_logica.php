<?php
declare(strict_types=1);

/**
 * Wrappers de consulta de envios acotados al cliente de sesion (solo lectura).
 */

require_once __DIR__ . '/client_logica.php';
require_once __DIR__ . '/../../admin/includes/envios_logica.php';

// INICIO BLOQUE: LISTADO Y DETALLE ACOTADOS AL CLIENTE
/**
 * Envios del usuario de sesion. Filtro opcional: remitente (enviados) o destinatario (recepciones).
 *
 * @param string|null $rolFiltro null = ambos; 'remitente' = enviados; 'destinatario' = recepciones
 */
function tcgx_client_envios_listar(PDO $pdo, string $idUsuario, ?string $rolFiltro = null): array
{
    $sql = 'SELECT e.id, e.idremitente, e.iddestinatario, e.idtiendaorigen, e.idtiendadestino, '
        . 'e.formaenvio, e.montoapagar, e.estado, '
        . 'tor.nombre AS nombretiendaorigen, tde.nombre AS nombretiendadestino, '
        . 'ur.nombre AS nombreremitente, ud.nombre AS nombredestinatario '
        . 'FROM envios e '
        . 'LEFT JOIN tiendas tor ON tor.id = e.idtiendaorigen '
        . 'LEFT JOIN tiendas tde ON tde.id = e.idtiendadestino '
        . 'LEFT JOIN usuarios ur ON ur.id = e.idremitente '
        . 'LEFT JOIN usuarios ud ON ud.id = e.iddestinatario ';

    if ($rolFiltro === 'remitente') {
        $sql .= 'WHERE e.idremitente = ? ';
        $params = [$idUsuario];
    } elseif ($rolFiltro === 'destinatario') {
        $sql .= 'WHERE e.iddestinatario = ? ';
        $params = [$idUsuario];
    } else {
        $sql .= 'WHERE e.idremitente = ? OR e.iddestinatario = ? ';
        $params = [$idUsuario, $idUsuario];
    }

    $sql .= 'ORDER BY e.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtiene envio verificando que el cliente de sesion sea remitente o destinatario.
 */
function tcgx_client_envios_obtener(PDO $pdo, string $id, string $idUsuario): ?array
{
    $envio = tcgx_envios_obtener($pdo, $id);
    if ($envio === null) {
        return null;
    }
    if ((string) ($envio['idremitente'] ?? '') !== $idUsuario
        && (string) ($envio['iddestinatario'] ?? '') !== $idUsuario) {
        return null;
    }
    return $envio;
}
// FIN BLOQUE: LISTADO Y DETALLE ACOTADOS AL CLIENTE
