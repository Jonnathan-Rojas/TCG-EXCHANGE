<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO INCIDENCIAS (STORE)
require __DIR__ . '/includes/carga_sesion_store.php';
require __DIR__ . '/includes/store_incidencias_logica.php';

if (empty($_SESSION['tcgx_incidencias_csrf'])) {
    $_SESSION['tcgx_incidencias_csrf'] = bin2hex(random_bytes(32));
}
$tcgxIncCsrf = $_SESSION['tcgx_incidencias_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO INCIDENCIAS (STORE)


// INICIO BLOQUE: PROCESAMIENTO POST DE ALTA DE INCIDENCIA (REVALIDACION + ALCANCE STORE)
$tcgxFormValores = [];
$tcgxFormErrores = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxIncCsrf === '' || $tokenPost === '' || !hash_equals($tcgxIncCsrf, $tokenPost)) {
        $tcgxFormErrores[] = 'SOLICITUD NO VALIDA.';
        $tcgxFormValores = $_POST;
    } else {
        $errorRevalidacion = tcgx_store_revalidar_operacion($pdo, $idUsuarioVista, $idTiendaSesion);
        if ($errorRevalidacion !== null) {
            $tcgxFormErrores[] = $errorRevalidacion;
            $tcgxFormValores = $_POST;
        } else {
            $validacion = tcgx_store_incidencias_validar_registro($pdo, $_POST, $idTiendaSesion, $idUsuarioVista);
            $tcgxFormErrores = $validacion['errores'];
            $tcgxFormValores = $validacion['datos'];
            if (empty($tcgxFormErrores)) {
                $resultado = tcgx_incidencias_crear($pdo, $validacion['datos'], $idUsuarioVista);
                if ($resultado['ok']) {
                    $_SESSION['tcgx_incidencias_flash'] = ['tipo' => 'ok', 'texto' => 'INCIDENCIA REGISTRADA CORRECTAMENTE.'];
                    header('Location: incidencias.php', true, 303);
                    exit;
                }
                $tcgxFormErrores[] = $resultado['error'];
            }
        }
    }
}
// FIN BLOQUE: PROCESAMIENTO POST DE ALTA DE INCIDENCIA

$tcgxPageTitle = 'Registrar incidencia | TCG EXCHANGE';
$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo $esc($tcgxPageTitle); ?></title>
   <meta name="robots" content="noindex, nofollow">

   <link rel="icon" href="<?php echo $esc($tcgxStoreUrlFavicon); ?>" type="image/png" sizes="512x512">

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

            <section class="contenedor-central-sec">
               <div class="tcgx-store-form-card">
                  <form method="post" action="incidencia-registrar.php" id="tcgx-inc-registrar-form" novalidate>
                     <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxIncCsrf); ?>">

                     <?php require __DIR__ . '/includes/incidencia_formulario.php'; ?>

                     <div class="tcgx-store-form-actions mt-4">
                        <a href="incidencias.php" class="btn btn-outline-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                           <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Registrar incidencia
                        </button>
                     </div>
                  </form>
               </div>
            </section>

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxStoreSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxStoreSidebarModo);
   ?>

   <?php if ($tcgxFormErrores !== []): ?>
      <script id="tcgx-inc-registrar-flash" type="application/json"><?php
         echo json_encode(['tipo' => 'error', 'errores' => $tcgxFormErrores], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/store-panel.js?v=20260612a"></script>
   <script src="vendor/js/incidencia-registrar.js?v=20260612f"></script>
</body>
</html>
