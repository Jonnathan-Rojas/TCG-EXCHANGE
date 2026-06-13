<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL CRUD DE EVALUACIONES (STORE)
require __DIR__ . '/includes/carga_sesion_store.php';
require __DIR__ . '/includes/store_evaluaciones_logica.php';

if (empty($_SESSION['tcgx_evaluaciones_csrf'])) {
    $_SESSION['tcgx_evaluaciones_csrf'] = bin2hex(random_bytes(32));
}
$tcgxEvaluacionesCsrf = $_SESSION['tcgx_evaluaciones_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL CRUD DE EVALUACIONES (STORE)


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
$tcgxEvaluacionesFlash = null;
if (isset($_SESSION['tcgx_evaluaciones_flash']) && is_array($_SESSION['tcgx_evaluaciones_flash'])) {
    $tcgxEvaluacionesFlash = $_SESSION['tcgx_evaluaciones_flash'];
    unset($_SESSION['tcgx_evaluaciones_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: PROCESAMIENTO POST DE ELIMINACION ACOTADA A TIENDA
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_evaluaciones_eliminar'])) {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    $idRaw = trim((string) ($_POST['id_evaluacion'] ?? ''));

    if ($tcgxEvaluacionesCsrf === '' || $tokenPost === '' || !hash_equals($tcgxEvaluacionesCsrf, $tokenPost)) {
        $_SESSION['tcgx_evaluaciones_flash'] = ['tipo' => 'error', 'texto' => 'SOLICITUD NO VALIDA.'];
    } elseif ($idRaw === '' || !ctype_digit($idRaw)) {
        $_SESSION['tcgx_evaluaciones_flash'] = ['tipo' => 'error', 'texto' => 'EVALUACION NO INDICADA.'];
    } else {
        $idEvaluacion = (int) $idRaw;
        $evaluacionScope = tcgx_store_evaluaciones_obtener($pdo, $idEvaluacion, $idTiendaSesion);
        if ($evaluacionScope === null) {
            $_SESSION['tcgx_evaluaciones_flash'] = ['tipo' => 'error', 'texto' => 'LA EVALUACION NO EXISTE O NO PERTENECE A SU TIENDA.'];
        } else {
            $resultadoEliminar = tcgx_evaluaciones_eliminar($pdo, $idEvaluacion, $idUsuarioVista);
            if ($resultadoEliminar['ok']) {
                $_SESSION['tcgx_evaluaciones_flash'] = ['tipo' => 'ok', 'texto' => 'EVALUACION ELIMINADA CORRECTAMENTE.'];
            } else {
                $_SESSION['tcgx_evaluaciones_flash'] = ['tipo' => 'error', 'texto' => $resultadoEliminar['error']];
            }
        }
    }

    header('Location: ' . $tcgxStoreScriptNombre, true, 303);
    exit;
}
// FIN BLOQUE: PROCESAMIENTO POST DE ELIMINACION ACOTADA A TIENDA


// INICIO BLOQUE: CONSULTA DE LISTADO ACOTADO A TIENDA DE SESION
$tcgxListaEvaluaciones = tcgx_store_evaluaciones_listar($pdo, $idTiendaSesion);
// FIN BLOQUE: CONSULTA DE LISTADO ACOTADO A TIENDA DE SESION

$tcgxPageTitle = 'Evaluaciones | TCG EXCHANGE';
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($tcgxPageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
   <meta name="robots" content="noindex, nofollow">

   <link rel="icon" href="<?php echo htmlspecialchars($tcgxStoreUrlFavicon, ENT_QUOTES, 'UTF-8'); ?>" type="image/png" sizes="512x512">

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

   <link rel="stylesheet" href="vendor/css/store-panel.css?v=20260612c">
</head>

<body class="tcgx-store-app" id="tcgx-store-app-root">

   <div class="tcgx-store-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-store-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-store-content" id="tcgx-store-main">

            <!-- INICIO BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->
            <div class="tcgx-store-tabla-toolbar">
               <a class="btn btn-success" href="evaluacion-crear.php">
                  <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Crear evaluación
               </a>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->

            <!-- INICIO BLOQUE: TABLA DE EVALUACIONES (DATATABLES) -->
            <div class="tcgx-store-table-card">
               <table class="table table-hover align-middle tcgx-store-dt-table" id="tcgx-tabla-evaluaciones">
                  <thead>
                     <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Tienda</th>
                        <th>Rapidez</th>
                        <th>Confianza</th>
                        <th>Seguridad</th>
                        <th>Calidad</th>
                        <th>Reputación</th>
                        <th>Lista negra</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaEvaluaciones as $evaluacion): ?>
                        <?php
                        $eId = (int) $evaluacion['id'];
                        $eIdEsc = htmlspecialchars((string) $eId, ENT_QUOTES, 'UTF-8');
                        $eIdUsuario = htmlspecialchars((string) ($evaluacion['idusuario'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $eNombreUsuario = htmlspecialchars((string) ($evaluacion['nombreusuario'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $eNombreTienda = htmlspecialchars((string) ($evaluacion['nombretienda'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $eRapidez = (int) $evaluacion['rapidez'];
                        $eConfianza = (int) $evaluacion['confianza'];
                        $eSeguridad = (int) $evaluacion['seguridad'];
                        $eCalidad = (int) $evaluacion['calidad'];
                        $eReputacion = htmlspecialchars((string) tcgx_evaluaciones_reputacion($evaluacion), ENT_QUOTES, 'UTF-8');
                        $eListaNegra = (int) $evaluacion['listanegra'] === 1;
                        $eMotivo = htmlspecialchars((string) ($evaluacion['motivolistanegra'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $eFecha = htmlspecialchars((string) $evaluacion['fecharegistro'], ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                           <td><?php echo $eIdEsc; ?></td>
                           <td><?php echo $eNombreUsuario; ?> <span class="text-secondary">(<?php echo $eIdUsuario; ?>)</span></td>
                           <td><?php echo $eNombreTienda; ?></td>
                           <td><?php echo $eRapidez; ?></td>
                           <td><?php echo $eConfianza; ?></td>
                           <td><?php echo $eSeguridad; ?></td>
                           <td><?php echo $eCalidad; ?></td>
                           <td><strong><?php echo $eReputacion; ?></strong></td>
                           <td>
                              <?php if ($eListaNegra): ?>
                                 <span class="badge text-bg-danger" title="<?php echo $eMotivo; ?>">SÍ</span>
                              <?php else: ?>
                                 <span class="badge text-bg-secondary">NO</span>
                              <?php endif; ?>
                           </td>
                           <td><?php echo $eFecha; ?></td>
                           <td class="text-end">
                              <div class="tcgx-store-actions justify-content-end">
                                 <button type="button" class="btn btn-warning" data-tcgx-action="editar" data-tcgx-id="<?php echo $eIdEsc; ?>" title="Editar" aria-label="Editar evaluación <?php echo $eIdEsc; ?>">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                 </button>
                                 <button type="button" class="btn btn-danger" data-tcgx-action="eliminar" data-tcgx-id="<?php echo $eIdEsc; ?>" data-tcgx-cliente="<?php echo $eNombreUsuario; ?>" title="Eliminar" aria-label="Eliminar evaluación <?php echo $eIdEsc; ?>">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                 </button>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE EVALUACIONES (DATATABLES) -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxStoreSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxStoreSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST (EDITAR / ELIMINAR) -->
   <form id="tcgx-form-editar" method="post" action="evaluacion-editar.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxEvaluacionesCsrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="id_evaluacion" id="tcgx-form-editar-id" value="">
   </form>
   <form id="tcgx-form-eliminar" method="post" action="<?php echo htmlspecialchars($tcgxStoreScriptNombre, ENT_QUOTES, 'UTF-8'); ?>" class="d-none">
      <input type="hidden" name="tcgx_evaluaciones_eliminar" value="1">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxEvaluacionesCsrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="id_evaluacion" id="tcgx-form-eliminar-id" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST (EDITAR / ELIMINAR) -->

   <?php if ($tcgxEvaluacionesFlash !== null): ?>
      <script id="tcgx-evaluaciones-flash" type="application/json"><?php
         echo json_encode($tcgxEvaluacionesFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>

   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>

   <script src="vendor/js/store-panel.js?v=20260612a"></script>
   <script src="vendor/js/evaluaciones.js?v=20260612a"></script>
</body>
</html>
