/**
 * Listado de usuarios del modulo admin: inicializa DataTables (es, responsive),
 * gestiona acciones por fila (editar via POST, baja logica con confirmacion) y muestra el flash de resultado.
 * Depende de jQuery, DataTables, Bootstrap y SweetAlert2 cargados antes en la pagina.
 */
(function () {
	'use strict';

	/* === BLOQUE INICIALIZACION DATATABLES: INICIO ===
	 * Buscador, selector de filas y paginador obligatorios (diseño.md); en movil, detalle desplegable (Responsive).
	 * La columna de acciones se prioriza para permanecer visible y no colapsar en pantallas estrechas.
	 */
	var tabla = null;
	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		tabla = jQuery('#tcgx-tabla-usuarios').DataTable({
			responsive: true,
			pageLength: 10,
			lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
			order: [[7, 'desc']],
			columnDefs: [
				{ orderable: false, targets: 8 },
				{ responsivePriority: 1, targets: 0 },
				{ responsivePriority: 2, targets: 8 }
			],
			language: {
				url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
			}
		});
	}
	/* === BLOQUE INICIALIZACION DATATABLES: FIN === */


	/* === BLOQUE ACCIONES POR FILA (EDITAR / BAJA): INICIO ===
	 * Delegacion de eventos sobre la tabla: completa y envia el formulario oculto correspondiente.
	 * Editar: POST directo a usuario-editar.php. Baja: confirmacion SweetAlert2 antes de enviar.
	 */
	var formEditar = document.getElementById('tcgx-form-editar');
	var inputEditarId = document.getElementById('tcgx-form-editar-id');
	var formEstado = document.getElementById('tcgx-form-estado');
	var inputEstadoId = document.getElementById('tcgx-form-estado-id');
	var inputEstadoValor = document.getElementById('tcgx-form-estado-valor');
	var formClave = document.getElementById('tcgx-form-clave');
	var inputClaveId = document.getElementById('tcgx-form-clave-id');

	function enviarEstado(id, destino) {
		if (formEstado && inputEstadoId && inputEstadoValor) {
			inputEstadoId.value = id;
			inputEstadoValor.value = destino;
			formEstado.submit();
		}
	}

	function enviarClave(id) {
		if (formClave && inputClaveId) {
			inputClaveId.value = id;
			formClave.submit();
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
				title: esDesactivar ? '¿Desactivar usuario?' : '¿Activar usuario?',
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
			return;
		}

		// Regenerar contraseña: confirma antes de generar una nueva clave que invalida la anterior.
		if (accion === 'clave') {
			var nombreClave = boton.getAttribute('data-tcgx-nombre') || id;
			if (typeof window.Swal === 'undefined') {
				enviarClave(id);
				return;
			}
			Swal.fire({
				icon: 'warning',
				title: '¿Regenerar contraseña?',
				text: 'Se generará una nueva contraseña para ' + nombreClave + ' y la anterior dejará de funcionar.',
				showCancelButton: true,
				confirmButtonText: 'REGENERAR',
				cancelButtonText: 'CANCELAR',
				confirmButtonColor: '#6c757d'
			}).then(function (resultado) {
				if (resultado.isConfirmed) {
					enviarClave(id);
				}
			});
		}
	}

	var contenedorTabla = document.getElementById('tcgx-tabla-usuarios');
	if (contenedorTabla) {
		contenedorTabla.addEventListener('click', function (evento) {
			var boton = evento.target.closest('[data-tcgx-action]');
			if (boton) {
				manejarAccion(boton);
			}
		});
	}
	/* === BLOQUE ACCIONES POR FILA (EDITAR / BAJA): FIN === */


	/* === BLOQUE MENSAJE FLASH DE RESULTADO: INICIO ===
	 * Lee el JSON embebido por PHP (resultado de alta/edicion/baja) y lo muestra una vez.
	 * Caso especial clave_generada: muestra la contrasena con boton de copiar (entrega unica).
	 */
	function mostrarFlash() {
		var nodo = document.getElementById('tcgx-usuarios-flash');
		if (!nodo || typeof window.Swal === 'undefined') {
			return;
		}
		var datos;
		try {
			datos = JSON.parse(nodo.textContent || '{}');
		} catch (e) {
			return;
		}

		if (datos.tipo === 'clave_generada') {
			var clave = datos.clave || '';
			var idNuevo = datos.id || '';
			var esRegenerada = datos.modo === 'regenerada';
			var tituloClave = esRegenerada ? 'Contraseña regenerada' : 'Usuario creado';
			var introClave = esRegenerada
				? '<p class="mb-2">Nueva contraseña para el usuario <strong>' + idNuevo + '</strong>.</p>'
				: '<p class="mb-2">Usuario <strong>' + idNuevo + '</strong> creado correctamente.</p>';
			Swal.fire({
				icon: 'success',
				title: tituloClave,
				html:
					introClave +
					'<p class="mb-1">Contraseña generada (se muestra una sola vez):</p>' +
					'<div class="tcgx-clave-box"><code id="tcgx-clave-valor">' + clave + '</code>' +
					'<button type="button" class="btn btn-sm btn-primary ms-2" id="tcgx-clave-copiar"><i class="fa-solid fa-copy"></i></button></div>',
				confirmButtonText: 'ENTENDIDO',
				didOpen: function () {
					var btnCopiar = document.getElementById('tcgx-clave-copiar');
					if (btnCopiar) {
						btnCopiar.addEventListener('click', function () {
							if (navigator.clipboard && navigator.clipboard.writeText) {
								navigator.clipboard.writeText(clave);
							}
							btnCopiar.innerHTML = '<i class="fa-solid fa-check"></i>';
						});
					}
				}
			});
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
