/**
 * Listado de binders (client): DataTable, acciones por POST (ver, editar, eliminar con confirmacion) y flash.
 */
(function () {
	'use strict';

	/* === BLOQUE INICIALIZACION DATATABLES: INICIO === */
	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		jQuery('#tcgx-tabla-binders').DataTable({
			responsive: true,
			pageLength: 10,
			lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
			order: [[0, 'desc']],
			columnDefs: [
				{ orderable: false, targets: -1 },
				{ responsivePriority: 1, targets: 0 },
				{ responsivePriority: 2, targets: -1 }
			],
			language: {
				url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
			}
		});
	}
	/* === BLOQUE INICIALIZACION DATATABLES: FIN === */


	/* === BLOQUE ACCIONES POR FILA (VER / EDITAR / ELIMINAR): INICIO === */
	var formVer = document.getElementById('tcgx-form-ver');
	var inputVerId = document.getElementById('tcgx-form-ver-id');
	var formEditar = document.getElementById('tcgx-form-editar');
	var inputEditarId = document.getElementById('tcgx-form-editar-id');
	var formEliminar = document.getElementById('tcgx-form-eliminar');
	var inputEliminarId = document.getElementById('tcgx-form-eliminar-id');
	var contenedorTabla = document.getElementById('tcgx-tabla-binders');

	function enviarEliminar(id) {
		if (formEliminar && inputEliminarId) {
			inputEliminarId.value = id;
			formEliminar.submit();
		}
	}

	function manejarAccion(boton) {
		var accion = boton.getAttribute('data-tcgx-action');
		var id = boton.getAttribute('data-tcgx-id') || '';

		if (accion === 'ver' && formVer && inputVerId) {
			inputVerId.value = id;
			formVer.submit();
			return;
		}
		if (accion === 'editar' && formEditar && inputEditarId) {
			inputEditarId.value = id;
			formEditar.submit();
			return;
		}
		if (accion === 'eliminar') {
			var nombre = boton.getAttribute('data-tcgx-nombre') || id;
			if (typeof window.Swal === 'undefined') {
				enviarEliminar(id);
				return;
			}
			Swal.fire({
				icon: 'warning',
				title: '¿Eliminar binder?',
				text: 'Se eliminará el binder ' + nombre + ' y todos sus productos e imágenes. Esta acción no se puede deshacer.',
				showCancelButton: true,
				confirmButtonText: 'ELIMINAR',
				cancelButtonText: 'CANCELAR',
				confirmButtonColor: '#dc3545'
			}).then(function (resultado) {
				if (resultado.isConfirmed) {
					enviarEliminar(id);
				}
			});
		}
	}

	if (contenedorTabla) {
		contenedorTabla.addEventListener('click', function (evento) {
			var boton = evento.target.closest('[data-tcgx-action]');
			if (boton) {
				manejarAccion(boton);
			}
		});
	}
	/* === BLOQUE ACCIONES POR FILA: FIN === */


	/* === BLOQUE FLASH DEL RESULTADO PREVIO: INICIO === */
	var nodoFlash = document.getElementById('tcgx-binders-flash');
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
