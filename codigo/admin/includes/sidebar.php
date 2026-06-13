<?php
declare(strict_types=1);

/**
 * Barra lateral del modulo admin: por defecto aside escritorio; con $tcgxAdminSidebarModo === 'offcanvas' solo el panel movil.
 *
 * Variables requeridas: $tcgxAdminUrlLogoOscuro (string, URL absoluta al sitio hacia images/logo-on-dark.svg).
 * Variable opcional: $tcgxAdminSidebarModo ('escritorio' | 'offcanvas'); sin definir se asume escritorio.
 * Variable requerida para item activo: $tcgxAdminScriptNombre (string, basename del script actual).
 */

$tcgxAdminSidebarModo = $tcgxAdminSidebarModo ?? 'escritorio';
$tcgxNavInicioActivo = ($tcgxAdminScriptNombre ?? '') === 'index.php';
$tcgxNavUsuariosActivo = ($tcgxAdminScriptNombre ?? '') === 'usuarios.php';
$tcgxNavTiendasActivo = ($tcgxAdminScriptNombre ?? '') === 'tiendas.php';
$tcgxNavTarifasActivo = ($tcgxAdminScriptNombre ?? '') === 'tarifas.php';
$tcgxNavRutasActivo = ($tcgxAdminScriptNombre ?? '') === 'rutas.php';
// Envios (Proceso 1): listado de envios individuales, su registro y su detalle con acciones individuales.
$tcgxNavEnviosActivo = in_array(($tcgxAdminScriptNombre ?? ''), ['envios.php', 'envio-registrar.php', 'envio-ver.php'], true);
// Consolidados (Proceso 2): agrupacion de envios al HUB y su gestion en bloque (armar, despachar, recibir).
$tcgxNavConsolidadosActivo = in_array(($tcgxAdminScriptNombre ?? ''), ['consolidados.php', 'consolidado-armar.php', 'consolidado-ver.php'], true);
// Incidencias: listado y gestion con historial de actualizaciones (cambio de estado).
$tcgxNavIncidenciasActivo = in_array(($tcgxAdminScriptNombre ?? ''), ['incidencias.php', 'incidencia-ver.php', 'incidencia-registrar.php'], true);
// Devoluciones: supervision de envios en cadena de devolucion y avance de estados.
$tcgxNavDevolucionesActivo = in_array(($tcgxAdminScriptNombre ?? ''), ['devoluciones.php', 'devolucion-ver.php'], true);
// Auditorias: visor de solo lectura de eventos registrados en la tabla auditorias.
$tcgxNavAuditoriasActivo = in_array(($tcgxAdminScriptNombre ?? ''), ['auditorias.php', 'auditoria-ver.php'], true);
$tcgxNavEvaluacionesActivo = ($tcgxAdminScriptNombre ?? '') === 'evaluaciones.php';
$tcgxNavPerfilActivo = ($tcgxAdminScriptNombre ?? '') === 'mi-perfil.php';

if ($tcgxAdminSidebarModo === 'offcanvas') {
    ?>
   <!-- INICIO FRAGMENTO: OFFCANVAS MENU MOVIL (admin/includes/sidebar.php) -->
   <div class="offcanvas offcanvas-start tcgx-admin-offcanvas" tabindex="-1" id="tcgx-admin-offcanvas-nav" aria-labelledby="tcgx-admin-offcanvas-label">
      <div class="offcanvas-header">
         <h2 class="h5 offcanvas-title text-white" id="tcgx-admin-offcanvas-label">Menú del panel</h2>
         <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
      </div>
      <div class="offcanvas-body p-0">
         <div class="tcgx-admin-sidebar__brand border-0 pt-0">
            <a href="index.php" class="tcgx-admin-sidebar__logo-link" aria-label="TCG EXCHANGE, inicio del panel">
               <img class="tcgx-admin-sidebar__logo-full" src="<?php echo htmlspecialchars($tcgxAdminUrlLogoOscuro, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="290" height="68" decoding="async">
            </a>
         </div>
         <nav class="tcgx-admin-sidebar__nav flex-column nav px-3 pb-3" aria-label="Navegación principal en móvil">
            <a class="nav-link <?php echo $tcgxNavInicioActivo ? 'active' : ''; ?>" href="index.php" title="Inicio"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Inicio</span></a>
            <a class="nav-link <?php echo $tcgxNavUsuariosActivo ? 'active' : ''; ?>" href="usuarios.php" title="Usuarios"><i class="fa-solid fa-users" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Usuarios</span></a>
            <a class="nav-link <?php echo $tcgxNavTiendasActivo ? 'active' : ''; ?>" href="tiendas.php" title="Tiendas"><i class="fa-solid fa-store" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Tiendas</span></a>
            <a class="nav-link <?php echo $tcgxNavTarifasActivo ? 'active' : ''; ?>" href="tarifas.php" title="Tarifas"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Tarifas</span></a>
            <a class="nav-link <?php echo $tcgxNavRutasActivo ? 'active' : ''; ?>" href="rutas.php" title="Rutas"><i class="fa-solid fa-route" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Rutas</span></a>
            <a class="nav-link <?php echo $tcgxNavEnviosActivo ? 'active' : ''; ?>" href="envios.php" title="Envíos"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Envíos</span></a>
            <a class="nav-link <?php echo $tcgxNavConsolidadosActivo ? 'active' : ''; ?>" href="consolidados.php" title="Consolidados"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Consolidados</span></a>
            <a class="nav-link <?php echo $tcgxNavIncidenciasActivo ? 'active' : ''; ?>" href="incidencias.php" title="Incidencias"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Incidencias</span></a>
            <a class="nav-link <?php echo $tcgxNavDevolucionesActivo ? 'active' : ''; ?>" href="devoluciones.php" title="Devoluciones"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Devoluciones</span></a>
            <a class="nav-link <?php echo $tcgxNavAuditoriasActivo ? 'active' : ''; ?>" href="auditorias.php" title="Auditorías"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Auditorías</span></a>
            <a class="nav-link <?php echo $tcgxNavEvaluacionesActivo ? 'active' : ''; ?>" href="evaluaciones.php" title="Evaluaciones"><i class="fa-solid fa-star" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Evaluaciones</span></a>
            <a class="nav-link <?php echo $tcgxNavPerfilActivo ? 'active' : ''; ?>" href="mi-perfil.php" title="Mi perfil"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Mi perfil</span></a>
         </nav>
      </div>
   </div>
   <!-- FIN FRAGMENTO: OFFCANVAS MENU MOVIL -->
    <?php
    return;
}
?>
      <!-- INICIO FRAGMENTO: SIDEBAR ESCRITORIO (admin/includes/sidebar.php) -->
      <aside class="tcgx-admin-sidebar d-none d-xl-flex" id="tcgx-admin-sidebar-desktop" aria-label="Navegación principal del panel">
         <div class="tcgx-admin-sidebar__brand">
            <a href="index.php" class="tcgx-admin-sidebar__logo-link" aria-label="TCG EXCHANGE, inicio del panel">
               <img class="tcgx-admin-sidebar__logo-full" src="<?php echo htmlspecialchars($tcgxAdminUrlLogoOscuro, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="290" height="68" decoding="async">
               <span class="tcgx-admin-sidebar__logo-mark" aria-hidden="true"><i class="fa-solid fa-paper-plane"></i></span>
            </a>
         </div>
         <div class="tcgx-admin-sidebar__label"><span class="tcgx-admin-sidebar__label-text">Menú</span></div>
         <nav class="tcgx-admin-sidebar__nav flex-column nav" aria-label="Navegación principal">
            <a class="nav-link <?php echo $tcgxNavInicioActivo ? 'active' : ''; ?>" href="index.php" title="Inicio"><i class="fa-solid fa-house" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Inicio</span></a>
            <a class="nav-link <?php echo $tcgxNavUsuariosActivo ? 'active' : ''; ?>" href="usuarios.php" title="Usuarios"><i class="fa-solid fa-users" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Usuarios</span></a>
            <a class="nav-link <?php echo $tcgxNavTiendasActivo ? 'active' : ''; ?>" href="tiendas.php" title="Tiendas"><i class="fa-solid fa-store" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Tiendas</span></a>
            <a class="nav-link <?php echo $tcgxNavTarifasActivo ? 'active' : ''; ?>" href="tarifas.php" title="Tarifas"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Tarifas</span></a>
            <a class="nav-link <?php echo $tcgxNavRutasActivo ? 'active' : ''; ?>" href="rutas.php" title="Rutas"><i class="fa-solid fa-route" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Rutas</span></a>
            <a class="nav-link <?php echo $tcgxNavEnviosActivo ? 'active' : ''; ?>" href="envios.php" title="Envíos"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Envíos</span></a>
            <a class="nav-link <?php echo $tcgxNavConsolidadosActivo ? 'active' : ''; ?>" href="consolidados.php" title="Consolidados"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Consolidados</span></a>
            <a class="nav-link <?php echo $tcgxNavIncidenciasActivo ? 'active' : ''; ?>" href="incidencias.php" title="Incidencias"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Incidencias</span></a>
            <a class="nav-link <?php echo $tcgxNavDevolucionesActivo ? 'active' : ''; ?>" href="devoluciones.php" title="Devoluciones"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Devoluciones</span></a>
            <a class="nav-link <?php echo $tcgxNavAuditoriasActivo ? 'active' : ''; ?>" href="auditorias.php" title="Auditorías"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Auditorías</span></a>
            <a class="nav-link <?php echo $tcgxNavEvaluacionesActivo ? 'active' : ''; ?>" href="evaluaciones.php" title="Evaluaciones"><i class="fa-solid fa-star" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Evaluaciones</span></a>
            <a class="nav-link <?php echo $tcgxNavPerfilActivo ? 'active' : ''; ?>" href="mi-perfil.php" title="Mi perfil"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span class="tcgx-admin-sidebar__text">Mi perfil</span></a>
         </nav>
      </aside>
      <!-- FIN FRAGMENTO: SIDEBAR ESCRITORIO -->
