/**
 * Panel centro de distribucion (cd): cierre del offcanvas al usar anclas del menu movil y colapsar sidebar en escritorio.
 */
(function () {
	'use strict';

	/* === BLOQUE OFFCANVAS NAV ANCLAS: INICIO ===
	 * Tras elegir una seccion en menu movil, oculta el offcanvas para no dejar la capa bloqueando el contenido.
	 */
	var offcanvasEl = document.getElementById('tcgx-cd-offcanvas-nav');
	if (offcanvasEl && typeof window.bootstrap !== 'undefined') {
		var offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
		offcanvasEl.querySelectorAll('.tcgx-cd-sidebar__nav a.nav-link').forEach(function (a) {
			a.addEventListener('click', function () {
				offcanvas.hide();
			});
		});
	}
	/* === BLOQUE OFFCANVAS NAV ANCLAS: FIN === */

	/* === BLOQUE SIDEBAR COLAPSAR ESCRITORIO: INICIO ===
	 * Alterna clase en el root del panel, guarda preferencia en localStorage y sincroniza aria/titulo del boton.
	 */
	var LS_SIDEBAR = 'tcgexchange_cd_sidebar_collapsed';
	var rootApp = document.getElementById('tcgx-cd-app-root');
	var btnToggle = document.getElementById('tcgx-cd-btn-toggle-sidebar');

	function tcgexchangeCdSyncSidebarButton() {
		if (!btnToggle || !rootApp) {
			return;
		}
		var collapsed = rootApp.classList.contains('tcgx-cd-app--sidebar-collapsed');
		btnToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
		btnToggle.setAttribute('title', collapsed ? 'Expandir menú lateral' : 'Contraer menú lateral');
	}

	if (rootApp && window.localStorage.getItem(LS_SIDEBAR) === '1') {
		rootApp.classList.add('tcgx-cd-app--sidebar-collapsed');
	}
	tcgexchangeCdSyncSidebarButton();

	if (btnToggle && rootApp) {
		btnToggle.addEventListener('click', function () {
			rootApp.classList.toggle('tcgx-cd-app--sidebar-collapsed');
			var on = rootApp.classList.contains('tcgx-cd-app--sidebar-collapsed');
			try {
				window.localStorage.setItem(LS_SIDEBAR, on ? '1' : '0');
			} catch (e) {
				// Almacenamiento no disponible: el estado solo vive en la sesion de documento.
			}
			tcgexchangeCdSyncSidebarButton();
		});
	}
	/* === BLOQUE SIDEBAR COLAPSAR ESCRITORIO: FIN === */
})();
