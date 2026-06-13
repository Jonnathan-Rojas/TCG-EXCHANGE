/**
 * Detalle de envio (cd): DataTables, Select2 del receptor via POST a envio-ver.php y validacion de acciones.
 */
(function () {
	'use strict';

	var token = '';
	var nodoConfig = document.getElementById('tcgx-envio-config');
	if (nodoConfig) {
		try {
			token = (JSON.parse(nodoConfig.textContent || '{}').token) || '';
		} catch (e) {
			token = '';
		}
	}

	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		function iniciarTabla(selector) {
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
	}

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
					url: 'envio-ver.php',
					type: 'POST',
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							q: params.term || '',
							tcgx_csrf_token: token,
							tcgx_envios_buscar_ajax: '1'
						};
					},
					processResults: function (data) {
						return { results: (data && data.results) ? data.results : [] };
					},
					cache: true
				}
			});
		}
	}

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

})();
