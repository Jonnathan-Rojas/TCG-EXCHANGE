<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO ENVIOS
require __DIR__ . '/includes/carga_sesion_store.php';
require_once __DIR__ . '/includes/store_envios_logica.php';

// Token CSRF propio del modulo envios (misma clave que admin; independiente del token rotado por carga_sesion_store).
if (empty($_SESSION['tcgx_envios_csrf'])) {
    $_SESSION['tcgx_envios_csrf'] = bin2hex(random_bytes(32));
}
$tcgxEnviosCsrf = $_SESSION['tcgx_envios_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO ENVIOS


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
$tcgxEnviosFlash = null;
if (isset($_SESSION['tcgx_envios_flash']) && is_array($_SESSION['tcgx_envios_flash'])) {
    $tcgxEnviosFlash = $_SESSION['tcgx_envios_flash'];
    unset($_SESSION['tcgx_envios_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: CONSULTA DE LISTADO ACOTADO A LA TIENDA DE SESION
$tcgxListaEnvios = tcgx_store_envios_listar(Bd::getPdo(), $idTiendaSesion);
// FIN BLOQUE: CONSULTA DE LISTADO ACOTADO A LA TIENDA DE SESION

$tcgxPageTitle = 'Envíos | TCG EXCHANGE';
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

   <!-- INICIO BLOQUE: ESTILOS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->
   <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css" crossorigin="anonymous">
   <!-- FIN BLOQUE: ESTILOS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->

   <link rel="stylesheet" href="vendor/css/store-panel.css?v=20260612c">
</head>

<body class="tcgx-store-app" id="tcgx-store-app-root">

   <div class="tcgx-store-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-store-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-store-content" id="tcgx-store-main">

            <!-- INICIO BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->
            <div class="tcgx-store-tabla-toolbar">
               <a class="btn btn-success" href="envio-registrar.php">
                  <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Registrar envío
               </a>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->

            <!-- INICIO BLOQUE: TABLA DE ENVIOS (DATATABLES) -->
            <div class="tcgx-store-table-card">
               <table class="table table-hover align-middle tcgx-store-dt-table" id="tcgx-tabla-envios">
                  <thead>
                     <tr>
                        <th>Rastreo</th>
                        <th>Remitente</th>
                        <th>Destinatario</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Forma</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaEnvios as $envio): ?>
                        <?php
                        $vId = $esc($envio['id']);
                        $estadoTxt = (string) $envio['estado'];
                        $estadoClase = 'text-bg-primary';
                        if ($estadoTxt === 'ENTREGADO') {
                            $estadoClase = 'text-bg-success';
                        } elseif ($estadoTxt === 'CANCELADO') {
                            $estadoClase = 'text-bg-dark';
                        } elseif (str_starts_with($estadoTxt, 'DEVOLUCION')) {
                            $estadoClase = 'text-bg-warning';
                        }
                        ?>
                        <tr>
                           <td><?php echo $vId; ?></td>
                           <td><?php echo $esc($envio['nombreremitente']); ?> <span class="text-secondary">(<?php echo $esc($envio['idremitente']); ?>)</span></td>
                           <td><?php echo $esc($envio['nombredestinatario']); ?> <span class="text-secondary">(<?php echo $esc($envio['iddestinatario']); ?>)</span></td>
                           <td><?php echo $esc($envio['nombretiendaorigen']); ?></td>
                           <td><?php echo $esc($envio['nombretiendadestino']); ?></td>
                           <td><?php echo $esc($envio['formaenvio']); ?></td>
                           <td><?php echo $esc(number_format((float) $envio['montoapagar'], 2, '.', ',')); ?></td>
                           <td><span class="badge <?php echo $estadoClase; ?>"><?php echo $esc($estadoTxt); ?></span></td>
                           <td class="text-end">
                              <div class="tcgx-store-actions justify-content-end">
                                 <!-- Ver (azul/primary): detalle por POST (sin GET con datos). -->
                                 <button type="button" class="btn btn-primary" data-tcgx-action="ver" data-tcgx-id="<?php echo $vId; ?>" title="Ver" aria-label="Ver envío <?php echo $vId; ?>">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                 </button>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE ENVIOS (DATATABLES) -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxStoreSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxStoreSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIO OCULTO PARA VER DETALLE (POST + CSRF, SIN GET CON DATOS) -->
   <form id="tcgx-form-ver" method="post" action="envio-ver.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
      <input type="hidden" name="id_envio" id="tcgx-form-ver-id" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIO OCULTO PARA VER DETALLE -->

   <!-- INICIO BLOQUE: CARGA UTIL DEL FLASH PARA SWEETALERT2 -->
   <?php if ($tcgxEnviosFlash !== null): ?>
      <script id="tcgx-envios-flash" type="application/json"><?php
         echo json_encode($tcgxEnviosFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: CARGA UTIL DEL FLASH PARA SWEETALERT2 -->

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>

   <!-- INICIO BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <!-- FIN BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->

   <script src="vendor/js/store-panel.js?v=20260612a"></script>
   <script src="vendor/js/envios.js?v=20260612a"></script>
</body>
</html>
