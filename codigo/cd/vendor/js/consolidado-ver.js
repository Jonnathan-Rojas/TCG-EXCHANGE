/**
 * Detalle de consolidado (admin): confirma con SweetAlert2 las operaciones sensibles del consolidado
 * (despachar, recibir, cancelar, sacar envio) antes de enviar el formulario por POST.
 * Depende de SweetAlert2 cargado antes en la pagina.
 */
(function () {
	'use strict';

	/* === BLOQUE CONFIRMACION DE OPERACIONES DEL CONSOLIDADO: INICIO === */
	var formularios = document.querySelectorAll('.tcgx-cons-confirm');

	formularios.forEach(function (form) {
		form.addEventListener('submit', function (evento) {
			if (form.dataset.tcgxConfirmado === '1') {
				return;
			}
			evento.preventDefault();

			var texto = form.getAttribute('data-tcgx-confirm') || '¿Confirmar la operación?';

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
				confirmButtonText: 'SÍ, CONTINUAR',
				cancelButtonText: 'CANCELAR',
				confirmButtonColor: '#d33'
			}).then(function (res) {
				if (res.isConfirmed) {
					form.dataset.tcgxConfirmado = '1';
					form.submit();
				}
			});
		});
	});
	/* === BLOQUE CONFIRMACION DE OPERACIONES DEL CONSOLIDADO: FIN === */

})();
