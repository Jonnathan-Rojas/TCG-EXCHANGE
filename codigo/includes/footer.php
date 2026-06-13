<?php
declare(strict_types=1);

require_once __DIR__ . '/rutas_assets.php';

$tcgxUrlLogoFooter = tcgexchange_url_recurso_proyecto('images/logo-footer.svg');

/**
 * Fragmento de plantilla: desde el comentario BLOQUE FOOTER INICIO hasta el cierre de documento.
 * Motivo tecnico: division solicitada por el programador; incluye pie, boton ir arriba, cookies, scripts y cierre html/body.
 * Lo consume index.php con require; el body debe estar abierto previamente (p. ej. via includes/header.php).
 */
?>

   <!-- === BLOQUE FOOTER: INICIO ===
        Pie de pagina con contacto, enlaces, redes y barra legal. -->

   <footer class="site-footer">
      <div class="site-footer__main">
         <div class="container">
            <div class="row g-4 g-lg-5 justify-content-center align-items-center">
               <div class="col-lg-4 col-md-6 text-start">
                  <a href="index.php" class="site-footer__logo d-inline-block mb-4">
                     <img src="<?php echo htmlspecialchars($tcgxUrlLogoFooter, ENT_QUOTES, 'UTF-8'); ?>" alt="TCG EXCHANGE, By TCG GAMES STORE" class="img-fluid" decoding="async">
                  </a>
                  <ul class="site-footer__contact list-unstyled mb-0">
                     <li><span class="site-footer__contact-icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span><span id="site-footer-mail"></span></li>
                     <li><span class="site-footer__contact-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span><span>Limón, Costa Rica</span></li>
                     <li><span class="site-footer__contact-icon" aria-hidden="true"><i class="fa-solid fa-phone"></i></span><a href="tel:+50661355305">+506 6135 5305</a></li>
                  </ul>
               </div>
               <div class="col-lg-3 col-md-6 text-start">
                  <h3 class="site-footer__title">Links Utiles</h3>
                  <ul class="site-footer__list list-unstyled mb-0">
                     <li><a href="index.php"><i class="fa-solid fa-angle-right" aria-hidden="true"></i>Inicio</a></li>
                     <li><a href="login.php"><i class="fa-solid fa-angle-right" aria-hidden="true"></i>Login</a></li>
                     <li><a href="calificacion_usuarios.php"><i class="fa-solid fa-angle-right" aria-hidden="true"></i>Calificación de Usuarios</a></li>
                     <li><a href="preguntas-frecuentes.php"><i class="fa-solid fa-angle-right" aria-hidden="true"></i>Preguntas Frecuentes</a></li>
                  </ul>
               </div>
               <div class="col-lg-4 col-md-6 offset-md-3 offset-lg-0 text-start">
                  <div class="site-footer__socials mb-3">
                     <a href="https://www.facebook.com/tcgstoregames/" class="site-footer__social" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f"></i></a>
                     <a href="https://www.tiktok.com/@tcg_limon_oficial" class="site-footer__social" aria-label="TikTok" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-tiktok"></i></a>
                     <a href="https://www.instagram.com/tcg_store_limon/" class="site-footer__social" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram"></i></a>
                  </div>
                  <p class="site-footer__credit mb-0">Desarrollado por <a href="https://www.arvexlabs.com/" target="_blank" rel="noopener noreferrer">Arvex Labs</a></p>
               </div>
            </div>
         </div>
      </div>
      <div class="site-footer__bar">
         <div class="container site-footer__bar-inner">
            <p class="site-footer__copy">&copy; 2026 <a href="index.php">TCG EXCHANGE</a>. Todos los derechos reservados.</p>
            <nav class="site-footer__nav" aria-label="Legal">
               <a href="politica-privacidad.php">Política de privacidad</a>
               <span class="site-footer__sep">|</span>
               <!-- Destino terminos-condiciones.php: pagina legal homogenea con el layout global del sitio. -->
               <a href="terminos-condiciones.php">Términos y condiciones</a>
            </nav>
         </div>
      </div>
   </footer>

   <!-- === BLOQUE FOOTER: FIN === -->

   <!-- === BLOQUE BOTON IR ARRIBA: INICIO ===
        Control fijo para scroll suave al inicio del documento; visibilidad y click en tcgexchange.js -->
   <button type="button" class="scroll-to-top" id="scroll-to-top" aria-label="Volver al inicio de la página" title="Volver arriba" aria-hidden="true" tabindex="-1">
      <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
   </button>
   <!-- === BLOQUE BOTON IR ARRIBA: FIN === -->

   <!-- === BLOQUE AVISO DE COOKIES: INICIO ===
        Banda informativa para consentimiento de cookies con acciones de preferencia. -->

   <section class="cookie-banner is-hidden" id="cookie-consent-banner" aria-label="Aviso de cookies" aria-hidden="true">
      <div class="container cookie-banner__inner">
         <div class="cookie-banner__copy">
            <h2 class="cookie-banner__title">Cookies</h2>
            <p class="cookie-banner__text">
               Este sitio utiliza cookies para el funcionamiento esencial y para mejorar la experiencia de navegación.
               Puede elegir solo cookies esenciales o aceptar todas.
               Consulte el detalle en la
               <a href="politica-privacidad.php">Política de privacidad</a>.
            </p>
         </div>
         <div class="cookie-banner__actions">
            <button class="cookie-banner__btn cookie-banner__btn--secondary" type="button" data-cookie-choice="essential">Solo esenciales</button>
            <button class="cookie-banner__btn cookie-banner__btn--primary" type="button" data-cookie-choice="all">Aceptar todas</button>
         </div>
      </div>
   </section>

   <!-- === BLOQUE AVISO DE COOKIES: FIN === -->

   <!-- === BLOQUE SCRIPTS: INICIO ===
        Dependencias JS de Bootstrap y script tecnico de la pagina. -->

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <!-- INICIO BLOQUE: SWEETALERT2 JS
        API global Swal para mensajes al usuario; version fijada en URL acorde a diseño.md. -->
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <!-- FIN BLOQUE: SWEETALERT2 JS -->
   <script src="vendor/js/tcgexchange.js"></script>

   <!-- === BLOQUE SCRIPTS: FIN === -->

</body>

<!-- === BLOQUE BODY: FIN === -->

</html>
