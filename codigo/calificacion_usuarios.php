<?php
declare(strict_types=1);

/**
 * Listado publico de clientes calificados: orden alfabetico y busqueda por nombre en cliente (sin GET).
 */

require __DIR__ . '/vendor/bd.php';
require_once __DIR__ . '/includes/calificacion_usuarios_logica.php';

$pdo = Bd::getPdo();
$tcgxClientesCalificados = tcgx_calificacion_usuarios_listar($pdo);

// INICIO BLOQUE: VALORES UNICOS PARA FILTROS DINAMICOS DE CALIFICACION PUBLICA
$tcgxFiltroProvincias = [];
$tcgxFiltroCantones = [];
$tcgxCantonesPorProvincia = [];
foreach ($tcgxClientesCalificados as $tcgxCliFiltro) {
    $tcgxTxtProv = mb_strtoupper(trim((string) ($tcgxCliFiltro['provincia_buscar'] ?? '')), 'UTF-8');
    $tcgxTxtCant = mb_strtoupper(trim((string) ($tcgxCliFiltro['canton_buscar'] ?? '')), 'UTF-8');
    if ($tcgxTxtProv !== '' && !in_array($tcgxTxtProv, $tcgxFiltroProvincias, true)) {
        $tcgxFiltroProvincias[] = $tcgxTxtProv;
    }
    if ($tcgxTxtCant !== '' && !in_array($tcgxTxtCant, $tcgxFiltroCantones, true)) {
        $tcgxFiltroCantones[] = $tcgxTxtCant;
    }
    if ($tcgxTxtProv !== '' && $tcgxTxtCant !== '') {
        if (!isset($tcgxCantonesPorProvincia[$tcgxTxtProv])) {
            $tcgxCantonesPorProvincia[$tcgxTxtProv] = [];
        }
        if (!in_array($tcgxTxtCant, $tcgxCantonesPorProvincia[$tcgxTxtProv], true)) {
            $tcgxCantonesPorProvincia[$tcgxTxtProv][] = $tcgxTxtCant;
        }
    }
}
sort($tcgxFiltroProvincias, SORT_STRING);
sort($tcgxFiltroCantones, SORT_STRING);
foreach ($tcgxCantonesPorProvincia as $tcgxProvKey => $tcgxCantArr) {
    sort($tcgxCantonesPorProvincia[$tcgxProvKey], SORT_STRING);
}
// FIN BLOQUE: VALORES UNICOS PARA FILTROS DINAMICOS DE CALIFICACION PUBLICA

$esc = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$tcgxPageTitle = 'Calificación de Usuarios | TCG EXCHANGE';
$tcgxMetaDescription = 'Consulta la calificación pública de clientes evaluados en la red TCG EXCHANGE.';
$tcgxOcultarRastreo = true;
$tcgxCabeceraOscura = true;
$tcgxBodyClass = 'tcgx-pagina-calificacion-usuarios';
require __DIR__ . '/includes/header.php';
?>

   <!-- INICIO BLOQUE: LISTADO PUBLICO CALIFICACION DE USUARIOS -->
   <section class="contenedor-central-sec tcgx-calificacion-usuarios-sec">
      <div class="container">
         <div class="row justify-content-center">
            <div class="col-12">

               <?php if ($tcgxClientesCalificados === []): ?>
                  <div class="contenedor-central-box">
                     <div class="page-content px-3 px-md-4 py-4">
                        <p class="text-secondary mb-0">NO HAY CLIENTES CALIFICADOS DISPONIBLES EN ESTE MOMENTO.</p>
                     </div>
                  </div>
               <?php else: ?>
                  <!-- INICIO BLOQUE: FILTROS DINAMICOS CALIFICACION PUBLICA (SIN GET) -->
                  <div class="tcgx-calificacion-usuarios-filtro mb-2" id="tcgx-calificacion-usuarios-filtros">
                     <div class="tcgx-calificacion-usuarios-filtro__barra">
                        <div class="tcgx-calificacion-usuarios-filtro__campo tcgx-calificacion-usuarios-filtro__campo--buscar">
                           <label class="tcgx-calificacion-usuarios-filtro__lbl" for="tcgx-calificacion-usuarios-buscar">Buscar</label>
                           <input type="search" class="form-control form-control-sm text-uppercase" id="tcgx-calificacion-usuarios-buscar" placeholder="NOMBRE DEL CLIENTE" autocomplete="off" autofocus>
                        </div>
                        <div class="tcgx-calificacion-usuarios-filtro__campo">
                           <label class="tcgx-calificacion-usuarios-filtro__lbl" for="tcgx-calificacion-usuarios-provincia">Provincia</label>
                           <select class="form-select form-select-sm" id="tcgx-calificacion-usuarios-provincia" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxFiltroProvincias as $tcgxOptProv): ?>
                                 <option value="<?php echo $esc($tcgxOptProv); ?>"><?php echo $esc($tcgxOptProv); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-calificacion-usuarios-filtro__campo">
                           <label class="tcgx-calificacion-usuarios-filtro__lbl" for="tcgx-calificacion-usuarios-canton">Cantón</label>
                           <select class="form-select form-select-sm" id="tcgx-calificacion-usuarios-canton" data-tcgx-filtro-dinamico>
                              <option value="">TODOS</option>
                              <?php foreach ($tcgxFiltroCantones as $tcgxOptCant): ?>
                                 <option value="<?php echo $esc($tcgxOptCant); ?>"><?php echo $esc($tcgxOptCant); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-calificacion-usuarios-filtro__campo">
                           <label class="tcgx-calificacion-usuarios-filtro__lbl" for="tcgx-calificacion-usuarios-reputacion">Reputación mín.</label>
                           <select class="form-select form-select-sm" id="tcgx-calificacion-usuarios-reputacion" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php for ($tcgxPunt = 5; $tcgxPunt >= 0; $tcgxPunt--): ?>
                                 <option value="<?php echo $esc((string) $tcgxPunt); ?>"><?php echo $esc((string) $tcgxPunt); ?></option>
                              <?php endfor; ?>
                           </select>
                        </div>
                        <div class="tcgx-calificacion-usuarios-filtro__acciones">
                           <span class="tcgx-calificacion-usuarios-filtro__contador" id="tcgx-calificacion-usuarios-contador"><?php echo $esc((string) count($tcgxClientesCalificados)); ?> / <?php echo $esc((string) count($tcgxClientesCalificados)); ?></span>
                           <button type="button" class="btn btn-primary btn-sm" id="tcgx-calificacion-usuarios-limpiar">Limpiar filtros</button>
                        </div>
                     </div>
                  </div>
                  <?php if ($tcgxCantonesPorProvincia !== []): ?>
                     <script type="application/json" id="tcgx-calificacion-usuarios-cantones-json"><?php
                        echo json_encode($tcgxCantonesPorProvincia, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
                     ?></script>
                  <?php endif; ?>
                  <p class="text-secondary d-none mb-2" id="tcgx-calificacion-usuarios-sin-resultados">NINGÚN CLIENTE COINCIDE CON EL FILTRO.</p>
                  <!-- FIN BLOQUE: FILTROS DINAMICOS CALIFICACION PUBLICA -->

                  <div class="contenedor-central-box">
                     <div class="page-content px-3 px-md-4 py-3">
                        <div class="table-responsive">
                           <table class="table table-sm align-middle tcgx-calificacion-usuarios-tabla mb-0" id="tcgx-calificacion-usuarios-lista">
                              <thead>
                                 <tr>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Provincia</th>
                                    <th scope="col">Cantón</th>
                                    <th scope="col">Rapidez</th>
                                    <th scope="col">Confianza</th>
                                    <th scope="col">Seguridad</th>
                                    <th scope="col">Calidad</th>
                                    <th scope="col">Reputación</th>
                                 </tr>
                              </thead>
                              <tbody>
                                 <?php foreach ($tcgxClientesCalificados as $tcgxCliente): ?>
                                    <tr class="tcgx-calificacion-usuarios-fila"
                                       data-tcgx-nombre="<?php echo $esc($tcgxCliente['nombre_buscar'] ?? ''); ?>"
                                       data-tcgx-provincia="<?php echo $esc($tcgxCliente['provincia_buscar'] ?? ''); ?>"
                                       data-tcgx-canton="<?php echo $esc($tcgxCliente['canton_buscar'] ?? ''); ?>"
                                       data-tcgx-rapidez="<?php echo $esc((string) (int) ($tcgxCliente['rapidez'] ?? 0)); ?>"
                                       data-tcgx-confianza="<?php echo $esc((string) (int) ($tcgxCliente['confianza'] ?? 0)); ?>"
                                       data-tcgx-seguridad="<?php echo $esc((string) (int) ($tcgxCliente['seguridad'] ?? 0)); ?>"
                                       data-tcgx-calidad="<?php echo $esc((string) (int) ($tcgxCliente['calidad'] ?? 0)); ?>"
                                       data-tcgx-reputacion="<?php echo $esc(number_format((float) ($tcgxCliente['reputacion'] ?? 0), 1, '.', '')); ?>">
                                       <td><?php echo $esc(mb_strtoupper(trim((string) ($tcgxCliente['nombreusuario'] ?? '')), 'UTF-8')); ?></td>
                                       <td><?php echo $esc($tcgxCliente['provincia_buscar'] ?? '—'); ?></td>
                                       <td><?php echo $esc($tcgxCliente['canton_buscar'] ?? '—'); ?></td>
                                       <td><?php echo $esc((string) (int) ($tcgxCliente['rapidez'] ?? 0)); ?></td>
                                       <td><?php echo $esc((string) (int) ($tcgxCliente['confianza'] ?? 0)); ?></td>
                                       <td><?php echo $esc((string) (int) ($tcgxCliente['seguridad'] ?? 0)); ?></td>
                                       <td><?php echo $esc((string) (int) ($tcgxCliente['calidad'] ?? 0)); ?></td>
                                       <td><strong><?php echo $esc(number_format((float) ($tcgxCliente['reputacion'] ?? 0), 1, '.', '')); ?></strong></td>
                                    </tr>
                                 <?php endforeach; ?>
                              </tbody>
                           </table>
                        </div>
                     </div>
                  </div>
               <?php endif; ?>

            </div>
         </div>
      </div>
   </section>
   <!-- FIN BLOQUE: LISTADO PUBLICO CALIFICACION DE USUARIOS -->

<?php
echo '<script src="vendor/js/calificacion-usuarios.js?v=20260612d"></script>' . "\n";
require __DIR__ . '/includes/footer.php';
