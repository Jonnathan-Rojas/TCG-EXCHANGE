<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de producto binder (client).
 * Compartido por alta y edicion. La pagina contenedora aporta form, token CSRF, id_binder y acciones.
 *
 * Variables requeridas:
 * - $tcgxFormModo (string): 'crear' | 'editar'.
 * - $tcgxFormValores (array): valores actuales o previos por campo.
 * - $tcgxIdBinder (int): identificador del binder contenedor.
 */

$tcgxFormModo = $tcgxFormModo ?? 'crear';
$tcgxFormValores = $tcgxFormValores ?? [];
$tcgxIdBinder = isset($tcgxIdBinder) ? (int) $tcgxIdBinder : 0;

$vId = htmlspecialchars((string) ($tcgxFormValores['id'] ?? ''), ENT_QUOTES, 'UTF-8');
$vNombreCarta = htmlspecialchars((string) ($tcgxFormValores['nombrecarta'] ?? ''), ENT_QUOTES, 'UTF-8');
$vExpansion = htmlspecialchars((string) ($tcgxFormValores['expansion'] ?? ''), ENT_QUOTES, 'UTF-8');
$vNumeroCarta = htmlspecialchars((string) ($tcgxFormValores['numerocarta'] ?? ''), ENT_QUOTES, 'UTF-8');
$vRareza = htmlspecialchars((string) ($tcgxFormValores['rareza'] ?? ''), ENT_QUOTES, 'UTF-8');
$vIdioma = mb_strtoupper(trim((string) ($tcgxFormValores['idioma'] ?? '')), 'UTF-8');
$vCondicion = mb_strtoupper(trim((string) ($tcgxFormValores['condicion'] ?? '')), 'UTF-8');
$vCantidad = htmlspecialchars((string) ($tcgxFormValores['cantidad'] ?? '1'), ENT_QUOTES, 'UTF-8');
$vPrecio = htmlspecialchars((string) ($tcgxFormValores['precioventa'] ?? ''), ENT_QUOTES, 'UTF-8');
$vMoneda = htmlspecialchars((string) ($tcgxFormValores['tipomoneda'] ?? 'COLONES'), ENT_QUOTES, 'UTF-8');
$vPublicado = (string) ($tcgxFormValores['publicado'] ?? '0');
$esEdicion = $tcgxFormModo === 'editar';
$esPublicado = $vPublicado === '1';
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE PRODUCTO (CLIENT) -->
<input type="hidden" name="id_binder" value="<?php echo htmlspecialchars((string) $tcgxIdBinder, ENT_QUOTES, 'UTF-8'); ?>">

<div class="row g-3">

   <?php if ($esEdicion): ?>
      <div class="col-12 col-md-3">
         <label class="form-label" for="producto-id">ID</label>
         <input type="text" class="form-control" id="producto-id" value="<?php echo $vId; ?>" readonly disabled>
      </div>
   <?php endif; ?>

   <div class="col-12 col-md-<?php echo $esEdicion ? '9' : '12'; ?> col-lg-3">
      <label class="form-label" for="producto-nombrecarta">Nombre de la carta</label>
      <input type="text" class="form-control text-uppercase" id="producto-nombrecarta" name="nombrecarta" maxlength="150" value="<?php echo $vNombreCarta; ?>" required>
   </div>
   <div class="col-12 col-md-4 col-lg-3">
      <label class="form-label" for="producto-expansion">Expansión</label>
      <input type="text" class="form-control text-uppercase" id="producto-expansion" name="expansion" maxlength="100" value="<?php echo $vExpansion; ?>">
   </div>
   <div class="col-12 col-md-4 col-lg-3">
      <label class="form-label" for="producto-numerocarta">Número de carta</label>
      <input type="text" class="form-control text-uppercase" id="producto-numerocarta" name="numerocarta" maxlength="40" value="<?php echo $vNumeroCarta; ?>">
   </div>
   <div class="col-12 col-md-4 col-lg-3">
      <label class="form-label" for="producto-rareza">Rareza</label>
      <input type="text" class="form-control text-uppercase" id="producto-rareza" name="rareza" maxlength="50" value="<?php echo $vRareza; ?>">
   </div>

   <!-- INICIO BLOQUE: IDIOMA DE CARTA (CATALOGO CONTROLADO) -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="producto-idioma">Idioma</label>
      <select class="form-select" id="producto-idioma" name="idioma" required>
         <option value="" <?php echo $vIdioma === '' ? 'selected' : ''; ?> disabled>SELECCIONE</option>
         <?php foreach (TCGX_CLIENT_PRODUCTO_IDIOMAS as $tcgxOpcionIdioma): ?>
            <option value="<?php echo htmlspecialchars($tcgxOpcionIdioma, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $vIdioma === $tcgxOpcionIdioma ? 'selected' : ''; ?>><?php echo htmlspecialchars($tcgxOpcionIdioma, ENT_QUOTES, 'UTF-8'); ?></option>
         <?php endforeach; ?>
      </select>
   </div>
   <!-- FIN BLOQUE: IDIOMA DE CARTA (CATALOGO CONTROLADO) -->
   <!-- INICIO BLOQUE: CONDICION DE CARTA (ESCALA TCG ESTANDAR) -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="producto-condicion">Condición</label>
      <select class="form-select" id="producto-condicion" name="condicion" required>
         <option value="" <?php echo $vCondicion === '' ? 'selected' : ''; ?> disabled>SELECCIONE</option>
         <?php foreach (TCGX_CLIENT_PRODUCTO_CONDICIONES as $tcgxOpcionCondicion): ?>
            <option value="<?php echo htmlspecialchars($tcgxOpcionCondicion, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $vCondicion === $tcgxOpcionCondicion ? 'selected' : ''; ?>><?php echo htmlspecialchars($tcgxOpcionCondicion, ENT_QUOTES, 'UTF-8'); ?></option>
         <?php endforeach; ?>
      </select>
   </div>
   <!-- FIN BLOQUE: CONDICION DE CARTA (ESCALA TCG ESTANDAR) -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="producto-cantidad">Cantidad</label>
      <input type="number" min="1" step="1" class="form-control" id="producto-cantidad" name="cantidad" value="<?php echo $vCantidad; ?>" required>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="producto-precioventa">Precio de venta</label>
      <input type="number" min="0" step="0.01" class="form-control" id="producto-precioventa" name="precioventa" value="<?php echo $vPrecio; ?>" required>
   </div>

   <div class="col-12 col-md-3">
      <label class="form-label" for="producto-tipomoneda">Moneda</label>
      <select class="form-select" id="producto-tipomoneda" name="tipomoneda" required>
         <option value="COLONES" <?php echo $vMoneda === 'COLONES' ? 'selected' : ''; ?>>COLONES</option>
         <option value="DOLARES" <?php echo $vMoneda === 'DOLARES' ? 'selected' : ''; ?>>DÓLARES</option>
      </select>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="producto-publicado">Publicado</label>
      <select class="form-select" id="producto-publicado" name="publicado" required>
         <option value="0" <?php echo $esPublicado ? '' : 'selected'; ?>>NO</option>
         <option value="1" <?php echo $esPublicado ? 'selected' : ''; ?>>SÍ</option>
      </select>
   </div>

   <?php if (!$esEdicion): ?>
      <div class="col-12 col-md-6">
         <label class="form-label" for="producto-imagenes">Imágenes (máx. 5, JPG/PNG/WEBP)</label>
         <input type="file" class="form-control" id="producto-imagenes" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple>
      </div>
   <?php endif; ?>

</div>
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE PRODUCTO (CLIENT) -->
