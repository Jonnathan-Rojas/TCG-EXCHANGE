<?php
declare(strict_types=1);

/**
 * Detalle publico de una carta del catalogo Buscar Cartas (acceso por POST id_producto).
 */

require __DIR__ . '/vendor/bd.php';
require_once __DIR__ . '/includes/catalogo_publico_logica.php';

// INICIO BLOQUE: SESION MINIMA PARA REPLAY SEGURO TRAS POST (SIN ID EN URL)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}
// FIN BLOQUE: SESION MINIMA PARA REPLAY SEGURO TRAS POST

// INICIO BLOQUE: RESOLUCION DEL ID DE PRODUCTO (POST O REPLAY EN SESION)
$tcgxIdProducto = 0;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tcgxIdRaw = trim((string) ($_POST['id_producto'] ?? ''));
    if ($tcgxIdRaw !== '' && ctype_digit($tcgxIdRaw)) {
        $tcgxIdProducto = (int) $tcgxIdRaw;
        $_SESSION['tcgx_catalogo_publico_detalle_id'] = (string) $tcgxIdProducto;
    }
} elseif (!empty($_SESSION['tcgx_catalogo_publico_detalle_id'])) {
    $tcgxIdRaw = trim((string) $_SESSION['tcgx_catalogo_publico_detalle_id']);
    if ($tcgxIdRaw !== '' && ctype_digit($tcgxIdRaw)) {
        $tcgxIdProducto = (int) $tcgxIdRaw;
    }
}

if ($tcgxIdProducto <= 0) {
    header('Location: buscar-cartas.php');
    exit;
}
// FIN BLOQUE: RESOLUCION DEL ID DE PRODUCTO

$pdo = Bd::getPdo();
$tcgxProducto = tcgx_catalogo_publico_obtener($pdo, $tcgxIdProducto);

if ($tcgxProducto === null) {
    unset($_SESSION['tcgx_catalogo_publico_detalle_id']);
    header('Location: buscar-cartas.php');
    exit;
}

$esc = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$tcgxTxt = static fn (mixed $v): string => mb_strtoupper(trim((string) $v), 'UTF-8');

$tcgxWhatsappMsg = tcgx_catalogo_publico_mensaje_whatsapp($tcgxProducto);
$tcgxWhatsappUrl = tcgx_catalogo_publico_whatsapp_enlace(
    isset($tcgxProducto['telefono']) ? (string) $tcgxProducto['telefono'] : '',
    $tcgxWhatsappMsg
);

$tcgxNombreCarta = $tcgxTxt($tcgxProducto['nombrecarta'] ?? '');
$tcgxPrecioPublico = tcgx_catalogo_publico_precio_formateado(
    (float) ($tcgxProducto['precioventa'] ?? 0),
    (string) ($tcgxProducto['tipomoneda'] ?? '')
);
$tcgxPageTitle = $tcgxNombreCarta . ' | Buscar Cartas | TCG EXCHANGE';
$tcgxMetaDescription = 'Detalle de carta TCG publicada en el catálogo público de TCG EXCHANGE.';
$tcgxOcultarRastreo = true;
$tcgxCabeceraOscura = true;
$tcgxBodyClass = 'tcgx-pagina-carta-detalle';
require __DIR__ . '/includes/header.php';
?>

   <!-- INICIO BLOQUE: DETALLE PUBLICO DE CARTA -->
   <section class="contenedor-central-sec tcgx-carta-detalle-sec">
      <div class="container">
         <div class="row justify-content-center">
            <div class="col-12 col-xl-10">

               <div class="contenedor-central-box">
                  <div class="page-content px-3 px-md-4 py-3 py-md-4">
                     <div class="row g-4 align-items-start">
                        <div class="col-md-5 col-lg-4">
                           <div class="tcgx-carta-detalle-galeria">
                              <?php if (($tcgxProducto['imagenes'] ?? []) !== []): ?>
                                 <div class="tcgx-carta-detalle-galeria__principal">
                                    <img src="<?php echo $esc((string) $tcgxProducto['url_imagen']); ?>"
                                       alt="<?php echo $esc($tcgxNombreCarta); ?>"
                                       id="tcgx-carta-detalle-imagen-principal">
                                 </div>
                                 <?php if (count($tcgxProducto['imagenes']) > 1): ?>
                                    <div class="tcgx-carta-detalle-galeria__miniaturas">
                                       <?php foreach ($tcgxProducto['imagenes'] as $tcgxIdxImg => $tcgxUrlImg): ?>
                                          <button type="button"
                                             class="tcgx-carta-detalle-galeria__mini<?php echo $tcgxIdxImg === 0 ? ' is-active' : ''; ?>"
                                             data-tcgx-imagen-detalle="<?php echo $esc($tcgxUrlImg); ?>"
                                             aria-label="Ver imagen <?php echo $esc((string) ($tcgxIdxImg + 1)); ?>">
                                             <img src="<?php echo $esc($tcgxUrlImg); ?>" alt="">
                                          </button>
                                       <?php endforeach; ?>
                                    </div>
                                 <?php endif; ?>
                              <?php else: ?>
                                 <div class="tcgx-carta-detalle-galeria__principal tcgx-carta-detalle-galeria__principal--vacia">
                                    <span aria-hidden="true"><i class="fa-solid fa-image"></i></span>
                                 </div>
                              <?php endif; ?>
                           </div>
                        </div>
                        <div class="col-md-7 col-lg-8">
                           <!-- INICIO BLOQUE: DATOS DETALLE CARTA (ETIQUETA Y VALOR EN LA MISMA LINEA; ORDEN FIJO) -->
                           <dl class="tcgx-carta-detalle-datos mb-4">
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>TCG</dt>
                                 <dd><?php echo $esc($tcgxTxt($tcgxProducto['juego'] ?? '') ?: '—'); ?></dd>
                              </div>
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>Carta</dt>
                                 <dd><?php echo $esc($tcgxNombreCarta); ?></dd>
                              </div>
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>Expansión</dt>
                                 <dd><?php echo $esc($tcgxTxt($tcgxProducto['expansion'] ?? '') ?: '—'); ?></dd>
                              </div>
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>Número</dt>
                                 <dd><?php echo $esc($tcgxTxt($tcgxProducto['numerocarta'] ?? '') ?: '—'); ?></dd>
                              </div>
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>Rareza</dt>
                                 <dd><?php echo $esc($tcgxTxt($tcgxProducto['rareza'] ?? '') ?: '—'); ?></dd>
                              </div>
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>Condición</dt>
                                 <dd><?php echo $esc($tcgxTxt($tcgxProducto['condicion'] ?? '') ?: '—'); ?></dd>
                              </div>
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>Idioma</dt>
                                 <dd><?php echo $esc($tcgxTxt($tcgxProducto['idioma'] ?? '') ?: '—'); ?></dd>
                              </div>
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>Cantidad</dt>
                                 <dd><?php echo $esc((string) ($tcgxProducto['cantidad'] ?? '')); ?></dd>
                              </div>
                              <div class="tcgx-carta-detalle-datos__fila">
                                 <dt>Precio</dt>
                                 <dd class="tcgx-carta-detalle-datos__precio"><?php echo $esc($tcgxPrecioPublico); ?></dd>
                              </div>
                           </dl>
                           <!-- FIN BLOQUE: DATOS DETALLE CARTA -->

                           <!-- INICIO BLOQUE: ACCIONES DETALLE CARTA (VOLVER Y WHATSAPP EN LA MISMA FILA) -->
                           <div class="tcgx-carta-detalle-acciones">
                              <a href="buscar-cartas.php" class="btn btn-primary btn-sm tcgx-carta-detalle-volver__btn">
                                 <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver al catálogo<span></span>
                              </a>
                              <?php if ($tcgxWhatsappUrl !== null): ?>
                                 <a href="<?php echo $esc($tcgxWhatsappUrl); ?>"
                                    class="btn btn-success btn-sm tcgx-carta-detalle-whatsapp"
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                                    Contactar a Propietario
                                 </a>
                              <?php endif; ?>
                           </div>
                           <!-- FIN BLOQUE: ACCIONES DETALLE CARTA -->
                        </div>
                     </div>
                  </div>
               </div>

            </div>
         </div>
      </div>
   </section>
   <!-- FIN BLOQUE: DETALLE PUBLICO DE CARTA -->

<?php
echo '<script src="vendor/js/carta-detalle.js?v=20260612a"></script>' . "\n";
require __DIR__ . '/includes/footer.php';
