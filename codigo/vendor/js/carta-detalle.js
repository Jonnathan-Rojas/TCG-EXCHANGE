/**
 * Detalle publico de carta: cambio de imagen principal desde miniaturas de galeria.
 */
(function () {
	'use strict';

	/* === BLOQUE GALERIA DETALLE CARTA: INICIO === */
	var imagenPrincipal = document.getElementById('tcgx-carta-detalle-imagen-principal');
	var miniaturas = document.querySelectorAll('[data-tcgx-imagen-detalle]');
	var i;

	if (!imagenPrincipal || miniaturas.length === 0) {
		return;
	}

	for (i = 0; i < miniaturas.length; i++) {
		miniaturas[i].addEventListener('click', function () {
			var url = this.getAttribute('data-tcgx-imagen-detalle') || '';
			if (url === '') {
				return;
			}
			imagenPrincipal.src = url;
			var j;
			for (j = 0; j < miniaturas.length; j++) {
				miniaturas[j].classList.remove('is-active');
			}
			this.classList.add('is-active');
		});
	}
	/* === BLOQUE GALERIA DETALLE CARTA: FIN === */
})();
