<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DE MI PERFIL (STORE)
require __DIR__ . '/includes/carga_sesion_store.php';
require __DIR__ . '/../admin/includes/usuarios_logica.php';

if (empty($_SESSION['tcgx_usuarios_csrf'])) {
    $_SESSION['tcgx_usuarios_csrf'] = bin2hex(random_bytes(32));
}
$tcgxUsuariosCsrf = $_SESSION['tcgx_usuarios_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DE MI PERFIL (STORE)


// INICIO BLOQUE: LECTURA DE FLASH DE EXITO (PRG)
$tcgxPerfilExito = null;
if (isset($_SESSION['tcgx_perfil_flash'])) {
    $tcgxPerfilExito = (string) $_SESSION['tcgx_perfil_flash'];
    unset($_SESSION['tcgx_perfil_flash']);
}
// FIN BLOQUE: LECTURA DE FLASH DE EXITO (PRG)


// INICIO BLOQUE: CARGA DEL USUARIO EN SESION
$usuarioActual = tcgx_usuarios_obtener($pdo, $idUsuarioVista);
if ($usuarioActual === null) {
    header('Location: index.php', true, 302);
    exit;
}
$tcgxFormErrores = [];
$tcgxFormValores = $usuarioActual;
// FIN BLOQUE: CARGA DEL USUARIO EN SESION


// INICIO BLOQUE: PROCESAMIENTO POST DE GUARDADO DE PERFIL PROPIO
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_perfil_guardar'])) {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');

    // --- Bloque seguridad: validacion CSRF antes de cualquier efecto ---
    if ($tcgxUsuariosCsrf === '' || $tokenPost === '' || !hash_equals($tcgxUsuariosCsrf, $tokenPost)) {
        $tcgxFormErrores[] = 'SOLICITUD NO VALIDA.';
    } else {
        // --- Fin bloque seguridad ---

        $validacion = tcgx_usuarios_validar_perfil_propio($pdo, $_POST);
        $tcgxFormErrores = $validacion['errores'];
        $tcgxFormValores = array_merge($usuarioActual, $validacion['datos']);

        // --- Bloque contrasena opcional: solo se valida y cambia si el usuario escribio una nueva ---
        $claveNueva = (string) ($_POST['clave'] ?? '');
        $claveConfirma = (string) ($_POST['clave_confirma'] ?? '');
        $nuevaClaveAplicar = null;
        if ($claveNueva !== '' || $claveConfirma !== '') {
            $erroresClave = tcgx_usuarios_validar_clave($claveNueva, $claveConfirma);
            if (!empty($erroresClave)) {
                $tcgxFormErrores = array_merge($tcgxFormErrores, $erroresClave);
            } else {
                $nuevaClaveAplicar = $claveNueva;
            }
        }
        // --- Fin bloque contrasena opcional ---

        if (empty($tcgxFormErrores)) {
            $resultado = tcgx_usuarios_actualizar_perfil_propio($pdo, $idUsuarioVista, $validacion['datos'], $nuevaClaveAplicar, $usuarioActual);
            if ($resultado['ok']) {
                if (!empty($resultado['clave_cambiada'])) {
                    // INICIO BLOQUE: CIERRE DE SESION TRAS CAMBIO DE CONTRASENA
                    $_SESSION = [];
                    if (ini_get('session.use_cookies')) {
                        $p = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
                    }
                    session_destroy();
                    session_start();
                    session_regenerate_id(true);
                    $_SESSION['tcgx_login_aviso'] = 'CONTRASEÑA ACTUALIZADA. INICIE SESIÓN CON LA NUEVA CONTRASEÑA.';
                    header('Location: ../login.php', true, 303);
                    exit;
                    // FIN BLOQUE: CIERRE DE SESION TRAS CAMBIO DE CONTRASENA
                }
                $_SESSION['tcgx_perfil_flash'] = 'PERFIL ACTUALIZADO CORRECTAMENTE.';
                header('Location: mi-perfil.php', true, 303);
                exit;
            }
            $tcgxFormErrores[] = $resultado['error'];
        }
    }
}
// FIN BLOQUE: PROCESAMIENTO POST DE GUARDADO DE PERFIL PROPIO


// INICIO BLOQUE: CATALOGO GEOGRAFICO Y VALORES PARA EL FORMULARIO
$tcgxCatalogoGeo = tcgx_usuarios_catalogo_geografico();

$vId = htmlspecialchars((string) ($tcgxFormValores['id'] ?? ''), ENT_QUOTES, 'UTF-8');
$vNombre = htmlspecialchars((string) ($tcgxFormValores['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$vPerfil = htmlspecialchars((string) ($tcgxFormValores['perfil'] ?? ''), ENT_QUOTES, 'UTF-8');
$vCorreo = htmlspecialchars((string) ($tcgxFormValores['correo'] ?? ''), ENT_QUOTES, 'UTF-8');
$vTelefono = htmlspecialchars((string) ($tcgxFormValores['telefono'] ?? ''), ENT_QUOTES, 'UTF-8');
$vProvincia = htmlspecialchars((string) ($tcgxFormValores['provincia'] ?? ''), ENT_QUOTES, 'UTF-8');
$vCanton = htmlspecialchars((string) ($tcgxFormValores['canton'] ?? ''), ENT_QUOTES, 'UTF-8');
$vDistrito = htmlspecialchars((string) ($tcgxFormValores['distrito'] ?? ''), ENT_QUOTES, 'UTF-8');
$vDireccion = htmlspecialchars((string) ($tcgxFormValores['direccion'] ?? ''), ENT_QUOTES, 'UTF-8');

$tcgxPageTitle = 'Mi perfil | TCG EXCHANGE';
// FIN BLOQUE: CATALOGO GEOGRAFICO Y VALORES PARA EL FORMULARIO
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($tcgxPageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
   <meta name="robots" content="noindex, nofollow">

   <link rel="icon" href="<?php echo htmlspecialchars($tcgxStoreUrlFavicon, ENT_QUOTES, 'UTF-8'); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="vendor/css/store-panel.css?v=20260612c">
</head>

<body class="tcgx-store-app" id="tcgx-store-app-root">

   <div class="tcgx-store-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-store-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-store-content" id="tcgx-store-main">

            <!-- INICIO BLOQUE: TARJETA FORMULARIO MI PERFIL -->
            <div class="tcgx-store-form-card">
               <form method="post" action="mi-perfil.php" id="tcgx-perfil-form" novalidate>
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxUsuariosCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="tcgx_perfil_guardar" value="1">

                  <div class="row g-3">

                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-id">Cédula</label>
                        <input type="text" class="form-control" id="perfil-id" value="<?php echo $vId; ?>" readonly disabled>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-nombre">Nombre</label>
                        <input type="text" class="form-control" id="perfil-nombre" value="<?php echo $vNombre; ?>" readonly disabled>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-perfil">Perfil</label>
                        <input type="text" class="form-control" id="perfil-perfil" value="<?php echo $vPerfil; ?>" readonly disabled>
                     </div>

                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-correo">Correo electrónico</label>
                        <input type="email" class="form-control text-lowercase" id="perfil-correo" name="correo" maxlength="150" value="<?php echo $vCorreo; ?>" required>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-telefono">Teléfono</label>
                        <input type="text" class="form-control" id="perfil-telefono" name="telefono" maxlength="20" value="<?php echo $vTelefono; ?>" required>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-provincia">Provincia</label>
                        <select class="form-select" id="perfil-provincia" name="provincia" data-tcgx-selected="<?php echo $vProvincia; ?>">
                           <option value="">SELECCIONE…</option>
                        </select>
                     </div>

                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-canton">Cantón</label>
                        <select class="form-select" id="perfil-canton" name="canton" data-tcgx-selected="<?php echo $vCanton; ?>">
                           <option value="">SELECCIONE…</option>
                        </select>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-distrito">Distrito</label>
                        <select class="form-select" id="perfil-distrito" name="distrito" data-tcgx-selected="<?php echo $vDistrito; ?>">
                           <option value="">SELECCIONE…</option>
                        </select>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-direccion">Dirección</label>
                        <input type="text" class="form-control text-uppercase" id="perfil-direccion" name="direccion" maxlength="255" value="<?php echo $vDireccion; ?>">
                     </div>

                  </div>

                  <!-- INICIO BLOQUE: SECCION CAMBIO DE CONTRASENA -->
                  <h3 class="tcgx-store-form-subtitle">Cambiar contraseña</h3>
                  <div class="row g-3">
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-clave">Nueva contraseña</label>
                        <input type="password" class="form-control" id="perfil-clave" name="clave" maxlength="255" autocomplete="new-password">
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="perfil-clave-confirma">Confirmar contraseña</label>
                        <input type="password" class="form-control" id="perfil-clave-confirma" name="clave_confirma" maxlength="255" autocomplete="new-password">
                     </div>
                  </div>
                  <!-- FIN BLOQUE: SECCION CAMBIO DE CONTRASENA -->

                  <div class="tcgx-store-form-actions">
                     <a class="btn btn-outline-secondary" href="index.php">CANCELAR</a>
                     <button type="submit" class="btn btn-warning">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>GUARDAR CAMBIOS
                     </button>
                  </div>
               </form>
            </div>
            <!-- FIN BLOQUE: TARJETA FORMULARIO MI PERFIL -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxStoreSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxStoreSidebarModo);
   ?>

   <!-- INICIO BLOQUE: DATOS PARA JS (CATALOGO GEOGRAFICO, ERRORES Y EXITO DE SERVIDOR) -->
   <script id="tcgx-catalogo-geo" type="application/json"><?php
      echo json_encode($tcgxCatalogoGeo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
   ?></script>
   <?php if (!empty($tcgxFormErrores)): ?>
      <script id="tcgx-perfil-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxFormErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php elseif ($tcgxPerfilExito !== null): ?>
      <script id="tcgx-perfil-flash" type="application/json"><?php
         echo json_encode(['tipo' => 'ok', 'texto' => $tcgxPerfilExito], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: DATOS PARA JS -->

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/store-panel.js?v=20260612a"></script>
   <script src="vendor/js/mi-perfil.js?v=20260611b"></script>
</body>
</html>
