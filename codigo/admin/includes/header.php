<?php
declare(strict_types=1);

/**
 * Barra superior del modulo admin: menu hamburguesa, colapsar sidebar, usuario y cierre de sesion.
 *
 * Variables requeridas:
 * - $tcgxAdminNombreUsuario (string)
 * - $tcgxAdminCsrf (string, token CSRF para el POST de logout)
 * - $tcgxAdminScriptNombre (string, basename del script actual para action del formulario de logout)
 */
?>
         <!-- INICIO FRAGMENTO: TOPBAR (admin/includes/header.php) -->
         <header class="tcgx-admin-topbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
               <button class="btn btn-outline-light d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#tcgx-admin-offcanvas-nav" aria-controls="tcgx-admin-offcanvas-nav" aria-label="Abrir menú del panel">
                  <i class="fa-solid fa-bars" aria-hidden="true"></i>
               </button>
               <button class="btn btn-outline-light d-none d-xl-inline-flex align-items-center justify-content-center tcgx-admin-sidebar-toggle" type="button" id="tcgx-admin-btn-toggle-sidebar" aria-expanded="true" aria-controls="tcgx-admin-sidebar-desktop" title="Contraer menú lateral">
                  <i class="fa-solid fa-angles-left tcgx-admin-sidebar-toggle__icon tcgx-admin-sidebar-toggle__icon--open" aria-hidden="true"></i>
                  <i class="fa-solid fa-angles-right tcgx-admin-sidebar-toggle__icon tcgx-admin-sidebar-toggle__icon--closed" aria-hidden="true"></i>
               </button>
               <h1 class="visually-hidden">Administración</h1>
            </div>
            <div class="tcgx-admin-topbar__meta">
               <span class="tcgx-admin-chip">
                  <i class="fa-solid fa-user" aria-hidden="true"></i>
                  <?php echo htmlspecialchars($tcgxAdminNombreUsuario, ENT_QUOTES, 'UTF-8'); ?>
               </span>
               <form method="post" action="<?php echo htmlspecialchars($tcgxAdminScriptNombre, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline tcgx-admin-topbar__logout">
                  <input type="hidden" name="tcgx_admin_logout" value="1">
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxAdminCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="submit" class="btn btn-outline-light btn-sm">
                     <i class="fa-solid fa-right-from-bracket me-1" aria-hidden="true"></i>Cerrar sesión
                  </button>
               </form>
            </div>
         </header>
         <!-- FIN FRAGMENTO: TOPBAR -->
