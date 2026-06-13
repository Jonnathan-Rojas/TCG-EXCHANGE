<?php
declare(strict_types=1);

/**
 * Barra superior del modulo store: menu hamburguesa, colapsar sidebar, usuario/tienda y cierre de sesion.
 *
 * Variables requeridas:
 * - $tcgxStoreNombreUsuario (string)
 * - $tcgxStoreNombreTienda (string)
 * - $tcgxStoreCsrf (string, token CSRF para el POST de logout)
 * - $tcgxStoreScriptNombre (string, basename del script actual para action del formulario de logout)
 */
?>
         <!-- INICIO FRAGMENTO: TOPBAR (store/includes/header.php) -->
         <header class="tcgx-store-topbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
               <button class="btn btn-outline-light d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#tcgx-store-offcanvas-nav" aria-controls="tcgx-store-offcanvas-nav" aria-label="Abrir menú del panel">
                  <i class="fa-solid fa-bars" aria-hidden="true"></i>
               </button>
               <button class="btn btn-outline-light d-none d-xl-inline-flex align-items-center justify-content-center tcgx-store-sidebar-toggle" type="button" id="tcgx-store-btn-toggle-sidebar" aria-expanded="true" aria-controls="tcgx-store-sidebar-desktop" title="Contraer menú lateral">
                  <i class="fa-solid fa-angles-left tcgx-store-sidebar-toggle__icon tcgx-store-sidebar-toggle__icon--open" aria-hidden="true"></i>
                  <i class="fa-solid fa-angles-right tcgx-store-sidebar-toggle__icon tcgx-store-sidebar-toggle__icon--closed" aria-hidden="true"></i>
               </button>
               <h1 class="visually-hidden">Tienda</h1>
            </div>
            <div class="tcgx-store-topbar__meta">
               <span class="tcgx-store-chip">
                  <i class="fa-solid fa-store" aria-hidden="true"></i>
                  <?php echo htmlspecialchars($tcgxStoreNombreTienda, ENT_QUOTES, 'UTF-8'); ?>
               </span>
               <span class="tcgx-store-chip">
                  <i class="fa-solid fa-user" aria-hidden="true"></i>
                  <?php echo htmlspecialchars($tcgxStoreNombreUsuario, ENT_QUOTES, 'UTF-8'); ?>
               </span>
               <form method="post" action="<?php echo htmlspecialchars($tcgxStoreScriptNombre, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline tcgx-store-topbar__logout">
                  <input type="hidden" name="tcgx_store_logout" value="1">
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxStoreCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="submit" class="btn btn-outline-light btn-sm">
                     <i class="fa-solid fa-right-from-bracket me-1" aria-hidden="true"></i>Cerrar sesión
                  </button>
               </form>
            </div>
         </header>
         <!-- FIN FRAGMENTO: TOPBAR -->
