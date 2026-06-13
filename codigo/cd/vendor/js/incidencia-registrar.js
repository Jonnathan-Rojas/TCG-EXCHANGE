/**
 * Formulario de registro de incidencia (admin): muestra errores de validacion del servidor.
 */
(function () {
	'use strict';

	var nodoFlash = document.getElementById('tcgx-inc-registrar-flash');
	if (!nodoFlash || typeof window.Swal === 'undefined') {
		return;
	}
	var datos;
	try {
		datos = JSON.parse(nodoFlash.textContent || '{}');
	} catch (e) {
		return;
	}
	if (datos.errores && datos.errores.length > 0) {
		Swal.fire({
			icon: 'error',
			title: 'Revise los datos',
			html: '<ul class="text-start mb-0">' + datos.errores.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul>',
			confirmButtonText: 'ACEPTAR'
		});
	}
})();
