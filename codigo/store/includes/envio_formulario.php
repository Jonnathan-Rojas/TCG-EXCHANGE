<?php
declare(strict_types=1);

/**
 * Formulario de REGISTRO DE ENVIO INDIVIDUAL (store). Origen fijo a la tienda de sesion (solo lectura).
 * Se incluye dentro del <form> de envio-registrar.php.
 *
 * Variables esperadas:
 *   $idTiendaSesion, $tcgxStoreNombreTienda, $tcgxRutas, $tcgxHubUnico,
 *   $tcgxTiendasDestino, $tcgxFormValores, $tcgxPaquetesPrev.
 */

$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$vForma = (string) ($tcgxFormValores['formaenvio'] ?? '');
$vDestino = (string) ($tcgxFormValores['idtiendadestino'] ?? '');
$vMonto = $esc((string) ($tcgxFormValores['montoapagar'] ?? ''));
$vRemitente = (string) ($tcgxFormValores['idremitente'] ?? '');
$vRemitenteNombre = (string) ($tcgxFormValores['nombreremitente'] ?? '');
$vDestinatario = (string) ($tcgxFormValores['iddestinatario'] ?? '');
$vDestinatarioNombre = (string) ($tcgxFormValores['nombredestinatario'] ?? '');

$paquetesPrev = (isset($tcgxPaquetesPrev) && is_array($tcgxPaquetesPrev) && !empty($tcgxPaquetesPrev))
    ? $tcgxPaquetesPrev
    : [['tipo' => '', 'descripcion' => '', 'cantidad' => '', 'valordeclarado' => '']];
?>

<!-- INICIO BLOQUE: DATOS GENERALES DEL ENVIO (ORIGEN FIJO) -->
<div class="row g-3">
   <div class="col-12 col-md-3">
      <label class="form-label" for="envio-forma">Forma de envío</label>
      <select class="form-select" id="envio-forma" name="formaenvio" required autofocus>
         <option value="">SELECCIONE…</option>
         <?php foreach ($tcgxRutas as $r): ?>
            <?php
            $rNombre = (string) $r['nombre'];
            $rMedio = (string) ($r['medioenvio'] ?? '');
            ?>
            <option value="<?php echo $esc($rNombre); ?>" data-medioenvio="<?php echo $esc($rMedio); ?>" <?php echo $rNombre === $vForma ? 'selected' : ''; ?>><?php echo $esc($rNombre); ?></option>
         <?php endforeach; ?>
      </select>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="envio-origen">Tienda de origen</label>
      <!-- Origen fijo a la tienda de sesion: hidden para POST y campo visible solo lectura. -->
      <input type="hidden" name="idtiendaorigen" id="envio-origen-hidden" value="<?php echo $esc((string) $idTiendaSesion); ?>">
      <input type="text" class="form-control" id="envio-origen" value="<?php echo $esc($tcgxStoreNombreTienda); ?>" readonly>
   </div>
   <div class="col-12 col-md-3" id="envio-destino-wrap">
      <label class="form-label" for="envio-destino">Tienda de destino</label>
      <select class="form-select" id="envio-destino" name="idtiendadestino">
         <option value="">SELECCIONE…</option>
         <?php foreach ($tcgxTiendasDestino as $t): ?>
            <?php $tid = (string) $t['id']; ?>
            <option value="<?php echo $esc($tid); ?>" <?php echo $tid === $vDestino ? 'selected' : ''; ?>><?php echo $esc($t['nombre']); ?></option>
         <?php endforeach; ?>
      </select>
   </div>
   <div class="col-12 col-md-3 d-none" id="envio-hub-wrap">
      <label class="form-label" for="envio-hub">Centro de distribución</label>
      <input type="text" class="form-control" id="envio-hub" value="<?php echo $tcgxHubUnico ? $esc((string) $tcgxHubUnico['nombre']) : 'SIN CENTRO DE DISTRIBUCIÓN ACTIVO'; ?>" readonly>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="envio-monto">Monto a pagar (automático)</label>
      <input type="number" min="0" step="0.01" class="form-control" id="envio-monto" name="montoapagar" value="<?php echo $vMonto; ?>" readonly>
      <div class="form-text text-danger" id="envio-monto-aviso"></div>
   </div>
</div>
<!-- FIN BLOQUE: DATOS GENERALES DEL ENVIO -->

<!-- INICIO BLOQUE: PERSONAS (REMITENTE / DESTINATARIO) -->
<div class="row g-3 mt-1">
   <div class="col-12 col-md-6">
      <label class="form-label" for="envio-remitente">Remitente (cliente)</label>
      <select class="form-select" id="envio-remitente" name="idremitente" required>
         <?php if ($vRemitente !== ''): ?>
            <option value="<?php echo $esc($vRemitente); ?>" selected><?php echo $esc(($vRemitenteNombre !== '' ? $vRemitenteNombre . ' ' : '') . '(' . $vRemitente . ')'); ?></option>
         <?php endif; ?>
      </select>
   </div>
   <div class="col-12 col-md-6">
      <label class="form-label" for="envio-destinatario">Destinatario (cliente)</label>
      <select class="form-select" id="envio-destinatario" name="iddestinatario" required>
         <?php if ($vDestinatario !== ''): ?>
            <option value="<?php echo $esc($vDestinatario); ?>" selected><?php echo $esc(($vDestinatarioNombre !== '' ? $vDestinatarioNombre . ' ' : '') . '(' . $vDestinatario . ')'); ?></option>
         <?php endif; ?>
      </select>
   </div>
</div>
<!-- FIN BLOQUE: PERSONAS -->

<!-- INICIO BLOQUE: PAQUETES (FILAS DINAMICAS) -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 mb-2">
   <h3 class="h6 mb-0"><i class="fa-solid fa-box me-2" aria-hidden="true"></i>Paquetes</h3>
   <button type="button" class="btn btn-outline-primary btn-sm" id="tcgx-paquete-agregar">
      <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Agregar paquete
   </button>
</div>

<div id="tcgx-paquetes-contenedor">
   <?php $indicePaquete = 0; ?>
   <?php foreach ($paquetesPrev as $pq): ?>
      <?php
      $pTipo = (string) ($pq['tipo'] ?? '');
      $pDesc = $esc((string) ($pq['descripcion'] ?? ''));
      $pCant = $esc((string) ($pq['cantidad'] ?? ''));
      $pVal = $esc((string) ($pq['valordeclarado'] ?? ''));
      ?>
      <div class="row g-2 align-items-end tcgx-paquete-fila mb-2">
         <div class="col-12 col-md-3">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="paquete_tipo[]">
               <option value="">SELECCIONE…</option>
               <?php foreach (TCGX_ENVIOS_TIPOS_PAQUETE as $tipo): ?>
                  <option value="<?php echo $esc($tipo); ?>" <?php echo $tipo === $pTipo ? 'selected' : ''; ?>><?php echo $esc($tipo); ?></option>
               <?php endforeach; ?>
            </select>
         </div>
         <div class="col-12 col-md-4">
            <label class="form-label">Descripción</label>
            <input type="text" class="form-control text-uppercase" name="paquete_descripcion[]" maxlength="255" value="<?php echo $pDesc; ?>">
         </div>
         <div class="col-6 col-md-2">
            <label class="form-label">Cantidad</label>
            <input type="number" min="1" step="1" class="form-control" name="paquete_cantidad[]" value="<?php echo $pCant; ?>">
         </div>
         <div class="col-6 col-md-2">
            <label class="form-label">Valor declarado</label>
            <input type="number" min="0" step="0.01" class="form-control" name="paquete_valor[]" value="<?php echo $pVal; ?>">
         </div>
         <div class="col-12 col-md-1 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm tcgx-paquete-quitar" title="Quitar paquete" aria-label="Quitar paquete">
               <i class="fa-solid fa-trash" aria-hidden="true"></i>
            </button>
         </div>
         <div class="col-12">
            <label class="form-label">Imágenes (evidencia, opcional)</label>
            <input type="file" class="form-control tcgx-paquete-imagenes" name="paquete_imagenes[<?php echo $indicePaquete; ?>][]" accept="image/jpeg,image/png,image/webp" multiple>
         </div>
      </div>
      <?php $indicePaquete++; ?>
   <?php endforeach; ?>
</div>
<!-- FIN BLOQUE: PAQUETES -->
