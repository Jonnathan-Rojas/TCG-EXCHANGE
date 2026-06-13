/**
 * Formulario de usuario (alta y edicion) del modulo admin.
 * Responsabilidades: selects encadenados provincia/canton/distrito desde catalogo estatico,
 * visibilidad del campo tienda segun perfil, validacion en cliente y despliegue de errores de servidor.
 * Depende de SweetAlert2 cargado antes en la pagina.
 */
(function () {
	'use strict';

	/* === BLOQUE LECTURA DE CATALOGO GEOGRAFICO: INICIO ===
	 * Catalogo embebido por PHP como JSON: { PROVINCIA: { CANTON: [DISTRITO, ...] } }.
	 */
	var catalogo = {};
	var nodoCatalogo = document.getElementById('tcgx-catalogo-geo');
	if (nodoCatalogo) {
		try {
			catalogo = JSON.parse(nodoCatalogo.textContent || '{}');
		} catch (e) {
			catalogo = {};
		}
	}
	/* === BLOQUE LECTURA DE CATALOGO GEOGRAFICO: FIN === */


	/* === BLOQUE SELECTS ENCADENADOS PROVINCIA/CANTON/DISTRITO: INICIO ===
	 * Poblado dependiente y preseleccion a partir de data-tcgx-selected (valores ya en MAYUSCULAS).
	 */
	var selProvincia = document.getElementById('usuario-provincia');
	var selCanton = document.getElementById('usuario-canton');
	var selDistrito = document.getElementById('usuario-distrito');

	function limpiarSelect(select) {
		if (!select) {
			return;
		}
		select.innerHTML = '<option value="">SELECCIONE…</option>';
	}

	function agregarOpciones(select, valores) {
		if (!select) {
			return;
		}
		valores.forEach(function (valor) {
			var opcion = document.createElement('option');
			opcion.value = valor;
			opcion.textContent = valor;
			select.appendChild(opcion);
		});
	}

	function poblarProvincias() {
		if (!selProvincia) {
			return;
		}
		agregarOpciones(selProvincia, Object.keys(catalogo));
	}

	function poblarCantones(provincia) {
		limpiarSelect(selCanton);
		limpiarSelect(selDistrito);
		if (provincia && catalogo[provincia]) {
			agregarOpciones(selCanton, Object.keys(catalogo[provincia]));
		}
	}

	function poblarDistritos(provincia, canton) {
		limpiarSelect(selDistrito);
		if (provincia && canton && catalogo[provincia] && catalogo[provincia][canton]) {
			agregarOpciones(selDistrito, catalogo[provincia][canton]);
		}
	}

	if (selProvincia && selCanton && selDistrito) {
		poblarProvincias();

		// Preseleccion (modo edicion o reintento por error de validacion).
		var provSel = selProvincia.getAttribute('data-tcgx-selected') || '';
		var cantSel = selCanton.getAttribute('data-tcgx-selected') || '';
		var distSel = selDistrito.getAttribute('data-tcgx-selected') || '';
		if (provSel && catalogo[provSel]) {
			selProvincia.value = provSel;
			poblarCantones(provSel);
			if (cantSel && catalogo[provSel][cantSel]) {
				selCanton.value = cantSel;
				poblarDistritos(provSel, cantSel);
				if (distSel) {
					selDistrito.value = distSel;
				}
			}
		}

		selProvincia.addEventListener('change', function () {
			poblarCantones(selProvincia.value);
		});
		selCanton.addEventListener('change', function () {
			poblarDistritos(selProvincia.value, selCanton.value);
		});
	}
	/* === BLOQUE SELECTS ENCADENADOS PROVINCIA/CANTON/DISTRITO: FIN === */


	/* === BLOQUE VISIBILIDAD DE TIENDA SEGUN PERFIL: INICIO ===
	 * El campo tienda solo aplica a perfil TIENDA; se oculta y limpia para CLIENTE y ADMINISTRADOR.
	 */
	var selPerfil = document.getElementById('usuario-perfil');
	var campoTienda = document.getElementById('usuario-campo-tienda');
	var selTienda = document.getElementById('usuario-idtienda');

	function sincronizarCampoTienda() {
		if (!selPerfil || !campoTienda) {
			return;
		}
		var esTienda = selPerfil.value === 'TIENDA';
		campoTienda.classList.toggle('d-none', !esTienda);
		if (selTienda) {
			if (esTienda) {
				selTienda.setAttribute('required', 'required');
			} else {
				selTienda.removeAttribute('required');
				selTienda.value = '';
			}
		}
	}

	if (selPerfil) {
		sincronizarCampoTienda();
		selPerfil.addEventListener('change', sincronizarCampoTienda);
	}
	/* === BLOQUE VISIBILIDAD DE TIENDA SEGUN PERFIL: FIN === */


	/* === BLOQUE VALIDACION EN CLIENTE: INICIO ===
	 * Comprobaciones previas al envio (complemento de la validacion definitiva en servidor).
	 */
	var formulario = document.getElementById('tcgx-usuario-form');

	function valor(id) {
		var el = document.getElementById(id);
		return el ? el.value.trim() : '';
	}

	function validarFormulario() {
		var errores = [];
		var inputId = document.getElementById('usuario-id');
		if (inputId && !inputId.readOnly && valor('usuario-id') === '') {
			errores.push('El identificador es obligatorio.');
		}
		if (valor('usuario-nombre') === '') {
			errores.push('El nombre es obligatorio.');
		}
		var correo = valor('usuario-correo');
		if (correo === '') {
			errores.push('El correo es obligatorio.');
		} else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
			errores.push('El formato del correo no es válido.');
		}
		if (valor('usuario-telefono') === '') {
			errores.push('El teléfono es obligatorio.');
		}
		if (valor('usuario-perfil') === '') {
			errores.push('Debe seleccionar un perfil.');
		} else if (valor('usuario-perfil') === 'TIENDA' && valor('usuario-idtienda') === '') {
			errores.push('El perfil TIENDA requiere seleccionar una tienda.');
		}
		// Coherencia geografica: si hay provincia, exige canton y distrito.
		if (valor('usuario-provincia') !== '') {
			if (valor('usuario-canton') === '') {
				errores.push('Debe seleccionar un cantón.');
			} else if (valor('usuario-distrito') === '') {
				errores.push('Debe seleccionar un distrito.');
			}
		}
		return errores;
	}

	if (formulario) {
		formulario.addEventListener('submit', function (evento) {
			var errores = validarFormulario();
			if (errores.length > 0) {
				evento.preventDefault();
				if (typeof window.Swal !== 'undefined') {
					Swal.fire({
						icon: 'error',
						title: 'Revise los datos',
						html: '<ul class="text-start mb-0">' + errores.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul>',
						confirmButtonText: 'ACEPTAR'
					});
				}
			}
		});
	}
	/* === BLOQUE VALIDACION EN CLIENTE: FIN === */


	/* === BLOQUE ERRORES DE SERVIDOR (REINTENTO): INICIO ===
	 * Muestra los errores devueltos por la validacion de servidor tras un envio fallido.
	 */
	function mostrarErroresServidor() {
		var nodo = document.getElementById('tcgx-form-flash');
		if (!nodo || typeof window.Swal === 'undefined') {
			return;
		}
		var datos;
		try {
			datos = JSON.parse(nodo.textContent || '{}');
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
	}
	mostrarErroresServidor();
	/* === BLOQUE ERRORES DE SERVIDOR (REINTENTO): FIN === */

})();
