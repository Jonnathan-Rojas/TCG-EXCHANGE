/**
 * Gestion de incidencia (admin): DataTable del historial, validacion del formulario de actualizacion
 * y despliegue de errores de servidor via SweetAlert2.
 */
(function () {
	'use strict';

	/* === BLOQUE INICIALIZACION DATATABLE HISTORIAL: INICIO === */
	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		var $tabla = jQuery('#tcgx-tabla-historial');
		if ($tabla.length > 0) {
			$tabla.DataTable({
				responsive: true,
				pageLength: 10,
				lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
				order: [[0, 'desc']],
				language: {
					url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
				}
			});
		}
	}
	/* === BLOQUE INICIALIZACION DATATABLE HISTORIAL: FIN === */


	/* === BLOQUE VALIDACION FORMULARIO ACTUALIZACION: INICIO === */
	var formulario = document.getElementById('tcgx-inc-form');
	if (formulario) {
		formulario.addEventListener('submit', function (evento) {
			var detalle = (document.getElementById('inc-detalle') || {}).value || '';
			if (detalle.trim() === '') {
				evento.preventDefault();
				if (typeof window.Swal !== 'undefined') {
					Swal.fire({
						icon: 'error',
						title: 'Revise los datos',
						text: 'Debe indicar el detalle de la actualización.',
						confirmButtonText: 'ACEPTAR'
					});
				}
			}
		});
	}
	/* === BLOQUE VALIDACION FORMULARIO ACTUALIZACION: FIN === */


	/* === BLOQUE ERRORES DE SERVIDOR: INICIO === */
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
	/* === BLOQUE ERRORES DE SERVIDOR: FIN === */

})();
