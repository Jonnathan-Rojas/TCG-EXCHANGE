<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/consolidados_logica.php';

if (empty($_SESSION['tcgx_consolidados_csrf'])) {
    $_SESSION['tcgx_consolidados_csrf'] = bin2hex(random_bytes(32));
}
$tcgxConsCsrf = $_SESSION['tcgx_consolidados_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS


// INICIO BLOQUE: PROCESAMIENTO POST (BUSCAR ELEGIBLES / ARMAR)
$tcgxErrores = [];
$tcgxTramoSel = '';
$tcgxTiendaSel = '';
// Lista de envios elegibles: null mientras no se haya buscado; arreglo (posible vacio) tras buscar.
$tcgxElegibles = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxConsCsrf === '' || $tokenPost === '' || !hash_equals($tcgxConsCsrf, $tokenPost)) {
        $tcgxErrores[] = 'SOLICITUD NO VALIDA.';
    } else {
        $tcgxTramoSel = (string) ($_POST['tipotramo'] ?? '');
        $tcgxTiendaSel = trim((string) ($_POST['idtienda'] ?? ''));

        if (isset($_POST['tcgx_cons_armar'])) {
            // Armado definitivo del consolidado con los envios marcados.
            $idEnvios = (array) ($_POST['idenvios'] ?? []);
            $resultado = tcgx_consolidados_armar(Bd::getPdo(), $tcgxTramoSel, $tcgxTiendaSel, $idEnvios, $idUsuarioVista);
            if ($resultado['ok']) {
                $_SESSION['tcgx_consolidados_flash'] = ['tipo' => 'ok', 'texto' => 'CONSOLIDADO ARMADO CORRECTAMENTE (' . $resultado['id'] . ').'];
                header('Location: consolidados.php', true, 303);
                exit;
            }
            $tcgxErrores[] = $resultado['error'];
        }

        // Tras buscar o tras un armado fallido, recargar la lista de elegibles para volver a mostrarla.
        if (in_array($tcgxTramoSel, TCGX_CONS_TRAMOS, true) && $tcgxTiendaSel !== '' && ctype_digit($tcgxTiendaSel)) {
            $tcgxElegibles = tcgx_consolidados_envios_elegibles(Bd::getPdo(), $tcgxTramoSel, (int) $tcgxTiendaSel);
        } elseif (isset($_POST['tcgx_cons_buscar'])) {
            $tcgxErrores[] = 'DEBE SELECCIONAR EL TRAMO Y LA TIENDA.';
        }
    }
}
// FIN BLOQUE: PROCESAMIENTO POST (BUSCAR ELEGIBLES / ARMAR)


// INICIO BLOQUE: CATALOGO DE TIENDAS (NO HUB) PARA EL SELECT
$tcgxTiendas = tcgx_envios_listar_tiendas_punto(Bd::getPdo());
// FIN BLOQUE: CATALOGO DE TIENDAS (NO HUB) PARA EL SELECT

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

   <link rel="icon" href="<?php echo $esc($tcgxAdminUrlFavicon); ?>" type="image/png" sizes="512x512">

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

            <!-- INICIO BLOQUE: BARRA DE ACCION (VOLVER AL LISTADO POR POST) -->
            <div class="tcgx-admin-tabla-toolbar">
               <form method="post" action="consolidados.php" class="d-inline">
                  <button type="submit" class="btn btn-outline-secondary">
                     <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Listado de consolidados
                  </button>
               </form>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION -->

            <!-- INICIO BLOQUE: SELECCION DE TRAMO Y TIENDA -->
            <div class="tcgx-admin-form-card mb-3">
               <form method="post" action="consolidado-armar.php" id="tcgx-cons-buscar-form">
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                  <input type="hidden" name="tcgx_cons_buscar" value="1">
                  <div class="row g-3 align-items-end">
                     <div class="col-12 col-md-5">
                        <label class="form-label" for="cons-tramo">Tramo</label>
                        <select class="form-select" id="cons-tramo" name="tipotramo" required>
                           <option value="">SELECCIONE…</option>
                           <?php foreach (TCGX_CONS_TRAMOS as $tramo): ?>
                              <option value="<?php echo $esc($tramo); ?>" <?php echo $tramo === $tcgxTramoSel ? 'selected' : ''; ?>><?php echo $esc($tramo); ?></option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-12 col-md-5">
                        <label class="form-label" for="cons-tienda">Tienda</label>
                        <select class="form-select" id="cons-tienda" name="idtienda" required>
                           <option value="">SELECCIONE…</option>
                           <?php foreach ($tcgxTiendas as $t): ?>
                              <?php $tid = (string) $t['id']; ?>
                              <option value="<?php echo $esc($tid); ?>" <?php echo $tid === $tcgxTiendaSel ? 'selected' : ''; ?>><?php echo $esc($t['nombre']); ?></option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                           <i class="fa-solid fa-magnifying-glass me-2" aria-hidden="true"></i>Buscar
                        </button>
                     </div>
                  </div>
               </form>
            </div>
            <!-- FIN BLOQUE: SELECCION DE TRAMO Y TIENDA -->

            <?php if ($tcgxElegibles !== null): ?>
               <!-- INICIO BLOQUE: ENVIOS ELEGIBLES PARA ARMAR -->
               <div class="tcgx-admin-table-card">
                  <?php if (empty($tcgxElegibles)): ?>
                     <p class="text-secondary mb-0">No hay envíos elegibles para el tramo y la tienda seleccionados.</p>
                  <?php else: ?>
                     <form method="post" action="consolidado-armar.php" id="tcgx-cons-armar-form">
                        <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                        <input type="hidden" name="tcgx_cons_armar" value="1">
                        <input type="hidden" name="tipotramo" value="<?php echo $esc($tcgxTramoSel); ?>">
                        <input type="hidden" name="idtienda" value="<?php echo $esc($tcgxTiendaSel); ?>">

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

                        <div class="tcgx-admin-form-actions">
                           <button type="submit" class="btn btn-success">
                              <i class="fa-solid fa-layer-group me-2" aria-hidden="true"></i>ARMAR CONSOLIDADO
                           </button>
                        </div>
                     </form>
                  <?php endif; ?>
               </div>
               <!-- FIN BLOQUE: ENVIOS ELEGIBLES PARA ARMAR -->
            <?php endif; ?>

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
   <?php if (!empty($tcgxErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: DATOS PARA JS -->

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/consolidado-armar.js?v=20260612a"></script>
</body>
</html>
