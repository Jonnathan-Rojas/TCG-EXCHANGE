/**
 * Armado de consolidado (admin): "seleccionar todos" para las casillas de envios, validacion de que
 * exista al menos un envio marcado antes de armar y despliegue de errores de servidor.
 * Depende de SweetAlert2 cargado antes en la pagina.
 */
(function () {
	'use strict';

	/* === BLOQUE SELECCIONAR TODOS LOS ENVIOS: INICIO === */
	var todos = document.getElementById('tcgx-cons-todos');
	var casillas = document.querySelectorAll('.tcgx-cons-envio');
	if (todos) {
		todos.addEventListener('change', function () {
			casillas.forEach(function (c) { c.checked = todos.checked; });
		});
	}
	/* === BLOQUE SELECCIONAR TODOS LOS ENVIOS: FIN === */


	/* === BLOQUE VALIDACION DE ARMADO (AL MENOS UN ENVIO): INICIO === */
	var formArmar = document.getElementById('tcgx-cons-armar-form');
	if (formArmar) {
		formArmar.addEventListener('submit', function (evento) {
			var marcados = formArmar.querySelectorAll('.tcgx-cons-envio:checked').length;
			if (marcados < 1) {
				evento.preventDefault();
				if (typeof window.Swal !== 'undefined') {
					Swal.fire({ icon: 'error', title: 'Revise los datos', text: 'Debe seleccionar al menos un envío para consolidar.', confirmButtonText: 'ACEPTAR' });
				}
			}
		});
	}
	/* === BLOQUE VALIDACION DE ARMADO: FIN === */


	/* === BLOQUE ERRORES DE SERVIDOR (REINTENTO): INICIO === */
	var nodo = document.getElementById('tcgx-form-flash');
	if (nodo && typeof window.Swal !== 'undefined') {
		var datos;
		try {
			datos = JSON.parse(nodo.textContent || '{}');
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
	/* === BLOQUE ERRORES DE SERVIDOR (REINTENTO): FIN === */

})();
