<?php
declare(strict_types=1);

/**
 * Parcial de campos del formulario de tarifa (compartido por alta y edicion del modulo admin).
 * No incluye la etiqueta <form> ni el boton de envio: la pagina contenedora aporta accion, hidden y acciones.
 * Maquetado denso por linea en escritorio segun convencion de formularios en diseño.md.
 * NUNCA USAR PRECIO BASE: el formulario expone un solo campo "Precio" (name=precioporpaquete); preciobase no se solicita.
 *
 * Variables requeridas:
 * - $tcgxFormModo (string): 'crear' | 'editar'.
 * - $tcgxFormValores (array): valores actuales/previos por campo (escapados aqui en salida).
 * - $tcgxTiendasOpciones (array): filas [id, nombre] de tiendas ACTIVAS para el select.
 * - $tcgxRutasOpciones (array): filas [id, nombre] de rutas ACTIVAS del catalogo para el select.
 */

$tcgxFormModo = $tcgxFormModo ?? 'crear';
$tcgxFormValores = $tcgxFormValores ?? [];
$tcgxTiendasOpciones = $tcgxTiendasOpciones ?? [];
$tcgxRutasOpciones = $tcgxRutasOpciones ?? [];

// Lecturas seguras y escapadas de cada valor previo (anti-XSS).
$vId = htmlspecialchars((string) ($tcgxFormValores['id'] ?? ''), ENT_QUOTES, 'UTF-8');
$vIdTienda = (string) ($tcgxFormValores['idtienda'] ?? '');
$vIdRuta = (string) ($tcgxFormValores['idruta'] ?? '');
// PRECIO UNICO DE LA TARIFA: SE PERSISTE EN LA COLUMNA precioporpaquete. NUNCA USAR PRECIO BASE (preciobase queda en 0 y se ignora).
$vPrecio = htmlspecialchars((string) ($tcgxFormValores['precioporpaquete'] ?? ''), ENT_QUOTES, 'UTF-8');
$esEdicion = $tcgxFormModo === 'editar';
?>
<!-- INICIO BLOQUE: CAMPOS DEL FORMULARIO DE TARIFA (UN SOLO PRECIO) -->
<div class="row g-3">

   <?php if ($esEdicion): ?>
      <!-- Identificador numerico de solo lectura (AUTO_INCREMENT en BD). -->
      <div class="col-12 col-md-3">
         <label class="form-label" for="tarifa-id">ID</label>
         <input type="text" class="form-control" id="tarifa-id" value="<?php echo $vId; ?>" readonly disabled>
      </div>
   <?php endif; ?>

   <!-- Tienda, Tipo de tramo, Precio base, Precio por paquete -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="tarifa-tienda">Tienda</label>
      <select class="form-select" id="tarifa-tienda" name="idtienda" required <?php echo $esEdicion ? '' : 'autofocus'; ?>>
         <option value="">SELECCIONE…</option>
         <?php foreach ($tcgxTiendasOpciones as $opcion): ?>
            <?php
            $opId = (string) $opcion['id'];
            $opNombre = htmlspecialchars((string) $opcion['nombre'], ENT_QUOTES, 'UTF-8');
            $opSeleccion = $opId === $vIdTienda ? 'selected' : '';
            ?>
            <option value="<?php echo htmlspecialchars($opId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $opSeleccion; ?>><?php echo $opNombre; ?></option>
         <?php endforeach; ?>
      </select>
   </div>
   <div class="col-12 col-md-3">
      <label class="form-label" for="tarifa-ruta">Ruta</label>
      <select class="form-select" id="tarifa-ruta" name="idruta" required>
         <option value="">SELECCIONE…</option>
         <?php foreach ($tcgxRutasOpciones as $opcionRuta): ?>
            <?php
            $rOpId = (string) $opcionRuta['id'];
            $rOpNombre = htmlspecialchars((string) $opcionRuta['nombre'], ENT_QUOTES, 'UTF-8');
            $rOpSeleccion = $rOpId === $vIdRuta ? 'selected' : '';
            ?>
            <option value="<?php echo htmlspecialchars($rOpId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $rOpSeleccion; ?>><?php echo $rOpNombre; ?></option>
         <?php endforeach; ?>
      </select>
   </div>
   <!-- Precio unico del envio para esa tienda y ruta (se guarda en precioporpaquete). -->
   <div class="col-12 col-md-3">
      <label class="form-label" for="tarifa-precio">Precio</label>
      <input type="number" step="0.01" min="0" class="form-control" id="tarifa-precio" name="precioporpaquete" value="<?php echo $vPrecio; ?>" required>
   </div>

</div>
<!-- FIN BLOQUE: CAMPOS DEL FORMULARIO DE TARIFA (UN SOLO PRECIO) -->
