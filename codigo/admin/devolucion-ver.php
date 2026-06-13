<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO DEVOLUCIONES
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/devoluciones_logica.php';
require __DIR__ . '/includes/auditorias_logica.php';

if (empty($_SESSION['tcgx_devoluciones_csrf'])) {
    $_SESSION['tcgx_devoluciones_csrf'] = bin2hex(random_bytes(32));
}
$tcgxDevCsrf = $_SESSION['tcgx_devoluciones_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO DEVOLUCIONES


// INICIO BLOQUE: ACCESO POR POST (SIN GET CON DATOS) Y CARGA DEL ENVIO EN DEVOLUCION
$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $tcgxDevCsrf === '' || $tokenPost === '' || !hash_equals($tcgxDevCsrf, $tokenPost)) {
    header('Location: devoluciones.php', true, 303);
    exit;
}

$idEnvio = mb_strtoupper(trim((string) ($_POST['id_envio'] ?? '')), 'UTF-8');
$tcgxDev = $idEnvio === '' ? null : tcgx_devoluciones_obtener(Bd::getPdo(), $idEnvio);
if ($tcgxDev === null) {
    $_SESSION['tcgx_devoluciones_flash'] = ['tipo' => 'error', 'texto' => 'EL ENVIO INDICADO NO EXISTE O NO ESTA EN DEVOLUCION.'];
    header('Location: devoluciones.php', true, 303);
    exit;
}

// INICIO BLOQUE: AUDITORIA DE LECTURA DEL DETALLE (ACCION LEER)
if (!isset($_POST['tcgx_dev_avanzar'])) {
    tcgx_auditorias_registrar_lectura(Bd::getPdo(), $idUsuarioVista, 'envios', $idEnvio);
}
// FIN BLOQUE: AUDITORIA DE LECTURA DEL DETALLE
// FIN BLOQUE: ACCESO POR POST Y CARGA DEL ENVIO EN DEVOLUCION


// INICIO BLOQUE: PROCESAMIENTO DE AVANCE DE ESTADO EN LA CADENA
$tcgxFormErrores = [];
if (isset($_POST['tcgx_dev_avanzar'])) {
    $validacion = tcgx_devoluciones_validar_avance($tcgxDev, $_POST);
    $tcgxFormErrores = $validacion['errores'];

    if (empty($tcgxFormErrores)) {
        $resultado = tcgx_devoluciones_avanzar(Bd::getPdo(), $tcgxDev, $validacion['datos']['detalle'], $idUsuarioVista);
        if ($resultado['ok']) {
            $texto = 'ESTADO ACTUALIZADO A ' . $resultado['nuevoestado'] . '.';
            if ($resultado['nuevoestado'] === TCGX_ENVIOS_ESTADO_ENTREGADO) {
                $texto = 'DEVOLUCION COMPLETADA: ENVIO ENTREGADO EN ORIGEN.';
            }
            $_SESSION['tcgx_devoluciones_flash'] = ['tipo' => 'ok', 'texto' => $texto];
            header('Location: devoluciones.php', true, 303);
            exit;
        }
        $tcgxFormErrores[] = $resultado['error'];
    }
}
// FIN BLOQUE: PROCESAMIENTO DE AVANCE DE ESTADO


// INICIO BLOQUE: CARGA DE TRAZABILIDAD Y SIGUIENTE PASO
$tcgxMovimientos = tcgx_envios_movimientos(Bd::getPdo(), $idEnvio);
$estadoDev = (string) $tcgxDev['estado'];
$siguienteEstado = tcgx_devoluciones_siguiente_estado($estadoDev);
$puedeAvanzar = tcgx_devoluciones_puede_avanzar($estadoDev);
// FIN BLOQUE: CARGA DE TRAZABILIDAD Y SIGUIENTE PASO

$tcgxPageTitle = 'Seguimiento de devolución | TCG EXCHANGE';
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

   <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css" crossorigin="anonymous">

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
               <form method="post" action="devoluciones.php" class="d-inline">
                  <button type="submit" class="btn btn-outline-secondary">
                     <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Listado de devoluciones
                  </button>
               </form>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION -->

            <!-- INICIO BLOQUE: CABECERA DEL ENVIO EN DEVOLUCION -->
            <div class="tcgx-admin-form-card mb-3">
               <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <h2 class="h5 mb-0"><i class="fa-solid fa-rotate-left me-2" aria-hidden="true"></i><?php echo $esc($tcgxDev['id']); ?></h2>
                  <span class="badge text-bg-warning fs-6"><?php echo $esc($estadoDev); ?></span>
               </div>
               <div class="row g-3">
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Forma de envío</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxDev['formaenvio']); ?></div>
                  </div>
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Tienda de origen</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxDev['nombretiendaorigen']); ?></div>
                  </div>
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Tienda de destino</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxDev['nombretiendadestino']); ?></div>
                  </div>
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Centro de distribución</div>
                     <div class="fw-semibold"><?php echo $tcgxDev['nombrehub'] !== null ? $esc($tcgxDev['nombrehub']) : '—'; ?></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Remitente</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxDev['nombreremitente']); ?> <span class="text-secondary">(<?php echo $esc($tcgxDev['idremitente']); ?>)</span></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Destinatario</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxDev['nombredestinatario']); ?> <span class="text-secondary">(<?php echo $esc($tcgxDev['iddestinatario']); ?>)</span></div>
                  </div>
                  <?php if ($siguienteEstado !== null): ?>
                     <div class="col-12 col-md-4">
                        <div class="text-secondary small">Siguiente estado</div>
                        <div class="fw-semibold text-primary"><?php echo $esc($siguienteEstado); ?></div>
                     </div>
                  <?php endif; ?>
               </div>
            </div>
            <!-- FIN BLOQUE: CABECERA DEL ENVIO EN DEVOLUCION -->

            <!-- INICIO BLOQUE: CADENA DE ESTADOS DE DEVOLUCION (REFERENCIA VISUAL) -->
            <div class="tcgx-admin-form-card mb-3">
               <h3 class="h6 mb-3"><i class="fa-solid fa-list-ol me-2" aria-hidden="true"></i>Cadena de devolución</h3>
               <div class="d-flex flex-wrap gap-2">
                  <?php
                  $indiceActual = array_search($estadoDev, TCGX_DEVOLUCION_CADENA, true);
                  foreach (TCGX_DEVOLUCION_CADENA as $indicePaso => $paso):
                     $clasePaso = 'badge text-bg-light border';
                     if ($paso === $estadoDev) {
                         $clasePaso = 'badge text-bg-warning';
                     } elseif ($indiceActual !== false && $indicePaso < $indiceActual) {
                         $clasePaso = 'badge text-bg-success';
                     }
                  ?>
                     <span class="<?php echo $clasePaso; ?>"><?php echo $esc($paso); ?></span>
                  <?php endforeach; ?>
               </div>
            </div>
            <!-- FIN BLOQUE: CADENA DE ESTADOS DE DEVOLUCION -->

            <!-- INICIO BLOQUE: TRAZABILIDAD (MOVIMIENTOS) -->
            <div class="tcgx-admin-table-card mb-3">
               <h3 class="h6 mb-3"><i class="fa-solid fa-route me-2" aria-hidden="true"></i>Trazabilidad</h3>
               <table class="table table-hover align-middle tcgx-admin-dt-table" id="tcgx-tabla-movimientos">
                  <thead>
                     <tr>
                        <th>Fecha</th>
                        <th>Acción / Estado</th>
                        <th>Detalle</th>
                        <th>Tienda</th>
                        <th>Responsable</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxMovimientos as $mov): ?>
                        <tr>
                           <td><?php echo $esc($mov['fecharegistro']); ?></td>
                           <td><?php echo $esc($mov['accion']); ?></td>
                           <td><?php echo $mov['detalle'] !== null && $mov['detalle'] !== '' ? $esc($mov['detalle']) : '—'; ?></td>
                           <td><?php echo $esc($mov['nombretienda'] ?? ''); ?></td>
                           <td><?php echo $esc($mov['nombreusuario'] ?? ''); ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TRAZABILIDAD -->

            <?php if ($puedeAvanzar && $siguienteEstado !== null): ?>
               <!-- INICIO BLOQUE: AVANZAR ESTADO DE DEVOLUCION -->
               <div class="tcgx-admin-form-card">
                  <h3 class="h6 mb-3"><i class="fa-solid fa-forward me-2" aria-hidden="true"></i>Avanzar devolución</h3>
                  <form method="post" action="devolucion-ver.php" id="tcgx-dev-avanzar-form" class="tcgx-dev-confirm" data-tcgx-confirm="¿Avanzar el envío al estado <?php echo $esc($siguienteEstado); ?>?">
                     <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxDevCsrf); ?>">
                     <input type="hidden" name="id_envio" value="<?php echo $esc($tcgxDev['id']); ?>">
                     <input type="hidden" name="tcgx_dev_avanzar" value="1">
                     <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-8">
                           <label class="form-label" for="dev-detalle">Detalle del movimiento (opcional)</label>
                           <input type="text" class="form-control text-uppercase" id="dev-detalle" name="detalle" maxlength="255">
                        </div>
                        <div class="col-12 col-md-4">
                           <button type="submit" class="btn btn-success w-100">
                              <i class="fa-solid fa-forward me-2" aria-hidden="true"></i>AVANZAR A <?php echo $esc($siguienteEstado); ?>
                           </button>
                        </div>
                     </div>
                  </form>
               </div>
               <!-- FIN BLOQUE: AVANZAR ESTADO DE DEVOLUCION -->
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

   <?php if (!empty($tcgxFormErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxFormErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>

   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>

   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/devolucion-ver.js?v=20260612e"></script>
</body>
</html>
