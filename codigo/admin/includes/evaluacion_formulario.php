<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de evaluacion (reputacion por usuario) compartido por alta y edicion.
 * No incluye la etiqueta <form> ni el boton de envio: la pagina contenedora aporta accion, hidden y acciones.
 * Maquetado denso (varios controles por linea en escritorio) segun convencion de formularios en diseño.md.
 *
 * Variables requeridas:
 * - $tcgxFormModo (string): 'crear' | 'editar'.
 * - $tcgxFormValores (array): valores actuales/previos por campo (escapados aqui en salida).
 * - $tcgxTiendasOpciones (array): filas [id, nombre] de tiendas ACTIVAS para el select de la tienda emisora.
 *
 * El cliente evaluado se identifica por su CEDULA digitada (no por select): la validacion de servidor
 * comprueba que exista y sea CLIENTE. En edicion se muestra ademas su nombre resuelto.
 */

$tcgxFormModo = $tcgxFormModo ?? 'crear';
$tcgxFormValores = $tcgxFormValores ?? [];
$tcgxTiendasOpciones = $tcgxTiendasOpciones ?? [];

// Lecturas seguras de cada valor previo (anti-XSS en la salida).
$vId = htmlspecialchars((string) ($tcgxFormValores['id'] ?? ''), ENT_QUOTES, 'UTF-8');
$vIdUsuario = htmlspecialchars((string) ($tcgxFormValores['idusuario'] ?? ''), ENT_QUOTES, 'UTF-8');
$vNombreUsuario = htmlspecialchars((string) ($tcgxFormValores['nombreusuario'] ?? ''), ENT_QUOTES, 'UTF-8');
$vIdTienda = (string) ($tcgxFormValores['idtienda'] ?? '');
$vRapidez = htmlspecialchars((string) ($tcgxFormValores['rapidez'] ?? ''), ENT_QUOTES, 'UTF-8');
$vConfianza = htmlspecialchars((string) ($tcgxFormValores['confianza'] ?? ''), ENT_QUOTES, 'UTF-8');
$vSeguridad = htmlspecialchars((string) ($tcgxFormValores['seguridad'] ?? ''), ENT_QUOTES, 'UTF-8');
$vCalidad = htmlspecialchars((string) ($tcgxFormValores['calidad'] ?? ''), ENT_QUOTES, 'UTF-8');
$vListaNegra = (string) ($tcgxFormValores['listanegra'] ?? '0');
$vMotivo = htmlspecialchars((string) ($tcgxFormValores['motivolistanegra'] ?? ''), ENT_QUOTES, 'UTF-8');
$esEdicion = $tcgxFormModo === 'editar';
$enListaNegra = $vListaNegra === '1';
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE EVALUACION -->
<div class="row g-3">

   <?php if ($esEdicion): ?>
      <!-- Identificador numerico de solo lectura (AUTO_INCREMENT en BD). -->
      <div class="col-12 col-md-3">
         <label class="form-label" for="evaluacion-id">ID</label>
         <input type="text" class="form-control" id="evaluacion-id" value="<?php echo $vId; ?>" readonly disabled>
      </div>
   <?php endif; ?>

   <!-- Cliente evaluado: busqueda dinamica por nombre/cedula (Select2 + AJAX POST). Debe ser CLIENTE (validacion en servidor).
        El <select> nace vacio; sus opciones llegan por AJAX. En edicion/reintento se precarga la opcion actual seleccionada. -->
   <div class="col-12 col-md-<?php echo $esEdicion ? '4' : '6'; ?>">
      <label class="form-label" for="evaluacion-usuario">Cliente evaluado</label>
      <select class="form-select tcgx-select-cliente" id="evaluacion-usuario" name="idusuario" required>
         <!-- Opcion vacia requerida por Select2 para el placeholder y el boton de limpiar. -->
         <option value=""></option>
         <?php if ($vIdUsuario !== ''): ?>
            <option value="<?php echo $vIdUsuario; ?>" selected><?php echo $vNombreUsuario !== '' ? $vNombreUsuario . ' (' . $vIdUsuario . ')' : $vIdUsuario; ?></option>
         <?php endif; ?>
      </select>
   </div>

   <!-- Tienda emisora: el administrador puede elegir cualquier tienda. -->
   <div class="col-12 col-md-<?php echo $esEdicion ? '5' : '6'; ?>">
      <label class="form-label" for="evaluacion-tienda">Tienda emisora</label>
      <select class="form-select" id="evaluacion-tienda" name="idtienda" required>
         <option value="">SELECCIONE…</option>
         <?php foreach ($tcgxTiendasOpciones as $opcionTienda): ?>
            <?php
            $tOpId = (string) $opcionTienda['id'];
            $tOpNombre = htmlspecialchars((string) $opcionTienda['nombre'], ENT_QUOTES, 'UTF-8');
            $tOpSeleccion = $tOpId === $vIdTienda ? 'selected' : '';
            ?>
            <option value="<?php echo htmlspecialchars($tOpId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $tOpSeleccion; ?>><?php echo $tOpNombre; ?></option>
         <?php endforeach; ?>
      </select>
   </div>

   <!-- Cuatro criterios calificables (0 a 5). -->
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

   <!-- Lista negra y su motivo (el motivo es obligatorio solo cuando se marca lista negra). -->
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
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE EVALUACION -->
