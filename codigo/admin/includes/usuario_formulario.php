<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de usuario (compartido por alta y edicion del modulo admin).
 * No incluye la etiqueta <form> ni el boton de envio: la pagina contenedora aporta accion, hidden y acciones.
 * Maquetado denso (4 controles por linea en escritorio) segun convencion de formularios en diseño.md.
 * El estado NO se edita aqui: nace ACTIVO y se cambia con el boton Activar/Desactivar del listado.
 *
 * Variables requeridas:
 * - $tcgxFormModo (string): 'crear' | 'editar'.
 * - $tcgxFormValores (array): valores actuales/previos por campo (escapados aqui en salida).
 * - $tcgxFormTiendas (array): filas [id, nombre] para el select de tienda.
 */

$tcgxFormModo = $tcgxFormModo ?? 'crear';
$tcgxFormValores = $tcgxFormValores ?? [];
$tcgxFormTiendas = $tcgxFormTiendas ?? [];

// Lecturas seguras y escapadas de cada valor previo (anti-XSS).
$vId = htmlspecialchars((string) ($tcgxFormValores['id'] ?? ''), ENT_QUOTES, 'UTF-8');
$vNombre = htmlspecialchars((string) ($tcgxFormValores['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$vCorreo = htmlspecialchars((string) ($tcgxFormValores['correo'] ?? ''), ENT_QUOTES, 'UTF-8');
$vTelefono = htmlspecialchars((string) ($tcgxFormValores['telefono'] ?? ''), ENT_QUOTES, 'UTF-8');
$vPerfil = (string) ($tcgxFormValores['perfil'] ?? '');
$vIdTienda = (string) ($tcgxFormValores['idtienda'] ?? '');
$vProvincia = htmlspecialchars((string) ($tcgxFormValores['provincia'] ?? ''), ENT_QUOTES, 'UTF-8');
$vCanton = htmlspecialchars((string) ($tcgxFormValores['canton'] ?? ''), ENT_QUOTES, 'UTF-8');
$vDistrito = htmlspecialchars((string) ($tcgxFormValores['distrito'] ?? ''), ENT_QUOTES, 'UTF-8');
$vDireccion = htmlspecialchars((string) ($tcgxFormValores['direccion'] ?? ''), ENT_QUOTES, 'UTF-8');
$esEdicion = $tcgxFormModo === 'editar';
// El campo tienda solo aplica a perfil TIENDA; queda oculto en el resto (lo controla el JS al cambiar el perfil).
$tiendaOculta = $vPerfil !== 'TIENDA';
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE USUARIO (4 CONTROLES POR LINEA) -->
<div class="row g-3">

   <!-- Linea 1: Cedula, Nombre, Correo electronico, Perfil -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="usuario-id">Cédula</label>
      <input type="text" class="form-control text-uppercase" id="usuario-id" name="id" maxlength="20"
         value="<?php echo $vId; ?>"
         <?php echo $esEdicion ? 'readonly' : 'required autofocus autocapitalize="characters"'; ?>>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="usuario-nombre">Nombre</label>
      <input type="text" class="form-control text-uppercase" id="usuario-nombre" name="nombre" maxlength="120" value="<?php echo $vNombre; ?>" required>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="usuario-correo">Correo electrónico</label>
      <input type="email" class="form-control text-lowercase" id="usuario-correo" name="correo" maxlength="150" value="<?php echo $vCorreo; ?>" required>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="usuario-perfil">Perfil</label>
      <select class="form-select" id="usuario-perfil" name="perfil" required>
         <option value="">SELECCIONE…</option>
         <?php foreach (TCGX_USUARIOS_PERFILES as $perfilOpcion): ?>
            <option value="<?php echo htmlspecialchars($perfilOpcion, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $vPerfil === $perfilOpcion ? 'selected' : ''; ?>>
               <?php echo htmlspecialchars($perfilOpcion, ENT_QUOTES, 'UTF-8'); ?>
            </option>
         <?php endforeach; ?>
      </select>
   </div>

   <!-- Tienda: solo perfil TIENDA; fluye en linea (sin crear una fila casi vacia). JS muestra/oculta. -->
   <div class="col-12 col-md-3 tcgx-campo-tienda <?php echo $tiendaOculta ? 'd-none' : ''; ?>" id="usuario-campo-tienda">
      <label class="form-label" for="usuario-idtienda">Tienda</label>
      <select class="form-select" id="usuario-idtienda" name="idtienda" <?php echo $tiendaOculta ? '' : 'required'; ?>>
         <option value="">SELECCIONE…</option>
         <?php foreach ($tcgxFormTiendas as $tienda): ?>
            <option value="<?php echo (int) $tienda['id']; ?>" <?php echo $vIdTienda !== '' && (string) $tienda['id'] === $vIdTienda ? 'selected' : ''; ?>>
               <?php echo htmlspecialchars((string) $tienda['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
         <?php endforeach; ?>
      </select>
   </div>

   <!-- Linea 2: Telefono, Provincia, Canton, Distrito -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="usuario-telefono">Teléfono</label>
      <input type="text" class="form-control" id="usuario-telefono" name="telefono" maxlength="20" value="<?php echo $vTelefono; ?>" required>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="usuario-provincia">Provincia</label>
      <select class="form-select" id="usuario-provincia" name="provincia" data-tcgx-selected="<?php echo $vProvincia; ?>">
         <option value="">SELECCIONE…</option>
      </select>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="usuario-canton">Cantón</label>
      <select class="form-select" id="usuario-canton" name="canton" data-tcgx-selected="<?php echo $vCanton; ?>">
         <option value="">SELECCIONE…</option>
      </select>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="usuario-distrito">Distrito</label>
      <select class="form-select" id="usuario-distrito" name="distrito" data-tcgx-selected="<?php echo $vDistrito; ?>">
         <option value="">SELECCIONE…</option>
      </select>
   </div>

   <!-- Linea 3: Direccion (ancho completo) -->
   <div class="col-12">
      <label class="form-label" for="usuario-direccion">Dirección</label>
      <input type="text" class="form-control text-uppercase" id="usuario-direccion" name="direccion" maxlength="255" value="<?php echo $vDireccion; ?>">
   </div>

</div>
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE USUARIO (4 CONTROLES POR LINEA) -->
