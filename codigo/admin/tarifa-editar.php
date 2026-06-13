<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/tarifas_logica.php';

if (empty($_SESSION['tcgx_tarifas_csrf'])) {
    $_SESSION['tcgx_tarifas_csrf'] = bin2hex(random_bytes(32));
}
$tcgxTarifasCsrf = $_SESSION['tcgx_tarifas_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF


// INICIO BLOQUE: RESOLUCION DE OBJETIVO Y FLUJO (APERTURA / GUARDADO)
// El identificador objetivo llega por POST desde el listado o desde el propio formulario (nunca por GET).
$tcgxFormErrores = [];
$tcgxFormValores = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    // Sin POST no hay tarifa objetivo: regresar al listado.
    header('Location: tarifas.php', true, 302);
    exit;
}

$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
if ($tcgxTarifasCsrf === '' || $tokenPost === '' || !hash_equals($tcgxTarifasCsrf, $tokenPost)) {
    $_SESSION['tcgx_tarifas_flash'] = ['tipo' => 'error', 'texto' => 'SOLICITUD NO VALIDA.'];
    header('Location: tarifas.php', true, 303);
    exit;
}

$idRaw = trim((string) ($_POST['id_tarifa'] ?? ''));
if ($idRaw === '' || !ctype_digit($idRaw)) {
    $_SESSION['tcgx_tarifas_flash'] = ['tipo' => 'error', 'texto' => 'TARIFA NO INDICADA.'];
    header('Location: tarifas.php', true, 303);
    exit;
}
$idObjetivo = (int) $idRaw;

$tarifaActual = tcgx_tarifas_obtener($pdo, $idObjetivo);
if ($tarifaActual === null) {
    $_SESSION['tcgx_tarifas_flash'] = ['tipo' => 'error', 'texto' => 'LA TARIFA NO EXISTE.'];
    header('Location: tarifas.php', true, 303);
    exit;
}

$esGuardar = isset($_POST['tcgx_tarifas_guardar']);
if ($esGuardar) {
    $validacion = tcgx_tarifas_validar($pdo, $_POST);
    $tcgxFormErrores = $validacion['errores'];
    $tcgxFormValores = $validacion['datos'];
    if (empty($tcgxFormErrores)) {
        $resultado = tcgx_tarifas_actualizar($pdo, $idObjetivo, $validacion['datos'], $idUsuarioVista, $tarifaActual);
        if ($resultado['ok']) {
            $_SESSION['tcgx_tarifas_flash'] = ['tipo' => 'ok', 'texto' => 'TARIFA ACTUALIZADA CORRECTAMENTE.'];
            header('Location: tarifas.php', true, 303);
            exit;
        }
        $tcgxFormErrores[] = $resultado['error'];
    }
} else {
    // Apertura del formulario: precarga con los datos actuales de la tarifa.
    $tcgxFormValores = $tarifaActual;
}

// El identificador mostrado siempre es el objetivo (campo de solo lectura en edicion).
$tcgxFormValores['id'] = $idObjetivo;
// FIN BLOQUE: RESOLUCION DE OBJETIVO Y FLUJO (APERTURA / GUARDADO)


// INICIO BLOQUE: CATALOGOS PARA EL FORMULARIO (TIENDAS Y RUTAS ACTIVAS)
$tcgxTiendasOpciones = tcgx_tarifas_listar_tiendas($pdo);
$tcgxRutasOpciones = tcgx_tarifas_listar_rutas($pdo);
// FIN BLOQUE: CATALOGOS PARA EL FORMULARIO (TIENDAS Y RUTAS ACTIVAS)

$tcgxPageTitle = 'Editar tarifa | TCG EXCHANGE';
$tcgxFormModo = 'editar';
$idObjetivoEsc = htmlspecialchars((string) $idObjetivo, ENT_QUOTES, 'UTF-8');
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

            <!-- INICIO BLOQUE: TARJETA FORMULARIO DE EDICION
                 El identificador es de solo lectura. -->
            <div class="tcgx-admin-form-card">
               <form method="post" action="tarifa-editar.php" id="tcgx-tarifa-form" novalidate>
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxTarifasCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="tcgx_tarifas_guardar" value="1">
                  <input type="hidden" name="id_tarifa" value="<?php echo $idObjetivoEsc; ?>">

                  <?php require __DIR__ . '/includes/tarifa_formulario.php'; ?>

                  <div class="tcgx-admin-form-actions">
                     <a class="btn btn-outline-secondary" href="tarifas.php">CANCELAR</a>
                     <button type="submit" class="btn btn-warning">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>GUARDAR CAMBIOS
                     </button>
                  </div>
               </form>
            </div>
            <!-- FIN BLOQUE: TARJETA FORMULARIO DE EDICION -->

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

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/tarifa-form.js?v=20260612a"></script>
</body>
</html>
