/**
 * Panel cliente (client): offcanvas, colapsar sidebar y foco inicial en formularios prioritarios.
 */
(function () {
	'use strict';

	/* === BLOQUE OFFCANVAS NAV ANCLAS: INICIO ===
	 * Tras elegir una seccion en menu movil, oculta el offcanvas para no dejar la capa bloqueando el contenido.
	 */
	var offcanvasEl = document.getElementById('tcgx-client-offcanvas-nav');
	if (offcanvasEl && typeof window.bootstrap !== 'undefined') {
		var offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
		offcanvasEl.querySelectorAll('.tcgx-client-sidebar__nav a.nav-link').forEach(function (a) {
			a.addEventListener('click', function () {
				offcanvas.hide();
			});
		});
	}
	/* === BLOQUE OFFCANVAS NAV ANCLAS: FIN === */

	/* === BLOQUE SIDEBAR COLAPSAR ESCRITORIO: INICIO ===
	 * Alterna clase en el root del panel, guarda preferencia en localStorage y sincroniza aria/titulo del boton.
	 */
	var LS_SIDEBAR = 'tcgexchange_client_sidebar_collapsed';
	var rootApp = document.getElementById('tcgx-client-app-root');
	var btnToggle = document.getElementById('tcgx-client-btn-toggle-sidebar');

	function tcgexchangeStoreSyncSidebarButton() {
		if (!btnToggle || !rootApp) {
			return;
		}
		var collapsed = rootApp.classList.contains('tcgx-client-app--sidebar-collapsed');
		btnToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
		btnToggle.setAttribute('title', collapsed ? 'Expandir menú lateral' : 'Contraer menú lateral');
	}

	if (rootApp && window.localStorage.getItem(LS_SIDEBAR) === '1') {
		rootApp.classList.add('tcgx-client-app--sidebar-collapsed');
	}
	tcgexchangeStoreSyncSidebarButton();

	if (btnToggle && rootApp) {
		btnToggle.addEventListener('click', function () {
			rootApp.classList.toggle('tcgx-client-app--sidebar-collapsed');
			var on = rootApp.classList.contains('tcgx-client-app--sidebar-collapsed');
			try {
				window.localStorage.setItem(LS_SIDEBAR, on ? '1' : '0');
			} catch (e) {
				// Almacenamiento no disponible: el estado solo vive en la sesion de documento.
			}
			tcgexchangeStoreSyncSidebarButton();
		});
	}
	/* === BLOQUE SIDEBAR COLAPSAR ESCRITORIO: FIN === */


	/* === BLOQUE FOCO INICIAL EN FORMULARIO PRIORITARIO: INICIO ===
	 * Primer control habilitado del primer form en .tcgx-client-content; respeta autofocus HTML declarado.
	 */
	function focusFirstEligibleField(form) {
		if (!form) {
			return false;
		}
		var fields = form.querySelectorAll('input, select, textarea');
		var i;
		for (i = 0; i < fields.length; i++) {
			var field = fields[i];
			var type = (field.getAttribute('type') || '').toLowerCase();
			if (type === 'hidden' || type === 'submit' || type === 'reset' || type === 'button') {
				continue;
			}
			if (field.disabled || field.hasAttribute('data-tcgx-skip-autofocus')) {
				continue;
			}
			field.focus();
			return true;
		}
		return false;
	}

	function initPrimaryFormAutofocus() {
		if (document.querySelector('.tcgx-client-content form [autofocus]')) {
			return;
		}
		var main = document.getElementById('tcgx-client-main');
		if (!main) {
			return;
		}
		var forms = main.querySelectorAll('form');
		var f;
		for (f = 0; f < forms.length; f++) {
			if (focusFirstEligibleField(forms[f])) {
				return;
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			window.setTimeout(initPrimaryFormAutofocus, 0);
		});
	} else {
		window.setTimeout(initPrimaryFormAutofocus, 0);
	}
	/* === BLOQUE FOCO INICIAL EN FORMULARIO PRIORITARIO: FIN === */
})();
