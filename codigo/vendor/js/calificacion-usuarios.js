/**
 * Calificacion publica de usuarios: filtros dinamicos en cliente (sin GET).
 */
(function () {
	'use strict';

	/* === BLOQUE FILTROS CALIFICACION USUARIOS: INICIO === */
	var lista = document.getElementById('tcgx-calificacion-usuarios-lista');
	var inputBuscar = document.getElementById('tcgx-calificacion-usuarios-buscar');
	var selectProvincia = document.getElementById('tcgx-calificacion-usuarios-provincia');
	var selectCanton = document.getElementById('tcgx-calificacion-usuarios-canton');
	var selectReputacion = document.getElementById('tcgx-calificacion-usuarios-reputacion');
	var btnLimpiar = document.getElementById('tcgx-calificacion-usuarios-limpiar');
	var contador = document.getElementById('tcgx-calificacion-usuarios-contador');
	var avisoSinResultados = document.getElementById('tcgx-calificacion-usuarios-sin-resultados');
	var jsonCantones = document.getElementById('tcgx-calificacion-usuarios-cantones-json');
	var filas = lista ? lista.querySelectorAll('.tcgx-calificacion-usuarios-fila') : [];
	var totalFilas = filas.length;
	var debounceBuscar = null;
	var cantonesPorProvincia = {};
	var cantonesIniciales = [];

	if (!lista || totalFilas === 0) {
		return;
	}

	if (jsonCantones && jsonCantones.textContent) {
		try {
			cantonesPorProvincia = JSON.parse(jsonCantones.textContent);
		} catch (e) {
			cantonesPorProvincia = {};
		}
	}

	if (selectCanton) {
		var i;
		for (i = 0; i < selectCanton.options.length; i++) {
			if (selectCanton.options[i].value !== '') {
				cantonesIniciales.push(selectCanton.options[i].value);
			}
		}
	}

	function normalizarTexto(texto) {
		return (texto || '').toString().toUpperCase().replace(/\s+/g, ' ').trim();
	}

	function cumpleMinimo(valorFila, minimoSeleccionado) {
		if (minimoSeleccionado === '') {
			return true;
		}
		return parseFloat(valorFila) >= parseFloat(minimoSeleccionado);
	}

	function coincideSelect(valorFiltro, valorFila) {
		return valorFiltro === '' || valorFila === valorFiltro;
	}

	function repoblarCantones(provinciaSeleccionada, cantonActual) {
		if (!selectCanton) {
			return;
		}
		var opciones = cantonesIniciales;
		if (provinciaSeleccionada !== '' && cantonesPorProvincia[provinciaSeleccionada]) {
			opciones = cantonesPorProvincia[provinciaSeleccionada];
		}
		var optTodos = document.createElement('option');
		optTodos.value = '';
		optTodos.textContent = 'TODOS';
		selectCanton.textContent = '';
		selectCanton.appendChild(optTodos);
		var j;
		for (j = 0; j < opciones.length; j++) {
			var opt = document.createElement('option');
			opt.value = opciones[j];
			opt.textContent = opciones[j];
			selectCanton.appendChild(opt);
		}
		if (cantonActual !== '' && opciones.indexOf(cantonActual) !== -1) {
			selectCanton.value = cantonActual;
		}
	}

	function aplicarFiltroCalificacion() {
		var termino = normalizarTexto(inputBuscar ? inputBuscar.value : '');
		var filtroProvincia = selectProvincia ? selectProvincia.value : '';
		var filtroCanton = selectCanton ? selectCanton.value : '';
		var filtroReputacion = selectReputacion ? selectReputacion.value : '';
		var visibles = 0;
		var i;

		for (i = 0; i < filas.length; i++) {
			var fila = filas[i];
			var mostrar = true;

			if (termino !== '' && normalizarTexto(fila.getAttribute('data-tcgx-nombre') || '').indexOf(termino) === -1) {
				mostrar = false;
			}
			if (mostrar && !coincideSelect(filtroProvincia, fila.getAttribute('data-tcgx-provincia') || '')) {
				mostrar = false;
			}
			if (mostrar && !coincideSelect(filtroCanton, fila.getAttribute('data-tcgx-canton') || '')) {
				mostrar = false;
			}
			if (mostrar && !cumpleMinimo(fila.getAttribute('data-tcgx-reputacion') || '0', filtroReputacion)) {
				mostrar = false;
			}

			if (mostrar) {
				fila.classList.remove('tcgx-calificacion-usuarios-fila--oculta');
				visibles += 1;
			} else {
				fila.classList.add('tcgx-calificacion-usuarios-fila--oculta');
			}
		}

		if (contador) {
			contador.textContent = visibles + ' / ' + totalFilas;
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
		debounceBuscar = window.setTimeout(aplicarFiltroCalificacion, 120);
	}

	function limpiarFiltrosCalificacion() {
		if (inputBuscar) {
			inputBuscar.value = '';
		}
		if (selectProvincia) {
			selectProvincia.value = '';
		}
		repoblarCantones('', '');
		if (selectReputacion) {
			selectReputacion.value = '';
		}
		aplicarFiltroCalificacion();
		if (inputBuscar) {
			inputBuscar.focus();
		}
	}

	if (inputBuscar) {
		inputBuscar.addEventListener('input', programarFiltroBuscar);
	}
	if (selectProvincia) {
		selectProvincia.addEventListener('change', function () {
			var cantonActual = selectCanton ? selectCanton.value : '';
			repoblarCantones(selectProvincia.value, cantonActual);
			aplicarFiltroCalificacion();
		});
	}
	if (selectCanton) {
		selectCanton.addEventListener('change', aplicarFiltroCalificacion);
	}
	if (selectReputacion) {
		selectReputacion.addEventListener('change', aplicarFiltroCalificacion);
	}
	if (btnLimpiar) {
		btnLimpiar.addEventListener('click', limpiarFiltrosCalificacion);
	}
	/* === BLOQUE FILTROS CALIFICACION USUARIOS: FIN === */
})();
