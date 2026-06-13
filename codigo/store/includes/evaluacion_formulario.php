<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de evaluacion (store): tienda fijada por sesion (campo oculto).
 * Compartido por alta y edicion. La pagina contenedora aporta form, token CSRF y acciones.
 *
 * Variables requeridas:
 * - $tcgxFormModo (string): 'crear' | 'editar'.
 * - $tcgxFormValores (array): valores actuales/previos por campo.
 * - $idTiendaSesion (int): identificador de la tienda emisora (sesion store).
 */

$tcgxFormModo = $tcgxFormModo ?? 'crear';
$tcgxFormValores = $tcgxFormValores ?? [];
$idTiendaSesion = isset($idTiendaSesion) ? (int) $idTiendaSesion : 0;

$vId = htmlspecialchars((string) ($tcgxFormValores['id'] ?? ''), ENT_QUOTES, 'UTF-8');
$vIdUsuario = htmlspecialchars((string) ($tcgxFormValores['idusuario'] ?? ''), ENT_QUOTES, 'UTF-8');
$vNombreUsuario = htmlspecialchars((string) ($tcgxFormValores['nombreusuario'] ?? ''), ENT_QUOTES, 'UTF-8');
$vRapidez = htmlspecialchars((string) ($tcgxFormValores['rapidez'] ?? ''), ENT_QUOTES, 'UTF-8');
$vConfianza = htmlspecialchars((string) ($tcgxFormValores['confianza'] ?? ''), ENT_QUOTES, 'UTF-8');
$vSeguridad = htmlspecialchars((string) ($tcgxFormValores['seguridad'] ?? ''), ENT_QUOTES, 'UTF-8');
$vCalidad = htmlspecialchars((string) ($tcgxFormValores['calidad'] ?? ''), ENT_QUOTES, 'UTF-8');
$vListaNegra = (string) ($tcgxFormValores['listanegra'] ?? '0');
$vMotivo = htmlspecialchars((string) ($tcgxFormValores['motivolistanegra'] ?? ''), ENT_QUOTES, 'UTF-8');
$esEdicion = $tcgxFormModo === 'editar';
$enListaNegra = $vListaNegra === '1';
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE EVALUACION (STORE) -->
<div class="row g-3">

   <?php if ($esEdicion): ?>
      <div class="col-12 col-md-3">
         <label class="form-label" for="evaluacion-id">ID</label>
         <input type="text" class="form-control" id="evaluacion-id" value="<?php echo $vId; ?>" readonly disabled>
      </div>
   <?php endif; ?>

   <!-- Cliente evaluado: busqueda dinamica Select2 + AJAX POST al endpoint del modulo store. -->
   <div class="col-12 col-md-<?php echo $esEdicion ? '9' : '12'; ?>">
      <label class="form-label" for="evaluacion-usuario">Cliente evaluado</label>
      <select class="form-select tcgx-select-cliente" id="evaluacion-usuario" name="idusuario" required>
         <option value=""></option>
         <?php if ($vIdUsuario !== ''): ?>
            <option value="<?php echo $vIdUsuario; ?>" selected><?php echo $vNombreUsuario !== '' ? $vNombreUsuario . ' (' . $vIdUsuario . ')' : $vIdUsuario; ?></option>
         <?php endif; ?>
      </select>
   </div>

   <!-- Tienda emisora fijada por sesion: no se expone select; se envia como campo oculto. -->
   <input type="hidden" name="idtienda" value="<?php echo htmlspecialchars((string) $idTiendaSesion, ENT_QUOTES, 'UTF-8'); ?>">

   <div class="col-6 col-md-3">
      <label class="form-label" for="evaluacion-rapidez">Rapidez (0-5)</label>
      <input type="number" min="0" max="5" step="1" class="form-control" id="evaluacion-rapidez" name="rapidez" value="<?php echo $vRapidez; ?>" required>
   </div>
   <div class="col-6 col-md-3">
      <label class="form-label" for="evaluacion-confianza">Confianza (0-5)</label>
      <input type="number" min="0" max="5" step="1" class="form-control" id="evaluacion-confianza" name="confianza" value="<?php echo $vConfianza; ?>" required>
   </div>
   <div class="col-6 col-md-3">
      <label class="form-label" for="evaluacion-seguridad">Seguridad (0-5)</label>
      <input type="number" min="0" max="5" step="1" class="form-control" id="evaluacion-seguridad" name="seguridad" value="<?php echo $vSeguridad; ?>" required>
   </div>
   <div class="col-6 col-md-3">
      <label class="form-label" for="evaluacion-calidad">Calidad (0-5)</label>
      <input type="number" min="0" max="5" step="1" class="form-control" id="evaluacion-calidad" name="calidad" value="<?php echo $vCalidad; ?>" required>
   </div>

   <div class="col-12 col-md-3">
      <label class="form-label" for="evaluacion-listanegra">Lista negra</label>
      <select class="form-select" id="evaluacion-listanegra" name="listanegra" required>
         <option value="0" <?php echo $enListaNegra ? '' : 'selected'; ?>>NO</option>
         <option value="1" <?php echo $enListaNegra ? 'selected' : ''; ?>>SÍ</option>
      </select>
   </div>
   <div class="col-12 col-md-9">
      <label class="form-label" for="evaluacion-motivo">Motivo de lista negra</label>
      <input type="text" class="form-control text-uppercase" id="evaluacion-motivo" name="motivolistanegra" maxlength="255" value="<?php echo $vMotivo; ?>" <?php echo $enListaNegra ? 'required' : 'disabled'; ?>>
   </div>

</div>
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE EVALUACION (STORE) -->
