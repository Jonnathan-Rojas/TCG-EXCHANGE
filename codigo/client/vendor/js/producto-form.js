/**
 * Formulario de producto binder (client): validacion en cliente y mensajes de servidor.
 */
(function () {
	'use strict';

	/* === BLOQUE VALIDACION EN CLIENTE: INICIO === */
	var formulario = document.getElementById('tcgx-producto-form');

	function valor(name) {
		var el = formulario ? formulario.querySelector('[name="' + name + '"]') : null;
		return el ? el.value.trim() : '';
	}

	function validarFormulario() {
		var errores = [];
		if (valor('nombrecarta') === '') {
			errores.push('El nombre de la carta es obligatorio.');
		}
		var cantidad = valor('cantidad');
		if (cantidad === '' || !/^\d+$/.test(cantidad) || parseInt(cantidad, 10) < 1) {
			errores.push('La cantidad debe ser un entero mayor o igual a 1.');
		}
		var precio = valor('precioventa');
		if (precio === '' || isNaN(parseFloat(precio)) || parseFloat(precio) < 0) {
			errores.push('El precio de venta debe ser un número mayor o igual a 0.');
		}
		if (valor('tipomoneda') === '') {
			errores.push('Debe seleccionar la moneda.');
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
