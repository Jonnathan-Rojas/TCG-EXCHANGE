<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/evaluaciones_logica.php';

if (empty($_SESSION['tcgx_evaluaciones_csrf'])) {
    $_SESSION['tcgx_evaluaciones_csrf'] = bin2hex(random_bytes(32));
}
$tcgxEvaluacionesCsrf = $_SESSION['tcgx_evaluaciones_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF


// INICIO BLOQUE: PROCESAMIENTO POST DE ALTA
// Valores por defecto del formulario; en error se conservan los valores enviados.
$tcgxFormValores = [];
$tcgxFormErrores = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxEvaluacionesCsrf === '' || $tokenPost === '' || !hash_equals($tcgxEvaluacionesCsrf, $tokenPost)) {
        $tcgxFormErrores[] = 'SOLICITUD NO VALIDA.';
        $tcgxFormValores = $_POST;
    } else {
        $validacion = tcgx_evaluaciones_validar(Bd::getPdo(), $_POST);
        $tcgxFormErrores = $validacion['errores'];
        $tcgxFormValores = $validacion['datos'];
        if (empty($tcgxFormErrores)) {
            $resultado = tcgx_evaluaciones_crear(Bd::getPdo(), $validacion['datos'], $idUsuarioVista);
            if ($resultado['ok']) {
                $_SESSION['tcgx_evaluaciones_flash'] = ['tipo' => 'ok', 'texto' => 'EVALUACION CREADA CORRECTAMENTE.'];
                header('Location: evaluaciones.php', true, 303);
                exit;
            }
            $tcgxFormErrores[] = $resultado['error'];
        }
    }
}
// FIN BLOQUE: PROCESAMIENTO POST DE ALTA


// INICIO BLOQUE: CATALOGO PARA EL FORMULARIO (TIENDAS ACTIVAS)
// El cliente evaluado se busca dinamicamente (Select2 AJAX); solo se cargan las tiendas para su select.
$tcgxTiendasOpciones = tcgx_evaluaciones_listar_tiendas(Bd::getPdo());
// Tras un reintento fallido, resuelve el nombre del cliente para precargar la opcion seleccionada de Select2.
if (!empty($tcgxFormValores['idusuario'])) {
    $tcgxFormValores['nombreusuario'] = tcgx_evaluaciones_nombre_cliente(Bd::getPdo(), (string) $tcgxFormValores['idusuario']);
}
// FIN BLOQUE: CATALOGO PARA EL FORMULARIO (TIENDAS ACTIVAS)

$tcgxPageTitle = 'Crear evaluación | TCG EXCHANGE';
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

   <!-- INICIO BLOQUE: ESTILOS SELECT2 (BUSQUEDA DINAMICA DEL CLIENTE) - COPIAS LOCALES -->
   <link rel="stylesheet" href="vendor/css/lib/select2.min.css?v=20260612b">
   <link rel="stylesheet" href="vendor/css/lib/select2-bootstrap-5-theme.min.css?v=20260612b">
   <!-- FIN BLOQUE: ESTILOS SELECT2 (BUSQUEDA DINAMICA DEL CLIENTE) - COPIAS LOCALES -->

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
               <form method="post" action="evaluacion-crear.php" id="tcgx-evaluacion-form" novalidate>
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxEvaluacionesCsrf, ENT_QUOTES, 'UTF-8'); ?>">

                  <?php require __DIR__ . '/includes/evaluacion_formulario.php'; ?>

                  <div class="tcgx-admin-form-actions">
                     <a class="btn btn-outline-secondary" href="evaluaciones.php">CANCELAR</a>
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

   <!-- INICIO BLOQUE: DATOS PARA JS (ERRORES DE SERVIDOR) -->
   <?php if (!empty($tcgxFormErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxFormErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: DATOS PARA JS (ERRORES DE SERVIDOR) -->

   <script src="vendor/js/lib/jquery-3.7.1.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="vendor/js/lib/select2.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/evaluacion-form.js?v=20260612c"></script>
</body>
</html>
