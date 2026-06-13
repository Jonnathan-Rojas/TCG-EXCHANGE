/**
 * Runtime de utilidades globales de la interfaz.
 * Incluye correo ofuscado, menu sticky, boton ir arriba, consentimiento de cookies
 * y foco inicial del primer campo habilitado en formularios prioritarios (contenedor central, luego rastreo).
 */
(function () {

	/* === BLOQUE DE OFUSCACION DE CORREO: INICIO ===
	 * Motivo técnico: reducir exposición directa del correo en el HTML y disminuir
	 * recolección automatizada básica por scrapers que leen texto plano.
	 */

	// Clave XOR usada para desofuscar cada código ASCII.
	var k = 19;

	// Reconstruye y monta un enlace mailto dentro del nodo objetivo.
	function mountMailObf(id, codes, linkClass) {
		// Resuelve el contenedor destino; si no existe, se omite sin romper el flujo.
		var el = document.getElementById(id);
		if (!el) {
			return;
		}
		// Transforma la secuencia ofuscada a texto plano aplicando XOR por carácter.
		var raw = codes
			.map(function (x) {
				return String.fromCharCode(x ^ k);
			})
			.join("");
		// Construye el ancla final y aplica clase opcional para estilado contextual.
		var a = document.createElement("a");
		a.href = "mailto:" + raw;
		a.textContent = raw;
		if (linkClass) {
			a.className = linkClass;
		}
		// Limpia contenido previo para evitar duplicidad y monta el enlace final.
		el.textContent = "";
		el.appendChild(a);
	}

	// Secuencia ASCII ofuscada del correo de la empresa.
	var cTcgStore = [
		122, 125, 117, 124, 83, 103, 112, 116, 96, 103, 124, 97, 118, 112, 97, 61, 112, 124, 126,
	];

	// Inyecta correo ofuscado en cabecera (si existe en la vista actual).
	mountMailObf("cab-sup-mail", cTcgStore, "cab-sup__mail-a");
	// Inyecta correo ofuscado en footer (si existe en la vista actual).
	mountMailObf("site-footer-mail", cTcgStore, "site-footer__mail-obf");

	/* === BLOQUE DE OFUSCACION DE CORREO: FIN === */


	/* === BLOQUE DE MENU STICKY POR SCROLL: INICIO ===
	 * Activa o desactiva la clase scroll-on para conservar diseno inicial
	 * y aplicar cabecera fija/compacta solo cuando existe desplazamiento vertical.
	 */
	var stickyNav = document.querySelector(".header-nav-wrapper.header-sticky");
	var stickyHeight = document.querySelector(".sticky-height");
	var stickyTriggerPx = 200;

	function updateStickyMenuState() {
		if (!stickyNav) {
			return;
		}
		if (window.scrollY > stickyTriggerPx) {
			stickyNav.classList.add("scroll-on");
			if (stickyHeight) {
				stickyHeight.style.height = stickyNav.offsetHeight + "px";
			}
			return;
		}
		stickyNav.classList.remove("scroll-on");
		if (stickyHeight) {
			stickyHeight.style.height = "0px";
		}
	}

	window.addEventListener("scroll", updateStickyMenuState, { passive: true });
	window.addEventListener("resize", updateStickyMenuState);
	updateStickyMenuState();
	/* === BLOQUE DE MENU STICKY POR SCROLL: FIN === */


	/* === BLOQUE DE BOTON IR ARRIBA: INICIO ===
	 * Muestra el control tras desplazamiento vertical y ejecuta scroll suave al origen del documento.
	 */
	var scrollToTopBtn = document.getElementById("scroll-to-top");
	var scrollToTopThresholdPx = 380;

	function updateScrollToTopVisibility() {
		if (!scrollToTopBtn) {
			return;
		}
		if (window.scrollY > scrollToTopThresholdPx) {
			scrollToTopBtn.classList.add("scroll-to-top--visible");
			scrollToTopBtn.setAttribute("aria-hidden", "false");
			scrollToTopBtn.removeAttribute("tabindex");
			return;
		}
		scrollToTopBtn.classList.remove("scroll-to-top--visible");
		scrollToTopBtn.setAttribute("aria-hidden", "true");
		scrollToTopBtn.setAttribute("tabindex", "-1");
	}

	if (scrollToTopBtn) {
		scrollToTopBtn.addEventListener("click", function () {
			window.scrollTo({ top: 0, behavior: "smooth" });
		});
		window.addEventListener("scroll", updateScrollToTopVisibility, { passive: true });
		window.addEventListener("resize", updateScrollToTopVisibility);
		updateScrollToTopVisibility();
	}
	/* === BLOQUE DE BOTON IR ARRIBA: FIN === */


	/* === BLOQUE DE CONSENTIMIENTO DE COOKIES: INICIO ===
	 * Controla la visualizacion inicial del aviso y persiste la preferencia en almacenamiento local.
	 */

	var cookieBanner = document.getElementById("cookie-consent-banner");
	var cookieStorageKey = "tcgx_cookie_pref_v1";

	function setCookieBannerVisibility(isVisible) {
		if (!cookieBanner) {
			return;
		}
		if (isVisible) {
			cookieBanner.classList.remove("is-hidden");
			cookieBanner.setAttribute("aria-hidden", "false");
			return;
		}
		cookieBanner.classList.add("is-hidden");
		cookieBanner.setAttribute("aria-hidden", "true");
	}

	function saveCookiePreference(value) {
		try {
			localStorage.setItem(cookieStorageKey, value);
		} catch (error) {
			return;
		}
		setCookieBannerVisibility(false);
	}

	function initCookieBanner() {
		if (!cookieBanner) {
			return;
		}

		var savedPreference = "";

		try {
			savedPreference = localStorage.getItem(cookieStorageKey) || "";
		} catch (error) {
			savedPreference = "";
		}

		if (savedPreference === "essential" || savedPreference === "all") {
			setCookieBannerVisibility(false);
			return;
		}

		setCookieBannerVisibility(true);

		var choiceButtons = cookieBanner.querySelectorAll("[data-cookie-choice]");
		choiceButtons.forEach(function (button) {
			button.addEventListener("click", function () {
				var choice = button.getAttribute("data-cookie-choice");
				if (choice === "essential" || choice === "all") {
					saveCookiePreference(choice);
				}
			});
		});
	}

	initCookieBanner();

	/* === BLOQUE DE CONSENTIMIENTO DE COOKIES: FIN === */


	/* === BLOQUE FOCO PRIMER CAMPO DE FORMULARIOS: INICIO ===
	 * Convencion de UX: al cargar la vista, enfocar el primer control de entrada habilitado del formulario prioritario,
	 * sin sustituir un atributo HTML autofocus ya declarado dentro de un <form>.
	 * Prioridad tecnica: (1) primer <form> dentro de section.contenedor-central-sec (p. ej. login.php); (2) si ninguno
	 * aplica o el formulario no tiene campos enfocables, primer <form> en section.rastreo-sec (portada con guia).
	 * Se omiten input hidden, submit, reset, button y controles disabled; un campo puede excluirse con data-tcgx-skip-autofocus.
	 * --- Foco diferido (DOMContentLoaded + setTimeout 0): INICIO ---
	 * La ejecucion sincrona al parsear tcgexchange.js ocurria antes de que otros listeners (p. ej. Bootstrap en DOMContentLoaded)
	 * terminaran; el foco se perdia en login. Se programa el intento al final del ciclo de carga del documento y un macrotick despues.
	 * --- Foco diferido (DOMContentLoaded + setTimeout 0): FIN ---
	 */
	function focusFirstEligibleField(form) {
		if (!form || typeof form.querySelectorAll !== "function") {
			return false;
		}
		var selector =
			"input:not([type=\"hidden\"]):not([type=\"button\"]):not([type=\"submit\"]):not([type=\"reset\"]):not([disabled])," +
			"select:not([disabled]),textarea:not([disabled])";
		var list = form.querySelectorAll(selector);
		var i;
		for (i = 0; i < list.length; i++) {
			var field = list[i];
			if (field.hasAttribute("data-tcgx-skip-autofocus")) {
				continue;
			}
			/* Sin preventScroll: si el formulario esta bajo el fold (p. ej. login), el navegador puede acercar la vista al control. */
			field.focus();
			return true;
		}
		return false;
	}

	function initPrimaryFormAutofocus() {
		if (document.querySelector("form [autofocus]")) {
			return;
		}
		var central = document.querySelector("section.contenedor-central-sec");
		var forms;
		var f;
		if (central) {
			forms = central.querySelectorAll("form");
			for (f = 0; f < forms.length; f++) {
				if (focusFirstEligibleField(forms[f])) {
					return;
				}
			}
		}
		var rastreo = document.querySelector("section.rastreo-sec");
		if (rastreo) {
			forms = rastreo.querySelectorAll("form");
			for (f = 0; f < forms.length; f++) {
				if (focusFirstEligibleField(forms[f])) {
					return;
				}
			}
		}
	}

	function schedulePrimaryFormAutofocus() {
		function runAfterLayout() {
			window.setTimeout(initPrimaryFormAutofocus, 0);
		}
		if (document.readyState === "loading") {
			document.addEventListener("DOMContentLoaded", runAfterLayout);
			return;
		}
		runAfterLayout();
	}

	schedulePrimaryFormAutofocus();

	/* === BLOQUE FOCO PRIMER CAMPO DE FORMULARIOS: FIN === */

})();
