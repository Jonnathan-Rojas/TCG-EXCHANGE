<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS (CD)
require __DIR__ . '/includes/carga_sesion_cd.php';
require __DIR__ . '/includes/cd_consolidados_logica.php';

if (empty($_SESSION['tcgx_consolidados_csrf'])) {
    $_SESSION['tcgx_consolidados_csrf'] = bin2hex(random_bytes(32));
}
$tcgxConsCsrf = $_SESSION['tcgx_consolidados_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS (CD)


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
$tcgxConsFlash = null;
if (isset($_SESSION['tcgx_consolidados_flash']) && is_array($_SESSION['tcgx_consolidados_flash'])) {
    $tcgxConsFlash = $_SESSION['tcgx_consolidados_flash'];
    unset($_SESSION['tcgx_consolidados_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: CONSULTA DE LISTADO ACOTADO A TIENDA DE SESION
$tcgxListaConsolidados = tcgx_cd_consolidados_listar($pdo, $idTiendaSesion);
// FIN BLOQUE: CONSULTA DE LISTADO ACOTADO A TIENDA DE SESION

$tcgxPageTitle = 'Consolidados | TCG EXCHANGE';
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

            <!-- INICIO BLOQUE: BARRA DE ACCION DE ARMADO (TRAMO 2 DESDE EL HUB) -->
            <div class="tcgx-cd-tabla-toolbar">
               <a class="btn btn-success" href="consolidado-armar.php">
                  <i class="fa-solid fa-layer-group me-2" aria-hidden="true"></i>Armar consolidado
               </a>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION DE ARMADO -->

            <!-- INICIO BLOQUE: TABLA DE CONSOLIDADOS (DATATABLES) -->
            <div class="tcgx-cd-table-card">
               <table class="table table-hover align-middle tcgx-cd-dt-table" id="tcgx-tabla-consolidados">
                  <thead>
                     <tr>
                        <th>Rastreo</th>
                        <th>Tramo</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Envíos</th>
                        <th>Estado</th>
                        <th>Salida</th>
                        <th>Recepción</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaConsolidados as $cons): ?>
                        <?php
                        $vId = $esc($cons['id']);
                        $estadoTxt = (string) $cons['estado'];
                        $estadoClase = 'text-bg-secondary';
                        if ($estadoTxt === 'ARMADO') {
                            $estadoClase = 'text-bg-info';
                        } elseif ($estadoTxt === 'EN TRANSITO') {
                            $estadoClase = 'text-bg-primary';
                        } elseif ($estadoTxt === 'RECIBIDO') {
                            $estadoClase = 'text-bg-success';
                        } elseif ($estadoTxt === 'CANCELADO') {
                            $estadoClase = 'text-bg-dark';
                        }
                        ?>
                        <tr>
                           <td><?php echo $vId; ?></td>
                           <td><?php echo $esc($cons['tipotramo']); ?></td>
                           <td><?php echo $esc($cons['nombreorigen']); ?></td>
                           <td><?php echo $esc($cons['nombredestino']); ?></td>
                           <td><?php echo (int) $cons['totalenvios']; ?></td>
                           <td><span class="badge <?php echo $estadoClase; ?>"><?php echo $esc($estadoTxt); ?></span></td>
                           <td><?php echo $cons['fechasalida'] !== null ? $esc($cons['fechasalida']) : '—'; ?></td>
                           <td><?php echo $cons['fecharecepcion'] !== null ? $esc($cons['fecharecepcion']) : '—'; ?></td>
                           <td class="text-end">
                              <div class="tcgx-cd-actions justify-content-end">
                                 <button type="button" class="btn btn-primary" data-tcgx-action="ver" data-tcgx-id="<?php echo $vId; ?>" title="Ver" aria-label="Ver consolidado <?php echo $vId; ?>">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                 </button>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE CONSOLIDADOS (DATATABLES) -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxCdSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxCdSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIO OCULTO PARA VER DETALLE (POST + CSRF) -->
   <form id="tcgx-form-ver" method="post" action="consolidado-ver.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
      <input type="hidden" name="id_consolidado" id="tcgx-form-ver-id" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIO OCULTO PARA VER DETALLE -->

   <!-- INICIO BLOQUE: CARGA UTIL DEL FLASH PARA SWEETALERT2 -->
   <?php if ($tcgxConsFlash !== null): ?>
      <script id="tcgx-consolidados-flash" type="application/json"><?php
         echo json_encode($tcgxConsFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: CARGA UTIL DEL FLASH PARA SWEETALERT2 -->

   <script src="vendor/js/lib/jquery-3.7.1.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>

   <!-- INICIO BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <!-- FIN BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->

   <script src="vendor/js/cd-panel.js?v=20260612a"></script>
   <script src="vendor/js/consolidados.js?v=20260612a"></script>
</body>
</html>
