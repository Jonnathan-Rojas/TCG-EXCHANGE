<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE SESION Y LOGIN
// session_start con HttpOnly, SameSite=Lax, strict mode; cookie Secure condicionada a HTTPS en la petición.
require __DIR__ . '/vendor/bd.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'use_strict_mode' => true,
    ]);
}

// INICIO BLOQUE: LECTURA MENSAJE LOGIN (PRG)
// Un solo uso del flash en sesion tras redireccion POST; se consume aqui para SweetAlert2 en la misma respuesta GET.
$tcgxLoginError = null;
if (isset($_SESSION['tcgx_login_error'])) {
    $tcgxLoginError = (string) $_SESSION['tcgx_login_error'];
    unset($_SESSION['tcgx_login_error']);
}
// Aviso informativo (no error): por ejemplo tras un cambio de contrasena que exige nuevo inicio de sesion.
$tcgxLoginAviso = null;
if (isset($_SESSION['tcgx_login_aviso'])) {
    $tcgxLoginAviso = (string) $_SESSION['tcgx_login_aviso'];
    unset($_SESSION['tcgx_login_aviso']);
}
// FIN BLOQUE: LECTURA MENSAJE LOGIN (PRG)

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_once __DIR__ . '/includes/login_post_handler.php';
    $resultado = tcgexchangeLoginEjecutarPost(Bd::getPdo());
    if (!empty($resultado['ok'])) {
        header('Location: ' . $resultado['redirect'], true, 302);
        exit;
    }
    // INICIO BLOQUE: MENSAJE POST LOGIN
    // Solo persiste error si hay texto; flash null deja formulario sin alerta (PRG deja campos vacios; foco en primer control por autofocus o vendor/js/tcgexchange.js).
    if (isset($resultado['flash']) && $resultado['flash'] !== null && $resultado['flash'] !== '') {
        $_SESSION['tcgx_login_error'] = (string) $resultado['flash'];
    } elseif (!array_key_exists('flash', $resultado)) {
        $_SESSION['tcgx_login_error'] = TCGX_LOGIN_FLASH_DATOS_INCORRECTOS;
    }
    // FIN BLOQUE: MENSAJE POST LOGIN
    header('Location: ' . tcgexchange_login_url_pagina(), true, 303);
    exit;
}

$_SESSION['tcgx_login_csrf'] = bin2hex(random_bytes(32));
$tcgxLoginCsrf = $_SESSION['tcgx_login_csrf'];
// FIN BLOQUE: ARRANQUE SESION Y LOGIN

// INICIO BLOQUE: METADATOS DOCUMENTO LOGIN
// Titulo y descripcion meta antes de cabecera compartida.
$tcgxPageTitle = 'Iniciar sesión | TCG EXCHANGE';
$tcgxMetaDescription = 'Acceso a la cuenta de usuario en TCG EXCHANGE';
require __DIR__ . '/includes/header.php';
// FIN BLOQUE: METADATOS DOCUMENTO LOGIN
?>

   <!-- INICIO BLOQUE: VISTA FORMULARIO LOGIN
        Capa presentación: autenticación por id de usuario estable (usuarios.id) y contraseña; POST al mismo recurso (PRG). Mensaje de error solo vía SweetAlert2 (diseño.md). -->

   <section class="contenedor-central-sec">
      <div class="container">
         <div class="row justify-content-center">
            <div class="col-12">
               <div class="contenedor-central-box">

                  <div class="page-content px-3 px-md-4 py-4">
                     <div class="row justify-content-center">
                        <div class="col-12 col-md-9 col-lg-6 col-xl-5">
                           <div class="tcgx-login-inline">

                              <form class="tcgx-login-inline__form" method="post" action="login.php">
                                 <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxLoginCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                                 <div class="mb-3">
                                    <label class="form-label tcgx-login-inline__label" for="login-id-usuario">Identificador de usuario</label>
                                    <input type="text" class="form-control tcgx-login-inline__control" id="login-id-usuario" name="id_usuario" maxlength="20" autocomplete="username" autocapitalize="characters" autofocus required>
                                 </div>
                                 <div class="mb-3">
                                    <label class="form-label tcgx-login-inline__label" for="login-clave">Contraseña</label>
                                    <input type="password" class="form-control tcgx-login-inline__control" id="login-clave" name="clave" placeholder="••••••••" autocomplete="current-password" required>
                                 </div>
                                 <div class="mb-4 text-end">
                                    <a class="tcgx-login-inline__link" href="#">¿Olvidó su contraseña?</a>
                                 </div>
                                 <button type="submit" class="btn btn-primary">
                                    Iniciar Sesión <i class="fa fa-arrow-right" aria-hidden="true"></i><span></span>
                                 </button>
                              </form>

                           </div>
                        </div>
                     </div>
                  </div>

               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- FIN BLOQUE: VISTA FORMULARIO LOGIN -->

<?php
// INICIO BLOQUE: SWEETALERT2 MENSAJE LOGIN
// Texto escapado como JSON para pasarlo a Swal.fire sin XSS; tras cerrar el modal se devuelve foco al primer campo del formulario.
if ($tcgxLoginError !== null && $tcgxLoginError !== '') {
    $tcgxLoginErrorJson = json_encode(
        $tcgxLoginError,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof Swal==="undefined"){return;}Swal.fire({icon:"error",text:'
        . $tcgxLoginErrorJson
        . ',confirmButtonText:"ACEPTAR"}).then(function(){var e=document.getElementById("login-id-usuario");if(e){e.focus();}});});</script>';
} elseif ($tcgxLoginAviso !== null && $tcgxLoginAviso !== '') {
    // Aviso de exito informativo (icono success); tras cerrar, foco en el primer campo del formulario.
    $tcgxLoginAvisoJson = json_encode(
        $tcgxLoginAviso,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof Swal==="undefined"){return;}Swal.fire({icon:"success",text:'
        . $tcgxLoginAvisoJson
        . ',confirmButtonText:"ACEPTAR"}).then(function(){var e=document.getElementById("login-id-usuario");if(e){e.focus();}});});</script>';
}
// FIN BLOQUE: SWEETALERT2 MENSAJE LOGIN

require __DIR__ . '/includes/footer.php';
