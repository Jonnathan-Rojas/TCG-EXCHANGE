<?php
declare(strict_types=1);

/**
 * Barra superior del modulo client: menu hamburguesa, colapsar sidebar, usuario y cierre de sesion.
 *
 * Variables requeridas:
 * - $tcgxClientNombreUsuario (string)
 * - $tcgxClientCsrf (string)
 * - $tcgxClientScriptNombre (string)
 */
?>
         <!-- INICIO FRAGMENTO: TOPBAR (client/includes/header.php) -->
         <header class="tcgx-client-topbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
               <button class="btn btn-outline-light d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#tcgx-client-offcanvas-nav" aria-controls="tcgx-client-offcanvas-nav" aria-label="Abrir menú del panel">
                  <i class="fa-solid fa-bars" aria-hidden="true"></i>
               </button>
               <button class="btn btn-outline-light d-none d-xl-inline-flex align-items-center justify-content-center tcgx-client-sidebar-toggle" type="button" id="tcgx-client-btn-toggle-sidebar" aria-expanded="true" aria-controls="tcgx-client-sidebar-desktop" title="Contraer menú lateral">
                  <i class="fa-solid fa-angles-left tcgx-client-sidebar-toggle__icon tcgx-client-sidebar-toggle__icon--open" aria-hidden="true"></i>
                  <i class="fa-solid fa-angles-right tcgx-client-sidebar-toggle__icon tcgx-client-sidebar-toggle__icon--closed" aria-hidden="true"></i>
               </button>
               <h1 class="visually-hidden">Cliente</h1>
            </div>
            <div class="tcgx-client-topbar__meta">
               <span class="tcgx-client-chip">
                  <i class="fa-solid fa-user" aria-hidden="true"></i>
                  <?php echo htmlspecialchars($tcgxClientNombreUsuario, ENT_QUOTES, 'UTF-8'); ?>
               </span>
               <form method="post" action="<?php echo htmlspecialchars($tcgxClientScriptNombre, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline tcgx-client-topbar__logout">
                  <input type="hidden" name="tcgx_client_logout" value="1">
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxClientCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="submit" class="btn btn-outline-light btn-sm">
                     <i class="fa-solid fa-right-from-bracket me-1" aria-hidden="true"></i>Cerrar sesión
                  </button>
               </form>
            </div>
         </header>
         <!-- FIN FRAGMENTO: TOPBAR -->
