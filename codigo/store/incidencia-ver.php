<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO INCIDENCIAS (STORE)
require __DIR__ . '/includes/carga_sesion_store.php';
require __DIR__ . '/includes/store_incidencias_logica.php';
require __DIR__ . '/../admin/includes/auditorias_logica.php';

if (empty($_SESSION['tcgx_incidencias_csrf'])) {
    $_SESSION['tcgx_incidencias_csrf'] = bin2hex(random_bytes(32));
}
$tcgxIncCsrf = $_SESSION['tcgx_incidencias_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO INCIDENCIAS (STORE)


// INICIO BLOQUE: ACCESO POR POST (SIN GET CON DATOS) Y CARGA DE LA INCIDENCIA ACOTADA
$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $tcgxIncCsrf === '' || $tokenPost === '' || !hash_equals($tcgxIncCsrf, $tokenPost)) {
    header('Location: incidencias.php', true, 303);
    exit;
}

$idRaw = trim((string) ($_POST['id_incidencia'] ?? ''));
$idIncidencia = ($idRaw !== '' && ctype_digit($idRaw)) ? (int) $idRaw : 0;
$tcgxInc = $idIncidencia > 0 ? tcgx_store_incidencias_obtener($pdo, $idIncidencia, $idTiendaSesion) : null;
if ($tcgxInc === null) {
    $_SESSION['tcgx_incidencias_flash'] = ['tipo' => 'error', 'texto' => 'LA INCIDENCIA INDICADA NO EXISTE O NO PERTENECE A SU ALCANCE.'];
    header('Location: incidencias.php', true, 303);
    exit;
}

// INICIO BLOQUE: AUDITORIA DE LECTURA DEL DETALLE (ACCION LEER)
if (!isset($_POST['tcgx_inc_actualizar'])) {
    tcgx_auditorias_registrar_lectura($pdo, $idUsuarioVista, 'incidencias', (string) $idIncidencia);
}
// FIN BLOQUE: AUDITORIA DE LECTURA DEL DETALLE
// FIN BLOQUE: ACCESO POR POST Y CARGA DE LA INCIDENCIA


// INICIO BLOQUE: PROCESAMIENTO DE ACTUALIZACION (REVALIDACION + LOGICA ADMIN)
$tcgxFormErrores = [];
if (isset($_POST['tcgx_inc_actualizar'])) {
    $errorRevalidacion = tcgx_store_revalidar_operacion($pdo, $idUsuarioVista, $idTiendaSesion);
    if ($errorRevalidacion !== null) {
        $tcgxFormErrores[] = $errorRevalidacion;
    } else {
        $validacion = tcgx_incidencias_validar_actualizacion($_POST, $tcgxInc);
        $tcgxFormErrores = $validacion['errores'];

        if (empty($tcgxFormErrores)) {
            $resultado = tcgx_incidencias_actualizar($pdo, $tcgxInc, $validacion['datos'], $idUsuarioVista);
            if ($resultado['ok']) {
                $_SESSION['tcgx_incidencias_flash'] = ['tipo' => 'ok', 'texto' => 'INCIDENCIA ACTUALIZADA CORRECTAMENTE.'];
                header('Location: incidencias.php', true, 303);
                exit;
            }
            $tcgxFormErrores[] = $resultado['error'];
        }
    }
}
// FIN BLOQUE: PROCESAMIENTO DE ACTUALIZACION


// INICIO BLOQUE: CARGA DE HISTORIAL Y FRONTERAS DE ACCION
$tcgxHistorial = tcgx_incidencias_historial($pdo, $idIncidencia);
$estadoInc = (string) $tcgxInc['estadoincidencia'];
$puedeActualizar = tcgx_incidencias_puede_actualizar($estadoInc);
// FIN BLOQUE: CARGA DE HISTORIAL Y FRONTERAS DE ACCION

$tcgxPageTitle = 'Gestionar incidencia | TCG EXCHANGE';
$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$estadoClase = 'text-bg-secondary';
if ($estadoInc === TCGX_INC_ESTADO_REPORTADA) {
    $estadoClase = 'text-bg-warning';
} elseif ($estadoInc === TCGX_INC_ESTADO_RESUELTA) {
    $estadoClase = 'text-bg-info';
} elseif ($estadoInc === TCGX_INC_ESTADO_CERRADA) {
    $estadoClase = 'text-bg-dark';
}
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

            <!-- INICIO BLOQUE: BARRA DE ACCION (VOLVER AL LISTADO POR POST) -->
            <div class="tcgx-store-tabla-toolbar">
               <form method="post" action="incidencias.php" class="d-inline">
                  <button type="submit" class="btn btn-outline-secondary">
                     <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Listado de incidencias
                  </button>
               </form>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION -->

            <!-- INICIO BLOQUE: CABECERA DE LA INCIDENCIA -->
            <div class="tcgx-store-form-card mb-3">
               <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <h2 class="h5 mb-0"><i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Incidencia #<?php echo $esc($tcgxInc['id']); ?></h2>
                  <span class="badge <?php echo $estadoClase; ?> fs-6"><?php echo $esc($estadoInc); ?></span>
               </div>
               <div class="row g-3">
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Envío</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxInc['idenvio']); ?> <span class="text-secondary">(<?php echo $esc($tcgxInc['estadoenvio']); ?>)</span></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Tipo</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxInc['tipoincidencia']); ?></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Forma de envío</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxInc['formaenvio']); ?></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Reportó</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxInc['nombrereporta']); ?> <span class="text-secondary">(<?php echo $esc($tcgxInc['idusuarioreporta']); ?>)</span></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Tienda reporta</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxInc['nombretiendareporta']); ?></div>
                  </div>
                  <div class="col-12 col-md-2">
                     <div class="text-secondary small">Fecha reporte</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxInc['fechareporte']); ?></div>
                  </div>
                  <div class="col-12 col-md-2">
                     <div class="text-secondary small">Fecha cierre</div>
                     <div class="fw-semibold"><?php echo $tcgxInc['fechacierre'] !== null ? $esc($tcgxInc['fechacierre']) : '—'; ?></div>
                  </div>
                  <div class="col-12">
                     <div class="text-secondary small">Detalle inicial</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxInc['detalleinicial']); ?></div>
                  </div>
               </div>
            </div>
            <!-- FIN BLOQUE: CABECERA DE LA INCIDENCIA -->

            <!-- INICIO BLOQUE: HISTORIAL DE ACTUALIZACIONES -->
            <div class="tcgx-store-table-card mb-3">
               <h3 class="h6 mb-3"><i class="fa-solid fa-clock-rotate-left me-2" aria-hidden="true"></i>Historial de actualizaciones</h3>
               <?php if (empty($tcgxHistorial)): ?>
                  <p class="text-secondary mb-0">Aún no hay actualizaciones registradas.</p>
               <?php else: ?>
                  <table class="table table-hover align-middle tcgx-store-dt-table" id="tcgx-tabla-historial">
                     <thead>
                        <tr>
                           <th>Fecha</th>
                           <th>Estado</th>
                           <th>Detalle</th>
                           <th>Responsable</th>
                           <th>Tienda</th>
                        </tr>
                     </thead>
                     <tbody>
                        <?php foreach ($tcgxHistorial as $h): ?>
                           <tr>
                              <td><?php echo $esc($h['fechaactualizacion']); ?></td>
                              <td><?php echo $esc($h['estadoincidencia']); ?></td>
                              <td><?php echo $esc($h['detalleactualizacion']); ?></td>
                              <td><?php echo $esc($h['nombreusuario']); ?></td>
                              <td><?php echo $esc($h['nombretienda']); ?></td>
                           </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               <?php endif; ?>
            </div>
            <!-- FIN BLOQUE: HISTORIAL DE ACTUALIZACIONES -->

            <?php if ($puedeActualizar): ?>
               <!-- INICIO BLOQUE: FORMULARIO DE ACTUALIZACION (CAMBIO DE ESTADO) -->
               <div class="tcgx-store-form-card">
                  <h3 class="h6 mb-3"><i class="fa-solid fa-sliders me-2" aria-hidden="true"></i>Actualizar incidencia</h3>
                  <form method="post" action="incidencia-ver.php" id="tcgx-inc-form">
                     <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxIncCsrf); ?>">
                     <input type="hidden" name="id_incidencia" value="<?php echo $esc((string) $tcgxInc['id']); ?>">
                     <input type="hidden" name="tcgx_inc_actualizar" value="1">
                     <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                           <label class="form-label" for="inc-estado">Estado</label>
                           <select class="form-select" id="inc-estado" name="estadoincidencia" required>
                              <?php foreach (TCGX_INC_ESTADOS as $est): ?>
                                 <option value="<?php echo $esc($est); ?>" <?php echo $est === $estadoInc ? 'selected' : ''; ?>><?php echo $esc($est); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="col-12 col-md-8">
                           <label class="form-label" for="inc-detalle">Detalle de la actualización</label>
                           <input type="text" class="form-control text-uppercase" id="inc-detalle" name="detalleactualizacion" maxlength="255" required>
                        </div>
                     </div>
                     <div class="tcgx-store-form-actions">
                        <button type="submit" class="btn btn-success">
                           <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>GUARDAR ACTUALIZACIÓN
                        </button>
                     </div>
                  </form>
               </div>
               <!-- FIN BLOQUE: FORMULARIO DE ACTUALIZACION -->
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
   <?php if (!empty($tcgxFormErrores)): ?>
      <script id="tcgx-form-flash" type="application/json"><?php
         echo json_encode(['errores' => array_values($tcgxFormErrores)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: DATOS PARA JS -->

   <script src="vendor/js/lib/jquery-3.7.1.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>

   <!-- INICIO BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <!-- FIN BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->

   <script src="vendor/js/store-panel.js?v=20260612a"></script>
   <script src="vendor/js/incidencia-ver.js?v=20260612d"></script>
</body>
</html>
