/**
 * Listado de evaluaciones (reputacion por usuario) del modulo admin: inicializa DataTables (es, responsive),
 * gestiona acciones por fila (editar via POST, eliminar con confirmacion) y muestra el flash de resultado.
 * Depende de jQuery, DataTables, Bootstrap y SweetAlert2 cargados antes en la pagina.
 */
(function () {
	'use strict';

	/* === BLOQUE INICIALIZACION DATATABLES: INICIO ===
	 * Buscador, selector de filas y paginador obligatorios (diseño.md); en movil, detalle desplegable (Responsive).
	 * La columna de acciones se prioriza para permanecer visible y no colapsar en pantallas estrechas.
	 */
	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		jQuery('#tcgx-tabla-evaluaciones').DataTable({
			responsive: true,
			pageLength: 10,
			lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
			order: [[9, 'desc']],
			columnDefs: [
				{ orderable: false, targets: 10 },
				{ responsivePriority: 1, targets: 1 },
				{ responsivePriority: 2, targets: 10 }
			],
			language: {
				url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
			}
		});
	}
	/* === BLOQUE INICIALIZACION DATATABLES: FIN === */


	/* === BLOQUE ACCIONES POR FILA (EDITAR / ELIMINAR): INICIO ===
	 * Delegacion de eventos sobre la tabla: completa y envia el formulario oculto correspondiente.
	 * Editar: POST directo a evaluacion-editar.php. Eliminar: confirmacion SweetAlert2 antes de enviar.
	 */
	var formEditar = document.getElementById('tcgx-form-editar');
	var inputEditarId = document.getElementById('tcgx-form-editar-id');
	var formEliminar = document.getElementById('tcgx-form-eliminar');
	var inputEliminarId = document.getElementById('tcgx-form-eliminar-id');

	function enviarEliminar(id) {
		if (formEliminar && inputEliminarId) {
			inputEliminarId.value = id;
			formEliminar.submit();
		}
	}

	function manejarAccion(boton) {
		var accion = boton.getAttribute('data-tcgx-action');
		var id = boton.getAttribute('data-tcgx-id') || '';

		if (accion === 'editar' && formEditar && inputEditarId) {
			inputEditarId.value = id;
			formEditar.submit();
			return;
		}

		if (accion === 'eliminar') {
			var cliente = boton.getAttribute('data-tcgx-cliente') || id;
			if (typeof window.Swal === 'undefined') {
				enviarEliminar(id);
				return;
			}
			Swal.fire({
				icon: 'warning',
				title: '¿Eliminar evaluación?',
				text: 'Se eliminará la evaluación de ' + cliente + '. Esta acción no se puede deshacer.',
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

	var contenedorTabla = document.getElementById('tcgx-tabla-evaluaciones');
	if (contenedorTabla) {
		contenedorTabla.addEventListener('click', function (evento) {
			var boton = evento.target.closest('[data-tcgx-action]');
			if (boton) {
				manejarAccion(boton);
			}
		});
	}
	/* === BLOQUE ACCIONES POR FILA (EDITAR / ELIMINAR): FIN === */


	/* === BLOQUE MENSAJE FLASH DE RESULTADO: INICIO ===
	 * Lee el JSON embebido por PHP (resultado de alta/edicion/eliminacion) y lo muestra una vez.
	 */
	function mostrarFlash() {
		var nodo = document.getElementById('tcgx-evaluaciones-flash');
		if (!nodo || typeof window.Swal === 'undefined') {
			return;
		}
		var datos;
		try {
			datos = JSON.parse(nodo.textContent || '{}');
		} catch (e) {
			return;
		}
		Swal.fire({
			icon: datos.tipo === 'ok' ? 'success' : 'error',
			text: datos.texto || '',
			confirmButtonText: 'ACEPTAR'
		});
	}
	mostrarFlash();
	/* === BLOQUE MENSAJE FLASH DE RESULTADO: FIN === */

})();
