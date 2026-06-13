/**
 * Panel administrador: cierre del offcanvas al usar anclas del menu movil y colapsar sidebar en escritorio.
 */
(function () {
	'use strict';

	/* === BLOQUE OFFCANVAS NAV ANCLAS: INICIO ===
	 * Tras elegir una seccion en menu movil, oculta el offcanvas para no dejar la capa bloqueando el contenido.
	 */
	var offcanvasEl = document.getElementById('tcgx-admin-offcanvas-nav');
	if (offcanvasEl && typeof window.bootstrap !== 'undefined') {
		var offcanvas = window.bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
		offcanvasEl.querySelectorAll('.tcgx-admin-sidebar__nav a.nav-link').forEach(function (a) {
			a.addEventListener('click', function () {
				offcanvas.hide();
			});
		});
	}
	/* === BLOQUE OFFCANVAS NAV ANCLAS: FIN === */

	/* === BLOQUE SIDEBAR COLAPSAR ESCRITORIO: INICIO ===
	 * Alterna clase en el root del panel, guarda preferencia en localStorage y sincroniza aria/titulo del boton.
	 */
	var LS_SIDEBAR = 'tcgexchange_admin_sidebar_collapsed';
	var rootApp = document.getElementById('tcgx-admin-app-root');
	var btnToggle = document.getElementById('tcgx-admin-btn-toggle-sidebar');

	function tcgexchangeAdminSyncSidebarButton() {
		if (!btnToggle || !rootApp) {
			return;
		}
		var collapsed = rootApp.classList.contains('tcgx-admin-app--sidebar-collapsed');
		btnToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
		btnToggle.setAttribute('title', collapsed ? 'Expandir menú lateral' : 'Contraer menú lateral');
	}

	if (rootApp && window.localStorage.getItem(LS_SIDEBAR) === '1') {
		rootApp.classList.add('tcgx-admin-app--sidebar-collapsed');
	}
	tcgexchangeAdminSyncSidebarButton();

	if (btnToggle && rootApp) {
		btnToggle.addEventListener('click', function () {
			rootApp.classList.toggle('tcgx-admin-app--sidebar-collapsed');
			var on = rootApp.classList.contains('tcgx-admin-app--sidebar-collapsed');
			try {
				window.localStorage.setItem(LS_SIDEBAR, on ? '1' : '0');
			} catch (e) {
				// Almacenamiento no disponible: el estado solo vive en la sesion de documento.
			}
			tcgexchangeAdminSyncSidebarButton();
		});
	}
	/* === BLOQUE SIDEBAR COLAPSAR ESCRITORIO: FIN === */
})();
