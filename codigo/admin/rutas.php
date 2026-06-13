<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL CRUD DE RUTAS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/rutas_logica.php';

// Token CSRF propio del CRUD de rutas: independiente del token rotado por carga_sesion_admin,
// para que los formularios de listado y de alta/edicion validen sin colisionar con la regeneracion del bootstrap.
if (empty($_SESSION['tcgx_rutas_csrf'])) {
    $_SESSION['tcgx_rutas_csrf'] = bin2hex(random_bytes(32));
}
$tcgxRutasCsrf = $_SESSION['tcgx_rutas_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL CRUD DE RUTAS


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
// Consume en un solo uso el resultado de alta/edicion/estado para mostrarlo via SweetAlert2 tras la redireccion.
$tcgxRutasFlash = null;
if (isset($_SESSION['tcgx_rutas_flash']) && is_array($_SESSION['tcgx_rutas_flash'])) {
    $tcgxRutasFlash = $_SESSION['tcgx_rutas_flash'];
    unset($_SESSION['tcgx_rutas_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: PROCESAMIENTO POST DE CAMBIO DE ESTADO (ACTIVAR / DESACTIVAR)
// Cambio de estado (ACTIVO/INACTIVO) validado con el token propio del CRUD; patron PRG hacia el listado.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_rutas_estado'])) {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    $idRaw = trim((string) ($_POST['id_ruta'] ?? ''));
    $nuevoEstado = mb_strtoupper(trim((string) ($_POST['nuevo_estado'] ?? '')), 'UTF-8');

    if ($tcgxRutasCsrf === '' || $tokenPost === '' || !hash_equals($tcgxRutasCsrf, $tokenPost)) {
        $_SESSION['tcgx_rutas_flash'] = ['tipo' => 'error', 'texto' => 'SOLICITUD NO VALIDA.'];
    } elseif ($idRaw === '' || !ctype_digit($idRaw)) {
        $_SESSION['tcgx_rutas_flash'] = ['tipo' => 'error', 'texto' => 'RUTA NO INDICADA.'];
    } else {
        $resultadoEstado = tcgx_rutas_cambiar_estado(Bd::getPdo(), (int) $idRaw, $nuevoEstado, $idUsuarioVista);
        if ($resultadoEstado['ok']) {
            $textoOk = $nuevoEstado === 'ACTIVO' ? 'RUTA ACTIVADA CORRECTAMENTE.' : 'RUTA DESACTIVADA CORRECTAMENTE.';
            $_SESSION['tcgx_rutas_flash'] = ['tipo' => 'ok', 'texto' => $textoOk];
        } else {
            $_SESSION['tcgx_rutas_flash'] = ['tipo' => 'error', 'texto' => $resultadoEstado['error']];
        }
    }

    header('Location: ' . $tcgxAdminScriptNombre, true, 303);
    exit;
}
// FIN BLOQUE: PROCESAMIENTO POST DE CAMBIO DE ESTADO (ACTIVAR / DESACTIVAR)


// INICIO BLOQUE: CONSULTA DE LISTADO
$tcgxListaRutas = tcgx_rutas_listar(Bd::getPdo());
// FIN BLOQUE: CONSULTA DE LISTADO

$tcgxPageTitle = 'Rutas | TCG EXCHANGE';
?>
<!DOCTYPE html>
<html lang="es">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($tcgxPageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
   <meta name="robots" content="noindex, nofollow">

   <link rel="icon" href="<?php echo htmlspecialchars($tcgxAdminUrlFavicon, ENT_QUOTES, 'UTF-8'); ?>" type="image/png" sizes="512x512">

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">

   <!-- INICIO BLOQUE: ESTILOS DATATABLES (BOOTSTRAP 5 + RESPONSIVE)
        Tablas con buscador, selector de filas y paginador; en movil detalle desplegable (diseño.md). -->
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

            <!-- INICIO BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA
                 Boton de alta alineado a la derecha en su propia fila, encima de la tabla. -->
            <div class="tcgx-admin-tabla-toolbar">
               <a class="btn btn-success" href="ruta-crear.php">
                  <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Crear ruta
               </a>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->

            <!-- INICIO BLOQUE: TABLA DE RUTAS (DATATABLES) -->
            <div class="tcgx-admin-table-card">
               <table class="table table-hover align-middle tcgx-admin-dt-table" id="tcgx-tabla-rutas">
                  <thead>
                     <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Medio de envío</th>
                        <th>Guía externa</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaRutas as $ruta): ?>
                        <?php
                        // Variables escapadas por celda: salida segura en HTML (anti-XSS).
                        $rId = (int) $ruta['id'];
                        $rIdEsc = htmlspecialchars((string) $rId, ENT_QUOTES, 'UTF-8');
                        $rNombre = htmlspecialchars((string) $ruta['nombre'], ENT_QUOTES, 'UTF-8');
                        $rMedio = htmlspecialchars((string) $ruta['medioenvio'], ENT_QUOTES, 'UTF-8');
                        $rExigeGuia = ((int) $ruta['exigeguiaexterna']) === 1;
                        $rEstado = (string) $ruta['estado'];
                        $rEstadoEsc = htmlspecialchars($rEstado, ENT_QUOTES, 'UTF-8');
                        $rFecha = htmlspecialchars((string) $ruta['fecharegistro'], ENT_QUOTES, 'UTF-8');
                        // Clase de color del badge segun estado controlado (sin ENUM en BD).
                        $estadoClase = $rEstado === 'ACTIVO' ? 'tcgx-estado--activo' : 'tcgx-estado--inactivo';
                        ?>
                        <tr>
                           <td><?php echo $rIdEsc; ?></td>
                           <td><?php echo $rNombre; ?></td>
                           <td><?php echo $rMedio; ?></td>
                           <td><?php echo $rExigeGuia ? '<span class="tcgx-estado tcgx-estado--activo">SÍ</span>' : '<span class="text-muted">NO</span>'; ?></td>
                           <td><span class="tcgx-estado <?php echo $estadoClase; ?>"><?php echo $rEstadoEsc; ?></span></td>
                           <td><?php echo $rFecha; ?></td>
                           <td class="text-end">
                              <div class="tcgx-admin-actions justify-content-end">
                                 <!-- Editar (naranja/warning): navega a la pagina de edicion por POST (sin GET con datos). -->
                                 <button type="button" class="btn btn-warning" data-tcgx-action="editar" data-tcgx-id="<?php echo $rIdEsc; ?>" title="Editar" aria-label="Editar ruta <?php echo $rIdEsc; ?>">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                 </button>
                                 <?php if ($rEstado === 'ACTIVO'): ?>
                                    <!-- Desactivar (rojo/danger): baja logica a estado INACTIVO con confirmacion. -->
                                    <button type="button" class="btn btn-danger" data-tcgx-action="estado" data-tcgx-target="INACTIVO" data-tcgx-id="<?php echo $rIdEsc; ?>" data-tcgx-nombre="<?php echo $rNombre; ?>" title="Desactivar" aria-label="Desactivar ruta <?php echo $rIdEsc; ?>">
                                       <i class="fa-solid fa-ban" aria-hidden="true"></i>
                                    </button>
                                 <?php else: ?>
                                    <!-- Activar (verde/success): reactiva la ruta a estado ACTIVO con confirmacion. -->
                                    <button type="button" class="btn btn-success" data-tcgx-action="estado" data-tcgx-target="ACTIVO" data-tcgx-id="<?php echo $rIdEsc; ?>" data-tcgx-nombre="<?php echo $rNombre; ?>" title="Activar" aria-label="Activar ruta <?php echo $rIdEsc; ?>">
                                       <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    </button>
                                 <?php endif; ?>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE RUTAS (DATATABLES) -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST (EDITAR / ESTADO)
        Un solo formulario por accion, completado y enviado por JS; mantiene POST + CSRF sin repetir formularios por fila. -->
   <form id="tcgx-form-editar" method="post" action="ruta-editar.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxRutasCsrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="id_ruta" id="tcgx-form-editar-id" value="">
   </form>
   <form id="tcgx-form-estado" method="post" action="<?php echo htmlspecialchars($tcgxAdminScriptNombre, ENT_QUOTES, 'UTF-8'); ?>" class="d-none">
      <input type="hidden" name="tcgx_rutas_estado" value="1">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxRutasCsrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="id_ruta" id="tcgx-form-estado-id" value="">
      <input type="hidden" name="nuevo_estado" id="tcgx-form-estado-valor" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST (EDITAR / ESTADO) -->

   <!-- INICIO BLOQUE: CARGA UTIL DEL FLASH PARA SWEETALERT2
        Datos del resultado de la operacion previa, escapados como JSON para consumo del JS del modulo. -->
   <?php if ($tcgxRutasFlash !== null): ?>
      <script id="tcgx-rutas-flash" type="application/json"><?php
         echo json_encode($tcgxRutasFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
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

   <script src="vendor/js/admin-panel.js?v=20260611d"></script>
   <script src="vendor/js/rutas.js?v=20260611d"></script>
</body>
</html>
