<?php
declare(strict_types=1);

/**
 * Barra lateral del modulo client.
 *
 * Variables requeridas: $tcgxClientUrlLogoOscuro, $tcgxClientScriptNombre.
 * Variable opcional: $tcgxClientSidebarModo ('escritorio' | 'offcanvas').
 * Variable opcional: $tcgxClientNavEnviosSeccion ('envios' | 'recepciones') en envio-ver.php.
 */

$tcgxClientSidebarModo = $tcgxClientSidebarModo ?? 'escritorio';
$tcgxNavInicioActivo = ($tcgxClientScriptNombre ?? '') === 'index.php';
$tcgxNavBindersActivo = in_array(($tcgxClientScriptNombre ?? ''), ['binders.php', 'binder-crear.php', 'binder-editar.php', 'binder-ver.php', 'producto-crear.php', 'producto-editar.php'], true);
$tcgxNavEnviosActivo = ($tcgxClientScriptNombre ?? '') === 'envios.php'
    || (($tcgxClientScriptNombre ?? '') === 'envio-ver.php' && ($tcgxClientNavEnviosSeccion ?? 'envios') === 'envios');
$tcgxNavRecepcionesActivo = ($tcgxClientScriptNombre ?? '') === 'recepciones.php'
    || (($tcgxClientScriptNombre ?? '') === 'envio-ver.php' && ($tcgxClientNavEnviosSeccion ?? '') === 'recepciones');
$tcgxNavPerfilActivo = ($tcgxClientScriptNombre ?? '') === 'mi-perfil.php';

if ($tcgxClientSidebarModo === 'offcanvas') {
    ?>
   <!-- INICIO FRAGMENTO: OFFCANVAS MENU MOVIL (client/includes/sidebar.php) -->
   <div class="offcanvas offcanvas-start tcgx-client-offcanvas" tabindex="-1" id="tcgx-client-offcanvas-nav" aria-labelledby="tcgx-client-offcanvas-label">
      <div class="offcanvas-header">
         <h2 class="h5 offcanvas-title text-white" id="tcgx-client-offcanvas-label">Menú del panel</h2>
         <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
      </div>
      <div class="offcanvas-body p-0">
         <div class="tcgx-client-sidebar__brand border-0 pt-0">
            <a href="index.php" class="tcgx-client-sidebar__logo-link" aria-label="TCG EXCHANGE, inicio del panel">
               <img class="tcgx-client-sidebar__logo-full" src="<?php echo htmlspecialchars($tcgxClientUrlLogoOscuro, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="290" height="68" decoding="async">
            </a>
         </div>
         <nav class="tcgx-client-sidebar__nav flex-column nav px-3 pb-3" aria-label="Navegación principal en móvil">
            <a class="nav-link <?php echo $tcgxNavInicioActivo ? 'active' : ''; ?>" href="index.php" title="Inicio"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Inicio</span></a>
            <a class="nav-link <?php echo $tcgxNavBindersActivo ? 'active' : ''; ?>" href="binders.php" title="Mis binders"><i class="fa-solid fa-book" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Mis binders</span></a>
            <a class="nav-link <?php echo $tcgxNavEnviosActivo ? 'active' : ''; ?>" href="envios.php" title="Envíos"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Envíos</span></a>
            <a class="nav-link <?php echo $tcgxNavRecepcionesActivo ? 'active' : ''; ?>" href="recepciones.php" title="Recepciones"><i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Recepciones</span></a>
            <a class="nav-link <?php echo $tcgxNavPerfilActivo ? 'active' : ''; ?>" href="mi-perfil.php" title="Mi perfil"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Mi perfil</span></a>
         </nav>
      </div>
   </div>
   <!-- FIN FRAGMENTO: OFFCANVAS MENU MOVIL -->
    <?php
    return;
}
?>
      <!-- INICIO FRAGMENTO: SIDEBAR ESCRITORIO (client/includes/sidebar.php) -->
      <aside class="tcgx-client-sidebar d-none d-xl-flex" id="tcgx-client-sidebar-desktop" aria-label="Navegación principal del panel">
         <div class="tcgx-client-sidebar__brand">
            <a href="index.php" class="tcgx-client-sidebar__logo-link" aria-label="TCG EXCHANGE, inicio del panel">
               <img class="tcgx-client-sidebar__logo-full" src="<?php echo htmlspecialchars($tcgxClientUrlLogoOscuro, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="290" height="68" decoding="async">
               <span class="tcgx-client-sidebar__logo-mark" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
            </a>
         </div>
         <div class="tcgx-client-sidebar__label"><span class="tcgx-client-sidebar__label-text">Menú</span></div>
         <nav class="tcgx-client-sidebar__nav flex-column nav" aria-label="Navegación principal">
            <a class="nav-link <?php echo $tcgxNavInicioActivo ? 'active' : ''; ?>" href="index.php" title="Inicio"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Inicio</span></a>
            <a class="nav-link <?php echo $tcgxNavBindersActivo ? 'active' : ''; ?>" href="binders.php" title="Mis binders"><i class="fa-solid fa-book" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Mis binders</span></a>
            <a class="nav-link <?php echo $tcgxNavEnviosActivo ? 'active' : ''; ?>" href="envios.php" title="Envíos"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Envíos</span></a>
            <a class="nav-link <?php echo $tcgxNavRecepcionesActivo ? 'active' : ''; ?>" href="recepciones.php" title="Recepciones"><i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Recepciones</span></a>
            <a class="nav-link <?php echo $tcgxNavPerfilActivo ? 'active' : ''; ?>" href="mi-perfil.php" title="Mi perfil"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span class="tcgx-client-sidebar__text">Mi perfil</span></a>
         </nav>
      </aside>
      <!-- FIN FRAGMENTO: SIDEBAR ESCRITORIO -->
