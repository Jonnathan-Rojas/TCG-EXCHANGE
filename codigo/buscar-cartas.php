<?php
declare(strict_types=1);

/**
 * Catalogo publico Buscar Cartas: productos publicados de binders de usuarios no listados en lista negra.
 */

require __DIR__ . '/vendor/bd.php';
require_once __DIR__ . '/includes/catalogo_publico_logica.php';

$pdo = Bd::getPdo();
$tcgxCatalogoProductos = tcgx_catalogo_publico_listar($pdo);
$tcgxFiltroTcgs = tcgx_catalogo_publico_tcgs_disponibles($pdo);

// INICIO BLOQUE: VALORES UNICOS PARA FILTROS DINAMICOS DEL CATALOGO PUBLICO
$tcgxFiltroExpansiones = [];
$tcgxFiltroRarezas = [];
$tcgxFiltroCondiciones = [];
$tcgxFiltroIdiomas = [];
foreach ($tcgxCatalogoProductos as $tcgxProdFiltro) {
    $tcgxTxtExpansion = mb_strtoupper(trim((string) ($tcgxProdFiltro['expansion'] ?? '')), 'UTF-8');
    if ($tcgxTxtExpansion !== '' && !in_array($tcgxTxtExpansion, $tcgxFiltroExpansiones, true)) {
        $tcgxFiltroExpansiones[] = $tcgxTxtExpansion;
    }
    $tcgxTxtRareza = mb_strtoupper(trim((string) ($tcgxProdFiltro['rareza'] ?? '')), 'UTF-8');
    if ($tcgxTxtRareza !== '' && !in_array($tcgxTxtRareza, $tcgxFiltroRarezas, true)) {
        $tcgxFiltroRarezas[] = $tcgxTxtRareza;
    }
    $tcgxTxtCondicion = mb_strtoupper(trim((string) ($tcgxProdFiltro['condicion'] ?? '')), 'UTF-8');
    if ($tcgxTxtCondicion !== '' && !in_array($tcgxTxtCondicion, $tcgxFiltroCondiciones, true)) {
        $tcgxFiltroCondiciones[] = $tcgxTxtCondicion;
    }
    $tcgxTxtIdioma = mb_strtoupper(trim((string) ($tcgxProdFiltro['idioma'] ?? '')), 'UTF-8');
    if ($tcgxTxtIdioma !== '' && !in_array($tcgxTxtIdioma, $tcgxFiltroIdiomas, true)) {
        $tcgxFiltroIdiomas[] = $tcgxTxtIdioma;
    }
}
sort($tcgxFiltroExpansiones, SORT_STRING);
sort($tcgxFiltroRarezas, SORT_STRING);
sort($tcgxFiltroCondiciones, SORT_STRING);
sort($tcgxFiltroIdiomas, SORT_STRING);
// FIN BLOQUE: VALORES UNICOS PARA FILTROS DINAMICOS DEL CATALOGO PUBLICO

$esc = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$tcgxPageTitle = 'Buscar Cartas | TCG EXCHANGE';
$tcgxMetaDescription = 'Catálogo público de cartas TCG publicadas por usuarios de TCG EXCHANGE. Filtra por TCG, expansión, rareza, condición e idioma.';
$tcgxOcultarRastreo = true;
$tcgxCabeceraOscura = true;
$tcgxBodyClass = 'tcgx-pagina-buscar-cartas';
require __DIR__ . '/includes/header.php';
?>

   <!-- INICIO BLOQUE: CATALOGO PUBLICO BUSCAR CARTAS -->
   <section class="contenedor-central-sec tcgx-buscar-cartas-sec">
      <div class="container">
         <div class="row justify-content-center">
            <div class="col-12">

               <?php if ($tcgxCatalogoProductos === []): ?>
                  <div class="contenedor-central-box">
                     <div class="page-content px-3 px-md-4 py-4">
                        <p class="text-secondary mb-0">NO HAY CARTAS PUBLICADAS DISPONIBLES EN ESTE MOMENTO.</p>
                     </div>
                  </div>
               <?php else: ?>
                  <!-- INICIO BLOQUE: FILTROS DINAMICOS DEL CATALOGO PUBLICO (SIN GET; FUERA DEL BOX BLANCO) -->
                  <div class="tcgx-catalogo-publico-filtro mb-2" id="tcgx-catalogo-publico-filtros">
                     <div class="tcgx-catalogo-publico-filtro__barra">
                        <div class="tcgx-catalogo-publico-filtro__campo tcgx-catalogo-publico-filtro__campo--buscar">
                           <label class="tcgx-catalogo-publico-filtro__lbl" for="tcgx-catalogo-publico-buscar">Buscar</label>
                           <input type="search" class="form-control form-control-sm text-uppercase" id="tcgx-catalogo-publico-buscar" placeholder="NOMBRE, NÚMERO, ID…" autocomplete="off" autofocus>
                        </div>
                        <div class="tcgx-catalogo-publico-filtro__campo">
                           <label class="tcgx-catalogo-publico-filtro__lbl" for="tcgx-catalogo-publico-tcg">TCG</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-publico-tcg" data-tcgx-filtro-dinamico>
                              <option value="">TODOS</option>
                              <?php foreach ($tcgxFiltroTcgs as $tcgxOptTcg): ?>
                                 <option value="<?php echo $esc($tcgxOptTcg); ?>"><?php echo $esc($tcgxOptTcg); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-catalogo-publico-filtro__campo">
                           <label class="tcgx-catalogo-publico-filtro__lbl" for="tcgx-catalogo-publico-expansion">Expansión</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-publico-expansion" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxFiltroExpansiones as $tcgxOptExpansion): ?>
                                 <option value="<?php echo $esc($tcgxOptExpansion); ?>"><?php echo $esc($tcgxOptExpansion); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-catalogo-publico-filtro__campo">
                           <label class="tcgx-catalogo-publico-filtro__lbl" for="tcgx-catalogo-publico-rareza">Rareza</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-publico-rareza" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxFiltroRarezas as $tcgxOptRareza): ?>
                                 <option value="<?php echo $esc($tcgxOptRareza); ?>"><?php echo $esc($tcgxOptRareza); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-catalogo-publico-filtro__campo">
                           <label class="tcgx-catalogo-publico-filtro__lbl" for="tcgx-catalogo-publico-condicion">Condición</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-publico-condicion" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxFiltroCondiciones as $tcgxOptCondicion): ?>
                                 <option value="<?php echo $esc($tcgxOptCondicion); ?>"><?php echo $esc($tcgxOptCondicion); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-catalogo-publico-filtro__campo">
                           <label class="tcgx-catalogo-publico-filtro__lbl" for="tcgx-catalogo-publico-idioma">Idioma</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-publico-idioma" data-tcgx-filtro-dinamico>
                              <option value="">TODOS</option>
                              <?php foreach ($tcgxFiltroIdiomas as $tcgxOptIdioma): ?>
                                 <option value="<?php echo $esc($tcgxOptIdioma); ?>"><?php echo $esc($tcgxOptIdioma); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-catalogo-publico-filtro__acciones">
                           <span class="tcgx-catalogo-publico-filtro__contador" id="tcgx-catalogo-publico-contador"><?php echo $esc((string) count($tcgxCatalogoProductos)); ?> / <?php echo $esc((string) count($tcgxCatalogoProductos)); ?></span>
                           <button type="button" class="btn btn-primary btn-sm" id="tcgx-catalogo-publico-limpiar">Limpiar filtros</button>
                        </div>
                     </div>
                  </div>
                  <p class="text-secondary d-none mb-2" id="tcgx-catalogo-publico-sin-resultados">NINGÚN PRODUCTO COINCIDE CON EL FILTRO.</p>
                  <!-- FIN BLOQUE: FILTROS DINAMICOS DEL CATALOGO PUBLICO -->

                  <div class="contenedor-central-box">
                     <div class="page-content px-3 px-md-4 py-3">
                        <div class="tcgx-catalogo-publico-grid" id="tcgx-catalogo-publico">
                           <?php foreach ($tcgxCatalogoProductos as $prod): ?>
                              <?php
                              $urlImagen = isset($prod['url_imagen']) && $prod['url_imagen'] !== null
                                  ? $esc((string) $prod['url_imagen'])
                                  : '';
                              $tcgxBuscarCarta = mb_strtoupper(implode(' ', array_filter([
                                  (string) ($prod['id'] ?? ''),
                                  (string) ($prod['nombrecarta'] ?? ''),
                                  (string) ($prod['expansion'] ?? ''),
                                  (string) ($prod['numerocarta'] ?? ''),
                                  (string) ($prod['rareza'] ?? ''),
                                  (string) ($prod['idioma'] ?? ''),
                                  (string) ($prod['condicion'] ?? ''),
                                  (string) ($prod['tipomoneda'] ?? ''),
                                  (string) ($prod['juego'] ?? ''),
                              ], static fn ($v): bool => trim($v) !== '')), 'UTF-8');
                              $tcgxTcgCarta = mb_strtoupper(trim((string) ($prod['juego'] ?? '')), 'UTF-8');
                              $tcgxWhatsappMsg = tcgx_catalogo_publico_mensaje_whatsapp($prod);
                              $tcgxWhatsappUrl = tcgx_catalogo_publico_whatsapp_enlace(
                                  isset($prod['telefono']) ? (string) $prod['telefono'] : '',
                                  $tcgxWhatsappMsg
                              );
                              ?>
                              <form method="post" action="carta-detalle.php" class="tcgx-catalogo-publico-carta"
                                 data-tcgx-buscar="<?php echo $esc($tcgxBuscarCarta); ?>"
                                 data-tcgx-tcg="<?php echo $esc($tcgxTcgCarta); ?>"
                                 data-tcgx-expansion="<?php echo $esc(mb_strtoupper(trim((string) ($prod['expansion'] ?? '')), 'UTF-8')); ?>"
                                 data-tcgx-rareza="<?php echo $esc(mb_strtoupper(trim((string) ($prod['rareza'] ?? '')), 'UTF-8')); ?>"
                                 data-tcgx-condicion="<?php echo $esc(mb_strtoupper(trim((string) ($prod['condicion'] ?? '')), 'UTF-8')); ?>"
                                 data-tcgx-idioma="<?php echo $esc(mb_strtoupper(trim((string) ($prod['idioma'] ?? '')), 'UTF-8')); ?>"
                                 data-tcgx-moneda="<?php echo $esc(mb_strtoupper(trim((string) ($prod['tipomoneda'] ?? '')), 'UTF-8')); ?>"
                                 data-tcgx-precio="<?php echo $esc(number_format((float) ($prod['precioventa'] ?? 0), 2, '.', '')); ?>">
                                 <input type="hidden" name="id_producto" value="<?php echo $esc((string) ($prod['id'] ?? '')); ?>">
                                 <?php if ($tcgxWhatsappUrl !== null): ?>
                                    <a href="<?php echo $esc($tcgxWhatsappUrl); ?>"
                                       class="tcgx-catalogo-publico-carta__whatsapp"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       aria-label="Contactar al propietario por WhatsApp"
                                       title="Contactar al propietario">
                                       <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                                    </a>
                                 <?php endif; ?>
                                 <button type="submit" class="tcgx-catalogo-publico-carta__click">
                                 <div class="tcgx-catalogo-publico-carta__img">
                                    <?php if ($urlImagen !== ''): ?>
                                       <img src="<?php echo $urlImagen; ?>" alt="<?php echo $esc($prod['nombrecarta']); ?>" loading="lazy">
                                    <?php else: ?>
                                       <span class="tcgx-catalogo-publico-carta__placeholder" aria-hidden="true"><i class="fa-solid fa-image"></i></span>
                                    <?php endif; ?>
                                 </div>
                                 <div class="tcgx-catalogo-publico-carta__body">
                                    <div class="tcgx-catalogo-publico-carta__tcg"><?php echo $esc($prod['juego']); ?></div>
                                    <div class="tcgx-catalogo-publico-carta__nombre"><?php echo $esc($prod['nombrecarta']); ?></div>
                                    <div class="tcgx-catalogo-publico-carta__expansion"><?php echo $esc($prod['expansion'] ?? '—'); ?></div>
                                    <div class="tcgx-catalogo-publico-carta__precio">
                                       <?php echo $esc(number_format((float) $prod['precioventa'], 2, '.', ',')); ?>
                                       <?php echo $esc($prod['tipomoneda']); ?>
                                    </div>
                                 </div>
                                 </button>
                              </form>
                           <?php endforeach; ?>
                        </div>
                     </div>
                  </div>
               <?php endif; ?>

            </div>
         </div>
      </div>
   </section>
   <!-- FIN BLOQUE: CATALOGO PUBLICO BUSCAR CARTAS -->

<?php
echo '<script src="vendor/js/buscar-cartas.js?v=20260612f"></script>' . "\n";
require __DIR__ . '/includes/footer.php';
