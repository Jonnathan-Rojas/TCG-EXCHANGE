/**
 * Seguimiento de devolucion (admin): DataTable de movimientos, confirmacion de avance y errores de servidor.
 */
(function () {
	'use strict';

	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		var $tabla = jQuery('#tcgx-tabla-movimientos');
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

	var formularios = document.querySelectorAll('.tcgx-dev-confirm');
	formularios.forEach(function (form) {
		form.addEventListener('submit', function (evento) {
			if (form.dataset.tcgxConfirmado === '1') {
				return;
			}
			evento.preventDefault();
			var texto = form.getAttribute('data-tcgx-confirm') || '¿Confirmar el avance de la devolución?';
			if (typeof window.Swal === 'undefined') {
				form.dataset.tcgxConfirmado = '1';
				form.submit();
				return;
			}
			Swal.fire({
				icon: 'warning',
				title: 'Confirmar',
				text: texto,
				showCancelButton: true,
				confirmButtonText: 'SÍ, AVANZAR',
				cancelButtonText: 'CANCELAR'
			}).then(function (res) {
				if (res.isConfirmed) {
					form.dataset.tcgxConfirmado = '1';
					form.submit();
				}
			});
		});
	});

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

})();
