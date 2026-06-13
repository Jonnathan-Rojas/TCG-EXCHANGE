<?php
declare(strict_types=1);

/**
 * Barra superior del modulo cd: menu hamburguesa, colapsar sidebar, usuario/hub y cierre de sesion.
 *
 * Variables requeridas:
 * - $tcgxCdNombreUsuario (string)
 * - $tcgxCdNombreTienda (string)
 * - $tcgxCdCsrf (string, token CSRF para el POST de logout)
 * - $tcgxCdScriptNombre (string, basename del script actual para action del formulario de logout)
 */
?>
         <!-- INICIO FRAGMENTO: TOPBAR (cd/includes/header.php) -->
         <header class="tcgx-cd-topbar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
               <button class="btn btn-outline-light d-xl-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#tcgx-cd-offcanvas-nav" aria-controls="tcgx-cd-offcanvas-nav" aria-label="Abrir menú del panel">
                  <i class="fa-solid fa-bars" aria-hidden="true"></i>
               </button>
               <button class="btn btn-outline-light d-none d-xl-inline-flex align-items-center justify-content-center tcgx-cd-sidebar-toggle" type="button" id="tcgx-cd-btn-toggle-sidebar" aria-expanded="true" aria-controls="tcgx-cd-sidebar-desktop" title="Contraer menú lateral">
                  <i class="fa-solid fa-angles-left tcgx-cd-sidebar-toggle__icon tcgx-cd-sidebar-toggle__icon--open" aria-hidden="true"></i>
                  <i class="fa-solid fa-angles-right tcgx-cd-sidebar-toggle__icon tcgx-cd-sidebar-toggle__icon--closed" aria-hidden="true"></i>
               </button>
               <h1 class="visually-hidden">Centro de distribución</h1>
            </div>
            <div class="tcgx-cd-topbar__meta">
               <span class="tcgx-cd-chip">
                  <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                  <?php echo htmlspecialchars($tcgxCdNombreTienda, ENT_QUOTES, 'UTF-8'); ?>
               </span>
               <span class="tcgx-cd-chip">
                  <i class="fa-solid fa-user" aria-hidden="true"></i>
                  <?php echo htmlspecialchars($tcgxCdNombreUsuario, ENT_QUOTES, 'UTF-8'); ?>
               </span>
               <form method="post" action="<?php echo htmlspecialchars($tcgxCdScriptNombre, ENT_QUOTES, 'UTF-8'); ?>" class="d-inline tcgx-cd-topbar__logout">
                  <input type="hidden" name="tcgx_cd_logout" value="1">
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxCdCsrf, ENT_QUOTES, 'UTF-8'); ?>">
                  <button type="submit" class="btn btn-outline-light btn-sm">
                     <i class="fa-solid fa-right-from-bracket me-1" aria-hidden="true"></i>Cerrar sesión
                  </button>
               </form>
            </div>
         </header>
         <!-- FIN FRAGMENTO: TOPBAR -->
