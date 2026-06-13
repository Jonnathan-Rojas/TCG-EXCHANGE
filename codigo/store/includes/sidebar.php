<?php
declare(strict_types=1);

/**
 * Barra lateral del modulo store: por defecto aside escritorio; con $tcgxStoreSidebarModo === 'offcanvas' solo el panel movil.
 *
 * Variables requeridas: $tcgxStoreUrlLogoOscuro (string, URL absoluta al sitio hacia images/logo-on-dark.svg).
 * Variable opcional: $tcgxStoreSidebarModo ('escritorio' | 'offcanvas'); sin definir se asume escritorio.
 * Variable requerida para item activo: $tcgxStoreScriptNombre (string, basename del script actual).
 */

$tcgxStoreSidebarModo = $tcgxStoreSidebarModo ?? 'escritorio';
$tcgxNavInicioActivo = ($tcgxStoreScriptNombre ?? '') === 'index.php';
$tcgxNavEnviosActivo = in_array(($tcgxStoreScriptNombre ?? ''), ['envios.php', 'envio-registrar.php', 'envio-ver.php'], true);
$tcgxNavConsolidadosActivo = in_array(($tcgxStoreScriptNombre ?? ''), ['consolidados.php', 'consolidado-armar.php', 'consolidado-ver.php'], true);
$tcgxNavIncidenciasActivo = in_array(($tcgxStoreScriptNombre ?? ''), ['incidencias.php', 'incidencia-ver.php', 'incidencia-registrar.php'], true);
$tcgxNavDevolucionesActivo = in_array(($tcgxStoreScriptNombre ?? ''), ['devoluciones.php', 'devolucion-ver.php'], true);
$tcgxNavEvaluacionesActivo = in_array(($tcgxStoreScriptNombre ?? ''), ['evaluaciones.php', 'evaluacion-crear.php', 'evaluacion-editar.php'], true);
$tcgxNavPerfilActivo = ($tcgxStoreScriptNombre ?? '') === 'mi-perfil.php';

if ($tcgxStoreSidebarModo === 'offcanvas') {
    ?>
   <!-- INICIO FRAGMENTO: OFFCANVAS MENU MOVIL (store/includes/sidebar.php) -->
   <div class="offcanvas offcanvas-start tcgx-store-offcanvas" tabindex="-1" id="tcgx-store-offcanvas-nav" aria-labelledby="tcgx-store-offcanvas-label">
      <div class="offcanvas-header">
         <h2 class="h5 offcanvas-title text-white" id="tcgx-store-offcanvas-label">Menú del panel</h2>
         <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
      </div>
      <div class="offcanvas-body p-0">
         <div class="tcgx-store-sidebar__brand border-0 pt-0">
            <a href="index.php" class="tcgx-store-sidebar__logo-link" aria-label="TCG EXCHANGE, inicio del panel">
               <img class="tcgx-store-sidebar__logo-full" src="<?php echo htmlspecialchars($tcgxStoreUrlLogoOscuro, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="290" height="68" decoding="async">
            </a>
         </div>
         <nav class="tcgx-store-sidebar__nav flex-column nav px-3 pb-3" aria-label="Navegación principal en móvil">
            <a class="nav-link <?php echo $tcgxNavInicioActivo ? 'active' : ''; ?>" href="index.php" title="Inicio"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Inicio</span></a>
            <a class="nav-link <?php echo $tcgxNavEnviosActivo ? 'active' : ''; ?>" href="envios.php" title="Envíos"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Envíos</span></a>
            <a class="nav-link <?php echo $tcgxNavConsolidadosActivo ? 'active' : ''; ?>" href="consolidados.php" title="Consolidados"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Consolidados</span></a>
            <a class="nav-link <?php echo $tcgxNavIncidenciasActivo ? 'active' : ''; ?>" href="incidencias.php" title="Incidencias"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Incidencias</span></a>
            <a class="nav-link <?php echo $tcgxNavEvaluacionesActivo ? 'active' : ''; ?>" href="evaluaciones.php" title="Evaluaciones"><i class="fa-solid fa-star" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Evaluaciones</span></a>
            <a class="nav-link <?php echo $tcgxNavDevolucionesActivo ? 'active' : ''; ?>" href="devoluciones.php" title="Devoluciones"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Devoluciones</span></a>
            <a class="nav-link <?php echo $tcgxNavPerfilActivo ? 'active' : ''; ?>" href="mi-perfil.php" title="Mi perfil"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Mi perfil</span></a>
         </nav>
      </div>
   </div>
   <!-- FIN FRAGMENTO: OFFCANVAS MENU MOVIL -->
    <?php
    return;
}
?>
      <!-- INICIO FRAGMENTO: SIDEBAR ESCRITORIO (store/includes/sidebar.php) -->
      <aside class="tcgx-store-sidebar d-none d-xl-flex" id="tcgx-store-sidebar-desktop" aria-label="Navegación principal del panel">
         <div class="tcgx-store-sidebar__brand">
            <a href="index.php" class="tcgx-store-sidebar__logo-link" aria-label="TCG EXCHANGE, inicio del panel">
               <img class="tcgx-store-sidebar__logo-full" src="<?php echo htmlspecialchars($tcgxStoreUrlLogoOscuro, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="290" height="68" decoding="async">
               <span class="tcgx-store-sidebar__logo-mark" aria-hidden="true"><i class="fa-solid fa-paper-plane"></i></span>
            </a>
         </div>
         <div class="tcgx-store-sidebar__label"><span class="tcgx-store-sidebar__label-text">Menú</span></div>
         <nav class="tcgx-store-sidebar__nav flex-column nav" aria-label="Navegación principal">
            <a class="nav-link <?php echo $tcgxNavInicioActivo ? 'active' : ''; ?>" href="index.php" title="Inicio"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Inicio</span></a>
            <a class="nav-link <?php echo $tcgxNavEnviosActivo ? 'active' : ''; ?>" href="envios.php" title="Envíos"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Envíos</span></a>
            <a class="nav-link <?php echo $tcgxNavConsolidadosActivo ? 'active' : ''; ?>" href="consolidados.php" title="Consolidados"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Consolidados</span></a>
            <a class="nav-link <?php echo $tcgxNavIncidenciasActivo ? 'active' : ''; ?>" href="incidencias.php" title="Incidencias"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Incidencias</span></a>
            <a class="nav-link <?php echo $tcgxNavEvaluacionesActivo ? 'active' : ''; ?>" href="evaluaciones.php" title="Evaluaciones"><i class="fa-solid fa-star" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Evaluaciones</span></a>
            <a class="nav-link <?php echo $tcgxNavDevolucionesActivo ? 'active' : ''; ?>" href="devoluciones.php" title="Devoluciones"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Devoluciones</span></a>
            <a class="nav-link <?php echo $tcgxNavPerfilActivo ? 'active' : ''; ?>" href="mi-perfil.php" title="Mi perfil"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span class="tcgx-store-sidebar__text">Mi perfil</span></a>
         </nav>
      </aside>
      <!-- FIN FRAGMENTO: SIDEBAR ESCRITORIO -->
