<?php
declare(strict_types=1);

/**
 * Barra lateral del modulo cd: por defecto aside escritorio; con $tcgxCdSidebarModo === 'offcanvas' solo el panel movil.
 *
 * Variables requeridas: $tcgxCdUrlLogoOscuro (string).
 * Variable opcional: $tcgxCdSidebarModo ('escritorio' | 'offcanvas').
 * Variable requerida para item activo: $tcgxCdScriptNombre (string).
 */

$tcgxCdSidebarModo = $tcgxCdSidebarModo ?? 'escritorio';
$tcgxNavInicioActivo = ($tcgxCdScriptNombre ?? '') === 'index.php';
$tcgxNavEnviosActivo = in_array(($tcgxCdScriptNombre ?? ''), ['envios.php', 'envio-ver.php'], true);
$tcgxNavConsolidadosActivo = in_array(($tcgxCdScriptNombre ?? ''), ['consolidados.php', 'consolidado-armar.php', 'consolidado-ver.php'], true);
$tcgxNavIncidenciasActivo = in_array(($tcgxCdScriptNombre ?? ''), ['incidencias.php', 'incidencia-ver.php', 'incidencia-registrar.php'], true);
$tcgxNavDevolucionesActivo = in_array(($tcgxCdScriptNombre ?? ''), ['devoluciones.php', 'devolucion-ver.php'], true);
$tcgxNavEvaluacionesActivo = in_array(($tcgxCdScriptNombre ?? ''), ['evaluaciones.php', 'evaluacion-crear.php', 'evaluacion-editar.php'], true);
$tcgxNavPerfilActivo = ($tcgxCdScriptNombre ?? '') === 'mi-perfil.php';

if ($tcgxCdSidebarModo === 'offcanvas') {
    ?>
   <!-- INICIO FRAGMENTO: OFFCANVAS MENU MOVIL (cd/includes/sidebar.php) -->
   <div class="offcanvas offcanvas-start tcgx-cd-offcanvas" tabindex="-1" id="tcgx-cd-offcanvas-nav" aria-labelledby="tcgx-cd-offcanvas-label">
      <div class="offcanvas-header">
         <h2 class="h5 offcanvas-title text-white" id="tcgx-cd-offcanvas-label">Menú del panel</h2>
         <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
      </div>
      <div class="offcanvas-body p-0">
         <div class="tcgx-cd-sidebar__brand border-0 pt-0">
            <a href="index.php" class="tcgx-cd-sidebar__logo-link" aria-label="TCG EXCHANGE, inicio del panel">
               <img class="tcgx-cd-sidebar__logo-full" src="<?php echo htmlspecialchars($tcgxCdUrlLogoOscuro, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="290" height="68" decoding="async">
            </a>
         </div>
         <nav class="tcgx-cd-sidebar__nav flex-column nav px-3 pb-3" aria-label="Navegación principal en móvil">
            <a class="nav-link <?php echo $tcgxNavInicioActivo ? 'active' : ''; ?>" href="index.php" title="Inicio"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Inicio</span></a>
            <a class="nav-link <?php echo $tcgxNavEnviosActivo ? 'active' : ''; ?>" href="envios.php" title="Envíos"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Envíos</span></a>
            <a class="nav-link <?php echo $tcgxNavConsolidadosActivo ? 'active' : ''; ?>" href="consolidados.php" title="Consolidados"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Consolidados</span></a>
            <a class="nav-link <?php echo $tcgxNavIncidenciasActivo ? 'active' : ''; ?>" href="incidencias.php" title="Incidencias"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Incidencias</span></a>
            <a class="nav-link <?php echo $tcgxNavEvaluacionesActivo ? 'active' : ''; ?>" href="evaluaciones.php" title="Evaluaciones"><i class="fa-solid fa-star" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Evaluaciones</span></a>
            <a class="nav-link <?php echo $tcgxNavDevolucionesActivo ? 'active' : ''; ?>" href="devoluciones.php" title="Devoluciones"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Devoluciones</span></a>
            <a class="nav-link <?php echo $tcgxNavPerfilActivo ? 'active' : ''; ?>" href="mi-perfil.php" title="Mi perfil"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Mi perfil</span></a>
         </nav>
      </div>
   </div>
   <!-- FIN FRAGMENTO: OFFCANVAS MENU MOVIL -->
    <?php
    return;
}
?>
      <!-- INICIO FRAGMENTO: SIDEBAR ESCRITORIO (cd/includes/sidebar.php) -->
      <aside class="tcgx-cd-sidebar d-none d-xl-flex" id="tcgx-cd-sidebar-desktop" aria-label="Navegación principal del panel">
         <div class="tcgx-cd-sidebar__brand">
            <a href="index.php" class="tcgx-cd-sidebar__logo-link" aria-label="TCG EXCHANGE, inicio del panel">
               <img class="tcgx-cd-sidebar__logo-full" src="<?php echo htmlspecialchars($tcgxCdUrlLogoOscuro, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="290" height="68" decoding="async">
               <span class="tcgx-cd-sidebar__logo-mark" aria-hidden="true"><i class="fa-solid fa-warehouse"></i></span>
            </a>
         </div>
         <div class="tcgx-cd-sidebar__label"><span class="tcgx-cd-sidebar__label-text">Menú</span></div>
         <nav class="tcgx-cd-sidebar__nav flex-column nav" aria-label="Navegación principal">
            <a class="nav-link <?php echo $tcgxNavInicioActivo ? 'active' : ''; ?>" href="index.php" title="Inicio"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Inicio</span></a>
            <a class="nav-link <?php echo $tcgxNavEnviosActivo ? 'active' : ''; ?>" href="envios.php" title="Envíos"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Envíos</span></a>
            <a class="nav-link <?php echo $tcgxNavConsolidadosActivo ? 'active' : ''; ?>" href="consolidados.php" title="Consolidados"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Consolidados</span></a>
            <a class="nav-link <?php echo $tcgxNavIncidenciasActivo ? 'active' : ''; ?>" href="incidencias.php" title="Incidencias"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Incidencias</span></a>
            <a class="nav-link <?php echo $tcgxNavEvaluacionesActivo ? 'active' : ''; ?>" href="evaluaciones.php" title="Evaluaciones"><i class="fa-solid fa-star" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Evaluaciones</span></a>
            <a class="nav-link <?php echo $tcgxNavDevolucionesActivo ? 'active' : ''; ?>" href="devoluciones.php" title="Devoluciones"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Devoluciones</span></a>
            <a class="nav-link <?php echo $tcgxNavPerfilActivo ? 'active' : ''; ?>" href="mi-perfil.php" title="Mi perfil"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span class="tcgx-cd-sidebar__text">Mi perfil</span></a>
         </nav>
      </aside>
      <!-- FIN FRAGMENTO: SIDEBAR ESCRITORIO -->
