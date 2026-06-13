<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO DEVOLUCIONES (CD)
require __DIR__ . '/includes/carga_sesion_cd.php';
require __DIR__ . '/includes/cd_devoluciones_logica.php';

if (empty($_SESSION['tcgx_devoluciones_csrf'])) {
    $_SESSION['tcgx_devoluciones_csrf'] = bin2hex(random_bytes(32));
}
$tcgxDevCsrf = $_SESSION['tcgx_devoluciones_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO DEVOLUCIONES (CD)


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
$tcgxDevFlash = null;
if (isset($_SESSION['tcgx_devoluciones_flash']) && is_array($_SESSION['tcgx_devoluciones_flash'])) {
    $tcgxDevFlash = $_SESSION['tcgx_devoluciones_flash'];
    unset($_SESSION['tcgx_devoluciones_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: CONSULTA DE LISTADO ACOTADO A TIENDA DE SESION
$tcgxListaDevoluciones = tcgx_cd_devoluciones_listar($pdo, $idTiendaSesion);
// FIN BLOQUE: CONSULTA DE LISTADO ACOTADO A TIENDA DE SESION

$tcgxPageTitle = 'Devoluciones | TCG EXCHANGE';
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

   <link rel="icon" href="<?php echo $esc($tcgxCdUrlFavicon); ?>" type="image/png" sizes="512x512">

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

   <link rel="stylesheet" href="vendor/css/cd-panel.css?v=20260612c">
</head>

<body class="tcgx-cd-app" id="tcgx-cd-app-root">

   <div class="tcgx-cd-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-cd-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-cd-content" id="tcgx-cd-main">

            <!-- INICIO BLOQUE: TABLA DE DEVOLUCIONES EN CURSO (DATATABLES) -->
            <div class="tcgx-cd-table-card">
               <table class="table table-hover align-middle tcgx-cd-dt-table" id="tcgx-tabla-devoluciones">
                  <thead>
                     <tr>
                        <th>Rastreo</th>
                        <th>Remitente</th>
                        <th>Destinatario</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Estado devolución</th>
                        <th>Último movimiento</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaDevoluciones as $dev): ?>
                        <?php
                        $vId = $esc($dev['id']);
                        $estadoTxt = (string) $dev['estado'];
                        ?>
                        <tr>
                           <td><?php echo $vId; ?></td>
                           <td><?php echo $esc($dev['nombreremitente']); ?></td>
                           <td><?php echo $esc($dev['nombredestinatario']); ?></td>
                           <td><?php echo $esc($dev['nombretiendaorigen']); ?></td>
                           <td><?php echo $esc($dev['nombretiendadestino']); ?></td>
                           <td><span class="badge text-bg-warning"><?php echo $esc($estadoTxt); ?></span></td>
                           <td><?php echo $dev['ultimomovimiento'] !== null ? $esc($dev['ultimomovimiento']) : '—'; ?></td>
                           <td class="text-end">
                              <div class="tcgx-cd-actions justify-content-end">
                                 <button type="button" class="btn btn-primary" data-tcgx-action="ver" data-tcgx-id="<?php echo $vId; ?>" title="Seguimiento" aria-label="Seguimiento devolución <?php echo $vId; ?>">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                 </button>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE DEVOLUCIONES EN CURSO -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxCdSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxCdSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIO OCULTO PARA SEGUIMIENTO (POST + CSRF) -->
   <form id="tcgx-form-ver" method="post" action="devolucion-ver.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxDevCsrf); ?>">
      <input type="hidden" name="id_envio" id="tcgx-form-ver-id" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIO OCULTO PARA SEGUIMIENTO -->

   <?php if ($tcgxDevFlash !== null): ?>
      <script id="tcgx-devoluciones-flash" type="application/json"><?php
         echo json_encode($tcgxDevFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>

   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>

   <script src="vendor/js/cd-panel.js?v=20260612a"></script>
   <script src="vendor/js/devoluciones.js?v=20260612e"></script>
</body>
</html>
