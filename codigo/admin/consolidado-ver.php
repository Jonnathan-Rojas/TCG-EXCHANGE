<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/consolidados_logica.php';
require __DIR__ . '/includes/auditorias_logica.php';

if (empty($_SESSION['tcgx_consolidados_csrf'])) {
    $_SESSION['tcgx_consolidados_csrf'] = bin2hex(random_bytes(32));
}
$tcgxConsCsrf = $_SESSION['tcgx_consolidados_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO CONSOLIDADOS


// INICIO BLOQUE: ACCESO POR POST (SIN GET CON DATOS) Y CARGA DEL CONSOLIDADO
$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $tcgxConsCsrf === '' || $tokenPost === '' || !hash_equals($tcgxConsCsrf, $tokenPost)) {
    header('Location: consolidados.php', true, 303);
    exit;
}

$idConsolidado = mb_strtoupper(trim((string) ($_POST['id_consolidado'] ?? '')), 'UTF-8');
$tcgxCons = $idConsolidado === '' ? null : tcgx_consolidados_obtener(Bd::getPdo(), $idConsolidado);
if ($tcgxCons === null) {
    $_SESSION['tcgx_consolidados_flash'] = ['tipo' => 'error', 'texto' => 'EL CONSOLIDADO INDICADO NO EXISTE.'];
    header('Location: consolidados.php', true, 303);
    exit;
}

// INICIO BLOQUE: AUDITORIA DE LECTURA DEL DETALLE (ACCION LEER)
if (!isset($_POST['tcgx_cons_accion'])) {
    tcgx_auditorias_registrar_lectura(Bd::getPdo(), $idUsuarioVista, 'consolidados', $idConsolidado);
}
// FIN BLOQUE: AUDITORIA DE LECTURA DEL DETALLE
// FIN BLOQUE: ACCESO POR POST Y CARGA DEL CONSOLIDADO


// INICIO BLOQUE: PROCESAMIENTO DE ACCIONES DEL CONSOLIDADO (EN BLOQUE)
if (isset($_POST['tcgx_cons_accion'])) {
    $accion = (string) $_POST['tcgx_cons_accion'];
    $resultado = ['ok' => false, 'error' => 'ACCION NO RECONOCIDA.'];

    if ($accion === 'despachar') {
        $resultado = tcgx_consolidados_despachar(Bd::getPdo(), $tcgxCons, (string) ($_POST['guiaexterna'] ?? ''), $idUsuarioVista);
    } elseif ($accion === 'recibir') {
        $resultado = tcgx_consolidados_recibir(Bd::getPdo(), $tcgxCons, (array) ($_POST['recepcion'] ?? []), $idUsuarioVista);
    } elseif ($accion === 'cancelar') {
        $resultado = tcgx_consolidados_cancelar(Bd::getPdo(), $tcgxCons, $idUsuarioVista);
    } elseif ($accion === 'sacar') {
        $resultado = tcgx_consolidados_sacar_envio(Bd::getPdo(), $tcgxCons, (string) ($_POST['idenvio'] ?? ''), $idUsuarioVista);
    }

    $_SESSION['tcgx_consolidados_flash'] = $resultado['ok']
        ? ['tipo' => 'ok', 'texto' => 'OPERACION REALIZADA CORRECTAMENTE.']
        : ['tipo' => 'error', 'texto' => $resultado['error']];
    header('Location: consolidados.php', true, 303);
    exit;
}
// FIN BLOQUE: PROCESAMIENTO DE ACCIONES DEL CONSOLIDADO


// INICIO BLOQUE: CARGA DE DETALLE Y AGRUPACION POR ENVIO
$tcgxDetalle = tcgx_consolidados_detalle(Bd::getPdo(), $idConsolidado);

// Agrupa el detalle por envio (para la seccion de envios incluidos y la accion "sacar").
$tcgxEnviosIncluidos = [];
foreach ($tcgxDetalle as $linea) {
    $idEnvio = (string) $linea['idenvio'];
    if (!isset($tcgxEnviosIncluidos[$idEnvio])) {
        $tcgxEnviosIncluidos[$idEnvio] = [
            'idenvio' => $idEnvio,
            'nombredestinatario' => (string) $linea['nombredestinatario'],
            'nombredestino' => (string) $linea['nombredestinoenvio'],
            'estado' => (string) $linea['estadoenvio'],
            'paquetes' => 0,
        ];
    }
    $tcgxEnviosIncluidos[$idEnvio]['paquetes']++;
}

$estadoCons = (string) $tcgxCons['estado'];
$puedeDespachar = $estadoCons === TCGX_CONS_ESTADO_ARMADO;
$puedeRecibir = $estadoCons === TCGX_CONS_ESTADO_EN_TRANSITO;
$puedeCancelar = $estadoCons === TCGX_CONS_ESTADO_ARMADO;
$puedeSacar = $estadoCons === TCGX_CONS_ESTADO_ARMADO;
// FIN BLOQUE: CARGA DE DETALLE Y AGRUPACION POR ENVIO

$tcgxPageTitle = 'Detalle de consolidado | TCG EXCHANGE';
$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$estadoClase = 'text-bg-secondary';
if ($estadoCons === 'ARMADO') {
    $estadoClase = 'text-bg-info';
} elseif ($estadoCons === 'EN TRANSITO') {
    $estadoClase = 'text-bg-primary';
} elseif ($estadoCons === 'RECIBIDO') {
    $estadoClase = 'text-bg-success';
} elseif ($estadoCons === 'CANCELADO') {
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

            <!-- INICIO BLOQUE: CABECERA DEL CONSOLIDADO -->
            <div class="tcgx-admin-form-card mb-3">
               <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <h2 class="h5 mb-0"><i class="fa-solid fa-layer-group me-2" aria-hidden="true"></i><?php echo $esc($tcgxCons['id']); ?></h2>
                  <span class="badge <?php echo $estadoClase; ?> fs-6"><?php echo $esc($estadoCons); ?></span>
               </div>
               <div class="row g-3">
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Tramo</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxCons['tipotramo']); ?></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Origen</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxCons['nombreorigen']); ?></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Destino</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxCons['nombredestino']); ?></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Centro de distribución</div>
                     <div class="fw-semibold"><?php echo $esc($tcgxCons['nombrehub']); ?></div>
                  </div>
                  <div class="col-12 col-md-4">
                     <div class="text-secondary small">Guía externa</div>
                     <div class="fw-semibold"><?php echo $tcgxCons['guiaexterna'] !== null && $tcgxCons['guiaexterna'] !== '' ? $esc($tcgxCons['guiaexterna']) : '—'; ?></div>
                  </div>
                  <div class="col-12 col-md-2">
                     <div class="text-secondary small">Salida</div>
                     <div class="fw-semibold"><?php echo $tcgxCons['fechasalida'] !== null ? $esc($tcgxCons['fechasalida']) : '—'; ?></div>
                  </div>
                  <div class="col-12 col-md-2">
                     <div class="text-secondary small">Recepción</div>
                     <div class="fw-semibold"><?php echo $tcgxCons['fecharecepcion'] !== null ? $esc($tcgxCons['fecharecepcion']) : '—'; ?></div>
                  </div>
               </div>
            </div>
            <!-- FIN BLOQUE: CABECERA DEL CONSOLIDADO -->

            <!-- INICIO BLOQUE: ENVIOS INCLUIDOS (CON ACCION SACAR SI AUN NO SE DESPACHA) -->
            <div class="tcgx-admin-table-card mb-3">
               <h3 class="h6 mb-3"><i class="fa-solid fa-boxes-stacked me-2" aria-hidden="true"></i>Envíos incluidos</h3>
               <table class="table table-hover align-middle">
                  <thead>
                     <tr>
                        <th>Envío</th>
                        <th>Destinatario</th>
                        <th>Destino final</th>
                        <th>Paquetes</th>
                        <th>Estado del envío</th>
                        <?php if ($puedeSacar): ?><th class="text-end">Acción</th><?php endif; ?>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxEnviosIncluidos as $inc): ?>
                        <tr>
                           <td><?php echo $esc($inc['idenvio']); ?></td>
                           <td><?php echo $esc($inc['nombredestinatario']); ?></td>
                           <td><?php echo $esc($inc['nombredestino']); ?></td>
                           <td><?php echo (int) $inc['paquetes']; ?></td>
                           <td><?php echo $esc($inc['estado']); ?></td>
                           <?php if ($puedeSacar): ?>
                              <td class="text-end">
                                 <form method="post" action="consolidado-ver.php" class="d-inline tcgx-cons-confirm" data-tcgx-confirm="¿Sacar este envío del consolidado?">
                                    <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                                    <input type="hidden" name="id_consolidado" value="<?php echo $esc($tcgxCons['id']); ?>">
                                    <input type="hidden" name="tcgx_cons_accion" value="sacar">
                                    <input type="hidden" name="idenvio" value="<?php echo $esc($inc['idenvio']); ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Sacar del consolidado" aria-label="Sacar envío <?php echo $esc($inc['idenvio']); ?>">
                                       <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                                    </button>
                                 </form>
                              </td>
                           <?php endif; ?>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: ENVIOS INCLUIDOS -->

            <?php if ($puedeRecibir): ?>
               <!-- INICIO BLOQUE: RECEPCION PAQUETE POR PAQUETE (SOLO EN TRANSITO) -->
               <div class="tcgx-admin-form-card mb-3">
                  <h3 class="h6 mb-3"><i class="fa-solid fa-clipboard-check me-2" aria-hidden="true"></i>Recepción de paquetes</h3>
                  <form method="post" action="consolidado-ver.php" id="tcgx-cons-recibir-form" class="tcgx-cons-confirm" data-tcgx-confirm="¿Confirmar la recepción del consolidado? Los envíos avanzarán de estado en bloque.">
                     <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                     <input type="hidden" name="id_consolidado" value="<?php echo $esc($tcgxCons['id']); ?>">
                     <input type="hidden" name="tcgx_cons_accion" value="recibir">
                     <table class="table table-hover align-middle">
                        <thead>
                           <tr>
                              <th>Envío</th>
                              <th>Paquete</th>
                              <th>Tipo</th>
                              <th>Recibido correcto</th>
                              <th>Observación</th>
                           </tr>
                        </thead>
                        <tbody>
                           <?php foreach ($tcgxDetalle as $linea): ?>
                              <?php $idDet = (int) $linea['id']; ?>
                              <tr>
                                 <td><?php echo $esc($linea['idenvio']); ?></td>
                                 <td><?php echo $esc($linea['idpaquete']); ?> · <?php echo $esc($linea['descripcionpaquete'] ?? ''); ?></td>
                                 <td><?php echo $esc($linea['tipopaquete'] ?? ''); ?></td>
                                 <td>
                                    <input type="checkbox" class="form-check-input" name="recepcion[<?php echo $idDet; ?>][recibido]" value="1" checked>
                                 </td>
                                 <td>
                                    <input type="text" class="form-control text-uppercase" name="recepcion[<?php echo $idDet; ?>][observacion]" maxlength="255">
                                 </td>
                              </tr>
                           <?php endforeach; ?>
                        </tbody>
                     </table>
                     <div class="tcgx-admin-form-actions">
                        <button type="submit" class="btn btn-success">
                           <i class="fa-solid fa-clipboard-check me-2" aria-hidden="true"></i>RECIBIR CONSOLIDADO
                        </button>
                     </div>
                  </form>
               </div>
               <!-- FIN BLOQUE: RECEPCION PAQUETE POR PAQUETE -->
            <?php endif; ?>

            <!-- INICIO BLOQUE: ACCIONES DEL CONSOLIDADO -->
            <?php if ($puedeDespachar || $puedeCancelar): ?>
               <div class="tcgx-admin-form-card">
                  <h3 class="h6 mb-3"><i class="fa-solid fa-sliders me-2" aria-hidden="true"></i>Acciones del consolidado</h3>
                  <div class="row g-3 align-items-end">
                     <?php if ($puedeDespachar): ?>
                        <div class="col-12 col-lg-8">
                           <form method="post" action="consolidado-ver.php" class="tcgx-cons-confirm" data-tcgx-confirm="¿Despachar el consolidado? Los envíos pasarán a tránsito en bloque.">
                              <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                              <input type="hidden" name="id_consolidado" value="<?php echo $esc($tcgxCons['id']); ?>">
                              <input type="hidden" name="tcgx_cons_accion" value="despachar">
                              <label class="form-label" for="cons-guia">Guía externa (opcional)</label>
                              <div class="d-flex gap-2">
                                 <input type="text" class="form-control text-uppercase" id="cons-guia" name="guiaexterna" maxlength="80">
                                 <button type="submit" class="btn btn-primary text-nowrap">
                                    <i class="fa-solid fa-truck-fast me-2" aria-hidden="true"></i>DESPACHAR
                                 </button>
                              </div>
                           </form>
                        </div>
                     <?php endif; ?>
                  </div>

                  <?php if ($puedeCancelar): ?>
                     <div class="tcgx-admin-form-actions">
                        <form method="post" action="consolidado-ver.php" class="d-inline tcgx-cons-confirm" data-tcgx-confirm="¿Cancelar el consolidado completo? Los envíos volverán a su estado anterior.">
                           <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxConsCsrf); ?>">
                           <input type="hidden" name="id_consolidado" value="<?php echo $esc($tcgxCons['id']); ?>">
                           <input type="hidden" name="tcgx_cons_accion" value="cancelar">
                           <button type="submit" class="btn btn-danger">
                              <i class="fa-solid fa-ban me-2" aria-hidden="true"></i>CANCELAR CONSOLIDADO
                           </button>
                        </form>
                     </div>
                  <?php endif; ?>
               </div>
            <?php endif; ?>
            <!-- FIN BLOQUE: ACCIONES DEL CONSOLIDADO -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/consolidado-ver.js?v=20260612a"></script>
</body>
</html>
