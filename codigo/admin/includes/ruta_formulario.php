<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de ruta (catalogo) compartido por alta y edicion del modulo admin.
 * No incluye la etiqueta <form> ni el boton de envio: la pagina contenedora aporta accion, hidden y acciones.
 * Maquetado denso (varios controles por linea en escritorio) segun convencion de formularios en diseño.md.
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
$vMedioEnvio = htmlspecialchars((string) ($tcgxFormValores['medioenvio'] ?? ''), ENT_QUOTES, 'UTF-8');
$vExigeGuia = (string) ($tcgxFormValores['exigeguiaexterna'] ?? '0');
$esEdicion = $tcgxFormModo === 'editar';
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE RUTA (CATALOGO) -->
<div class="row g-3">

   <?php if ($esEdicion): ?>
      <!-- Identificador numerico de solo lectura (AUTO_INCREMENT en BD). -->
      <div class="col-12 col-md-3">
         <label class="form-label" for="ruta-id">ID</label>
         <input type="text" class="form-control" id="ruta-id" value="<?php echo $vId; ?>" readonly disabled>
      </div>
   <?php endif; ?>

   <!-- Nombre del tipo de ruta, Medio de envio, Exige guia externa -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="ruta-nombre">Nombre</label>
      <input type="text" class="form-control text-uppercase" id="ruta-nombre" name="nombre" maxlength="40" value="<?php echo $vNombre; ?>" required <?php echo $esEdicion ? '' : 'autofocus'; ?>>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="ruta-medioenvio">Medio de envío</label>
      <input type="text" class="form-control text-uppercase" id="ruta-medioenvio" name="medioenvio" maxlength="80" value="<?php echo $vMedioEnvio; ?>" required>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="ruta-exigeguia">Exige guía externa</label>
      <select class="form-select" id="ruta-exigeguia" name="exigeguiaexterna" required>
         <option value="0" <?php echo $vExigeGuia !== '1' ? 'selected' : ''; ?>>NO</option>
         <option value="1" <?php echo $vExigeGuia === '1' ? 'selected' : ''; ?>>SÍ</option>
      </select>
   </div>

</div>
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE RUTA (CATALOGO) -->
