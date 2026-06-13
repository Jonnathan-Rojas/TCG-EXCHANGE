/**
 * Detalle de envio individual (admin): inicializa las tablas de paquetes y trazabilidad como DataTables,
 * el Select2 del nuevo receptor (busqueda por nombre) y la confirmacion de las acciones destructivas
 * (cancelar / devolver). Depende de jQuery, DataTables, Select2 y SweetAlert2 cargados antes en la pagina.
 */
(function () {
	'use strict';

	/* === BLOQUE TOKEN CSRF DEL MODULO (PARA EL SELECT2 POR AJAX): INICIO === */
	var token = '';
	var nodoConfig = document.getElementById('tcgx-envio-config');
	if (nodoConfig) {
		try {
			token = (JSON.parse(nodoConfig.textContent || '{}').token) || '';
		} catch (e) {
			token = '';
		}
	}
	/* === BLOQUE TOKEN CSRF DEL MODULO: FIN === */


	/* === BLOQUE INICIALIZACION DATATABLES DEL DETALLE: INICIO === */
	function iniciarTabla(selector) {
		if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
			return;
		}
		var $t = jQuery(selector);
		if ($t.length === 0) {
			return;
		}
		$t.DataTable({
			responsive: true,
			pageLength: 10,
			lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
			order: [[0, 'asc']],
			language: {
				url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
			}
		});
	}
	iniciarTabla('#tcgx-tabla-paquetes');
	iniciarTabla('#tcgx-tabla-movimientos');
	/* === BLOQUE INICIALIZACION DATATABLES DEL DETALLE: FIN === */


	/* === BLOQUE SELECT2 DEL NUEVO RECEPTOR (AJAX POST POR NOMBRE): INICIO === */
	if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
		var $receptor = jQuery('#envio-nuevo-receptor');
		if ($receptor.length > 0) {
			$receptor.select2({
				theme: 'bootstrap-5',
				width: '100%',
				placeholder: 'BUSQUE EL NUEVO RECEPTOR POR NOMBRE…',
				allowClear: true,
				minimumInputLength: 1,
				language: {
					inputTooShort: function () { return 'INGRESE AL MENOS 1 CARÁCTER'; },
					searching: function () { return 'BUSCANDO…'; },
					noResults: function () { return 'SIN RESULTADOS'; },
					errorLoading: function () { return 'NO SE PUDO CARGAR'; }
				},
				ajax: {
					url: 'envio-buscar-clientes.php',
					type: 'POST',
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return { q: params.term || '', tcgx_csrf_token: token };
					},
					processResults: function (data) {
						return { results: (data && data.results) ? data.results : [] };
					},
					cache: true
				}
			});
		}
	}
	/* === BLOQUE SELECT2 DEL NUEVO RECEPTOR: FIN === */


	/* === BLOQUE VALIDACION DE CAMBIO DE DESTINO/RECEPTOR: INICIO === */
	document.querySelectorAll('form.tcgx-envio-accion').forEach(function (form) {
		form.addEventListener('submit', function (evento) {
			var select = form.querySelector('select');
			if (select && select.value === '') {
				evento.preventDefault();
				if (typeof window.Swal !== 'undefined') {
					Swal.fire({ icon: 'error', title: 'Revise los datos', text: 'Debe seleccionar una opción.', confirmButtonText: 'ACEPTAR' });
				}
			}
		});
	});
	/* === BLOQUE VALIDACION DE CAMBIO DE DESTINO/RECEPTOR: FIN === */


	/* === BLOQUE CONFIRMACION DE ACCIONES DESTRUCTIVAS (CANCELAR / DEVOLVER): INICIO === */
	document.querySelectorAll('form.tcgx-envio-confirm').forEach(function (form) {
		var confirmado = false;
		form.addEventListener('submit', function (evento) {
			if (confirmado || typeof window.Swal === 'undefined') {
				return;
			}
			evento.preventDefault();
			var mensaje = form.getAttribute('data-tcgx-confirm') || '¿Confirma la acción?';
			Swal.fire({
				icon: 'warning',
				title: 'Confirme',
				text: mensaje,
				showCancelButton: true,
				confirmButtonText: 'SÍ, CONTINUAR',
				cancelButtonText: 'CANCELAR',
				confirmButtonColor: '#dc3545'
			}).then(function (resultado) {
				if (resultado.isConfirmed) {
					confirmado = true;
					form.submit();
				}
			});
		});
	});
	/* === BLOQUE CONFIRMACION DE ACCIONES DESTRUCTIVAS: FIN === */

})();
