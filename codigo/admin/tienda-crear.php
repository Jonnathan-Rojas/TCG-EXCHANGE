<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/tiendas_logica.php';

if (empty($_SESSION['tcgx_tiendas_csrf'])) {
    $_SESSION['tcgx_tiendas_csrf'] = bin2hex(random_bytes(32));
}
$tcgxTiendasCsrf = $_SESSION['tcgx_tiendas_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF


// INICIO BLOQUE: PROCESAMIENTO POST DE ALTA
// Valor por defecto del formulario; en error se conservan los valores enviados.
$tcgxFormValores = ['eshub' => '0'];
$tcgxFormErrores = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxTiendasCsrf === '' || $tokenPost === '' || !hash_equals($tcgxTiendasCsrf, $tokenPost)) {
        $tcgxFormErrores[] = 'SOLICITUD NO VALIDA.';
        $tcgxFormValores = $_POST;
    } else {
        $validacion = tcgx_tiendas_validar(Bd::getPdo(), $_POST);
        $tcgxFormErrores = $validacion['errores'];
        $tcgxFormValores = $validacion['datos'];
        if (empty($tcgxFormErrores)) {
            $resultado = tcgx_tiendas_crear(Bd::getPdo(), $validacion['datos'], $idUsuarioVista);
            if ($resultado['ok']) {
                $_SESSION['tcgx_tiendas_flash'] = ['tipo' => 'ok', 'texto' => 'TIENDA CREADA CORRECTAMENTE.'];
                header('Location: tiendas.php', true, 303);
                exit;
            }
            $tcgxFormErrores[] = $resultado['error'];
        }
    }
}
// FIN BLOQUE: PROCESAMIENTO POST DE ALTA


// INICIO BLOQUE: CATALOGO GEOGRAFICO PARA EL FORMULARIO
$tcgxCatalogoGeo = tcgx_tiendas_catalogo_geografico();
// FIN BLOQUE: CATALOGO GEOGRAFICO PARA EL FORMULARIO

$tcgxPageTitle = 'Crear tienda | TCG EXCHANGE';
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

   <link rel="icon" href="<?php echo htmlspecialchars($tcgxAdminUrlFavicon, ENT_QUOTES, 'UTF-8'); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="vendor/css/admin-panel.css?v=20260612c">
</head>

<body class="tcgx-admin-app" id="tcgx-admin-app-root">

   <div class="tcgx-admin-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-admin-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-admin-content" id="tcgx-admin-main">

            <!-- INICIO BLOQUE: TARJETA FORMULARIO DE ALTA -->
            <div class="tcgx-admin-form-card">
               <form method="post" action="tienda-crear.php" id="tcgx-tienda-form" novalidate>
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxTiendasCsrf, ENT_QUOTES, 'UTF-8'); ?>">

                  <?php require __DIR__ . '/includes/tienda_formulario.php'; ?>

                  <div class="tcgx-admin-form-actions">
                     <a class="btn btn-outline-secondary" href="tiendas.php">CANCELAR</a>
                     <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>GUARDAR
                     </button>
                  </div>
               </form>
            </div>
            <!-- FIN BLOQUE: TARJETA FORMULARIO DE ALTA -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <!-- INICIO BLOQUE: DATOS PARA JS (CATALOGO GEOGRAFICO Y ERRORES DE SERVIDOR) -->
   <script id="tcgx-catalogo-geo" type="application/json"><?php
      echo json_encode($tcgxCatalogoGeo, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
   ?></script>
   <?php if (!empty($tcgxFormErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxFormErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: DATOS PARA JS (CATALOGO GEOGRAFICO Y ERRORES DE SERVIDOR) -->

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260611b"></script>
   <script src="vendor/js/tienda-form.js?v=20260611b"></script>
</body>
</html>
