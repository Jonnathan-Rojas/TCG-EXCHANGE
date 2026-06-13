<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE Y CONSULTA DE RESUMEN DEL PANEL
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/dashboard_logica.php';

$tcgxDashboard = tcgx_dashboard_resumen(Bd::getPdo());
$tcgxTotalEnvios = 0;
foreach ($tcgxDashboard['envios_por_estado'] as $filaEstado) {
    $tcgxTotalEnvios += (int) ($filaEstado['total'] ?? 0);
}
// FIN BLOQUE: ARRANQUE Y CONSULTA DE RESUMEN DEL PANEL

$tcgxPageTitle = 'Administración | TCG EXCHANGE';
$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo $esc($tcgxPageTitle); ?></title>
   <meta name="robots" content="noindex, nofollow">

   <link rel="icon" href="<?php echo $esc($tcgxAdminUrlFavicon); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="vendor/css/admin-panel.css?v=20260612c">
</head>

<body class="tcgx-admin-app" id="tcgx-admin-app-root">

   <div class="tcgx-admin-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-admin-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-admin-content" id="tcgx-admin-main">

            <!-- INICIO BLOQUE: KPIs PRINCIPALES DEL PANEL -->
            <div class="row g-3 mb-4 tcgx-admin-kpi">
               <div class="col-12 col-md-6 col-xl-3">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-admin-kpi__label">Incidencias abiertas</div>
                           <div class="tcgx-admin-kpi__value"><?php echo $esc($tcgxDashboard['incidencias_abiertas']); ?></div>
                        </div>
                        <span class="tcgx-admin-kpi__icon tcgx-admin-kpi__icon--primary" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                     </div>
                  </div>
               </div>
               <div class="col-12 col-md-6 col-xl-3">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-admin-kpi__label">Usuarios activos</div>
                           <div class="tcgx-admin-kpi__value"><?php echo $esc($tcgxDashboard['usuarios_activos']); ?></div>
                        </div>
                        <span class="tcgx-admin-kpi__icon tcgx-admin-kpi__icon--dark" aria-hidden="true"><i class="fa-solid fa-users"></i></span>
                     </div>
                  </div>
               </div>
               <div class="col-12 col-md-6 col-xl-3">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-admin-kpi__label">Tiendas activas</div>
                           <div class="tcgx-admin-kpi__value"><?php echo $esc($tcgxDashboard['tiendas_activas']); ?></div>
                        </div>
                        <span class="tcgx-admin-kpi__icon tcgx-admin-kpi__icon--muted" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
                     </div>
                  </div>
               </div>
               <div class="col-12 col-md-6 col-xl-3">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-admin-kpi__label">Envíos registrados</div>
                           <div class="tcgx-admin-kpi__value"><?php echo $esc($tcgxTotalEnvios); ?></div>
                        </div>
                        <span class="tcgx-admin-kpi__icon tcgx-admin-kpi__icon--primary" aria-hidden="true"><i class="fa-solid fa-truck-fast"></i></span>
                     </div>
                  </div>
               </div>
            </div>
            <!-- FIN BLOQUE: KPIs PRINCIPALES DEL PANEL -->

            <!-- INICIO BLOQUE: ENVIOS POR ESTADO -->
            <h2 class="tcgx-admin-section-title">Envíos por estado</h2>
            <div class="tcgx-admin-table-card mb-4">
               <table class="table table-hover align-middle tcgx-admin-dt-table" id="tcgx-tabla-envios-estado">
                  <thead>
                     <tr>
                        <th>Estado</th>
                        <th class="text-end">Cantidad</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if ($tcgxDashboard['envios_por_estado'] === []): ?>
                        <tr>
                           <td colspan="2">SIN ENVÍOS REGISTRADOS</td>
                        </tr>
                     <?php else: ?>
                        <?php foreach ($tcgxDashboard['envios_por_estado'] as $filaEst): ?>
                           <tr>
                              <td><?php echo $esc($filaEst['estado']); ?></td>
                              <td class="text-end"><?php echo $esc($filaEst['total']); ?></td>
                           </tr>
                        <?php endforeach; ?>
                     <?php endif; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: ENVIOS POR ESTADO -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/dashboard.js?v=20260612e"></script>
</body>
</html>
