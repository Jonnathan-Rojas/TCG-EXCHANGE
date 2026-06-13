/**
 * Catalogo publico Buscar Cartas: filtros dinamicos en cliente (sin GET).
 */
(function () {
	'use strict';

	/* === BLOQUE FILTROS DEL CATALOGO PUBLICO: INICIO ===
	 * Filtrado instantaneo sobre cartas cargadas; incluye selector de TCG.
	 */
	var catalogo = document.getElementById('tcgx-catalogo-publico');
	var inputBuscar = document.getElementById('tcgx-catalogo-publico-buscar');
	var selectTcg = document.getElementById('tcgx-catalogo-publico-tcg');
	var selectExpansion = document.getElementById('tcgx-catalogo-publico-expansion');
	var selectRareza = document.getElementById('tcgx-catalogo-publico-rareza');
	var selectCondicion = document.getElementById('tcgx-catalogo-publico-condicion');
	var selectIdioma = document.getElementById('tcgx-catalogo-publico-idioma');
	var btnLimpiarFiltros = document.getElementById('tcgx-catalogo-publico-limpiar');
	var contadorCatalogo = document.getElementById('tcgx-catalogo-publico-contador');
	var avisoSinResultados = document.getElementById('tcgx-catalogo-publico-sin-resultados');
	var cartasCatalogo = catalogo ? catalogo.querySelectorAll('.tcgx-catalogo-publico-carta') : [];
	var totalCartas = cartasCatalogo.length;
	var debounceBuscar = null;

	if (!catalogo || totalCartas === 0) {
		return;
	}

	function normalizarTexto(texto) {
		return (texto || '').toString().toUpperCase().replace(/\s+/g, ' ').trim();
	}

	function coincideSelect(valorFiltro, valorCarta) {
		return valorFiltro === '' || valorCarta === valorFiltro;
	}

	function aplicarFiltroCatalogo() {
		var termino = normalizarTexto(inputBuscar ? inputBuscar.value : '');
		var filtroTcg = selectTcg ? selectTcg.value : '';
		var filtroExpansion = selectExpansion ? selectExpansion.value : '';
		var filtroRareza = selectRareza ? selectRareza.value : '';
		var filtroCondicion = selectCondicion ? selectCondicion.value : '';
		var filtroIdioma = selectIdioma ? selectIdioma.value : '';
		var visibles = 0;
		var i;

		for (i = 0; i < cartasCatalogo.length; i++) {
			var carta = cartasCatalogo[i];
			var textoCarta = normalizarTexto(carta.getAttribute('data-tcgx-buscar') || '');
			var mostrar = true;

			if (termino !== '' && textoCarta.indexOf(termino) === -1) {
				mostrar = false;
			}
			if (mostrar && !coincideSelect(filtroTcg, carta.getAttribute('data-tcgx-tcg') || '')) {
				mostrar = false;
			}
			if (mostrar && !coincideSelect(filtroExpansion, carta.getAttribute('data-tcgx-expansion') || '')) {
				mostrar = false;
			}
			if (mostrar && !coincideSelect(filtroRareza, carta.getAttribute('data-tcgx-rareza') || '')) {
				mostrar = false;
			}
			if (mostrar && !coincideSelect(filtroCondicion, carta.getAttribute('data-tcgx-condicion') || '')) {
				mostrar = false;
			}
			if (mostrar && !coincideSelect(filtroIdioma, carta.getAttribute('data-tcgx-idioma') || '')) {
				mostrar = false;
			}

			if (mostrar) {
				carta.classList.remove('tcgx-catalogo-publico-carta--oculta');
				visibles += 1;
			} else {
				carta.classList.add('tcgx-catalogo-publico-carta--oculta');
			}
		}

		if (contadorCatalogo) {
			contadorCatalogo.textContent = visibles + ' / ' + totalCartas;
		}
		if (avisoSinResultados) {
			if (visibles === 0) {
				avisoSinResultados.classList.remove('d-none');
			} else {
				avisoSinResultados.classList.add('d-none');
			}
		}
	}

	function programarFiltroBuscar() {
		window.clearTimeout(debounceBuscar);
		debounceBuscar = window.setTimeout(aplicarFiltroCatalogo, 120);
	}

	function limpiarFiltrosCatalogo() {
		if (inputBuscar) {
			inputBuscar.value = '';
		}
		if (selectTcg) {
			selectTcg.value = '';
		}
		if (selectExpansion) {
			selectExpansion.value = '';
		}
		if (selectRareza) {
			selectRareza.value = '';
		}
		if (selectCondicion) {
			selectCondicion.value = '';
		}
		if (selectIdioma) {
			selectIdioma.value = '';
		}
		aplicarFiltroCatalogo();
		if (inputBuscar) {
			inputBuscar.focus();
		}
	}

	if (inputBuscar) {
		inputBuscar.addEventListener('input', programarFiltroBuscar);
	}
	if (selectTcg) {
		selectTcg.addEventListener('change', aplicarFiltroCatalogo);
	}
	if (selectExpansion) {
		selectExpansion.addEventListener('change', aplicarFiltroCatalogo);
	}
	if (selectRareza) {
		selectRareza.addEventListener('change', aplicarFiltroCatalogo);
	}
	if (selectCondicion) {
		selectCondicion.addEventListener('change', aplicarFiltroCatalogo);
	}
	if (selectIdioma) {
		selectIdioma.addEventListener('change', aplicarFiltroCatalogo);
	}
	if (btnLimpiarFiltros) {
		btnLimpiarFiltros.addEventListener('click', limpiarFiltrosCatalogo);
	}
	/* === BLOQUE FILTROS DEL CATALOGO PUBLICO: FIN === */
})();
