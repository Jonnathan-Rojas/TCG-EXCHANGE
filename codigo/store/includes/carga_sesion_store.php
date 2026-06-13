<?php
declare(strict_types=1);

/**
 * Arranque comun de paginas del modulo store: BD, sesion, logout POST, autorizacion TIENDA (eshub=0),
 * CSRF, datos de cabecera y URLs de imagenes. Incluir una sola vez al inicio de cada PHP bajo store/.
 */

// INICIO BLOQUE: ARRANQUE SESION Y DEPENDENCIAS
require __DIR__ . '/../../vendor/bd.php';
require_once __DIR__ . '/../../includes/rutas_assets.php';
date_default_timezone_set('America/Costa_Rica');

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'use_strict_mode' => true,
    ]);
}
// FIN BLOQUE: ARRANQUE SESION Y DEPENDENCIAS

$tcgxStoreScriptNombre = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));

// INICIO BLOQUE: CIERRE SESION TIENDA POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_store_logout'])) {
    $tokenSesion = $_SESSION['tcgx_store_csrf'] ?? '';
    $tokenPost = $_POST['tcgx_csrf_token'] ?? '';
    $idUsuarioSesion = isset($_SESSION['tcgx_usuario_id']) ? (string) $_SESSION['tcgx_usuario_id'] : '';
    $perfilSesion = isset($_SESSION['tcgx_perfil']) ? (string) $_SESSION['tcgx_perfil'] : '';

    if (
        $tokenSesion !== ''
        && $tokenPost !== ''
        && hash_equals($tokenSesion, (string) $tokenPost)
        && $idUsuarioSesion !== ''
        && $perfilSesion === 'TIENDA'
    ) {
        unset($_SESSION['tcgx_store_csrf']);
        try {
            $pdo = Bd::getPdo();
            $stmt = $pdo->prepare(
                'INSERT INTO auditorias (idusuario, accion, tablaafectada, idregistro, datosantes, datosdespues) '
                . 'VALUES (?, ?, ?, ?, NULL, NULL)'
            );
            $stmt->execute([$idUsuarioSesion, 'LOGOUT', 'usuarios', $idUsuarioSesion]);
        } catch (Throwable) {
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: ../login.php', true, 302);
        exit;
    }
    header('Location: ' . $tcgxStoreScriptNombre, true, 303);
    exit;
}
// FIN BLOQUE: CIERRE SESION TIENDA POST

// INICIO BLOQUE: AUTORIZACION PERFIL TIENDA (NO HUB)
$perfilActual = $_SESSION['tcgx_perfil'] ?? '';
$idUsuarioVista = isset($_SESSION['tcgx_usuario_id']) ? (string) $_SESSION['tcgx_usuario_id'] : '';
$idTiendaSesion = isset($_SESSION['tcgx_id_tienda']) ? (int) $_SESSION['tcgx_id_tienda'] : 0;

if ($perfilActual !== 'TIENDA' || $idUsuarioVista === '' || $idTiendaSesion <= 0) {
    header('Location: ../login.php', true, 302);
    exit;
}

$tcgxStoreNombreTienda = '';
$tcgxStoreEshub = 0;
try {
    $pdoAuth = Bd::getPdo();
    $stmtAuth = $pdoAuth->prepare(
        'SELECT u.nombre AS nombreusuario, u.estado AS estadousuario, t.nombre AS nombretienda, t.estado AS estadotienda, t.eshub '
        . 'FROM usuarios u INNER JOIN tiendas t ON t.id = u.idtienda '
        . 'WHERE u.id = ? AND u.idtienda = ? AND u.perfil = \'TIENDA\' LIMIT 1'
    );
    $stmtAuth->execute([$idUsuarioVista, $idTiendaSesion]);
    $filaAuth = $stmtAuth->fetch();
    if ($filaAuth === false
        || (string) $filaAuth['estadousuario'] !== 'ACTIVO'
        || (string) $filaAuth['estadotienda'] !== 'ACTIVO') {
        header('Location: ../login.php', true, 302);
        exit;
    }
    $tcgxStoreEshub = (int) ($filaAuth['eshub'] ?? 0);
    if ($tcgxStoreEshub === 1) {
        header('Location: ../cd/', true, 302);
        exit;
    }
    $tcgxStoreNombreUsuario = trim((string) $filaAuth['nombreusuario']);
    $tcgxStoreNombreTienda = trim((string) $filaAuth['nombretienda']);
} catch (Throwable) {
    header('Location: ../login.php', true, 302);
    exit;
}

if ($tcgxStoreNombreUsuario === '') {
    $tcgxStoreNombreUsuario = $idUsuarioVista;
}

$_SESSION['tcgx_store_csrf'] = bin2hex(random_bytes(32));
$tcgxStoreCsrf = $_SESSION['tcgx_store_csrf'];
// FIN BLOQUE: AUTORIZACION PERFIL TIENDA (NO HUB)

// INICIO BLOQUE: URL RECURSOS ESTATICOS (images/)
$tcgxStoreUrlLogoOscuro = tcgexchange_url_recurso_proyecto('images/logo-on-dark.svg');
$tcgxStoreUrlFavicon = tcgexchange_url_recurso_proyecto('images/logo512.png');
// FIN BLOQUE: URL RECURSOS ESTATICOS (images/)
