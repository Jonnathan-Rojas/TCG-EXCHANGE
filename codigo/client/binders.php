<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO BINDERS
require __DIR__ . '/includes/carga_sesion_client.php';
require_once __DIR__ . '/includes/client_binders_logica.php';

if (empty($_SESSION['tcgx_binders_csrf'])) {
    $_SESSION['tcgx_binders_csrf'] = bin2hex(random_bytes(32));
}
$tcgxBindersCsrf = $_SESSION['tcgx_binders_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO BINDERS


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
$tcgxBindersFlash = null;
if (isset($_SESSION['tcgx_binders_flash']) && is_array($_SESSION['tcgx_binders_flash'])) {
    $tcgxBindersFlash = $_SESSION['tcgx_binders_flash'];
    unset($_SESSION['tcgx_binders_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: PROCESAMIENTO POST DE ELIMINACION DE BINDER
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_binders_eliminar'])) {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    $idBinderRaw = trim((string) ($_POST['id_binder'] ?? ''));

    if ($tcgxBindersCsrf === '' || $tokenPost === '' || !hash_equals($tcgxBindersCsrf, $tokenPost)) {
        $_SESSION['tcgx_binders_flash'] = ['tipo' => 'error', 'texto' => 'SOLICITUD NO VALIDA.'];
    } elseif ($idBinderRaw === '' || !ctype_digit($idBinderRaw)) {
        $_SESSION['tcgx_binders_flash'] = ['tipo' => 'error', 'texto' => 'BINDER NO INDICADO.'];
    } else {
        $errorRevalidacion = tcgx_client_revalidar_operacion($pdo, $idUsuarioVista);
        if ($errorRevalidacion !== null) {
            $_SESSION['tcgx_binders_flash'] = ['tipo' => 'error', 'texto' => $errorRevalidacion];
        } else {
            $resultado = tcgx_client_binders_eliminar($pdo, (int) $idBinderRaw, $idUsuarioVista, $idUsuarioVista);
            $_SESSION['tcgx_binders_flash'] = $resultado['ok']
                ? ['tipo' => 'ok', 'texto' => 'BINDER ELIMINADO CORRECTAMENTE.']
                : ['tipo' => 'error', 'texto' => $resultado['error']];
        }
    }
    header('Location: binders.php', true, 303);
    exit;
}
// FIN BLOQUE: PROCESAMIENTO POST DE ELIMINACION DE BINDER


// INICIO BLOQUE: CONSULTA DE LISTADO ACOTADO AL CLIENTE DE SESION
$tcgxListaBinders = tcgx_client_binders_listar($pdo, $idUsuarioVista);
// FIN BLOQUE: CONSULTA DE LISTADO ACOTADO AL CLIENTE DE SESION

$tcgxPageTitle = 'Mis binders | TCG EXCHANGE';
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

   <link rel="icon" href="<?php echo $esc($tcgxClientUrlFavicon); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css" crossorigin="anonymous">

   <link rel="stylesheet" href="vendor/css/client-panel.css?v=20260612c">
</head>

<body class="tcgx-client-app" id="tcgx-client-app-root">

   <div class="tcgx-client-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-client-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-client-content" id="tcgx-client-main">

            <!-- INICIO BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->
            <div class="tcgx-client-tabla-toolbar">
               <a class="btn btn-success" href="binder-crear.php">
                  <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Crear binder
               </a>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->

            <!-- INICIO BLOQUE: TABLA DE BINDERS (DATATABLES) -->
            <div class="tcgx-client-table-card">
               <table class="table table-hover align-middle tcgx-client-dt-table" id="tcgx-tabla-binders">
                  <thead>
                     <tr>
                        <th>ID</th>
                           <th>TCG</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaBinders as $binder): ?>
                        <?php
                        $vId = $esc($binder['id']);
                        $estadoTxt = (string) $binder['estado'];
                        $estadoClase = $estadoTxt === 'ACTIVO' ? 'text-bg-success' : ($estadoTxt === 'BLOQUEADO' ? 'text-bg-warning' : 'text-bg-secondary');
                        ?>
                        <tr>
                           <td><?php echo $vId; ?></td>
                           <td><?php echo $esc($binder['juego']); ?></td>
                           <td><?php echo $esc($binder['nombre']); ?></td>
                           <td><?php echo $esc($binder['descripcion'] ?? ''); ?></td>
                           <td><?php echo $esc($binder['totalproductos']); ?></td>
                           <td><span class="badge <?php echo $estadoClase; ?>"><?php echo $esc($estadoTxt); ?></span></td>
                           <td><?php echo $esc($binder['fecharegistro']); ?></td>
                           <td class="text-end">
                              <div class="tcgx-client-actions justify-content-end">
                                 <button type="button" class="btn btn-primary" data-tcgx-action="ver" data-tcgx-id="<?php echo $vId; ?>" title="Ver" aria-label="Ver binder <?php echo $vId; ?>">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                 </button>
                                 <button type="button" class="btn btn-warning" data-tcgx-action="editar" data-tcgx-id="<?php echo $vId; ?>" title="Editar" aria-label="Editar binder <?php echo $vId; ?>">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                 </button>
                                 <button type="button" class="btn btn-danger" data-tcgx-action="eliminar" data-tcgx-id="<?php echo $vId; ?>" data-tcgx-nombre="<?php echo $esc($binder['nombre']); ?>" title="Eliminar" aria-label="Eliminar binder <?php echo $vId; ?>">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                 </button>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE BINDERS (DATATABLES) -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxClientSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxClientSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST (VER / EDITAR / ELIMINAR) -->
   <form id="tcgx-form-ver" method="post" action="binder-ver.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxBindersCsrf); ?>">
      <input type="hidden" name="id_binder" id="tcgx-form-ver-id" value="">
   </form>
   <form id="tcgx-form-editar" method="post" action="binder-editar.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxBindersCsrf); ?>">
      <input type="hidden" name="id_binder" id="tcgx-form-editar-id" value="">
   </form>
   <form id="tcgx-form-eliminar" method="post" action="binders.php" class="d-none">
      <input type="hidden" name="tcgx_binders_eliminar" value="1">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxBindersCsrf); ?>">
      <input type="hidden" name="id_binder" id="tcgx-form-eliminar-id" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST -->

   <?php if ($tcgxBindersFlash !== null): ?>
      <script id="tcgx-binders-flash" type="application/json"><?php
         echo json_encode($tcgxBindersFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/client-panel.js?v=20260612a"></script>
   <script src="vendor/js/binders.js?v=20260612a"></script>
</body>
</html>
