<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO AUDITORIAS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/auditorias_logica.php';

if (empty($_SESSION['tcgx_auditorias_csrf'])) {
    $_SESSION['tcgx_auditorias_csrf'] = bin2hex(random_bytes(32));
}
$tcgxAudCsrf = $_SESSION['tcgx_auditorias_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO AUDITORIAS


// INICIO BLOQUE: PROCESAMIENTO POST DE FILTROS (PRG EN SESION, SIN GET CON DATOS)
$tcgxAudFiltros = $_SESSION['tcgx_auditorias_filtros'] ?? [
    'accion' => '',
    'tablaafectada' => '',
    'idusuario' => '',
    'fechadesde' => '',
    'fechahasta' => '',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    if ($tcgxAudCsrf === '' || $tokenPost === '' || !hash_equals($tcgxAudCsrf, $tokenPost)) {
        $_SESSION['tcgx_auditorias_flash'] = ['tipo' => 'error', 'texto' => 'SOLICITUD NO VALIDA.'];
        header('Location: auditorias.php', true, 303);
        exit;
    }

    if (isset($_POST['tcgx_aud_limpiar'])) {
        unset($_SESSION['tcgx_auditorias_filtros']);
        header('Location: auditorias.php', true, 303);
        exit;
    }

    if (isset($_POST['tcgx_aud_filtrar'])) {
        $_SESSION['tcgx_auditorias_filtros'] = tcgx_auditorias_normalizar_filtros($_POST);
        header('Location: auditorias.php', true, 303);
        exit;
    }
}

if (isset($_SESSION['tcgx_auditorias_filtros']) && is_array($_SESSION['tcgx_auditorias_filtros'])) {
    $tcgxAudFiltros = tcgx_auditorias_normalizar_filtros($_SESSION['tcgx_auditorias_filtros']);
}
// FIN BLOQUE: PROCESAMIENTO POST DE FILTROS


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH Y CONSULTA DE LISTADO
$tcgxAudFlash = null;
if (isset($_SESSION['tcgx_auditorias_flash']) && is_array($_SESSION['tcgx_auditorias_flash'])) {
    $tcgxAudFlash = $_SESSION['tcgx_auditorias_flash'];
    unset($_SESSION['tcgx_auditorias_flash']);
}

$tcgxCatalogoTablas = tcgx_auditorias_catalogo_tablas($pdo);
$tcgxCatalogoUsuarios = tcgx_auditorias_catalogo_usuarios($pdo);
$tcgxListaAuditorias = tcgx_auditorias_listar($pdo, $tcgxAudFiltros);
// FIN BLOQUE: LECTURA DE MENSAJE FLASH Y CONSULTA DE LISTADO

$tcgxPageTitle = 'Auditorías | TCG EXCHANGE';
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

            <!-- INICIO BLOQUE: FORMULARIO DE FILTROS DE AUDITORIAS -->
            <section class="contenedor-central-sec mb-4">
               <div class="tcgx-admin-form-card">
                  <form method="post" action="auditorias.php" id="tcgx-form-aud-filtros">
                     <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxAudCsrf); ?>">
                     <div class="row g-3">
                        <div class="col-12 col-md-4 col-lg-2">
                           <label class="form-label" for="aud-accion">Acción</label>
                           <select class="form-select" id="aud-accion" name="accion" autofocus>
                              <option value="">TODAS</option>
                              <?php foreach (TCGX_AUD_ACCIONES as $accOpt): ?>
                                 <option value="<?php echo $esc($accOpt); ?>" <?php echo ($tcgxAudFiltros['accion'] ?? '') === $accOpt ? 'selected' : ''; ?>><?php echo $esc($accOpt); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-2">
                           <label class="form-label" for="aud-tabla">Tabla</label>
                           <select class="form-select" id="aud-tabla" name="tablaafectada">
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxCatalogoTablas as $tablaOpt): ?>
                                 <option value="<?php echo $esc((string) $tablaOpt); ?>" <?php echo ($tcgxAudFiltros['tablaafectada'] ?? '') === (string) $tablaOpt ? 'selected' : ''; ?>><?php echo $esc((string) $tablaOpt); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                           <label class="form-label" for="aud-usuario">Usuario</label>
                           <select class="form-select" id="aud-usuario" name="idusuario">
                              <option value="">TODOS</option>
                              <?php foreach ($tcgxCatalogoUsuarios as $usrOpt): ?>
                                 <option value="<?php echo $esc((string) $usrOpt['id']); ?>" <?php echo ($tcgxAudFiltros['idusuario'] ?? '') === (string) $usrOpt['id'] ? 'selected' : ''; ?>><?php echo $esc($usrOpt['nombre']); ?> (<?php echo $esc($usrOpt['id']); ?>)</option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                           <label class="form-label" for="aud-desde">Desde</label>
                           <input type="date" class="form-control" id="aud-desde" name="fechadesde" value="<?php echo $esc($tcgxAudFiltros['fechadesde'] ?? ''); ?>">
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                           <label class="form-label" for="aud-hasta">Hasta</label>
                           <input type="date" class="form-control" id="aud-hasta" name="fechahasta" value="<?php echo $esc($tcgxAudFiltros['fechahasta'] ?? ''); ?>">
                        </div>
                     </div>
                     <div class="tcgx-admin-form-actions mt-3">
                        <button type="submit" name="tcgx_aud_limpiar" value="1" class="btn btn-outline-secondary">Limpiar</button>
                        <button type="submit" name="tcgx_aud_filtrar" value="1" class="btn btn-primary">Filtrar</button>
                     </div>
                  </form>
               </div>
            </section>
            <!-- FIN BLOQUE: FORMULARIO DE FILTROS DE AUDITORIAS -->

            <!-- INICIO BLOQUE: TABLA DE AUDITORIAS (DATATABLES, SOLO LECTURA) -->
            <div class="tcgx-admin-table-card">
               <table class="table table-hover align-middle tcgx-admin-dt-table" id="tcgx-tabla-auditorias">
                  <thead>
                     <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Tabla</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaAuditorias as $aud): ?>
                        <?php
                        $vId = (int) $aud['id'];
                        $accionTxt = (string) $aud['accion'];
                        $accionClase = 'text-bg-secondary';
                        if ($accionTxt === 'ACCESO') {
                            $accionClase = 'text-bg-success';
                        } elseif ($accionTxt === 'LOGOUT') {
                            $accionClase = 'text-bg-dark';
                        } elseif ($accionTxt === 'CREAR') {
                            $accionClase = 'text-bg-primary';
                        } elseif ($accionTxt === 'ACTUALIZAR') {
                            $accionClase = 'text-bg-info';
                        } elseif ($accionTxt === 'ELIMINAR') {
                            $accionClase = 'text-bg-danger';
                        }
                        $nombreUsuario = trim((string) ($aud['nombreusuario'] ?? ''));
                        if ($nombreUsuario === '') {
                            $nombreUsuario = $aud['idusuario'] !== null ? (string) $aud['idusuario'] : '—';
                        }
                        ?>
                        <tr>
                           <td><?php echo $esc($vId); ?></td>
                           <td><?php echo $esc($aud['fechaevento']); ?></td>
                           <td><?php echo $esc($nombreUsuario); ?></td>
                           <td><span class="badge <?php echo $accionClase; ?>"><?php echo $esc($accionTxt); ?></span></td>
                           <td><?php echo $aud['tablaafectada'] !== null ? $esc($aud['tablaafectada']) : '—'; ?></td>
                           <td><?php echo $aud['idregistro'] !== null ? $esc($aud['idregistro']) : '—'; ?></td>
                           <td class="text-end">
                              <div class="tcgx-admin-actions justify-content-end">
                                 <button type="button" class="btn btn-primary" data-tcgx-action="ver" data-tcgx-id="<?php echo $esc((string) $vId); ?>" title="Ver detalle" aria-label="Ver detalle de auditoría <?php echo $esc((string) $vId); ?>">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                 </button>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE AUDITORIAS -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <form id="tcgx-form-ver" method="post" action="auditoria-ver.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxAudCsrf); ?>">
      <input type="hidden" name="id_auditoria" id="tcgx-form-ver-id" value="">
   </form>

   <?php if ($tcgxAudFlash !== null): ?>
      <script id="tcgx-auditorias-flash" type="application/json"><?php
         echo json_encode($tcgxAudFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js" crossorigin="anonymous"></script>
   <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
   <script src="vendor/js/auditorias.js?v=20260612e"></script>
</body>
</html>
