<?php
declare(strict_types=1);

// INICIO BLOQUE: TEXTOS FLASH LOGIN
// Un solo mensaje para cualquier dato incorrecto o anomalia coherente con inyeccion; solo BLOQUEADO tiene mensaje propio.
// Perfil o tienda incoherentes con la fila devuelven flash null: redireccion a login sin alerta, campos vacios y foco en primer control (autofocus o tcgexchange.js).
const TCGX_LOGIN_FLASH_DATOS_INCORRECTOS = 'DATOS INCORRECTOS.';
const TCGX_LOGIN_FLASH_USUARIO_BLOQUEADO = 'EL USUARIO ESTA BLOQUEADO.';
// FIN BLOQUE: TEXTOS FLASH LOGIN

// INICIO BLOQUE: RUTAS WEB PARA REDIRECCION TRAS LOGIN
// Calcula prefijo a partir de SCRIPT_NAME de login.php para que admin/, client/, store/ y cd/ funcionen en subcarpeta o en raiz.
function tcgexchange_login_prefijo_ruta_web(): string
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '/login.php';
    $dir = str_replace('\\', '/', dirname((string) $script));
    $dir = rtrim($dir, '/');
    if ($dir === '' || $dir === '.') {
        return '';
    }
    return $dir;
}

/**
 * Devuelve ruta absoluta en el sitio hacia la carpeta del modulo (siempre termina en barra).
 */
function tcgexchange_login_url_modulo(string $carpetaModulo): string
{
    $carpetaModulo = trim($carpetaModulo, '/') . '/';
    $prefijo = tcgexchange_login_prefijo_ruta_web();
    if ($prefijo === '') {
        return '/' . $carpetaModulo;
    }
    return $prefijo . '/' . $carpetaModulo;
}

/**
 * URL de la propia pagina de login para redireccion POST-GET tras error (evita reenvio de formulario).
 */
function tcgexchange_login_url_pagina(): string
{
    $prefijo = tcgexchange_login_prefijo_ruta_web();
    if ($prefijo === '') {
        return '/login.php';
    }
    return $prefijo . '/login.php';
}
// FIN BLOQUE: RUTAS WEB PARA REDIRECCION TRAS LOGIN

// INICIO BLOQUE: PROCESAMIENTO POST DE LOGIN
/**
 * Ejecuta validacion CSRF, consulta usuario por id estable (columna usuarios.id), verifica clave, estado y prepara sesion o mensaje de error.
 * Lista negra operativa: estado BLOQUEADO en tabla usuarios (sin consultar evaluaciones en este flujo).
 * Retorno: array ok true y redirect; ok false y flash string (datos incorrectos o bloqueado) o flash null (perfil o tienda incoherentes, sin mensaje).
 */
function tcgexchangeLoginEjecutarPost(PDO $pdo): array
{
    $tokenSesion = $_SESSION['tcgx_login_csrf'] ?? '';
    $tokenPost = $_POST['tcgx_csrf_token'] ?? '';
    if ($tokenSesion === '' || $tokenPost === '' || !hash_equals($tokenSesion, (string) $tokenPost)) {
        return ['ok' => false, 'flash' => TCGX_LOGIN_FLASH_DATOS_INCORRECTOS];
    }
    unset($_SESSION['tcgx_login_csrf']);

    // INICIO BLOQUE: ENTRADA POST IDENTIFICADOR Y CLAVE
    // id_usuario: clave primaria usuarios.id (VARCHAR 20); se normaliza a MAYUSCULAS segun reglas del proyecto.
    $idUsuarioLogin = isset($_POST['id_usuario']) ? trim((string) $_POST['id_usuario']) : '';
    $clave = isset($_POST['clave']) ? (string) $_POST['clave'] : '';
    if ($idUsuarioLogin === '' || $clave === '') {
        return ['ok' => false, 'flash' => TCGX_LOGIN_FLASH_DATOS_INCORRECTOS];
    }
    $idUsuarioLogin = mb_strtoupper($idUsuarioLogin, 'UTF-8');
    if (strlen($idUsuarioLogin) > 20) {
        return ['ok' => false, 'flash' => TCGX_LOGIN_FLASH_DATOS_INCORRECTOS];
    }
    // FIN BLOQUE: ENTRADA POST IDENTIFICADOR Y CLAVE

    // Consulta por id de usuario y tienda asociada; bloqueo por lista negra se infiere de usuarios.estado = BLOQUEADO.
    $sql = 'SELECT u.id, u.clavehash, u.perfil, u.idtienda, u.estado, t.eshub '
        . 'FROM usuarios u '
        . 'LEFT JOIN tiendas t ON t.id = u.idtienda '
        . 'WHERE u.id = ? LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuarioLogin]);
    $fila = $stmt->fetch();
    if ($fila === false) {
        tcgexchange_login_auditar_intento($pdo, null, false);
        return ['ok' => false, 'flash' => TCGX_LOGIN_FLASH_DATOS_INCORRECTOS];
    }
    if (!password_verify($clave, (string) $fila['clavehash'])) {
        tcgexchange_login_auditar_intento($pdo, (string) $fila['id'], false);
        return ['ok' => false, 'flash' => TCGX_LOGIN_FLASH_DATOS_INCORRECTOS];
    }
    $estadoUsuario = (string) $fila['estado'];
    if ($estadoUsuario === 'BLOQUEADO') {
        tcgexchange_login_auditar_intento($pdo, (string) $fila['id'], false);
        return ['ok' => false, 'flash' => TCGX_LOGIN_FLASH_USUARIO_BLOQUEADO];
    }
    if ($estadoUsuario !== 'ACTIVO') {
        tcgexchange_login_auditar_intento($pdo, (string) $fila['id'], false);
        return ['ok' => false, 'flash' => TCGX_LOGIN_FLASH_DATOS_INCORRECTOS];
    }
 
    $perfil = (string) $fila['perfil'];
    $idUsuario = (string) $fila['id'];
    $idTiendaRaw = $fila['idtienda'];
    $idTiendaSesion = ($idTiendaRaw !== null && $idTiendaRaw !== '') ? (int) $idTiendaRaw : null;

    if ($perfil === 'TIENDA') {
        if ($idTiendaSesion === null) {
            tcgexchange_login_auditar_intento($pdo, (string) $fila['id'], false, $perfil);
            return ['ok' => false, 'flash' => null];
        }
        if ($fila['eshub'] === null) {
            tcgexchange_login_auditar_intento($pdo, (string) $fila['id'], false, $perfil);
            return ['ok' => false, 'flash' => null];
        }
        $eshub = (int) $fila['eshub'];
        $destinoModulo = $eshub === 1 ? 'cd/' : 'store/';
    } elseif ($perfil === 'ADMINISTRADOR') {
        $destinoModulo = 'admin/';
    } elseif ($perfil === 'CLIENTE') {
        $destinoModulo = 'client/';
    } else {
        tcgexchange_login_auditar_intento($pdo, (string) $fila['id'], false, $perfil);
        return ['ok' => false, 'flash' => null];
    }

    session_regenerate_id(true);
    $_SESSION['tcgx_usuario_id'] = $idUsuario;
    $_SESSION['tcgx_perfil'] = $perfil;
    $_SESSION['tcgx_id_tienda'] = $idTiendaSesion;

    tcgexchange_login_auditar_intento($pdo, $idUsuario, true, $perfil);

    return ['ok' => true, 'redirect' => tcgexchange_login_url_modulo($destinoModulo)];
}

/**
 * Inserta fila de auditoria para ACCESO exitoso o fallido; sin datos sensibles en JSON.
 */
function tcgexchange_login_auditar_intento(PDO $pdo, ?string $idUsuario, bool $exito, ?string $perfil = null): void
{
    $accion = 'ACCESO';
    $tabla = 'usuarios';
    $idRegistro = ($idUsuario !== null && $idUsuario !== '') ? $idUsuario : null;
    $despues = json_encode(
        [
            'resultado' => $exito ? 'EXITOSO' : 'FALLIDO',
            'perfil' => $perfil,
        ],
        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    $ins = $pdo->prepare(
        'INSERT INTO auditorias (idusuario, accion, tablaafectada, idregistro, datosantes, datosdespues) '
        . 'VALUES (?, ?, ?, ?, NULL, ?)'
    );
    $ins->execute([$idUsuario, $accion, $tabla, $idRegistro, $despues]);
}
// FIN BLOQUE: PROCESAMIENTO POST DE LOGIN
