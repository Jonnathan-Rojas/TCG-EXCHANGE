<?php
declare(strict_types=1);

/**
 * Plantilla HTML del listado de envios del modulo client (enviados o recepciones).
 *
 * Variables requeridas antes del include:
 * - $tcgxEnviosListadoTitulo (string): titulo visible de la seccion.
 * - $tcgxEnviosListadoOrigen (string): 'envios' | 'recepciones' (origen del detalle POST).
 * - $tcgxListaEnvios (array): filas devueltas por tcgx_client_envios_listar().
 * - $tcgxEnviosCsrf (string)
 * - $tcgxEnviosFlash (array|null)
 * - $tcgxPageTitle (string)
 * - $tcgxClientUrlFavicon, $tcgxClientScriptNombre, $idUsuarioVista (desde carga_sesion_client)
 */

$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$tcgxEnviosOrigenPost = $tcgxEnviosListadoOrigen === 'recepciones' ? 'recepciones' : 'envios';
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo $esc($tcgxPageTitle); ?></title>
   <meta name="robots" content="noindex, nofollow">

   <link rel="icon" href="<?php echo $esc($tcgxClientUrlFavicon); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="vendor/css/client-panel.css?v=20260612c">
</head>

<body class="tcgx-client-app" id="tcgx-client-app-root">

   <div class="tcgx-client-app__body">

      <?php require __DIR__ . '/sidebar.php'; ?>

      <div class="tcgx-client-main">

         <?php require __DIR__ . '/header.php'; ?>

         <main class="tcgx-client-content" id="tcgx-client-main">

            <!-- INICIO BLOQUE: TITULO DE SECCION Y TABLA DE ENVIOS FILTRADA -->
            <h2 class="tcgx-client-section-title"><?php echo $esc($tcgxEnviosListadoTitulo); ?></h2>
            <div class="tcgx-client-table-card">
               <table class="table table-hover align-middle tcgx-client-dt-table" id="tcgx-tabla-envios">
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
                           <td><?php echo $esc($envio['nombreremitente']); ?></td>
                           <td><?php echo $esc($envio['nombredestinatario']); ?></td>
                           <td><?php echo $esc($envio['nombretiendaorigen']); ?></td>
                           <td><?php echo $esc($envio['nombretiendadestino']); ?></td>
                           <td><?php echo $esc($envio['formaenvio']); ?></td>
                           <td><?php echo $esc(number_format((float) $envio['montoapagar'], 2, '.', ',')); ?></td>
                           <td><span class="badge <?php echo $estadoClase; ?>"><?php echo $esc($estadoTxt); ?></span></td>
                           <td class="text-end">
                              <div class="tcgx-client-actions justify-content-end">
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
            <!-- FIN BLOQUE: TITULO DE SECCION Y TABLA DE ENVIOS FILTRADA -->

         </main>

         <?php require __DIR__ . '/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxClientSidebarModo = 'offcanvas';
   require __DIR__ . '/sidebar.php';
   unset($tcgxClientSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIO OCULTO VER DETALLE (POST + ORIGEN DE LISTADO) -->
   <form id="tcgx-form-ver" method="post" action="envio-ver.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
      <input type="hidden" name="tcgx_envios_origen_listado" value="<?php echo $esc($tcgxEnviosOrigenPost); ?>">
      <input type="hidden" name="id_envio" id="tcgx-form-ver-id" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIO OCULTO VER DETALLE -->

   <?php if ($tcgxEnviosFlash !== null): ?>
      <script id="tcgx-envios-flash" type="application/json"><?php
         echo json_encode($tcgxEnviosFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/client-panel.js?v=20260612a"></script>
   <script src="vendor/js/envios.js?v=20260612b"></script>
</body>
</html>
