/**
 * Listado de devoluciones (admin): DataTable, seguimiento por POST y flash SweetAlert2.
 */
(function () {
	'use strict';

	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		var $tabla = jQuery('#tcgx-tabla-devoluciones');
		if ($tabla.length > 0) {
			$tabla.DataTable({
				responsive: true,
				pageLength: 10,
				lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
				order: [[6, 'desc']],
				columnDefs: [{ orderable: false, targets: -1 }],
				language: {
					url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
				}
			});
		}
	}

	var formVer = document.getElementById('tcgx-form-ver');
	var inputVerId = document.getElementById('tcgx-form-ver-id');
	var tabla = document.getElementById('tcgx-tabla-devoluciones');

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

	var nodoFlash = document.getElementById('tcgx-devoluciones-flash');
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

})();
