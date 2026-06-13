<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE Y CONSULTA DE RESUMEN DEL PANEL CENTRO DE DISTRIBUCION
require __DIR__ . '/includes/carga_sesion_cd.php';
require __DIR__ . '/includes/cd_dashboard_logica.php';

$tcgxDashboard = tcgx_cd_dashboard_resumen(Bd::getPdo(), $idTiendaSesion);
// FIN BLOQUE: ARRANQUE Y CONSULTA DE RESUMEN DEL PANEL CENTRO DE DISTRIBUCION

$tcgxPageTitle = 'Centro de distribución | TCG EXCHANGE';
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

   <link rel="icon" href="<?php echo $esc($tcgxCdUrlFavicon); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="vendor/css/cd-panel.css?v=20260612c">
</head>

<body class="tcgx-cd-app" id="tcgx-cd-app-root">

   <div class="tcgx-cd-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-cd-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-cd-content" id="tcgx-cd-main">

            <!-- INICIO BLOQUE: KPIs OPERATIVOS DEL HUB -->
            <div class="row g-3 mb-4 tcgx-cd-kpi">
               <div class="col-12 col-md-6 col-xl">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-cd-kpi__label">Incidencias abiertas</div>
                           <div class="tcgx-cd-kpi__value"><?php echo $esc($tcgxDashboard['incidencias_abiertas']); ?></div>
                        </div>
                        <span class="tcgx-cd-kpi__icon tcgx-cd-kpi__icon--primary" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                     </div>
                  </div>
               </div>
               <div class="col-12 col-md-6 col-xl">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-cd-kpi__label">Envíos en centro</div>
                           <div class="tcgx-cd-kpi__value"><?php echo $esc($tcgxDashboard['envios_en_centro']); ?></div>
                        </div>
                        <span class="tcgx-cd-kpi__icon tcgx-cd-kpi__icon--primary" aria-hidden="true"><i class="fa-solid fa-warehouse"></i></span>
                     </div>
                  </div>
               </div>
               <div class="col-12 col-md-6 col-xl">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-cd-kpi__label">Consolidados entrantes</div>
                           <div class="tcgx-cd-kpi__value"><?php echo $esc($tcgxDashboard['consolidados_entrantes']); ?></div>
                        </div>
                        <span class="tcgx-cd-kpi__icon tcgx-cd-kpi__icon--dark" aria-hidden="true"><i class="fa-solid fa-truck-ramp-box"></i></span>
                     </div>
                  </div>
               </div>
               <div class="col-12 col-md-6 col-xl">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-cd-kpi__label">Consolidados activos</div>
                           <div class="tcgx-cd-kpi__value"><?php echo $esc($tcgxDashboard['consolidados_activos']); ?></div>
                        </div>
                        <span class="tcgx-cd-kpi__icon tcgx-cd-kpi__icon--muted" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>
                     </div>
                  </div>
               </div>
               <div class="col-12 col-md-6 col-xl">
                  <div class="card h-100">
                     <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                           <div class="tcgx-cd-kpi__label">Evaluaciones</div>
                           <div class="tcgx-cd-kpi__value"><?php echo $esc($tcgxDashboard['evaluaciones']); ?></div>
                        </div>
                        <span class="tcgx-cd-kpi__icon tcgx-cd-kpi__icon--primary" aria-hidden="true"><i class="fa-solid fa-star"></i></span>
                     </div>
                  </div>
               </div>
            </div>
            <!-- FIN BLOQUE: KPIs OPERATIVOS DEL HUB -->

            <!-- INICIO BLOQUE: ENVIOS POR ESTADO (ACOTADO AL HUB) -->
            <h2 class="tcgx-cd-section-title">Envíos por estado</h2>
            <div class="tcgx-cd-table-card mb-4">
               <table class="table table-hover align-middle tcgx-cd-dt-table" id="tcgx-tabla-envios-estado">
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
            <!-- FIN BLOQUE: ENVIOS POR ESTADO (ACOTADO AL HUB) -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxCdSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxCdSidebarModo);
   ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/cd-panel.js?v=20260612a"></script>
   <script src="vendor/js/dashboard.js?v=20260612e"></script>
</body>
</html>
