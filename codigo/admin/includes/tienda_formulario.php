<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de tienda (compartido por alta y edicion del modulo admin).
 * No incluye la etiqueta <form> ni el boton de envio: la pagina contenedora aporta accion, hidden y acciones.
 * Maquetado denso (4 controles por linea en escritorio) segun convencion de formularios en diseño.md.
 * El estado NO se edita aqui: nace ACTIVO y se cambia con el boton Activar/Desactivar del listado.
 *
 * Variables requeridas:
 * - $tcgxFormModo (string): 'crear' | 'editar'.
 * - $tcgxFormValores (array): valores actuales/previos por campo (escapados aqui en salida).
 */

$tcgxFormModo = $tcgxFormModo ?? 'crear';
$tcgxFormValores = $tcgxFormValores ?? [];

// Lecturas seguras y escapadas de cada valor previo (anti-XSS).
$vId = htmlspecialchars((string) ($tcgxFormValores['id'] ?? ''), ENT_QUOTES, 'UTF-8');
$vNombre = htmlspecialchars((string) ($tcgxFormValores['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$vCorreo = htmlspecialchars((string) ($tcgxFormValores['correo'] ?? ''), ENT_QUOTES, 'UTF-8');
$vTelefono = htmlspecialchars((string) ($tcgxFormValores['telefono'] ?? ''), ENT_QUOTES, 'UTF-8');
$vProvincia = htmlspecialchars((string) ($tcgxFormValores['provincia'] ?? ''), ENT_QUOTES, 'UTF-8');
$vCanton = htmlspecialchars((string) ($tcgxFormValores['canton'] ?? ''), ENT_QUOTES, 'UTF-8');
$vDistrito = htmlspecialchars((string) ($tcgxFormValores['distrito'] ?? ''), ENT_QUOTES, 'UTF-8');
$vDireccion = htmlspecialchars((string) ($tcgxFormValores['direccion'] ?? ''), ENT_QUOTES, 'UTF-8');
$vEshub = (string) ($tcgxFormValores['eshub'] ?? '0');
$esEdicion = $tcgxFormModo === 'editar';
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE TIENDA (4 CONTROLES POR LINEA) -->
<div class="row g-3">

   <?php if ($esEdicion): ?>
      <!-- Identificador numerico de solo lectura (AUTO_INCREMENT en BD). -->
      <div class="col-12 col-md-3">
         <label class="form-label" for="tienda-id">ID</label>
         <input type="text" class="form-control" id="tienda-id" value="<?php echo $vId; ?>" readonly disabled>
      </div>
   <?php endif; ?>

   <!-- Nombre, Correo, Teléfono, Centro de distribución -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="tienda-nombre">Nombre</label>
      <input type="text" class="form-control text-uppercase" id="tienda-nombre" name="nombre" maxlength="120" value="<?php echo $vNombre; ?>" required <?php echo $esEdicion ? '' : 'autofocus'; ?>>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="tienda-correo">Correo electrónico</label>
      <input type="email" class="form-control text-lowercase" id="tienda-correo" name="correo" maxlength="150" value="<?php echo $vCorreo; ?>" required>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="tienda-telefono">Teléfono</label>
      <input type="text" class="form-control" id="tienda-telefono" name="telefono" maxlength="20" value="<?php echo $vTelefono; ?>" required>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="tienda-eshub">Centro de distribución</label>
      <select class="form-select" id="tienda-eshub" name="eshub" required>
         <option value="0" <?php echo $vEshub !== '1' ? 'selected' : ''; ?>>NO</option>
         <option value="1" <?php echo $vEshub === '1' ? 'selected' : ''; ?>>SÍ</option>
      </select>
   </div>

   <!-- Provincia, Cantón, Distrito -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="tienda-provincia">Provincia</label>
      <select class="form-select" id="tienda-provincia" name="provincia" data-tcgx-selected="<?php echo $vProvincia; ?>" required>
         <option value="">SELECCIONE…</option>
      </select>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="tienda-canton">Cantón</label>
      <select class="form-select" id="tienda-canton" name="canton" data-tcgx-selected="<?php echo $vCanton; ?>" required>
         <option value="">SELECCIONE…</option>
      </select>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="tienda-distrito">Distrito</label>
      <select class="form-select" id="tienda-distrito" name="distrito" data-tcgx-selected="<?php echo $vDistrito; ?>" required>
         <option value="">SELECCIONE…</option>
      </select>
   </div>

   <!-- Dirección (ancho completo) -->
   <div class="col-12">
      <label class="form-label" for="tienda-direccion">Dirección</label>
      <input type="text" class="form-control text-uppercase" id="tienda-direccion" name="direccion" maxlength="255" value="<?php echo $vDireccion; ?>" required>
   </div>

</div>
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE TIENDA (4 CONTROLES POR LINEA) -->
