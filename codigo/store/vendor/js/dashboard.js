/**
 * Dashboard admin (index.php): DataTable del resumen de envios por estado.
 */
(function () {
	'use strict';

	/* === BLOQUE INICIALIZACION DATATABLE ENVIOS POR ESTADO: INICIO === */
	if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
		var $tabla = jQuery('#tcgx-tabla-envios-estado');
		if ($tabla.length > 0 && $tabla.find('tbody tr').length > 0 && $tabla.find('tbody tr td[colspan]').length === 0) {
			$tabla.DataTable({
				responsive: true,
				pageLength: 25,
				lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
				order: [[1, 'desc']],
				language: {
					url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'
				}
			});
		}
	}
	/* === BLOQUE INICIALIZACION DATATABLE ENVIOS POR ESTADO: FIN === */

})();
