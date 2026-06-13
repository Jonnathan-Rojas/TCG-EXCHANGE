/**
 * Listado de incidencias (admin): inicializa DataTable, abre gestion por POST (sin GET con datos)
 * y muestra flash del resultado previo via SweetAlert2.
 */
(function () {
	'use strict';

	/* === BLOQUE INICIALIZACION DATATABLE: INICIO === */
	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		var $tabla = jQuery('#tcgx-tabla-incidencias');
		if ($tabla.length > 0) {
			$tabla.DataTable({
				responsive: true,
				pageLength: 10,
				lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
				order: [[0, 'desc']],
				columnDefs: [{ orderable: false, targets: -1 }],
				language: {
					url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
				}
			});
		}
	}
	/* === BLOQUE INICIALIZACION DATATABLE: FIN === */


	/* === BLOQUE ACCION VER (POST + CSRF): INICIO === */
	var formVer = document.getElementById('tcgx-form-ver');
	var inputVerId = document.getElementById('tcgx-form-ver-id');
	var tabla = document.getElementById('tcgx-tabla-incidencias');

	if (tabla && formVer && inputVerId) {
		tabla.addEventListener('click', function (evento) {
			var boton = evento.target.closest('[data-tcgx-action="ver"]');
			if (!boton) {
				return;
			}
			inputVerId.value = boton.getAttribute('data-tcgx-id') || '';
			formVer.submit();
		});
	}
	/* === BLOQUE ACCION VER: FIN === */


	/* === BLOQUE FLASH DEL RESULTADO PREVIO: INICIO === */
	var nodoFlash = document.getElementById('tcgx-incidencias-flash');
	if (nodoFlash && typeof window.Swal !== 'undefined') {
		var datos;
		try {
			datos = JSON.parse(nodoFlash.textContent || '{}');
		} catch (e) {
			datos = null;
		}
		if (datos && datos.texto) {
			Swal.fire({
				icon: datos.tipo === 'ok' ? 'success' : 'error',
				title: datos.tipo === 'ok' ? 'Listo' : 'Atención',
				text: datos.texto,
				confirmButtonText: 'ACEPTAR'
			});
		}
	}
	/* === BLOQUE FLASH DEL RESULTADO PREVIO: FIN === */

})();
