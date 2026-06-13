<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DE ALTA DE BINDER
require __DIR__ . '/includes/carga_sesion_client.php';
require_once __DIR__ . '/includes/client_binders_logica.php';

if (empty($_SESSION['tcgx_binders_csrf'])) {
    $_SESSION['tcgx_binders_csrf'] = bin2hex(random_bytes(32));
}
$tcgxBindersCsrf = $_SESSION['tcgx_binders_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DE ALTA DE BINDER


// INICIO BLOQUE: PROCESAMIENTO POST DE ALTA
$tcgxFormValores = [];
$tcgxFormErrores = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxBindersCsrf === '' || $tokenPost === '' || !hash_equals($tcgxBindersCsrf, $tokenPost)) {
        $tcgxFormErrores[] = 'SOLICITUD NO VALIDA.';
        $tcgxFormValores = $_POST;
    } else {
        $errorRevalidacion = tcgx_client_revalidar_operacion($pdo, $idUsuarioVista);
        if ($errorRevalidacion !== null) {
            $tcgxFormErrores[] = $errorRevalidacion;
            $tcgxFormValores = $_POST;
        } else {
            $validacion = tcgx_client_binders_validar($_POST);
            $tcgxFormErrores = $validacion['errores'];
            $tcgxFormValores = $validacion['datos'];
            if (empty($tcgxFormErrores)) {
                $resultado = tcgx_client_binders_crear($pdo, $validacion['datos'], $idUsuarioVista, $idUsuarioVista);
                if ($resultado['ok']) {
                    $_SESSION['tcgx_binders_flash'] = ['tipo' => 'ok', 'texto' => 'BINDER CREADO CORRECTAMENTE.'];
                    header('Location: binders.php', true, 303);
                    exit;
                }
                $tcgxFormErrores[] = $resultado['error'];
            }
        }
    }
}
// FIN BLOQUE: PROCESAMIENTO POST DE ALTA

$tcgxPageTitle = 'Crear binder | TCG EXCHANGE';
$tcgxFormModo = 'crear';
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($tcgxPageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
   <meta name="robots" content="noindex, nofollow">

   <link rel="icon" href="<?php echo htmlspecialchars($tcgxClientUrlFavicon, ENT_QUOTES, 'UTF-8'); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="vendor/css/client-panel.css?v=20260612c">
</head>

<body class="tcgx-client-app" id="tcgx-client-app-root">

   <div class="tcgx-client-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-client-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-client-content" id="tcgx-client-main">

            <div class="tcgx-client-form-card">
               <form method="post" action="binder-crear.php" id="tcgx-binder-form" novalidate>
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxBindersCsrf, ENT_QUOTES, 'UTF-8'); ?>">

                  <?php require __DIR__ . '/includes/binder_formulario.php'; ?>

                  <div class="tcgx-client-form-actions">
                     <a class="btn btn-outline-secondary" href="binders.php">CANCELAR</a>
                     <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>GUARDAR
                     </button>
                  </div>
               </form>
            </div>

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxClientSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxClientSidebarModo);
   ?>

   <?php if (!empty($tcgxFormErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxFormErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/client-panel.js?v=20260612a"></script>
   <script src="vendor/js/binder-form.js?v=20260612a"></script>
</body>
</html>
