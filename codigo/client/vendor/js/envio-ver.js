/**
 * Detalle de envio (client, solo lectura): DataTables de paquetes y trazabilidad.
 */
(function () {
	'use strict';

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

})();
