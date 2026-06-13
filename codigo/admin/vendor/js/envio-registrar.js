/**
 * Formulario de REGISTRO DE ENVIO INDIVIDUAL (admin).
 * Responsabilidades: busqueda dinamica de remitente/destinatario (Select2 + AJAX POST por nombre),
 * comportamiento EN TIENDA (oculta destino y muestra el centro de distribucion informativo),
 * precio automatico desde la tarifa de la tienda de origen, filas dinamicas de paquetes,
 * validacion en cliente y despliegue de errores de servidor.
 * Depende de jQuery, Select2 y SweetAlert2 cargados antes en la pagina.
 */
(function () {
	'use strict';

	var formulario = document.getElementById('tcgx-envio-form');
	if (!formulario) {
		return;
	}
	var tokenInput = formulario.querySelector('input[name="tcgx_csrf_token"]');
	var token = tokenInput ? tokenInput.value : '';

	var selForma = document.getElementById('envio-forma');
	var selOrigen = document.getElementById('envio-origen');
	var selDestino = document.getElementById('envio-destino');
	var wrapDestino = document.getElementById('envio-destino-wrap');
	var wrapHub = document.getElementById('envio-hub-wrap');


	/* === BLOQUE SELECT2 DE CLIENTES (REMITENTE / DESTINATARIO) POR NOMBRE: INICIO === */
	function iniciarSelect2Cliente(idSelect, placeholder) {
		if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
			return;
		}
		var $select = jQuery('#' + idSelect);
		if ($select.length === 0) {
			return;
		}
		$select.select2({
			theme: 'bootstrap-5',
			width: '100%',
			placeholder: placeholder,
			allowClear: true,
			minimumInputLength: 1,
			language: {
				inputTooShort: function () { return 'INGRESE AL MENOS 1 CARÁCTER'; },
				searching: function () { return 'BUSCANDO…'; },
				noResults: function () { return 'SIN RESULTADOS'; },
				errorLoading: function () { return 'NO SE PUDO CARGAR'; }
			},
			ajax: {
				url: 'envio-buscar-clientes.php',
				type: 'POST',
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return { q: params.term || '', tcgx_csrf_token: token };
				},
				processResults: function (data) {
					return { results: (data && data.results) ? data.results : [] };
				},
				cache: true
			}
		});
	}
	iniciarSelect2Cliente('envio-remitente', 'BUSQUE EL REMITENTE POR NOMBRE…');
	iniciarSelect2Cliente('envio-destinatario', 'BUSQUE EL DESTINATARIO POR NOMBRE…');
	/* === BLOQUE SELECT2 DE CLIENTES: FIN === */


	/* === BLOQUE COMPORTAMIENTO POR FORMA DE ENVIO (RUTA): INICIO ===
	 * EN TIENDA: el paquete no sale de la tienda (destino = origen, sin centro de distribucion).
	 * Otras rutas: destino seleccionable y centro de distribucion (unico) mostrado como informacion.
	 */
	function sincronizarForma() {
		if (!selForma) {
			return;
		}
		var esEnTienda = (selForma.value === 'EN TIENDA');
		var opcion = selForma.options[selForma.selectedIndex];
		var medio = opcion ? (opcion.getAttribute('data-medioenvio') || '') : '';
		var esDirecto = medio.toUpperCase() === 'DIRECTO';
		var inpHub = document.getElementById('envio-hub');

		if (esEnTienda) {
			if (wrapDestino) { wrapDestino.classList.add('d-none'); }
			if (selDestino && selOrigen) { selDestino.value = selOrigen.value; }
			if (wrapHub) { wrapHub.classList.add('d-none'); }
		} else {
			if (wrapDestino) { wrapDestino.classList.remove('d-none'); }
			if (wrapHub) {
				if (esDirecto) {
					wrapHub.classList.remove('d-none');
					if (inpHub) { inpHub.value = 'NO APLICA (ENVÍO DIRECTO)'; }
				} else {
					wrapHub.classList.remove('d-none');
				}
			}
		}
	}

	if (selForma) {
		selForma.addEventListener('change', function () {
			sincronizarForma();
			traerPrecio();
		});
	}
	if (selOrigen) {
		selOrigen.addEventListener('change', function () {
			if (selForma && selForma.value === 'EN TIENDA' && selDestino) {
				selDestino.value = selOrigen.value;
			}
			traerPrecio();
		});
	}
	/* === BLOQUE COMPORTAMIENTO POR FORMA DE ENVIO: FIN === */


	/* === BLOQUE PRECIO AUTOMATICO DESDE LA TARIFA (POST + CSRF): INICIO ===
	 * El monto se toma de la tarifa (precio unico) de la tienda de ORIGEN para la forma de envio elegida.
	 * Es solo lectura: el servidor lo recalcula al guardar; aqui es vista previa para el administrador.
	 */
	function traerPrecio() {
		var inpMonto = document.getElementById('envio-monto');
		var aviso = document.getElementById('envio-monto-aviso');
		if (!inpMonto || !selOrigen || !selForma) {
			return;
		}
		var idtienda = selOrigen.value;
		var forma = selForma.value;
		if (aviso) { aviso.textContent = ''; }
		if (idtienda === '' || forma === '') {
			inpMonto.value = '';
			return;
		}
		var datos = new URLSearchParams();
		datos.append('idtienda', idtienda);
		datos.append('formaenvio', forma);
		datos.append('tcgx_csrf_token', token);

		fetch('envio-tarifa.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: datos.toString()
		}).then(function (respuesta) {
			return respuesta.json();
		}).then(function (data) {
			if (data && data.ok && data.precio !== null && typeof data.precio !== 'undefined') {
				inpMonto.value = data.precio;
				if (aviso) { aviso.textContent = ''; }
			} else {
				inpMonto.value = '';
				if (aviso) { aviso.textContent = 'No hay tarifa registrada para esa tienda y forma de envío.'; }
			}
		}).catch(function () {
			inpMonto.value = '';
			if (aviso) { aviso.textContent = 'No se pudo obtener la tarifa.'; }
		});
	}
	/* === BLOQUE PRECIO AUTOMATICO DESDE LA TARIFA: FIN === */


	/* === BLOQUE FILAS DINAMICAS DE PAQUETES: INICIO === */
	var contenedor = document.getElementById('tcgx-paquetes-contenedor');
	var btnAgregar = document.getElementById('tcgx-paquete-agregar');

	function plantillaFila() {
		var fila = document.querySelector('.tcgx-paquete-fila');
		if (!fila) {
			return null;
		}
		var clon = fila.cloneNode(true);
		// Limpia los valores del clon (incluido el campo de archivos de imagenes).
		clon.querySelectorAll('select, input').forEach(function (campo) {
			if (campo.tagName === 'SELECT') {
				campo.selectedIndex = 0;
			} else {
				campo.value = '';
			}
		});
		return clon;
	}

	// Renumera el campo de imagenes de cada fila (paquete_imagenes[i][]) segun su orden en el DOM,
	// para que el indice coincida con la posicion del paquete que el servidor procesa.
	function renumerarPaquetes() {
		if (!contenedor) {
			return;
		}
		contenedor.querySelectorAll('.tcgx-paquete-fila').forEach(function (fila, i) {
			var inputImg = fila.querySelector('.tcgx-paquete-imagenes');
			if (inputImg) {
				inputImg.name = 'paquete_imagenes[' + i + '][]';
			}
		});
	}

	if (btnAgregar && contenedor) {
		btnAgregar.addEventListener('click', function () {
			var nueva = plantillaFila();
			if (nueva) {
				contenedor.appendChild(nueva);
				renumerarPaquetes();
			}
		});
		// Delegacion para quitar filas (deja siempre al menos una).
		contenedor.addEventListener('click', function (evento) {
			var quitar = evento.target.closest('.tcgx-paquete-quitar');
			if (!quitar) {
				return;
			}
			var filas = contenedor.querySelectorAll('.tcgx-paquete-fila');
			if (filas.length <= 1) {
				return;
			}
			var fila = quitar.closest('.tcgx-paquete-fila');
			if (fila) {
				fila.remove();
				renumerarPaquetes();
			}
		});
	}
	/* === BLOQUE FILAS DINAMICAS DE PAQUETES: FIN === */


	/* === BLOQUE VALIDACION EN CLIENTE: INICIO === */
	function valor(id) {
		var el = document.getElementById(id);
		return el ? el.value.trim() : '';
	}

	function contarPaquetesValidos() {
		if (!contenedor) {
			return 0;
		}
		var validos = 0;
		contenedor.querySelectorAll('.tcgx-paquete-fila').forEach(function (fila) {
			var tipo = fila.querySelector('select[name="paquete_tipo[]"]');
			var cant = fila.querySelector('input[name="paquete_cantidad[]"]');
			var val = fila.querySelector('input[name="paquete_valor[]"]');
			if (tipo && tipo.value !== '' && cant && cant.value !== '' && val && val.value !== '') {
				validos++;
			}
		});
		return validos;
	}

	formulario.addEventListener('submit', function (evento) {
		// Asegura que los indices de imagenes esten alineados con el orden de las filas antes de enviar.
		renumerarPaquetes();

		var errores = [];
		var esEnTienda = selForma && selForma.value === 'EN TIENDA';

		if (valor('envio-forma') === '') {
			errores.push('Debe seleccionar la forma de envío.');
		}
		if (valor('envio-origen') === '') {
			errores.push('Debe seleccionar la tienda de origen.');
		}
		if (!esEnTienda && valor('envio-destino') === '') {
			errores.push('Debe seleccionar la tienda de destino.');
		}
		if (valor('envio-remitente') === '') {
			errores.push('Debe seleccionar el remitente.');
		}
		if (valor('envio-destinatario') === '') {
			errores.push('Debe seleccionar el destinatario.');
		}
		if (valor('envio-monto') === '') {
			errores.push('No hay tarifa para la tienda y forma de envío seleccionadas.');
		}
		if (contarPaquetesValidos() < 1) {
			errores.push('Debe registrar al menos un paquete.');
		}

		if (errores.length > 0) {
			evento.preventDefault();
			if (typeof window.Swal !== 'undefined') {
				Swal.fire({
					icon: 'error',
					title: 'Revise los datos',
					html: '<ul class="text-start mb-0">' + errores.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul>',
					confirmButtonText: 'ACEPTAR'
				});
			}
		}
	});
	/* === BLOQUE VALIDACION EN CLIENTE: FIN === */


	/* === BLOQUE ERRORES DE SERVIDOR (REINTENTO): INICIO === */
	function mostrarErroresServidor() {
		var nodo = document.getElementById('tcgx-form-flash');
		if (!nodo || typeof window.Swal === 'undefined') {
			return;
		}
		var datos;
		try {
			datos = JSON.parse(nodo.textContent || '{}');
		} catch (e) {
			return;
		}
		if (datos.errores && datos.errores.length > 0) {
			Swal.fire({
				icon: 'error',
				title: 'Revise los datos',
				html: '<ul class="text-start mb-0">' + datos.errores.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul>',
				confirmButtonText: 'ACEPTAR'
			});
		}
	}
	mostrarErroresServidor();
	/* === BLOQUE ERRORES DE SERVIDOR (REINTENTO): FIN === */


	/* === BLOQUE ARRANQUE: INICIO === */
	sincronizarForma();
	traerPrecio();
	renumerarPaquetes();
	/* === BLOQUE ARRANQUE: FIN === */

})();
