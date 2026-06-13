<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO ENVIOS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/envios_logica.php';

if (empty($_SESSION['tcgx_envios_csrf'])) {
    $_SESSION['tcgx_envios_csrf'] = bin2hex(random_bytes(32));
}
$tcgxEnviosCsrf = $_SESSION['tcgx_envios_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO ENVIOS


// INICIO BLOQUE: PROCESAMIENTO POST DE REGISTRO
$tcgxFormValores = [];
$tcgxFormErrores = [];
// Filas crudas de paquetes para repoblar el formulario tras un reintento fallido.
$tcgxPaquetesPrev = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxEnviosCsrf === '' || $tokenPost === '' || !hash_equals($tcgxEnviosCsrf, $tokenPost)) {
        $tcgxFormErrores[] = 'SOLICITUD NO VALIDA.';
        $tcgxFormValores = $_POST;
    } else {
        $validacion = tcgx_envios_validar_registro(Bd::getPdo(), $_POST);
        $tcgxFormErrores = $validacion['errores'];
        $tcgxFormValores = $validacion['datos'];

        if (empty($tcgxFormErrores)) {
            // Validacion de imagenes de evidencia (por paquete) usando los indices de filas validas.
            $tcgxIndicesPaquete = array_map(static fn ($p) => $p['indice'], $validacion['datos']['paquetes']);
            $tcgxImagenes = tcgx_envios_validar_imagenes($_FILES, $tcgxIndicesPaquete, $tcgxFormErrores);

            if (empty($tcgxFormErrores)) {
                $resultado = tcgx_envios_crear(Bd::getPdo(), $validacion['datos'], $tcgxImagenes, $idUsuarioVista);
                if ($resultado['ok']) {
                    $_SESSION['tcgx_envios_flash'] = ['tipo' => 'ok', 'texto' => 'ENVIO REGISTRADO CORRECTAMENTE (' . $resultado['id'] . ').'];
                    header('Location: envios.php', true, 303);
                    exit;
                }
                $tcgxFormErrores[] = $resultado['error'];
            }
        }
    }

    // Repoblado de las filas de paquetes con lo digitado (arreglos paralelos crudos).
    $tipos = (array) ($_POST['paquete_tipo'] ?? []);
    $descripciones = (array) ($_POST['paquete_descripcion'] ?? []);
    $cantidades = (array) ($_POST['paquete_cantidad'] ?? []);
    $valores = (array) ($_POST['paquete_valor'] ?? []);
    $totalFilas = count($tipos);
    for ($i = 0; $i < $totalFilas; $i++) {
        $tcgxPaquetesPrev[] = [
            'tipo' => (string) ($tipos[$i] ?? ''),
            'descripcion' => (string) ($descripciones[$i] ?? ''),
            'cantidad' => (string) ($cantidades[$i] ?? ''),
            'valordeclarado' => (string) ($valores[$i] ?? ''),
        ];
    }
}
// FIN BLOQUE: PROCESAMIENTO POST DE REGISTRO


// INICIO BLOQUE: CATALOGOS DEL FORMULARIO Y REPOBLADO DE SELECT2
// Origen y destino: solo tiendas no-hub. El centro de distribucion es unico y se asigna automaticamente.
$tcgxTiendas = tcgx_envios_listar_tiendas_punto(Bd::getPdo());
$tcgxHubUnico = tcgx_envios_hub_unico(Bd::getPdo());
$tcgxRutas = tcgx_envios_listar_rutas(Bd::getPdo());

// Tras un reintento, resuelve los nombres de remitente/destinatario para precargar las opciones de Select2.
if (!empty($tcgxFormValores['idremitente'])) {
    $tcgxFormValores['nombreremitente'] = tcgx_envios_nombre_cliente(Bd::getPdo(), (string) $tcgxFormValores['idremitente']);
}
if (!empty($tcgxFormValores['iddestinatario'])) {
    $tcgxFormValores['nombredestinatario'] = tcgx_envios_nombre_cliente(Bd::getPdo(), (string) $tcgxFormValores['iddestinatario']);
}
// FIN BLOQUE: CATALOGOS DEL FORMULARIO Y REPOBLADO DE SELECT2

$tcgxPageTitle = 'Registrar envío | TCG EXCHANGE';
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

   <link rel="icon" href="<?php echo $esc($tcgxAdminUrlFavicon); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">

   <!-- INICIO BLOQUE: ESTILOS SELECT2 (BUSQUEDA DINAMICA DE CLIENTES) - COPIAS LOCALES -->
   <link rel="stylesheet" href="vendor/css/lib/select2.min.css?v=20260612b">
   <link rel="stylesheet" href="vendor/css/lib/select2-bootstrap-5-theme.min.css?v=20260612b">
   <!-- FIN BLOQUE: ESTILOS SELECT2 (BUSQUEDA DINAMICA DE CLIENTES) - COPIAS LOCALES -->

   <link rel="stylesheet" href="vendor/css/admin-panel.css?v=20260612c">
</head>

<body class="tcgx-admin-app" id="tcgx-admin-app-root">

   <div class="tcgx-admin-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-admin-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-admin-content" id="tcgx-admin-main">

            <!-- INICIO BLOQUE: TARJETA FORMULARIO DE REGISTRO -->
            <div class="tcgx-admin-form-card">
               <form method="post" action="envio-registrar.php" id="tcgx-envio-form" enctype="multipart/form-data" novalidate>
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">

                  <?php require __DIR__ . '/includes/envio_formulario.php'; ?>

                  <div class="tcgx-admin-form-actions">
                     <a class="btn btn-outline-secondary" href="envios.php">CANCELAR</a>
                     <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>GUARDAR
                     </button>
                  </div>
               </form>
            </div>
            <!-- FIN BLOQUE: TARJETA FORMULARIO DE REGISTRO -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <!-- INICIO BLOQUE: DATOS PARA JS (TOKEN Y ERRORES DE SERVIDOR) -->
   <script id="tcgx-envio-config" type="application/json"><?php
      echo json_encode(['token' => $tcgxEnviosCsrf], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
   ?></script>
   <?php if (!empty($tcgxFormErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxFormErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: DATOS PARA JS -->

   <script src="vendor/js/lib/jquery-3.7.1.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="vendor/js/lib/select2.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/envio-registrar.js?v=20260612d"></script>
</body>
</html>
