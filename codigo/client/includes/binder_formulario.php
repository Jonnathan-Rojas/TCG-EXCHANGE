<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de binder (client): TCG, nombre y descripcion.
 * Compartido por alta y edicion. La pagina contenedora aporta form, token CSRF y acciones.
 *
 * Variables requeridas:
 * - $tcgxFormModo (string): 'crear' | 'editar'.
 * - $tcgxFormValores (array): valores actuales o previos por campo.
 */

$tcgxFormModo = $tcgxFormModo ?? 'crear';
$tcgxFormValores = $tcgxFormValores ?? [];

$vId = htmlspecialchars((string) ($tcgxFormValores['id'] ?? ''), ENT_QUOTES, 'UTF-8');
$vJuego = htmlspecialchars((string) ($tcgxFormValores['juego'] ?? ''), ENT_QUOTES, 'UTF-8');
$vNombre = htmlspecialchars((string) ($tcgxFormValores['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$vDescripcion = htmlspecialchars((string) ($tcgxFormValores['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
$esEdicion = $tcgxFormModo === 'editar';
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE BINDER (CLIENT) -->
<div class="row g-3">

   <?php if ($esEdicion): ?>
      <div class="col-12 col-md-3">
         <label class="form-label" for="binder-id">ID</label>
         <input type="text" class="form-control" id="binder-id" value="<?php echo $vId; ?>" readonly disabled>
      </div>
   <?php endif; ?>

   <div class="col-12 col-md-<?php echo $esEdicion ? '3' : '4'; ?>">
      <label class="form-label" for="binder-juego">TCG</label>
      <input type="text" class="form-control text-uppercase" id="binder-juego" name="juego" maxlength="50" value="<?php echo $vJuego; ?>" required>
   </div>
   <div class="col-12 col-md-<?php echo $esEdicion ? '3' : '4'; ?>">
      <label class="form-label" for="binder-nombre">Nombre</label>
      <input type="text" class="form-control text-uppercase" id="binder-nombre" name="nombre" maxlength="120" value="<?php echo $vNombre; ?>" required>
   </div>
   <div class="col-12 col-md-<?php echo $esEdicion ? '3' : '4'; ?>">
      <label class="form-label" for="binder-descripcion">Descripción</label>
      <input type="text" class="form-control text-uppercase" id="binder-descripcion" name="descripcion" maxlength="255" value="<?php echo $vDescripcion; ?>">
   </div>

</div>
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE BINDER (CLIENT) -->
