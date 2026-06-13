<?php
declare(strict_types=1);

/**
 * Arranque comun de paginas del modulo admin: BD, sesion, logout POST, autorizacion ADMINISTRADOR,
 * CSRF, nombre en cabecera, URLs de imagenes y nombre del script actual (menu activo y action del formulario logout).
 * Incluir una sola vez al inicio de cada PHP bajo admin/ antes de emitir HTML.
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

$tcgxAdminScriptNombre = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));

// INICIO BLOQUE: CIERRE SESION ADMINISTRADOR POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_admin_logout'])) {
    $tokenSesion = $_SESSION['tcgx_admin_csrf'] ?? '';
    $tokenPost = $_POST['tcgx_csrf_token'] ?? '';
    $idUsuarioSesion = isset($_SESSION['tcgx_usuario_id']) ? (string) $_SESSION['tcgx_usuario_id'] : '';
    $perfilSesion = isset($_SESSION['tcgx_perfil']) ? (string) $_SESSION['tcgx_perfil'] : '';

    if (
        $tokenSesion !== ''
        && $tokenPost !== ''
        && hash_equals($tokenSesion, (string) $tokenPost)
        && $idUsuarioSesion !== ''
        && $perfilSesion === 'ADMINISTRADOR'
    ) {
        unset($_SESSION['tcgx_admin_csrf']);
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
    header('Location: ' . $tcgxAdminScriptNombre, true, 303);
    exit;
}
// FIN BLOQUE: CIERRE SESION ADMINISTRADOR POST

// INICIO BLOQUE: AUTORIZACION PERFIL ADMINISTRADOR
$perfilActual = $_SESSION['tcgx_perfil'] ?? '';
if ($perfilActual !== 'ADMINISTRADOR') {
    header('Location: ../login.php', true, 302);
    exit;
}

$_SESSION['tcgx_admin_csrf'] = bin2hex(random_bytes(32));
$tcgxAdminCsrf = $_SESSION['tcgx_admin_csrf'];
$idUsuarioVista = isset($_SESSION['tcgx_usuario_id']) ? (string) $_SESSION['tcgx_usuario_id'] : '';
// FIN BLOQUE: AUTORIZACION PERFIL ADMINISTRADOR

// INICIO BLOQUE: NOMBRE USUARIO CABECERA
$tcgxAdminNombreUsuario = '';
if ($idUsuarioVista !== '') {
    try {
        $pdoNombre = Bd::getPdo();
        $stmtNombre = $pdoNombre->prepare('SELECT nombre FROM usuarios WHERE id = ? LIMIT 1');
        $stmtNombre->execute([$idUsuarioVista]);
        $filaNombre = $stmtNombre->fetch();
        if ($filaNombre !== false && isset($filaNombre['nombre'])) {
            $tcgxAdminNombreUsuario = trim((string) $filaNombre['nombre']);
        }
    } catch (Throwable) {
        $tcgxAdminNombreUsuario = '';
    }
}
if ($tcgxAdminNombreUsuario === '') {
    $tcgxAdminNombreUsuario = $idUsuarioVista;
}
// FIN BLOQUE: NOMBRE USUARIO CABECERA

// INICIO BLOQUE: URL RECURSOS ESTATICOS (images/)
$tcgxAdminUrlLogoOscuro = tcgexchange_url_recurso_proyecto('images/logo-on-dark.svg');
$tcgxAdminUrlFavicon = tcgexchange_url_recurso_proyecto('images/logo512.png');
// FIN BLOQUE: URL RECURSOS ESTATICOS (images/)
