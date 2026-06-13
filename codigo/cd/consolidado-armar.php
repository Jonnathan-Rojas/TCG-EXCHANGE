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

// Tramo fijo tramo 2: salida del hub hacia tienda destino seleccionada.
$tcgxTramoFijo = TCGX_CONS_TRAMO_2;
$tcgxTiendaOrigenHub = $idTiendaSesion;
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS (CD)


// INICIO BLOQUE: PROCESAMIENTO POST (BUSCAR ELEGIBLES / ARMAR TRAMO 2)
$tcgxErrores = [];
$tcgxTiendaDestinoSel = '';
$tcgxMostrarElegibles = false;
$tcgxElegibles = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxConsCsrf === '' || $tokenPost === '' || !hash_equals($tcgxConsCsrf, $tokenPost)) {
        $tcgxErrores[] = 'SOLICITUD NO VALIDA.';
    } else {
        $tcgxTiendaDestinoSel = trim((string) ($_POST['idtienda'] ?? ''));

        if (isset($_POST['tcgx_cons_armar'])) {
            $errorRevalidacion = tcgx_cd_revalidar_operacion($pdo, $idUsuarioVista, $idTiendaSesion);
            if ($errorRevalidacion !== null) {
                $tcgxErrores[] = $errorRevalidacion;
            } else {
                $idEnvios = (array) ($_POST['idenvios'] ?? []);
                $resultado = tcgx_consolidados_armar($pdo, $tcgxTramoFijo, $tcgxTiendaDestinoSel, $idEnvios, $idUsuarioVista);
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

if ($tcgxMostrarElegibles && $tcgxTiendaDestinoSel !== '' && ctype_digit($tcgxTiendaDestinoSel)) {
    $tcgxElegibles = tcgx_consolidados_envios_elegibles($pdo, $tcgxTramoFijo, (int) $tcgxTiendaDestinoSel);
} elseif (isset($_POST['tcgx_cons_buscar']) && ($tcgxTiendaDestinoSel === '' || !ctype_digit($tcgxTiendaDestinoSel))) {
    $tcgxErrores[] = 'DEBE SELECCIONAR LA TIENDA DE DESTINO.';
}
// FIN BLOQUE: PROCESAMIENTO POST (BUSCAR ELEGIBLES / ARMAR TRAMO 2)


// INICIO BLOQUE: CATALOGO DE TIENDAS DESTINO (PUNTO, NO HUB)
$tcgxTiendas = tcgx_envios_listar_tiendas_punto($pdo);
// FIN BLOQUE: CATALOGO DE TIENDAS DESTINO

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

   <link rel="icon" href="<?php echo $esc($tcgxCdUrlFavicon); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="vendor/css/cd-panel.css?v=20260612c">
</head>

<body class="tcgx-cd-app" id="tcgx-cd-app-root">

   <div class="tcgx-cd-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-cd-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-cd-content" id="tcgx-cd-main">

            <div class="tcgx-cd-tabla-toolbar">
               <form method="post" action="consolidados.php" class="d-inline">
                  <button type="submit" class="btn btn-outline-secondary">
                     <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Listado de consolidados
                  </button>
               </form>
            </div>

            <div class="tcgx-cd-form-card mb-3">
               <form method="post" action="consolidado-armar.php" id="tcgx-cons-buscar-form">
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                  <input type="hidden" name="tcgx_cons_buscar" value="1">
                  <input type="hidden" name="tipotramo" value="<?php echo $esc($tcgxTramoFijo); ?>">
                  <div class="row g-3 align-items-end">
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="cons-tramo">Tramo</label>
                        <input type="text" class="form-control" id="cons-tramo" value="<?php echo $esc($tcgxTramoFijo); ?>" readonly>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="cons-origen">Centro de origen</label>
                        <input type="text" class="form-control" id="cons-origen" value="<?php echo $esc($tcgxCdNombreTienda); ?>" readonly>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label" for="cons-destino">Tienda de destino</label>
                        <select class="form-select" id="cons-destino" name="idtienda" required>
                           <option value="">SELECCIONE…</option>
                           <?php foreach ($tcgxTiendas as $t): ?>
                              <?php $tid = (string) $t['id']; ?>
                              <option value="<?php echo $esc($tid); ?>" <?php echo $tid === $tcgxTiendaDestinoSel ? 'selected' : ''; ?>><?php echo $esc($t['nombre']); ?></option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                           <i class="fa-solid fa-magnifying-glass me-2" aria-hidden="true"></i>Buscar envíos elegibles
                        </button>
                     </div>
                  </div>
               </form>
            </div>

            <?php if ($tcgxMostrarElegibles): ?>
               <div class="tcgx-cd-table-card">
                  <?php if (empty($tcgxElegibles)): ?>
                     <p class="text-secondary mb-0">No hay envíos elegibles para consolidar hacia la tienda seleccionada.</p>
                  <?php else: ?>
                     <form method="post" action="consolidado-armar.php" id="tcgx-cons-armar-form">
                        <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                        <input type="hidden" name="tcgx_cons_armar" value="1">
                        <input type="hidden" name="tipotramo" value="<?php echo $esc($tcgxTramoFijo); ?>">
                        <input type="hidden" name="idtienda" value="<?php echo $esc($tcgxTiendaDestinoSel); ?>">

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

                        <div class="tcgx-cd-form-actions">
                           <button type="submit" class="btn btn-success">
                              <i class="fa-solid fa-layer-group me-2" aria-hidden="true"></i>ARMAR CONSOLIDADO
                           </button>
                        </div>
                     </form>
                  <?php endif; ?>
               </div>
            <?php endif; ?>

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxCdSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxCdSidebarModo);
   ?>

   <?php if (!empty($tcgxErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="vendor/js/lib/jquery-3.7.1.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/cd-panel.js?v=20260612a"></script>
   <script src="vendor/js/consolidado-armar.js?v=20260612a"></script>
</body>
</html>
