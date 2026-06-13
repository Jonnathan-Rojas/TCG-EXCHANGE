<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de registro de incidencia (admin).
 * Variables requeridas: $tcgxFormValores (array), $tcgxTiendasInc (array id/nombre).
 */

$tcgxFormValores = $tcgxFormValores ?? [];
$tcgxTiendasInc = $tcgxTiendasInc ?? [];

$vEnvio = htmlspecialchars((string) ($tcgxFormValores['idenvio'] ?? ''), ENT_QUOTES, 'UTF-8');
$vTipo = htmlspecialchars((string) ($tcgxFormValores['tipoincidencia'] ?? ''), ENT_QUOTES, 'UTF-8');
$vDetalle = htmlspecialchars((string) ($tcgxFormValores['detalleinicial'] ?? ''), ENT_QUOTES, 'UTF-8');
$vTienda = (string) ($tcgxFormValores['idtiendareporta'] ?? '');
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE INCIDENCIA -->
<div class="row g-3">
   <div class="col-12 col-md-4">
      <label class="form-label" for="inc-idenvio">Código de envío</label>
      <input type="text" class="form-control text-uppercase" id="inc-idenvio" name="idenvio" maxlength="17" value="<?php echo $vEnvio; ?>" required autofocus placeholder="CRE...">
   </div>
   <div class="col-12 col-md-4">
      <label class="form-label" for="inc-tipo">Tipo de incidencia</label>
      <input type="text" class="form-control text-uppercase" id="inc-tipo" name="tipoincidencia" maxlength="60" value="<?php echo $vTipo; ?>" required>
   </div>
   <div class="col-12 col-md-4">
      <label class="form-label" for="inc-tienda">Tienda que reporta</label>
      <select class="form-select" id="inc-tienda" name="idtiendareporta" required>
         <option value="">SELECCIONE…</option>
         <?php foreach ($tcgxTiendasInc as $t): ?>
            <?php $tid = (string) $t['id']; ?>
            <option value="<?php echo htmlspecialchars($tid, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $tid === $vTienda ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $t['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
         <?php endforeach; ?>
      </select>
   </div>
   <div class="col-12">
      <label class="form-label" for="inc-detalle">Detalle inicial</label>
      <input type="text" class="form-control text-uppercase" id="inc-detalle" name="detalleinicial" maxlength="255" value="<?php echo $vDetalle; ?>" required>
   </div>
</div>
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE INCIDENCIA -->
