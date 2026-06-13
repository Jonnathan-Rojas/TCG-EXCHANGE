/**
 * Formulario de evaluacion (alta y edicion) del modulo admin.
 * Responsabilidades: alternar el motivo segun lista negra, validar en cliente y mostrar errores de servidor.
 * Depende de SweetAlert2 cargado antes en la pagina.
 */
(function () {
	'use strict';

	var formulario = document.getElementById('tcgx-evaluacion-form');
	var selectListaNegra = document.getElementById('evaluacion-listanegra');
	var campoMotivo = document.getElementById('evaluacion-motivo');

	/* === BLOQUE BUSQUEDA DINAMICA DEL CLIENTE (SELECT2 + AJAX POST): INICIO ===
	 * Autocompletado por nombre o cedula: consulta al endpoint por POST (con token CSRF, sin GET)
	 * y muestra cada coincidencia con el nombre y la cedula debajo.
	 */
	(function inicializarSelect2Cliente() {
		if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2 || !formulario) {
			return;
		}
		var $select = jQuery('#evaluacion-usuario');
		if ($select.length === 0) {
			return;
		}
		// Token CSRF tomado del formulario (mismo que validan los endpoints del modulo).
		var tokenInput = formulario.querySelector('input[name="tcgx_csrf_token"]');
		var token = tokenInput ? tokenInput.value : '';

		$select.select2({
			theme: 'bootstrap-5',
			width: '100%',
			placeholder: 'BUSQUE POR NOMBRE…',
			allowClear: true,
			minimumInputLength: 1,
			language: {
				inputTooShort: function () { return 'INGRESE AL MENOS 1 CARÁCTER'; },
				searching: function () { return 'BUSCANDO…'; },
				noResults: function () { return 'SIN RESULTADOS'; },
				errorLoading: function () { return 'NO SE PUDO CARGAR'; }
			},
			ajax: {
				url: 'evaluacion-buscar-clientes.php',
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
			},
			templateResult: function (item) {
				if (item.loading || !item.cedula) {
					return item.text;
				}
				// Render con nombre arriba y cedula debajo; se usa .text() para evitar XSS.
				var $cont = jQuery('<div></div>');
				jQuery('<div></div>').text(item.nombre || '').appendTo($cont);
				jQuery('<small class="text-secondary"></small>').text(item.cedula).appendTo($cont);
				return $cont;
			}
		});
	})();
	/* === BLOQUE BUSQUEDA DINAMICA DEL CLIENTE (SELECT2 + AJAX POST): FIN === */

	/* === BLOQUE ALTERNADO DE MOTIVO SEGUN LISTA NEGRA: INICIO ===
	 * El motivo solo aplica cuando se marca lista negra: se habilita y exige; en caso contrario se limpia y deshabilita.
	 */
	function sincronizarMotivo() {
		if (!selectListaNegra || !campoMotivo) {
			return;
		}
		var enListaNegra = selectListaNegra.value === '1';
		campoMotivo.disabled = !enListaNegra;
		campoMotivo.required = enListaNegra;
		if (!enListaNegra) {
			campoMotivo.value = '';
		}
	}

	if (selectListaNegra) {
		selectListaNegra.addEventListener('change', sincronizarMotivo);
		sincronizarMotivo();
	}
	/* === BLOQUE ALTERNADO DE MOTIVO SEGUN LISTA NEGRA: FIN === */


	/* === BLOQUE VALIDACION EN CLIENTE: INICIO ===
	 * Comprobaciones previas al envio (complemento de la validacion definitiva en servidor).
	 */
	function valor(id) {
		var el = document.getElementById(id);
		return el ? el.value.trim() : '';
	}

	function validarPuntaje(id, etiqueta, errores) {
		var v = valor(id);
		var n = Number(v);
		if (v === '' || !Number.isInteger(n) || n < 0 || n > 5) {
			errores.push('El puntaje de ' + etiqueta + ' debe ser un entero entre 0 y 5.');
		}
	}

	function validarFormulario() {
		var errores = [];
		if (valor('evaluacion-usuario') === '') {
			errores.push('Debe seleccionar el cliente a evaluar.');
		}
		if (valor('evaluacion-tienda') === '') {
			errores.push('Debe seleccionar una tienda.');
		}
		validarPuntaje('evaluacion-rapidez', 'rapidez', errores);
		validarPuntaje('evaluacion-confianza', 'confianza', errores);
		validarPuntaje('evaluacion-seguridad', 'seguridad', errores);
		validarPuntaje('evaluacion-calidad', 'calidad', errores);
		if (selectListaNegra && selectListaNegra.value === '1' && valor('evaluacion-motivo') === '') {
			errores.push('Debe indicar el motivo de la lista negra.');
		}
		return errores;
	}

	if (formulario) {
		formulario.addEventListener('submit', function (evento) {
			var errores = validarFormulario();
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
	}
	/* === BLOQUE VALIDACION EN CLIENTE: FIN === */


	/* === BLOQUE ERRORES DE SERVIDOR (REINTENTO): INICIO ===
	 * Muestra los errores devueltos por la validacion de servidor tras un envio fallido.
	 */
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

})();
