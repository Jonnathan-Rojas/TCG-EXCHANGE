/**
 * Detalle de binder (client): catalogo visual, filtros dinamicos y acciones POST.
 */
(function () {
	'use strict';

	/* === BLOQUE ACCIONES TOOLBAR Y CATALOGO: INICIO === */
	var formEditarBinder = document.getElementById('tcgx-form-editar-binder');
	var formAccion = document.getElementById('tcgx-form-accion-binder');
	var inputAccionTipo = document.getElementById('tcgx-form-accion-binder-tipo');
	var inputProductoId = document.getElementById('tcgx-form-accion-producto-id');
	var formEditarProducto = document.getElementById('tcgx-form-editar-producto');
	var inputEditarProductoId = document.getElementById('tcgx-form-editar-producto-id');
	var btnEditarBinder = document.getElementById('tcgx-btn-editar-binder');
	var btnEliminarBinder = document.getElementById('tcgx-btn-eliminar-binder');
	var catalogo = document.getElementById('tcgx-catalogo-binder');

	function enviarAccion(tipo, idProducto) {
		if (formAccion && inputAccionTipo && inputProductoId) {
			inputAccionTipo.value = tipo;
			inputProductoId.value = idProducto || '';
			formAccion.submit();
		}
	}

	if (btnEditarBinder && formEditarBinder) {
		btnEditarBinder.addEventListener('click', function () {
			formEditarBinder.submit();
		});
	}

	if (btnEliminarBinder) {
		btnEliminarBinder.addEventListener('click', function () {
			var nombre = btnEliminarBinder.getAttribute('data-tcgx-nombre') || '';
			if (typeof window.Swal === 'undefined') {
				enviarAccion('eliminar_binder', '');
				return;
			}
			Swal.fire({
				icon: 'warning',
				title: '¿Eliminar binder?',
				text: 'Se eliminará el binder ' + nombre + ' y todos sus productos. Esta acción no se puede deshacer.',
				showCancelButton: true,
				confirmButtonText: 'ELIMINAR',
				cancelButtonText: 'CANCELAR',
				confirmButtonColor: '#dc3545'
			}).then(function (resultado) {
				if (resultado.isConfirmed) {
					enviarAccion('eliminar_binder', '');
				}
			});
		});
	}

	if (catalogo) {
		catalogo.addEventListener('click', function (evento) {
			var boton = evento.target.closest('[data-tcgx-action]');
			if (!boton) {
				return;
			}
			var accion = boton.getAttribute('data-tcgx-action');
			var id = boton.getAttribute('data-tcgx-id') || '';

			if (accion === 'editar' && formEditarProducto && inputEditarProductoId) {
				inputEditarProductoId.value = id;
				formEditarProducto.submit();
				return;
			}
			if (accion === 'toggle') {
				enviarAccion('toggle_publicado', id);
				return;
			}
			if (accion === 'eliminar') {
				var nombreProd = boton.getAttribute('data-tcgx-nombre') || id;
				if (typeof window.Swal === 'undefined') {
					enviarAccion('eliminar_producto', id);
					return;
				}
				Swal.fire({
					icon: 'warning',
					title: '¿Eliminar producto?',
					text: 'Se eliminará el producto ' + nombreProd + ' y sus imágenes.',
					showCancelButton: true,
					confirmButtonText: 'ELIMINAR',
					cancelButtonText: 'CANCELAR',
					confirmButtonColor: '#dc3545'
				}).then(function (resultado) {
					if (resultado.isConfirmed) {
						enviarAccion('eliminar_producto', id);
					}
				});
			}
		});
	}
	/* === BLOQUE ACCIONES TOOLBAR Y CATALOGO: FIN === */


	/* === BLOQUE FILTROS Y BUSQUEDA DINAMICOS DEL CATALOGO: INICIO ===
	 * Filtrado instantaneo en cliente sobre cartas cargadas; sin GET ni recarga.
	 */
	var inputBuscar = document.getElementById('tcgx-catalogo-buscar');
	var selectPublicado = document.getElementById('tcgx-catalogo-publicado');
	var selectExpansion = document.getElementById('tcgx-catalogo-expansion');
	var selectRareza = document.getElementById('tcgx-catalogo-rareza');
	var selectCondicion = document.getElementById('tcgx-catalogo-condicion');
	var selectIdioma = document.getElementById('tcgx-catalogo-idioma');
	var selectMoneda = document.getElementById('tcgx-catalogo-moneda');
	var inputPrecioMin = document.getElementById('tcgx-catalogo-precio-min');
	var inputPrecioMax = document.getElementById('tcgx-catalogo-precio-max');
	var btnLimpiarFiltros = document.getElementById('tcgx-catalogo-limpiar');
	var contadorCatalogo = document.getElementById('tcgx-catalogo-contador');
	var avisoSinResultados = document.getElementById('tcgx-catalogo-sin-resultados');
	var cartasCatalogo = catalogo ? catalogo.querySelectorAll('.tcgx-client-catalogo-carta') : [];
	var totalCartas = cartasCatalogo.length;
	var debounceBuscar = null;

	function normalizarTexto(texto) {
		return (texto || '').toString().toUpperCase().replace(/\s+/g, ' ').trim();
	}

	function parsePrecio(valor) {
		if (valor === null || valor === undefined) {
			return null;
		}
		var txt = String(valor).trim();
		if (txt === '') {
			return null;
		}
		var num = parseFloat(txt.replace(',', '.'));
		return Number.isFinite(num) ? num : null;
	}

	function coincideSelect(valorFiltro, valorCarta) {
		return valorFiltro === '' || valorCarta === valorFiltro;
	}

	function aplicarFiltroCatalogo() {
		var termino = normalizarTexto(inputBuscar ? inputBuscar.value : '');
		var filtroPub = selectPublicado ? selectPublicado.value : '';
		var filtroExpansion = selectExpansion ? selectExpansion.value : '';
		var filtroRareza = selectRareza ? selectRareza.value : '';
		var filtroCondicion = selectCondicion ? selectCondicion.value : '';
		var filtroIdioma = selectIdioma ? selectIdioma.value : '';
		var filtroMoneda = selectMoneda ? selectMoneda.value : '';
		var precioMin = parsePrecio(inputPrecioMin ? inputPrecioMin.value : '');
		var precioMax = parsePrecio(inputPrecioMax ? inputPrecioMax.value : '');
		var visibles = 0;
		var i;

		for (i = 0; i < cartasCatalogo.length; i++) {
			var carta = cartasCatalogo[i];
			var textoCarta = normalizarTexto(carta.getAttribute('data-tcgx-buscar') || '');
			var precioCarta = parsePrecio(carta.getAttribute('data-tcgx-precio') || '');
			var mostrar = true;

			if (termino !== '' && textoCarta.indexOf(termino) === -1) {
				mostrar = false;
			}
			if (mostrar && !coincideSelect(filtroPub, carta.getAttribute('data-tcgx-publicado') || '')) {
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
			if (mostrar && !coincideSelect(filtroMoneda, carta.getAttribute('data-tcgx-moneda') || '')) {
				mostrar = false;
			}
			if (mostrar && precioMin !== null && (precioCarta === null || precioCarta < precioMin)) {
				mostrar = false;
			}
			if (mostrar && precioMax !== null && (precioCarta === null || precioCarta > precioMax)) {
				mostrar = false;
			}

			if (mostrar) {
				carta.classList.remove('tcgx-client-catalogo-carta--oculta');
				visibles += 1;
			} else {
				carta.classList.add('tcgx-client-catalogo-carta--oculta');
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
		if (selectPublicado) {
			selectPublicado.value = '';
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
		if (selectMoneda) {
			selectMoneda.value = '';
		}
		if (inputPrecioMin) {
			inputPrecioMin.value = '';
		}
		if (inputPrecioMax) {
			inputPrecioMax.value = '';
		}
		aplicarFiltroCatalogo();
		if (inputBuscar) {
			inputBuscar.focus();
		}
	}

	if (inputBuscar) {
		inputBuscar.addEventListener('input', programarFiltroBuscar);
	}
	if (selectPublicado) {
		selectPublicado.addEventListener('change', aplicarFiltroCatalogo);
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
	if (selectMoneda) {
		selectMoneda.addEventListener('change', aplicarFiltroCatalogo);
	}
	if (inputPrecioMin) {
		inputPrecioMin.addEventListener('input', programarFiltroBuscar);
	}
	if (inputPrecioMax) {
		inputPrecioMax.addEventListener('input', programarFiltroBuscar);
	}
	if (btnLimpiarFiltros) {
		btnLimpiarFiltros.addEventListener('click', limpiarFiltrosCatalogo);
	}
	/* === BLOQUE FILTROS Y BUSQUEDA DINAMICOS DEL CATALOGO: FIN === */


	/* === BLOQUE FLASH DEL RESULTADO PREVIO: INICIO === */
	var nodoFlash = document.getElementById('tcgx-binders-flash');
	if (nodoFlash && typeof window.Swal !== 'undefined') {
		var datos;
		try {
			datos = JSON.parse(nodoFlash.textContent || '{}');
		} catch (e) {
			datos = null;
		}
		if (datos && datos.texto) {
			Swal.fire({
				icon: datos.tipo === 'ok' ? 'success' : 'error',
				title: datos.tipo === 'ok' ? 'Listo' : 'Atención',
				text: datos.texto,
				confirmButtonText: 'ACEPTAR'
			});
		}
	}
	/* === BLOQUE FLASH DEL RESULTADO PREVIO: FIN === */

})();
