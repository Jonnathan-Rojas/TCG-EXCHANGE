<?php
declare(strict_types=1);

/**
 * Resultado publico de rastreo por numero de envio individual (CRE). Acceso por POST; replay en sesion sin GET con datos.
 */

require __DIR__ . '/vendor/bd.php';
require_once __DIR__ . '/includes/rastreo_envio_logica.php';

// INICIO BLOQUE: SESION MINIMA PARA REPLAY SEGURO TRAS POST (SIN CODIGO EN URL)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}
// FIN BLOQUE: SESION MINIMA PARA REPLAY SEGURO TRAS POST

$pdo = Bd::getPdo();
$esc = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$tcgxTxt = static fn (mixed $v): string => mb_strtoupper(trim((string) $v), 'UTF-8');

$tcgxRastreoGuiaValor = '';
$tcgxRastreoError = null;
$tcgxRastreoEnvio = null;
$tcgxRastreoPaquetes = [];
$tcgxRastreoMovimientos = [];

$tcgxMetodo = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');

// INICIO BLOQUE: RESOLUCION DE CONSULTA (POST DESDE FORMULARIO O REPLAY EN SESION)
if ($tcgxMetodo === 'POST') {
    $tcgxRastreoGuiaValor = tcgx_rastreo_envio_normalizar((string) ($_POST['guia'] ?? ''));
    $_SESSION['tcgx_rastreo_envio_codigo'] = $tcgxRastreoGuiaValor;

    $tcgxErrorValidacion = tcgx_rastreo_envio_validar($tcgxRastreoGuiaValor);
    if ($tcgxErrorValidacion !== null) {
        $tcgxRastreoError = $tcgxErrorValidacion;
    } else {
        $tcgxResultado = tcgx_rastreo_envio_consultar($pdo, $tcgxRastreoGuiaValor);
        if (!$tcgxResultado['ok']) {
            $tcgxRastreoError = (string) $tcgxResultado['error'];
        } else {
            $tcgxRastreoEnvio = $tcgxResultado['envio'];
            $tcgxRastreoPaquetes = $tcgxResultado['paquetes'];
            $tcgxRastreoMovimientos = $tcgxResultado['movimientos'];
        }
    }
} elseif ($tcgxMetodo === 'GET') {
    if (empty($_SESSION['tcgx_rastreo_envio_codigo'])) {
        header('Location: index.php', true, 303);
        exit;
    }
    $tcgxRastreoGuiaValor = tcgx_rastreo_envio_normalizar((string) $_SESSION['tcgx_rastreo_envio_codigo']);
    $tcgxErrorValidacion = tcgx_rastreo_envio_validar($tcgxRastreoGuiaValor);
    if ($tcgxErrorValidacion !== null) {
        unset($_SESSION['tcgx_rastreo_envio_codigo']);
        header('Location: index.php', true, 303);
        exit;
    }
    $tcgxResultado = tcgx_rastreo_envio_consultar($pdo, $tcgxRastreoGuiaValor);
    if (!$tcgxResultado['ok']) {
        $tcgxRastreoError = (string) $tcgxResultado['error'];
    } else {
        $tcgxRastreoEnvio = $tcgxResultado['envio'];
        $tcgxRastreoPaquetes = $tcgxResultado['paquetes'];
        $tcgxRastreoMovimientos = $tcgxResultado['movimientos'];
    }
} else {
    header('Location: index.php', true, 303);
    exit;
}
// FIN BLOQUE: RESOLUCION DE CONSULTA

$tcgxPageTitle = 'Rastreo de envío | TCG EXCHANGE';
$tcgxMetaDescription = 'Consulta el estado de tu envio individual en la red TCG EXCHANGE.';
$tcgxBodyClass = 'tcgx-pagina-rastreo-envio';
require __DIR__ . '/includes/header.php';

$tcgxEstadoTxt = $tcgxRastreoEnvio !== null ? $tcgxTxt($tcgxRastreoEnvio['estado'] ?? '') : '';
$tcgxEstadoClase = 'tcgx-rastreo-envio-estado--activo';
if ($tcgxEstadoTxt === 'ENTREGADO') {
    $tcgxEstadoClase = 'tcgx-rastreo-envio-estado--entregado';
} elseif ($tcgxEstadoTxt === 'CANCELADO') {
    $tcgxEstadoClase = 'tcgx-rastreo-envio-estado--cancelado';
} elseif (str_starts_with($tcgxEstadoTxt, 'DEVOLUCION')) {
    $tcgxEstadoClase = 'tcgx-rastreo-envio-estado--devolucion';
}
?>

   <!-- INICIO BLOQUE: RESULTADO PUBLICO RASTREO ENVIO INDIVIDUAL -->
   <section class="contenedor-central-sec tcgx-rastreo-envio-sec">
      <div class="container">
         <div class="row justify-content-center">
            <div class="col-12 col-xl-10">

               <div class="contenedor-central-box">
                  <div class="page-content px-3 px-md-4 py-3 py-md-4">

                     <?php if ($tcgxRastreoError !== null): ?>
                        <!-- INICIO BLOQUE: MENSAJE ERROR RASTREO -->
                        <p class="tcgx-rastreo-envio-alerta tcgx-rastreo-envio-alerta--error mb-0"><?php echo $esc($tcgxRastreoError); ?></p>
                        <!-- FIN BLOQUE: MENSAJE ERROR RASTREO -->
                     <?php elseif ($tcgxRastreoEnvio !== null): ?>
                        <!-- INICIO BLOQUE: DATOS ENVIO RASTREO (ETIQUETA Y VALOR EN LA MISMA LINEA) -->
                        <dl class="tcgx-carta-detalle-datos tcgx-rastreo-envio-datos mb-4">
                           <div class="tcgx-carta-detalle-datos__fila">
                              <dt>Número de envío</dt>
                              <dd><strong><?php echo $esc($tcgxTxt($tcgxRastreoEnvio['id'] ?? '')); ?></strong></dd>
                           </div>
                           <div class="tcgx-carta-detalle-datos__fila">
                              <dt>Estado</dt>
                              <dd><span class="tcgx-rastreo-envio-estado <?php echo $esc($tcgxEstadoClase); ?>"><?php echo $esc($tcgxEstadoTxt); ?></span></dd>
                           </div>
                           <div class="tcgx-carta-detalle-datos__fila">
                              <dt>Forma de envío</dt>
                              <dd><?php echo $esc($tcgxTxt($tcgxRastreoEnvio['formaenvio'] ?? '') ?: '—'); ?></dd>
                           </div>
                           <div class="tcgx-carta-detalle-datos__fila">
                              <dt>Tienda de origen</dt>
                              <dd><?php echo $esc($tcgxTxt($tcgxRastreoEnvio['nombretiendaorigen'] ?? '') ?: '—'); ?></dd>
                           </div>
                           <div class="tcgx-carta-detalle-datos__fila">
                              <dt>Tienda de destino</dt>
                              <dd><?php echo $esc($tcgxTxt($tcgxRastreoEnvio['nombretiendadestino'] ?? '') ?: '—'); ?></dd>
                           </div>
                           <div class="tcgx-carta-detalle-datos__fila">
                              <dt>Centro de distribución</dt>
                              <dd><?php echo $esc($tcgxTxt($tcgxRastreoEnvio['nombrehub'] ?? '') ?: '—'); ?></dd>
                           </div>
                        </dl>
                        <!-- FIN BLOQUE: DATOS ENVIO RASTREO -->

                        <?php if ($tcgxRastreoPaquetes !== []): ?>
                           <!-- INICIO BLOQUE: PAQUETES DEL ENVIO (VISTA PUBLICA) -->
                           <h2 class="tcgx-rastreo-envio-subtitulo">Paquetes</h2>
                           <div class="table-responsive mb-4">
                              <table class="table table-sm align-middle tcgx-rastreo-envio-tabla mb-0">
                                 <thead>
                                    <tr>
                                       <th scope="col">Tipo</th>
                                       <th scope="col">Descripción</th>
                                       <th scope="col">Cantidad</th>
                                    </tr>
                                 </thead>
                                 <tbody>
                                    <?php foreach ($tcgxRastreoPaquetes as $tcgxPaquete): ?>
                                       <tr>
                                          <td><?php echo $esc($tcgxTxt($tcgxPaquete['tipo'] ?? '')); ?></td>
                                          <td><?php echo $esc($tcgxTxt($tcgxPaquete['descripcion'] ?? '') ?: '—'); ?></td>
                                          <td><?php echo $esc((string) (int) ($tcgxPaquete['cantidad'] ?? 0)); ?></td>
                                       </tr>
                                    <?php endforeach; ?>
                                 </tbody>
                              </table>
                           </div>
                           <!-- FIN BLOQUE: PAQUETES DEL ENVIO -->
                        <?php endif; ?>

                        <?php if ($tcgxRastreoMovimientos !== []): ?>
                           <!-- INICIO BLOQUE: TRAZABILIDAD PUBLICA DEL ENVIO -->
                           <h2 class="tcgx-rastreo-envio-subtitulo">Trazabilidad</h2>
                           <div class="table-responsive mb-3">
                              <table class="table table-sm align-middle tcgx-rastreo-envio-tabla mb-0">
                                 <thead>
                                    <tr>
                                       <th scope="col">Fecha</th>
                                       <th scope="col">Acción</th>
                                       <th scope="col">Detalle</th>
                                       <th scope="col">Guía externa</th>
                                       <th scope="col">Tienda</th>
                                    </tr>
                                 </thead>
                                 <tbody>
                                    <?php foreach ($tcgxRastreoMovimientos as $tcgxMov): ?>
                                       <tr>
                                          <td><?php echo $esc(tcgx_rastreo_envio_formatear_fecha(isset($tcgxMov['fecharegistro']) ? (string) $tcgxMov['fecharegistro'] : null)); ?></td>
                                          <td><?php echo $esc($tcgxTxt($tcgxMov['accion'] ?? '')); ?></td>
                                          <td><?php echo $esc($tcgxTxt($tcgxMov['detalle'] ?? '') ?: '—'); ?></td>
                                          <td><?php echo $esc($tcgxTxt($tcgxMov['guiaexterna'] ?? '') ?: '—'); ?></td>
                                          <td><?php echo $esc($tcgxTxt($tcgxMov['nombretienda'] ?? '') ?: '—'); ?></td>
                                       </tr>
                                    <?php endforeach; ?>
                                 </tbody>
                              </table>
                           </div>
                           <!-- FIN BLOQUE: TRAZABILIDAD PUBLICA DEL ENVIO -->
                        <?php endif; ?>
                     <?php endif; ?>

                     <!-- INICIO BLOQUE: ACCION VOLVER AL INICIO -->
                     <div class="tcgx-rastreo-envio-acciones">
                        <a href="index.php" class="btn btn-primary btn-sm">
                           <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver al inicio<span></span>
                        </a>
                     </div>
                     <!-- FIN BLOQUE: ACCION VOLVER AL INICIO -->

                  </div>
               </div>

            </div>
         </div>
      </div>
   </section>
   <!-- FIN BLOQUE: RESULTADO PUBLICO RASTREO ENVIO INDIVIDUAL -->

<?php
require __DIR__ . '/includes/footer.php';
