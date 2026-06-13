/**
 * Mi perfil (modulo admin): edicion de datos no sensibles del usuario en sesion.
 * Responsabilidades: selects encadenados provincia/canton/distrito desde catalogo estatico,
 * validacion en cliente (incluida la politica de contrasena si se escribe una nueva)
 * y despliegue de mensajes de servidor (errores o exito) via SweetAlert2.
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
	var selProvincia = document.getElementById('perfil-provincia');
	var selCanton = document.getElementById('perfil-canton');
	var selDistrito = document.getElementById('perfil-distrito');

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

		// Preseleccion con los valores actuales del usuario (o reintento tras error de validacion).
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


	/* === BLOQUE VALIDACION EN CLIENTE: INICIO ===
	 * Complemento de la validacion definitiva en servidor: datos no sensibles y politica de contrasena.
	 */
	var formulario = document.getElementById('tcgx-perfil-form');

	function valor(id) {
		var el = document.getElementById(id);
		return el ? el.value.trim() : '';
	}

	function validarFormulario() {
		var errores = [];

		var correo = valor('perfil-correo');
		if (correo === '') {
			errores.push('El correo es obligatorio.');
		} else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
			errores.push('El formato del correo no es válido.');
		}
		if (valor('perfil-telefono') === '') {
			errores.push('El teléfono es obligatorio.');
		}
		// Coherencia geografica: si hay provincia, exige canton y distrito.
		if (valor('perfil-provincia') !== '') {
			if (valor('perfil-canton') === '') {
				errores.push('Debe seleccionar un cantón.');
			} else if (valor('perfil-distrito') === '') {
				errores.push('Debe seleccionar un distrito.');
			}
		}

		// Contrasena: opcional; si se escribe en cualquiera de los dos campos se valida la politica.
		var clave = document.getElementById('perfil-clave');
		var clave2 = document.getElementById('perfil-clave-confirma');
		var v1 = clave ? clave.value : '';
		var v2 = clave2 ? clave2.value : '';
		if (v1 !== '' || v2 !== '') {
			if (v1.length < 10) {
				errores.push('La contraseña debe tener al menos 10 caracteres.');
			}
			if (!/[A-Z]/.test(v1)) {
				errores.push('La contraseña debe incluir al menos una letra mayúscula.');
			}
			if (!/[a-z]/.test(v1)) {
				errores.push('La contraseña debe incluir al menos una letra minúscula.');
			}
			if (!/[0-9]/.test(v1)) {
				errores.push('La contraseña debe incluir al menos un número.');
			}
			if (v1 !== v2) {
				errores.push('La confirmación no coincide con la contraseña.');
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


	/* === BLOQUE MENSAJES DE SERVIDOR (ERROR O EXITO): INICIO ===
	 * Lee el JSON embebido por PHP: lista de errores de validacion o confirmacion de exito tras PRG.
	 */
	function mostrarFlash() {
		var nodo = document.getElementById('tcgx-perfil-flash');
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
			return;
		}
		if (datos.tipo === 'ok') {
			Swal.fire({
				icon: 'success',
				text: datos.texto || '',
				confirmButtonText: 'ACEPTAR'
			});
		}
	}
	mostrarFlash();
	/* === BLOQUE MENSAJES DE SERVIDOR (ERROR O EXITO): FIN === */

})();
