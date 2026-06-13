<?php
declare(strict_types=1);

/**
 * Arranque comun de paginas del modulo client: BD, sesion, logout POST, autorizacion CLIENTE,
 * CSRF, datos de cabecera y URLs de imagenes. Incluir una sola vez al inicio de cada PHP bajo client/.
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

$tcgxClientScriptNombre = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));

// INICIO BLOQUE: CIERRE SESION CLIENTE POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_client_logout'])) {
    $tokenSesion = $_SESSION['tcgx_client_csrf'] ?? '';
    $tokenPost = $_POST['tcgx_csrf_token'] ?? '';
    $idUsuarioSesion = isset($_SESSION['tcgx_usuario_id']) ? (string) $_SESSION['tcgx_usuario_id'] : '';
    $perfilSesion = isset($_SESSION['tcgx_perfil']) ? (string) $_SESSION['tcgx_perfil'] : '';

    if (
        $tokenSesion !== ''
        && $tokenPost !== ''
        && hash_equals($tokenSesion, (string) $tokenPost)
        && $idUsuarioSesion !== ''
        && $perfilSesion === 'CLIENTE'
    ) {
        unset($_SESSION['tcgx_client_csrf']);
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
    header('Location: ' . $tcgxClientScriptNombre, true, 303);
    exit;
}
// FIN BLOQUE: CIERRE SESION CLIENTE POST

// INICIO BLOQUE: AUTORIZACION PERFIL CLIENTE
$perfilActual = $_SESSION['tcgx_perfil'] ?? '';
$idUsuarioVista = isset($_SESSION['tcgx_usuario_id']) ? (string) $_SESSION['tcgx_usuario_id'] : '';

if ($perfilActual !== 'CLIENTE' || $idUsuarioVista === '') {
    header('Location: ../login.php', true, 302);
    exit;
}

$tcgxClientNombreUsuario = '';
try {
    $pdoAuth = Bd::getPdo();
    $stmtAuth = $pdoAuth->prepare(
        'SELECT u.nombre AS nombreusuario, u.estado AS estadousuario '
        . 'FROM usuarios u '
        . 'WHERE u.id = ? AND u.perfil = \'CLIENTE\' AND u.idtienda IS NULL LIMIT 1'
    );
    $stmtAuth->execute([$idUsuarioVista]);
    $filaAuth = $stmtAuth->fetch();
    if ($filaAuth === false || (string) $filaAuth['estadousuario'] !== 'ACTIVO') {
        header('Location: ../login.php', true, 302);
        exit;
    }
    $tcgxClientNombreUsuario = trim((string) $filaAuth['nombreusuario']);
} catch (Throwable) {
    header('Location: ../login.php', true, 302);
    exit;
}

if ($tcgxClientNombreUsuario === '') {
    $tcgxClientNombreUsuario = $idUsuarioVista;
}

$_SESSION['tcgx_client_csrf'] = bin2hex(random_bytes(32));
$tcgxClientCsrf = $_SESSION['tcgx_client_csrf'];
// FIN BLOQUE: AUTORIZACION PERFIL CLIENTE

// INICIO BLOQUE: URL RECURSOS ESTATICOS (images/)
$tcgxClientUrlLogoOscuro = tcgexchange_url_recurso_proyecto('images/logo-on-dark.svg');
$tcgxClientUrlFavicon = tcgexchange_url_recurso_proyecto('images/logo512.png');
// FIN BLOQUE: URL RECURSOS ESTATICOS (images/)
