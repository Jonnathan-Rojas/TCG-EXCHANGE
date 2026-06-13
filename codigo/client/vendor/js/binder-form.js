/**
 * Formulario de binder (client): validacion en cliente y mensajes de error de servidor.
 */
(function () {
	'use strict';

	/* === BLOQUE VALIDACION EN CLIENTE: INICIO === */
	var formulario = document.getElementById('tcgx-binder-form');

	function valor(name) {
		var el = formulario ? formulario.querySelector('[name="' + name + '"]') : null;
		return el ? el.value.trim() : '';
	}

	function validarFormulario() {
		var errores = [];
		if (valor('juego') === '') {
			errores.push('EL TCG ES OBLIGATORIO.');
		}
		if (valor('nombre') === '') {
			errores.push('El nombre es obligatorio.');
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


	/* === BLOQUE MENSAJES DE SERVIDOR: INICIO === */
	var nodoFlash = document.getElementById('tcgx-form-flash');
	if (nodoFlash && typeof window.Swal !== 'undefined') {
		var datos;
		try {
			datos = JSON.parse(nodoFlash.textContent || '{}');
		} catch (e) {
			datos = null;
		}
		if (datos && datos.errores && datos.errores.length > 0) {
			Swal.fire({
				icon: 'error',
				title: 'Revise los datos',
				html: '<ul class="text-start mb-0">' + datos.errores.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul>',
				confirmButtonText: 'ACEPTAR'
			});
		}
	}
	/* === BLOQUE MENSAJES DE SERVIDOR: FIN === */

})();
