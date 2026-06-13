<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS (STORE)
require __DIR__ . '/includes/carga_sesion_store.php';
require __DIR__ . '/includes/store_consolidados_logica.php';

if (empty($_SESSION['tcgx_consolidados_csrf'])) {
    $_SESSION['tcgx_consolidados_csrf'] = bin2hex(random_bytes(32));
}
$tcgxConsCsrf = $_SESSION['tcgx_consolidados_csrf'];
$pdo = Bd::getPdo();

// Tramo fijo tramo 1 y tienda fija a la sesion (store solo arma salida hacia el centro de distribucion).
$tcgxTramoFijo = TCGX_CONS_TRAMO_1;
$tcgxTiendaFija = $idTiendaSesion;
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS (STORE)


// INICIO BLOQUE: PROCESAMIENTO POST (BUSCAR ELEGIBLES / ARMAR TRAMO 1)
$tcgxErrores = [];
$tcgxMostrarElegibles = false;
$tcgxElegibles = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxConsCsrf === '' || $tokenPost === '' || !hash_equals($tcgxConsCsrf, $tokenPost)) {
        $tcgxErrores[] = 'SOLICITUD NO VALIDA.';
    } else {
        if (isset($_POST['tcgx_cons_armar'])) {
            $errorRevalidacion = tcgx_store_revalidar_operacion($pdo, $idUsuarioVista, $idTiendaSesion);
            if ($errorRevalidacion !== null) {
                $tcgxErrores[] = $errorRevalidacion;
            } else {
                $idEnvios = (array) ($_POST['idenvios'] ?? []);
                $resultado = tcgx_consolidados_armar($pdo, $tcgxTramoFijo, (string) $tcgxTiendaFija, $idEnvios, $idUsuarioVista);
                if ($resultado['ok']) {
                    $_SESSION['tcgx_consolidados_flash'] = ['tipo' => 'ok', 'texto' => 'CONSOLIDADO ARMADO CORRECTAMENTE (' . $resultado['id'] . ').'];
                    header('Location: consolidados.php', true, 303);
                    exit;
                }
                $tcgxErrores[] = $resultado['error'];
            }
            $tcgxMostrarElegibles = true;
        }

        if (isset($_POST['tcgx_cons_buscar'])) {
            $tcgxMostrarElegibles = true;
        }
    }
}

if ($tcgxMostrarElegibles || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $tcgxElegibles = tcgx_consolidados_envios_elegibles($pdo, $tcgxTramoFijo, $tcgxTiendaFija);
    $tcgxMostrarElegibles = true;
}
// FIN BLOQUE: PROCESAMIENTO POST (BUSCAR ELEGIBLES / ARMAR TRAMO 1)

$tcgxPageTitle = 'Armar consolidado | TCG EXCHANGE';
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

            <!-- INICIO BLOQUE: BARRA DE ACCION (VOLVER AL LISTADO) -->
            <div class="tcgx-store-tabla-toolbar">
               <form method="post" action="consolidados.php" class="d-inline">
                  <button type="submit" class="btn btn-outline-secondary">
                     <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Listado de consolidados
                  </button>
               </form>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION -->

            <!-- INICIO BLOQUE: CONTEXTO FIJO (TRAMO 1 + TIENDA DE SESION) -->
            <div class="tcgx-store-form-card mb-3">
               <form method="post" action="consolidado-armar.php" id="tcgx-cons-buscar-form">
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                  <input type="hidden" name="tcgx_cons_buscar" value="1">
                  <input type="hidden" name="tipotramo" value="<?php echo $esc($tcgxTramoFijo); ?>">
                  <input type="hidden" name="idtienda" value="<?php echo $esc((string) $tcgxTiendaFija); ?>">
                  <div class="row g-3 align-items-end">
                     <div class="col-12 col-md-5">
                        <label class="form-label" for="cons-tramo">Tramo</label>
                        <input type="text" class="form-control" id="cons-tramo" value="<?php echo $esc($tcgxTramoFijo); ?>" readonly>
                     </div>
                     <div class="col-12 col-md-5">
                        <label class="form-label" for="cons-tienda">Tienda de origen</label>
                        <input type="text" class="form-control" id="cons-tienda" value="<?php echo $esc($tcgxStoreNombreTienda); ?>" readonly>
                     </div>
                     <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                           <i class="fa-solid fa-magnifying-glass me-2" aria-hidden="true"></i>Buscar
                        </button>
                     </div>
                  </div>
               </form>
            </div>
            <!-- FIN BLOQUE: CONTEXTO FIJO (TRAMO 1 + TIENDA DE SESION) -->

            <?php if ($tcgxMostrarElegibles): ?>
               <!-- INICIO BLOQUE: ENVIOS ELEGIBLES PARA ARMAR (TRAMO 1) -->
               <div class="tcgx-store-table-card">
                  <?php if (empty($tcgxElegibles)): ?>
                     <p class="text-secondary mb-0">No hay envíos elegibles para consolidar desde su tienda.</p>
                  <?php else: ?>
                     <form method="post" action="consolidado-armar.php" id="tcgx-cons-armar-form">
                        <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                        <input type="hidden" name="tcgx_cons_armar" value="1">
                        <input type="hidden" name="tipotramo" value="<?php echo $esc($tcgxTramoFijo); ?>">
                        <input type="hidden" name="idtienda" value="<?php echo $esc((string) $tcgxTiendaFija); ?>">

                        <table class="table table-hover align-middle">
                           <thead>
                              <tr>
                                 <th style="width:3rem;"><input type="checkbox" id="tcgx-cons-todos" class="form-check-input" aria-label="Seleccionar todos"></th>
                                 <th>Envío</th>
                                 <th>Destinatario</th>
                                 <th>Destino final</th>
                                 <th>Forma</th>
                                 <th>Paquetes</th>
                              </tr>
                           </thead>
                           <tbody>
                              <?php foreach ($tcgxElegibles as $e): ?>
                                 <tr>
                                    <td><input type="checkbox" class="form-check-input tcgx-cons-envio" name="idenvios[]" value="<?php echo $esc($e['id']); ?>"></td>
                                    <td><?php echo $esc($e['id']); ?></td>
                                    <td><?php echo $esc($e['nombredestinatario']); ?> <span class="text-secondary">(<?php echo $esc($e['iddestinatario']); ?>)</span></td>
                                    <td><?php echo $esc($e['nombredestino']); ?></td>
                                    <td><?php echo $esc($e['formaenvio']); ?></td>
                                    <td><?php echo (int) $e['totalpaquetes']; ?></td>
                                 </tr>
                              <?php endforeach; ?>
                           </tbody>
                        </table>

                        <div class="tcgx-store-form-actions">
                           <button type="submit" class="btn btn-success">
                              <i class="fa-solid fa-layer-group me-2" aria-hidden="true"></i>ARMAR CONSOLIDADO
                           </button>
                        </div>
                     </form>
                  <?php endif; ?>
               </div>
               <!-- FIN BLOQUE: ENVIOS ELEGIBLES PARA ARMAR (TRAMO 1) -->
            <?php endif; ?>

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxStoreSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxStoreSidebarModo);
   ?>

   <!-- INICIO BLOQUE: DATOS PARA JS (ERRORES DE SERVIDOR) -->
   <?php if (!empty($tcgxErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: DATOS PARA JS -->

   <script src="vendor/js/lib/jquery-3.7.1.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/store-panel.js?v=20260612a"></script>
   <script src="vendor/js/consolidado-armar.js?v=20260612a"></script>
</body>
</html>
