<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO ENVIOS (CLIENT)
require __DIR__ . '/includes/carga_sesion_client.php';
require_once __DIR__ . '/includes/client_envios_logica.php';
require __DIR__ . '/../admin/includes/auditorias_logica.php';

if (empty($_SESSION['tcgx_envios_csrf'])) {
    $_SESSION['tcgx_envios_csrf'] = bin2hex(random_bytes(32));
}
$tcgxEnviosCsrf = $_SESSION['tcgx_envios_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO ENVIOS (CLIENT)


// INICIO BLOQUE: ACCESO POR POST (SIN GET CON DATOS) Y CARGA DEL ENVIO ACOTADO AL CLIENTE
$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
$tcgxEnviosOrigenListadoRaw = mb_strtolower(trim((string) ($_POST['tcgx_envios_origen_listado'] ?? 'envios')), 'UTF-8');
$tcgxEnviosOrigenListado = $tcgxEnviosOrigenListadoRaw === 'recepciones' ? 'recepciones' : 'envios';
$tcgxEnviosUrlListado = $tcgxEnviosOrigenListado === 'recepciones' ? 'recepciones.php' : 'envios.php';
$tcgxEnviosEtiquetaListado = $tcgxEnviosOrigenListado === 'recepciones' ? 'Recepciones' : 'Envíos';
$tcgxClientNavEnviosSeccion = $tcgxEnviosOrigenListado;

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $tcgxEnviosCsrf === '' || $tokenPost === '' || !hash_equals($tcgxEnviosCsrf, $tokenPost)) {
    header('Location: ' . $tcgxEnviosUrlListado, true, 303);
    exit;
}

$idEnvio = mb_strtoupper(trim((string) ($_POST['id_envio'] ?? '')), 'UTF-8');
$tcgxEnvio = $idEnvio === '' ? null : tcgx_client_envios_obtener($pdo, $idEnvio, $idUsuarioVista);
if ($tcgxEnvio === null) {
    $_SESSION['tcgx_envios_flash'] = ['tipo' => 'error', 'texto' => 'EL ENVIO INDICADO NO EXISTE O NO LE PERTENECE.'];
    header('Location: ' . $tcgxEnviosUrlListado, true, 303);
    exit;
}

// INICIO BLOQUE: AUDITORIA DE LECTURA DEL DETALLE (ACCION LEER)
tcgx_auditorias_registrar_lectura($pdo, $idUsuarioVista, 'envios', $idEnvio);
// FIN BLOQUE: AUDITORIA DE LECTURA DEL DETALLE
// FIN BLOQUE: ACCESO POR POST Y CARGA DEL ENVIO


// INICIO BLOQUE: CARGA DE TRAZABILIDAD E IMAGENES (SOLO LECTURA)
$tcgxPaquetes = tcgx_envios_paquetes($pdo, $idEnvio);
$tcgxMovimientos = tcgx_envios_movimientos($pdo, $idEnvio);
$tcgxImagenesEnvio = tcgx_envios_imagenes($pdo, $idEnvio);
$tcgxImagenesPorPaquete = [];
foreach ($tcgxImagenesEnvio as $img) {
    $tcgxImagenesPorPaquete[(int) $img['idpaquete']][] = (string) $img['nombreimagen'];
}
// FIN BLOQUE: CARGA DE TRAZABILIDAD E IMAGENES

$estadoTxt = (string) $tcgxEnvio['estado'];
$tcgxPageTitle = 'Detalle de envío | TCG EXCHANGE';
$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$estadoClase = 'text-bg-primary';
if ($estadoTxt === 'ENTREGADO') {
    $estadoClase = 'text-bg-success';
} elseif ($estadoTxt === 'CANCELADO') {
    $estadoClase = 'text-bg-dark';
} elseif (str_starts_with($estadoTxt, 'DEVOLUCION')) {
    $estadoClase = 'text-bg-warning';
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

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-client-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-client-content" id="tcgx-client-main">

            <!-- INICIO BLOQUE: BARRA DE ACCION (VOLVER AL LISTADO) -->
            <div class="tcgx-client-tabla-toolbar">
               <form method="post" action="<?php echo $esc($tcgxEnviosUrlListado); ?>" class="d-inline">
                  <button type="submit" class="btn btn-outline-secondary">
                     <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i><?php echo $esc($tcgxEnviosEtiquetaListado); ?>
                  </button>
               </form>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION -->

            <!-- INICIO BLOQUE: CABECERA DEL ENVIO -->
            <div class="tcgx-client-form-card mb-3">
               <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <h2 class="h5 mb-0"><i class="fa-solid fa-barcode me-2" aria-hidden="true"></i><?php echo $esc($tcgxEnvio['id']); ?></h2>
                  <span class="badge <?php echo $estadoClase; ?> fs-6"><?php echo $esc($estadoTxt); ?></span>
               </div>
               <div class="row g-3">
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Forma de envío</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxEnvio['formaenvio']); ?></div>
                  </div>
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Monto a pagar</div>
                     <div class="fw-semibold"><?php echo $esc(number_format((float) $tcgxEnvio['montoapagar'], 2, '.', ',')); ?></div>
                  </div>
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Tienda de origen</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxEnvio['nombretiendaorigen']); ?></div>
                  </div>
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Tienda de destino</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxEnvio['nombretiendadestino']); ?></div>
                  </div>
                  <div class="col-12 col-md-3">
                     <div class="text-secondary small">Centro de distribución</div>
                     <div class="fw-semibold"><?php echo $tcgxEnvio['nombrehub'] !== null ? $esc($tcgxEnvio['nombrehub']) : '—'; ?></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Remitente</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxEnvio['nombreremitente']); ?> <span class="text-secondary">(<?php echo $esc($tcgxEnvio['idremitente']); ?>)</span></div>
                  </div>
                  <div class="col-12 col-md-5">
                     <div class="text-secondary small">Destinatario</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxEnvio['nombredestinatario']); ?> <span class="text-secondary">(<?php echo $esc($tcgxEnvio['iddestinatario']); ?>)</span></div>
                  </div>
               </div>
            </div>
            <!-- FIN BLOQUE: CABECERA DEL ENVIO -->

            <!-- INICIO BLOQUE: PAQUETES DEL ENVIO -->
            <div class="tcgx-client-table-card mb-3">
               <h3 class="h6 mb-3"><i class="fa-solid fa-box me-2" aria-hidden="true"></i>Paquetes</h3>
               <table class="table table-hover align-middle tcgx-client-dt-table" id="tcgx-tabla-paquetes">
                  <thead>
                     <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Valor declarado</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxPaquetes as $paquete): ?>
                        <tr>
                           <td><?php echo $esc($paquete['id']); ?></td>
                           <td><?php echo $esc($paquete['tipo']); ?></td>
                           <td><?php echo $esc($paquete['descripcion'] ?? ''); ?></td>
                           <td><?php echo $esc($paquete['cantidad']); ?></td>
                           <td><?php echo $esc(number_format((float) $paquete['valordeclarado'], 2, '.', ',')); ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: PAQUETES DEL ENVIO -->

            <?php if (!empty($tcgxImagenesEnvio)): ?>
               <!-- INICIO BLOQUE: IMAGENES DE EVIDENCIA (SOLO CONSULTA) -->
               <div class="tcgx-client-table-card mb-3">
                  <h3 class="h6 mb-3"><i class="fa-solid fa-images me-2" aria-hidden="true"></i>Imágenes de evidencia</h3>
                  <?php foreach ($tcgxImagenesPorPaquete as $idPaq => $nombres): ?>
                     <div class="mb-3">
                        <div class="text-secondary small mb-2">Paquete <?php echo $esc((string) $idPaq); ?></div>
                        <div class="d-flex flex-wrap gap-2">
                           <?php foreach ($nombres as $nombreImg): ?>
                              <?php $urlImg = '../uploads/envios/' . rawurlencode($nombreImg); ?>
                              <a href="<?php echo $esc($urlImg); ?>" target="_blank" rel="noopener" title="Ver imagen">
                                 <img src="<?php echo $esc($urlImg); ?>" alt="Evidencia del paquete <?php echo $esc((string) $idPaq); ?>" loading="lazy" class="tcgx-client-evidencia-img">
                              </a>
                           <?php endforeach; ?>
                        </div>
                     </div>
                  <?php endforeach; ?>
               </div>
               <!-- FIN BLOQUE: IMAGENES DE EVIDENCIA -->
            <?php endif; ?>

            <!-- INICIO BLOQUE: TRAZABILIDAD (MOVIMIENTOS) -->
            <div class="tcgx-client-table-card mb-3">
               <h3 class="h6 mb-3"><i class="fa-solid fa-route me-2" aria-hidden="true"></i>Trazabilidad</h3>
               <table class="table table-hover align-middle tcgx-client-dt-table" id="tcgx-tabla-movimientos">
                  <thead>
                     <tr>
                        <th>Fecha</th>
                        <th>Acción / Estado</th>
                        <th>Detalle</th>
                        <th>Guía externa</th>
                        <th>Tienda</th>
                        <th>Responsable</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxMovimientos as $mov): ?>
                        <tr>
                           <td><?php echo $esc($mov['fecharegistro']); ?></td>
                           <td><?php echo $esc($mov['accion']); ?></td>
                           <td><?php echo $esc($mov['detalle'] ?? ''); ?></td>
                           <td><?php echo $mov['guiaexterna'] !== null && $mov['guiaexterna'] !== '' ? $esc($mov['guiaexterna']) : '—'; ?></td>
                           <td><?php echo $esc($mov['nombretienda'] ?? ''); ?></td>
                           <td><?php echo $esc($mov['nombreusuario'] ?? ''); ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TRAZABILIDAD -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxClientSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxClientSidebarModo);
   ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/client-panel.js?v=20260612a"></script>
   <script src="vendor/js/envio-ver.js?v=20260612a"></script>
</body>
</html>
