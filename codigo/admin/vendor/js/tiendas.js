/**
 * Listado de tiendas del modulo admin: inicializa DataTables (es, responsive),
 * gestiona acciones por fila (editar via POST, cambio de estado con confirmacion) y muestra el flash de resultado.
 * Depende de jQuery, DataTables, Bootstrap y SweetAlert2 cargados antes en la pagina.
 */
(function () {
	'use strict';

	/* === BLOQUE INICIALIZACION DATATABLES: INICIO ===
	 * Buscador, selector de filas y paginador obligatorios (diseño.md); en movil, detalle desplegable (Responsive).
	 * La columna de acciones se prioriza para permanecer visible y no colapsar en pantallas estrechas.
	 */
	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		jQuery('#tcgx-tabla-tiendas').DataTable({
			responsive: true,
			pageLength: 10,
			lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
			order: [[7, 'desc']],
			columnDefs: [
				{ orderable: false, targets: 8 },
				{ responsivePriority: 1, targets: 1 },
				{ responsivePriority: 2, targets: 8 }
			],
			language: {
				url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
			}
		});
	}
	/* === BLOQUE INICIALIZACION DATATABLES: FIN === */


	/* === BLOQUE ACCIONES POR FILA (EDITAR / ESTADO): INICIO ===
	 * Delegacion de eventos sobre la tabla: completa y envia el formulario oculto correspondiente.
	 * Editar: POST directo a tienda-editar.php. Estado: confirmacion SweetAlert2 antes de enviar.
	 */
	var formEditar = document.getElementById('tcgx-form-editar');
	var inputEditarId = document.getElementById('tcgx-form-editar-id');
	var formEstado = document.getElementById('tcgx-form-estado');
	var inputEstadoId = document.getElementById('tcgx-form-estado-id');
	var inputEstadoValor = document.getElementById('tcgx-form-estado-valor');

	function enviarEstado(id, destino) {
		if (formEstado && inputEstadoId && inputEstadoValor) {
			inputEstadoId.value = id;
			inputEstadoValor.value = destino;
			formEstado.submit();
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

		if (accion === 'estado') {
			var destino = boton.getAttribute('data-tcgx-target') || '';
			var nombre = boton.getAttribute('data-tcgx-nombre') || id;
			var esDesactivar = destino === 'INACTIVO';
			if (typeof window.Swal === 'undefined') {
				enviarEstado(id, destino);
				return;
			}
			Swal.fire({
				icon: 'warning',
				title: esDesactivar ? '¿Desactivar tienda?' : '¿Activar tienda?',
				text: (esDesactivar ? 'Se desactivará (estado INACTIVO) a ' : 'Se activará (estado ACTIVO) a ') + nombre + '.',
				showCancelButton: true,
				confirmButtonText: esDesactivar ? 'DESACTIVAR' : 'ACTIVAR',
				cancelButtonText: 'CANCELAR',
				confirmButtonColor: esDesactivar ? '#dc3545' : '#198754'
			}).then(function (resultado) {
				if (resultado.isConfirmed) {
					enviarEstado(id, destino);
				}
			});
		}
	}

	var contenedorTabla = document.getElementById('tcgx-tabla-tiendas');
	if (contenedorTabla) {
		contenedorTabla.addEventListener('click', function (evento) {
			var boton = evento.target.closest('[data-tcgx-action]');
			if (boton) {
				manejarAccion(boton);
			}
		});
	}
	/* === BLOQUE ACCIONES POR FILA (EDITAR / ESTADO): FIN === */


	/* === BLOQUE MENSAJE FLASH DE RESULTADO: INICIO ===
	 * Lee el JSON embebido por PHP (resultado de alta/edicion/estado) y lo muestra una vez.
	 */
	function mostrarFlash() {
		var nodo = document.getElementById('tcgx-tiendas-flash');
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
