<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO ENVIOS (STORE)
require __DIR__ . '/includes/carga_sesion_store.php';
require __DIR__ . '/includes/store_envios_logica.php';
require __DIR__ . '/../admin/includes/auditorias_logica.php';

if (empty($_SESSION['tcgx_envios_csrf'])) {
    $_SESSION['tcgx_envios_csrf'] = bin2hex(random_bytes(32));
}
$tcgxEnviosCsrf = $_SESSION['tcgx_envios_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO ENVIOS (STORE)


// INICIO BLOQUE: ACCESO POR POST (SIN GET CON DATOS) Y CARGA DEL ENVIO ACOTADO A TIENDA
$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $tcgxEnviosCsrf === '' || $tokenPost === '' || !hash_equals($tcgxEnviosCsrf, $tokenPost)) {
    header('Location: envios.php', true, 303);
    exit;
}

$idEnvio = mb_strtoupper(trim((string) ($_POST['id_envio'] ?? '')), 'UTF-8');
$tcgxEnvio = $idEnvio === '' ? null : tcgx_store_envios_obtener($pdo, $idEnvio, $idTiendaSesion);
if ($tcgxEnvio === null) {
    $_SESSION['tcgx_envios_flash'] = ['tipo' => 'error', 'texto' => 'EL ENVIO INDICADO NO EXISTE O NO PERTENECE A SU TIENDA.'];
    header('Location: envios.php', true, 303);
    exit;
}

// INICIO BLOQUE: AUDITORIA DE LECTURA DEL DETALLE (ACCION LEER)
if (!isset($_POST['tcgx_envios_accion'])) {
    tcgx_auditorias_registrar_lectura($pdo, $idUsuarioVista, 'envios', $idEnvio);
}
// FIN BLOQUE: AUDITORIA DE LECTURA DEL DETALLE
// FIN BLOQUE: ACCESO POR POST Y CARGA DEL ENVIO


// INICIO BLOQUE: PROCESAMIENTO DE ACCIONES INDIVIDUALES (REVALIDACION + ALCANCE STORE)
if (isset($_POST['tcgx_envios_accion'])) {
    $errorRevalidacion = tcgx_store_revalidar_operacion($pdo, $idUsuarioVista, $idTiendaSesion);
    if ($errorRevalidacion !== null) {
        $_SESSION['tcgx_envios_flash'] = ['tipo' => 'error', 'texto' => $errorRevalidacion];
        header('Location: envios.php', true, 303);
        exit;
    }

    $accion = (string) $_POST['tcgx_envios_accion'];
    $resultado = ['ok' => false, 'error' => 'ACCION NO RECONOCIDA.'];

    if ($accion === 'cambiar_destino' && tcgx_store_envios_puede_cambiar_destino($tcgxEnvio, $idTiendaSesion)) {
        $resultado = tcgx_envios_cambiar_destino($pdo, $tcgxEnvio, (string) ($_POST['idnuevodestino'] ?? ''), $idUsuarioVista);
    } elseif ($accion === 'cambiar_receptor' && tcgx_store_envios_puede_cambiar_receptor($tcgxEnvio, $idTiendaSesion)) {
        $resultado = tcgx_envios_cambiar_receptor($pdo, $tcgxEnvio, (string) ($_POST['idnuevoreceptor'] ?? ''), $idUsuarioVista);
    } elseif ($accion === 'cancelar' && tcgx_store_envios_puede_cancelar($tcgxEnvio, $idTiendaSesion)) {
        $resultado = tcgx_envios_cancelar($pdo, $tcgxEnvio, $idUsuarioVista);
    } elseif ($accion === 'devolver' && tcgx_store_envios_puede_devolver($tcgxEnvio, $idTiendaSesion)) {
        $resultado = tcgx_envios_devolver($pdo, $tcgxEnvio, $idUsuarioVista);
    } elseif ($accion === 'entregar' && tcgx_store_envios_puede_entregar($tcgxEnvio, $idTiendaSesion)) {
        $resultado = tcgx_envios_entregar($pdo, $tcgxEnvio, $idUsuarioVista);
    } elseif ($accion === 'despachar_directo' && tcgx_store_envios_puede_despachar_directo($tcgxEnvio, $idTiendaSesion)) {
        $resultado = tcgx_envios_despachar_directo($pdo, $tcgxEnvio, (string) ($_POST['guiaexterna'] ?? ''), $idUsuarioVista);
    } elseif ($accion === 'recibir_destino' && tcgx_store_envios_puede_recibir_destino($tcgxEnvio, $idTiendaSesion)) {
        $resultado = tcgx_envios_recibir_en_destino($pdo, $tcgxEnvio, $idUsuarioVista);
    } else {
        $resultado = ['ok' => false, 'error' => 'ACCION NO PERMITIDA PARA SU TIENDA O ESTADO ACTUAL.'];
    }

    $_SESSION['tcgx_envios_flash'] = $resultado['ok']
        ? ['tipo' => 'ok', 'texto' => 'OPERACION REALIZADA CORRECTAMENTE.']
        : ['tipo' => 'error', 'texto' => $resultado['error']];
    header('Location: envios.php', true, 303);
    exit;
}
// FIN BLOQUE: PROCESAMIENTO DE ACCIONES INDIVIDUALES


// INICIO BLOQUE: CARGA DE TRAZABILIDAD, CATALOGOS Y FRONTERAS DE ACCION STORE
$tcgxPaquetes = tcgx_envios_paquetes($pdo, $idEnvio);
$tcgxMovimientos = tcgx_envios_movimientos($pdo, $idEnvio);
$tcgxTiendas = tcgx_store_envios_listar_tiendas_destino($pdo, $idTiendaSesion);

$tcgxImagenesEnvio = tcgx_envios_imagenes($pdo, $idEnvio);
$tcgxImagenesPorPaquete = [];
foreach ($tcgxImagenesEnvio as $img) {
    $tcgxImagenesPorPaquete[(int) $img['idpaquete']][] = (string) $img['nombreimagen'];
}

$estadoTxt = (string) $tcgxEnvio['estado'];

$puedeCambiarDestino = tcgx_store_envios_puede_cambiar_destino($tcgxEnvio, $idTiendaSesion);
$puedeCambiarReceptor = tcgx_store_envios_puede_cambiar_receptor($tcgxEnvio, $idTiendaSesion);
$puedeCancelar = tcgx_store_envios_puede_cancelar($tcgxEnvio, $idTiendaSesion);
$puedeDevolver = tcgx_store_envios_puede_devolver($tcgxEnvio, $idTiendaSesion);
$puedeEntregar = tcgx_store_envios_puede_entregar($tcgxEnvio, $idTiendaSesion);
$puedeDespacharDirecto = tcgx_store_envios_puede_despachar_directo($tcgxEnvio, $idTiendaSesion);
$puedeRecibirDestino = tcgx_store_envios_puede_recibir_destino($tcgxEnvio, $idTiendaSesion);
$exigeGuiaDirecto = $puedeDespacharDirecto && tcgx_envios_ruta_exige_guia($pdo, $tcgxEnvio);
$hayAcciones = $puedeCambiarDestino || $puedeCambiarReceptor || $puedeCancelar || $puedeDevolver
    || $puedeEntregar || $puedeDespacharDirecto || $puedeRecibirDestino;
// FIN BLOQUE: CARGA DE TRAZABILIDAD, CATALOGOS Y FRONTERAS DE ACCION STORE

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

   <link rel="icon" href="<?php echo $esc($tcgxStoreUrlFavicon); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">

   <!-- INICIO BLOQUE: ESTILOS DATATABLES Y SELECT2 (COPIAS LOCALES) -->
   <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="vendor/css/lib/select2.min.css?v=20260612b">
   <link rel="stylesheet" href="vendor/css/lib/select2-bootstrap-5-theme.min.css?v=20260612b">
   <!-- FIN BLOQUE: ESTILOS DATATABLES Y SELECT2 -->

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
               <form method="post" action="envios.php" class="d-inline">
                  <button type="submit" class="btn btn-outline-secondary">
                     <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Listado de envíos
                  </button>
               </form>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION -->

            <!-- INICIO BLOQUE: CABECERA DEL ENVIO -->
            <div class="tcgx-store-form-card mb-3">
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
            <div class="tcgx-store-table-card mb-3">
               <h3 class="h6 mb-3"><i class="fa-solid fa-box me-2" aria-hidden="true"></i>Paquetes</h3>
               <table class="table table-hover align-middle tcgx-store-dt-table" id="tcgx-tabla-paquetes">
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
               <div class="tcgx-store-table-card mb-3">
                  <h3 class="h6 mb-3"><i class="fa-solid fa-images me-2" aria-hidden="true"></i>Imágenes de evidencia</h3>
                  <?php foreach ($tcgxImagenesPorPaquete as $idPaq => $nombres): ?>
                     <div class="mb-3">
                        <div class="text-secondary small mb-2">Paquete <?php echo $esc((string) $idPaq); ?></div>
                        <div class="d-flex flex-wrap gap-2">
                           <?php foreach ($nombres as $nombreImg): ?>
                              <?php $urlImg = '../uploads/envios/' . rawurlencode($nombreImg); ?>
                              <a href="<?php echo $esc($urlImg); ?>" target="_blank" rel="noopener" title="Ver imagen">
                                 <img src="<?php echo $esc($urlImg); ?>" alt="Evidencia del paquete <?php echo $esc((string) $idPaq); ?>" loading="lazy" style="width:96px;height:96px;object-fit:cover;border-radius:8px;border:1px solid rgba(2,14,40,.12);">
                              </a>
                           <?php endforeach; ?>
                        </div>
                     </div>
                  <?php endforeach; ?>
               </div>
               <!-- FIN BLOQUE: IMAGENES DE EVIDENCIA (SOLO CONSULTA) -->
            <?php endif; ?>

            <!-- INICIO BLOQUE: TRAZABILIDAD (MOVIMIENTOS) -->
            <div class="tcgx-store-table-card mb-3">
               <h3 class="h6 mb-3"><i class="fa-solid fa-route me-2" aria-hidden="true"></i>Trazabilidad</h3>
               <table class="table table-hover align-middle tcgx-store-dt-table" id="tcgx-tabla-movimientos">
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
            <!-- FIN BLOQUE: TRAZABILIDAD (MOVIMIENTOS) -->

            <!-- INICIO BLOQUE: ACCIONES INDIVIDUALES SOBRE EL ENVIO (ALCANCE STORE) -->
            <div class="tcgx-store-form-card">
               <h3 class="h6 mb-3"><i class="fa-solid fa-sliders me-2" aria-hidden="true"></i>Acciones del envío</h3>
               <?php if (!$hayAcciones): ?>
                  <p class="text-secondary mb-0">El envío está en un estado que no admite más acciones individuales para su tienda.</p>
               <?php else: ?>
                  <div class="row g-4">

                     <?php if ($puedeCambiarDestino): ?>
                        <div class="col-12 col-lg-6">
                           <form method="post" action="envio-ver.php" class="tcgx-envio-accion">
                              <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
                              <input type="hidden" name="id_envio" value="<?php echo $esc($tcgxEnvio['id']); ?>">
                              <input type="hidden" name="tcgx_envios_accion" value="cambiar_destino">
                              <label class="form-label" for="envio-nuevo-destino">Cambiar tienda de destino</label>
                              <div class="d-flex gap-2">
                                 <select class="form-select" id="envio-nuevo-destino" name="idnuevodestino" required>
                                    <option value="">SELECCIONE…</option>
                                    <?php foreach ($tcgxTiendas as $t): ?>
                                       <?php $tid = (string) $t['id']; ?>
                                       <option value="<?php echo $esc($tid); ?>" <?php echo $tid === (string) $tcgxEnvio['idtiendadestino'] ? 'disabled' : ''; ?>><?php echo $esc($t['nombre']); ?></option>
                                    <?php endforeach; ?>
                                 </select>
                                 <button type="submit" class="btn btn-warning text-nowrap">
                                    <i class="fa-solid fa-location-dot me-2" aria-hidden="true"></i>CAMBIAR
                                 </button>
                              </div>
                           </form>
                        </div>
                     <?php endif; ?>

                     <?php if ($puedeCambiarReceptor): ?>
                        <div class="col-12 col-lg-6">
                           <form method="post" action="envio-ver.php" class="tcgx-envio-accion" id="tcgx-form-receptor">
                              <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
                              <input type="hidden" name="id_envio" value="<?php echo $esc($tcgxEnvio['id']); ?>">
                              <input type="hidden" name="tcgx_envios_accion" value="cambiar_receptor">
                              <label class="form-label" for="envio-nuevo-receptor">Cambiar receptor (destinatario)</label>
                              <div class="d-flex gap-2">
                                 <select class="form-select" id="envio-nuevo-receptor" name="idnuevoreceptor" required></select>
                                 <button type="submit" class="btn btn-warning text-nowrap">
                                    <i class="fa-solid fa-user-pen me-2" aria-hidden="true"></i>CAMBIAR
                                 </button>
                              </div>
                           </form>
                        </div>
                     <?php endif; ?>

                     <?php if ($puedeDespacharDirecto): ?>
                        <div class="col-12 col-lg-6">
                           <form method="post" action="envio-ver.php" class="tcgx-envio-accion">
                              <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
                              <input type="hidden" name="id_envio" value="<?php echo $esc($tcgxEnvio['id']); ?>">
                              <input type="hidden" name="tcgx_envios_accion" value="despachar_directo">
                              <label class="form-label" for="envio-guia-directo">Despacho directo<?php echo $exigeGuiaDirecto ? '' : ' (guía opcional)'; ?></label>
                              <div class="d-flex gap-2">
                                 <input type="text" class="form-control text-uppercase" id="envio-guia-directo" name="guiaexterna" maxlength="80" placeholder="GUIA EXTERNA" <?php echo $exigeGuiaDirecto ? 'required' : ''; ?>>
                                 <button type="submit" class="btn btn-primary text-nowrap">
                                    <i class="fa-solid fa-truck me-2" aria-hidden="true"></i>DESPACHAR
                                 </button>
                              </div>
                           </form>
                        </div>
                     <?php endif; ?>

                     <?php if ($puedeRecibirDestino): ?>
                        <div class="col-12 col-lg-6 d-flex align-items-end">
                           <form method="post" action="envio-ver.php" class="tcgx-envio-confirm w-100" data-tcgx-confirm="¿Confirmar recepción en tienda de destino?">
                              <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
                              <input type="hidden" name="id_envio" value="<?php echo $esc($tcgxEnvio['id']); ?>">
                              <input type="hidden" name="tcgx_envios_accion" value="recibir_destino">
                              <button type="submit" class="btn btn-primary w-100">
                                 <i class="fa-solid fa-warehouse me-2" aria-hidden="true"></i>RECIBIR EN DESTINO
                              </button>
                           </form>
                        </div>
                     <?php endif; ?>

                  </div>

                  <div class="tcgx-store-form-actions">
                     <?php if ($puedeEntregar): ?>
                        <form method="post" action="envio-ver.php" class="d-inline tcgx-envio-confirm" data-tcgx-confirm="¿Confirmar entrega al destinatario?">
                           <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
                           <input type="hidden" name="id_envio" value="<?php echo $esc($tcgxEnvio['id']); ?>">
                           <input type="hidden" name="tcgx_envios_accion" value="entregar">
                           <button type="submit" class="btn btn-success">
                              <i class="fa-solid fa-circle-check me-2" aria-hidden="true"></i>MARCAR ENTREGADO
                           </button>
                        </form>
                     <?php endif; ?>
                     <?php if ($puedeDevolver): ?>
                        <form method="post" action="envio-ver.php" class="d-inline tcgx-envio-confirm" data-tcgx-confirm="¿Iniciar la devolución de este envío?">
                           <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
                           <input type="hidden" name="id_envio" value="<?php echo $esc($tcgxEnvio['id']); ?>">
                           <input type="hidden" name="tcgx_envios_accion" value="devolver">
                           <button type="submit" class="btn btn-outline-warning">
                              <i class="fa-solid fa-rotate-left me-2" aria-hidden="true"></i>DEVOLVER
                           </button>
                        </form>
                     <?php endif; ?>
                     <?php if ($puedeCancelar): ?>
                        <form method="post" action="envio-ver.php" class="d-inline tcgx-envio-confirm" data-tcgx-confirm="¿Cancelar este envío? Esta acción no se puede deshacer.">
                           <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxEnviosCsrf); ?>">
                           <input type="hidden" name="id_envio" value="<?php echo $esc($tcgxEnvio['id']); ?>">
                           <input type="hidden" name="tcgx_envios_accion" value="cancelar">
                           <button type="submit" class="btn btn-danger">
                              <i class="fa-solid fa-ban me-2" aria-hidden="true"></i>CANCELAR ENVÍO
                           </button>
                        </form>
                     <?php endif; ?>
                  </div>
               <?php endif; ?>
            </div>
            <!-- FIN BLOQUE: ACCIONES INDIVIDUALES SOBRE EL ENVIO -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxStoreSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxStoreSidebarModo);
   ?>

   <!-- INICIO BLOQUE: TOKEN PARA EL SELECT2 DEL RECEPTOR -->
   <script id="tcgx-envio-config" type="application/json"><?php
      echo json_encode(['token' => $tcgxEnviosCsrf], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
   ?></script>
   <!-- FIN BLOQUE: TOKEN PARA EL SELECT2 DEL RECEPTOR -->

   <script src="vendor/js/lib/jquery-3.7.1.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="vendor/js/lib/select2.min.js?v=20260612b"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>

   <!-- INICIO BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <!-- FIN BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->

   <script src="vendor/js/store-panel.js?v=20260612a"></script>
   <script src="vendor/js/envio-ver.js?v=20260612a"></script>
</body>
</html>
