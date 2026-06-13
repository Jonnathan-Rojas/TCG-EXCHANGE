/**
 * Formulario de tarifa (alta y edicion) del modulo admin.
 * Responsabilidades: validacion en cliente y despliegue de errores de servidor.
 * Depende de SweetAlert2 cargado antes en la pagina.
 */
(function () {
	'use strict';

	/* === BLOQUE VALIDACION EN CLIENTE: INICIO ===
	 * Comprobaciones previas al envio (complemento de la validacion definitiva en servidor).
	 */
	var formulario = document.getElementById('tcgx-tarifa-form');

	function valor(id) {
		var el = document.getElementById(id);
		return el ? el.value.trim() : '';
	}

	function validarFormulario() {
		var errores = [];
		if (valor('tarifa-tienda') === '') {
			errores.push('Debe seleccionar una tienda.');
		}
		if (valor('tarifa-ruta') === '') {
			errores.push('Debe seleccionar una ruta.');
		}
		// PRECIO UNICO: se valida un solo campo. NUNCA USAR PRECIO BASE (no existe en el formulario).
		var precio = valor('tarifa-precio');
		if (precio === '' || isNaN(precio) || Number(precio) < 0) {
			errores.push('El precio debe ser un número mayor o igual a cero.');
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
