<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO INCIDENCIAS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/incidencias_logica.php';

if (empty($_SESSION['tcgx_incidencias_csrf'])) {
    $_SESSION['tcgx_incidencias_csrf'] = bin2hex(random_bytes(32));
}
$tcgxIncCsrf = $_SESSION['tcgx_incidencias_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO INCIDENCIAS


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
$tcgxIncFlash = null;
if (isset($_SESSION['tcgx_incidencias_flash']) && is_array($_SESSION['tcgx_incidencias_flash'])) {
    $tcgxIncFlash = $_SESSION['tcgx_incidencias_flash'];
    unset($_SESSION['tcgx_incidencias_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: CONSULTA DE LISTADO
$tcgxListaIncidencias = tcgx_incidencias_listar(Bd::getPdo());
// FIN BLOQUE: CONSULTA DE LISTADO

$tcgxPageTitle = 'Incidencias | TCG EXCHANGE';
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

   <!-- INICIO BLOQUE: ESTILOS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->
   <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css" crossorigin="anonymous">
   <!-- FIN BLOQUE: ESTILOS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->

   <link rel="stylesheet" href="vendor/css/admin-panel.css?v=20260612c">
</head>

<body class="tcgx-admin-app" id="tcgx-admin-app-root">

   <div class="tcgx-admin-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-admin-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-admin-content" id="tcgx-admin-main">

            <!-- INICIO BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->
            <div class="tcgx-admin-tabla-toolbar">
               <a class="btn btn-success" href="incidencia-registrar.php">
                  <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Registrar incidencia
               </a>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->

            <!-- INICIO BLOQUE: TABLA DE INCIDENCIAS (DATATABLES) -->
            <div class="tcgx-admin-table-card">
               <table class="table table-hover align-middle tcgx-admin-dt-table" id="tcgx-tabla-incidencias">
                  <thead>
                     <tr>
                        <th>ID</th>
                        <th>Envío</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Tienda reporta</th>
                        <th>Reporte</th>
                        <th>Cierre</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaIncidencias as $inc): ?>
                        <?php
                        $vId = (int) $inc['id'];
                        $estadoTxt = (string) $inc['estadoincidencia'];
                        $estadoClase = 'text-bg-secondary';
                        if ($estadoTxt === TCGX_INC_ESTADO_REPORTADA) {
                            $estadoClase = 'text-bg-warning';
                        } elseif ($estadoTxt === TCGX_INC_ESTADO_RESUELTA) {
                            $estadoClase = 'text-bg-info';
                        } elseif ($estadoTxt === TCGX_INC_ESTADO_CERRADA) {
                            $estadoClase = 'text-bg-dark';
                        }
                        ?>
                        <tr>
                           <td><?php echo $esc($vId); ?></td>
                           <td><?php echo $esc($inc['idenvio']); ?></td>
                           <td><?php echo $esc($inc['tipoincidencia']); ?></td>
                           <td><span class="badge <?php echo $estadoClase; ?>"><?php echo $esc($estadoTxt); ?></span></td>
                           <td><?php echo $esc($inc['nombretiendareporta']); ?></td>
                           <td><?php echo $esc($inc['fechareporte']); ?></td>
                           <td><?php echo $inc['fechacierre'] !== null ? $esc($inc['fechacierre']) : '—'; ?></td>
                           <td class="text-end">
                              <div class="tcgx-admin-actions justify-content-end">
                                 <button type="button" class="btn btn-primary" data-tcgx-action="ver" data-tcgx-id="<?php echo $esc((string) $vId); ?>" title="Gestionar" aria-label="Gestionar incidencia <?php echo $esc((string) $vId); ?>">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                 </button>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE INCIDENCIAS (DATATABLES) -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIO OCULTO PARA VER DETALLE (POST + CSRF) -->
   <form id="tcgx-form-ver" method="post" action="incidencia-ver.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxIncCsrf); ?>">
      <input type="hidden" name="id_incidencia" id="tcgx-form-ver-id" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIO OCULTO PARA VER DETALLE -->

   <!-- INICIO BLOQUE: CARGA UTIL DEL FLASH PARA SWEETALERT2 -->
   <?php if ($tcgxIncFlash !== null): ?>
      <script id="tcgx-incidencias-flash" type="application/json"><?php
         echo json_encode($tcgxIncFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>
   <!-- FIN BLOQUE: CARGA UTIL DEL FLASH PARA SWEETALERT2 -->

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>

   <!-- INICIO BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <!-- FIN BLOQUE: SCRIPTS DATATABLES (BOOTSTRAP 5 + RESPONSIVE) -->

   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/incidencias.js?v=20260612d"></script>
</body>
</html>
