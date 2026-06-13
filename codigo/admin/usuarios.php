<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL CRUD DE USUARIOS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/usuarios_logica.php';

// Token CSRF propio del CRUD de usuarios: independiente del token rotado por carga_sesion_admin,
// para que los formularios de listado (baja) y de alta/edicion validen sin colisionar con la regeneracion del bootstrap.
if (empty($_SESSION['tcgx_usuarios_csrf'])) {
    $_SESSION['tcgx_usuarios_csrf'] = bin2hex(random_bytes(32));
}
$tcgxUsuariosCsrf = $_SESSION['tcgx_usuarios_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL CRUD DE USUARIOS


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
// Consume en un solo uso el resultado de alta/edicion/baja para mostrarlo via SweetAlert2 tras la redireccion.
$tcgxUsuariosFlash = null;
if (isset($_SESSION['tcgx_usuarios_flash']) && is_array($_SESSION['tcgx_usuarios_flash'])) {
    $tcgxUsuariosFlash = $_SESSION['tcgx_usuarios_flash'];
    unset($_SESSION['tcgx_usuarios_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: PROCESAMIENTO POST DE CAMBIO DE ESTADO (ACTIVAR / DESACTIVAR)
// Cambio de estado (ACTIVO/INACTIVO) validado con el token propio del CRUD; patron PRG hacia el listado.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_usuarios_estado'])) {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    $idObjetivo = mb_strtoupper(trim((string) ($_POST['id_usuario'] ?? '')), 'UTF-8');
    $nuevoEstado = mb_strtoupper(trim((string) ($_POST['nuevo_estado'] ?? '')), 'UTF-8');

    if ($tcgxUsuariosCsrf === '' || $tokenPost === '' || !hash_equals($tcgxUsuariosCsrf, $tokenPost)) {
        $_SESSION['tcgx_usuarios_flash'] = ['tipo' => 'error', 'texto' => 'SOLICITUD NO VALIDA.'];
    } elseif ($idObjetivo === '') {
        $_SESSION['tcgx_usuarios_flash'] = ['tipo' => 'error', 'texto' => 'USUARIO NO INDICADO.'];
    } elseif ($idObjetivo === $idUsuarioVista && $nuevoEstado !== 'ACTIVO') {
        // Evita que el administrador en sesion se desactive a si mismo.
        $_SESSION['tcgx_usuarios_flash'] = ['tipo' => 'error', 'texto' => 'NO PUEDE DESACTIVAR SU PROPIO USUARIO.'];
    } else {
        $resultadoEstado = tcgx_usuarios_cambiar_estado(Bd::getPdo(), $idObjetivo, $nuevoEstado, $idUsuarioVista);
        if ($resultadoEstado['ok']) {
            $textoOk = $nuevoEstado === 'ACTIVO' ? 'USUARIO ACTIVADO CORRECTAMENTE.' : 'USUARIO DESACTIVADO CORRECTAMENTE.';
            $_SESSION['tcgx_usuarios_flash'] = ['tipo' => 'ok', 'texto' => $textoOk];
        } else {
            $_SESSION['tcgx_usuarios_flash'] = ['tipo' => 'error', 'texto' => $resultadoEstado['error']];
        }
    }

    header('Location: ' . $tcgxAdminScriptNombre, true, 303);
    exit;
}
// FIN BLOQUE: PROCESAMIENTO POST DE CAMBIO DE ESTADO (ACTIVAR / DESACTIVAR)


// INICIO BLOQUE: PROCESAMIENTO POST DE REGENERACION DE CONTRASENA
// Genera una nueva contrasena para el usuario indicado; la clave en claro viaja solo en el flash de un uso para mostrarse.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['tcgx_usuarios_clave'])) {
    $tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
    $idObjetivo = mb_strtoupper(trim((string) ($_POST['id_usuario'] ?? '')), 'UTF-8');

    if ($tcgxUsuariosCsrf === '' || $tokenPost === '' || !hash_equals($tcgxUsuariosCsrf, $tokenPost)) {
        $_SESSION['tcgx_usuarios_flash'] = ['tipo' => 'error', 'texto' => 'SOLICITUD NO VALIDA.'];
    } elseif ($idObjetivo === '') {
        $_SESSION['tcgx_usuarios_flash'] = ['tipo' => 'error', 'texto' => 'USUARIO NO INDICADO.'];
    } else {
        $resultadoClave = tcgx_usuarios_regenerar_clave(Bd::getPdo(), $idObjetivo, $idUsuarioVista);
        if ($resultadoClave['ok']) {
            $_SESSION['tcgx_usuarios_flash'] = [
                'tipo' => 'clave_generada',
                'modo' => 'regenerada',
                'id' => $idObjetivo,
                'clave' => $resultadoClave['clave'],
            ];
        } else {
            $_SESSION['tcgx_usuarios_flash'] = ['tipo' => 'error', 'texto' => $resultadoClave['error']];
        }
    }

    header('Location: ' . $tcgxAdminScriptNombre, true, 303);
    exit;
}
// FIN BLOQUE: PROCESAMIENTO POST DE REGENERACION DE CONTRASENA


// INICIO BLOQUE: CONSULTA DE LISTADO
$tcgxListaUsuarios = tcgx_usuarios_listar(Bd::getPdo());
// FIN BLOQUE: CONSULTA DE LISTADO

$tcgxPageTitle = 'Usuarios | TCG EXCHANGE';
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
               <a class="btn btn-success" href="usuario-crear.php">
                  <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Crear usuario
               </a>
            </div>
            <!-- FIN BLOQUE: BARRA DE ACCION DE ALTA SOBRE LA TABLA -->

            <!-- INICIO BLOQUE: TABLA DE USUARIOS (DATATABLES) -->
            <div class="tcgx-admin-table-card">
               <table class="table table-hover align-middle tcgx-admin-dt-table" id="tcgx-tabla-usuarios">
                  <thead>
                     <tr>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Perfil</th>
                        <th>Tienda</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($tcgxListaUsuarios as $usuario): ?>
                        <?php
                        // Variables escapadas por celda: salida segura en HTML (anti-XSS).
                        $uId = htmlspecialchars((string) $usuario['id'], ENT_QUOTES, 'UTF-8');
                        $uNombre = htmlspecialchars((string) $usuario['nombre'], ENT_QUOTES, 'UTF-8');
                        $uCorreo = htmlspecialchars((string) $usuario['correo'], ENT_QUOTES, 'UTF-8');
                        $uTelefono = htmlspecialchars((string) $usuario['telefono'], ENT_QUOTES, 'UTF-8');
                        $uPerfil = htmlspecialchars((string) $usuario['perfil'], ENT_QUOTES, 'UTF-8');
                        $uTienda = htmlspecialchars((string) ($usuario['nombretienda'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $uEstado = (string) $usuario['estado'];
                        $uEstadoEsc = htmlspecialchars($uEstado, ENT_QUOTES, 'UTF-8');
                        $uFecha = htmlspecialchars((string) $usuario['fecharegistro'], ENT_QUOTES, 'UTF-8');
                        // Clase de color del badge segun estado controlado (sin ENUM en BD).
                        $estadoClase = $uEstado === 'ACTIVO' ? 'tcgx-estado--activo' : ($uEstado === 'BLOQUEADO' ? 'tcgx-estado--bloqueado' : 'tcgx-estado--inactivo');
                        ?>
                        <tr>
                           <td><?php echo $uId; ?></td>
                           <td><?php echo $uNombre; ?></td>
                           <td class="text-lowercase"><?php echo $uCorreo; ?></td>
                           <td><?php echo $uTelefono; ?></td>
                           <td><?php echo $uPerfil; ?></td>
                           <td><?php echo $uTienda !== '' ? $uTienda : '<span class="text-muted">—</span>'; ?></td>
                           <td><span class="tcgx-estado <?php echo $estadoClase; ?>"><?php echo $uEstadoEsc; ?></span></td>
                           <td><?php echo $uFecha; ?></td>
                           <td class="text-end">
                              <div class="tcgx-admin-actions justify-content-end">
                                 <!-- Editar (naranja/warning): navega a la pagina de edicion por POST (sin GET con datos). -->
                                 <button type="button" class="btn btn-warning" data-tcgx-action="editar" data-tcgx-id="<?php echo $uId; ?>" title="Editar" aria-label="Editar usuario <?php echo $uId; ?>">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                 </button>
                                 <?php if ($uEstado === 'ACTIVO'): ?>
                                    <!-- Desactivar (rojo/danger): baja logica a estado INACTIVO con confirmacion. -->
                                    <button type="button" class="btn btn-danger" data-tcgx-action="estado" data-tcgx-target="INACTIVO" data-tcgx-id="<?php echo $uId; ?>" data-tcgx-nombre="<?php echo $uNombre; ?>" title="Desactivar" aria-label="Desactivar usuario <?php echo $uId; ?>">
                                       <i class="fa-solid fa-user-slash" aria-hidden="true"></i>
                                    </button>
                                 <?php else: ?>
                                    <!-- Activar (verde/success): reactiva el usuario a estado ACTIVO con confirmacion. -->
                                    <button type="button" class="btn btn-success" data-tcgx-action="estado" data-tcgx-target="ACTIVO" data-tcgx-id="<?php echo $uId; ?>" data-tcgx-nombre="<?php echo $uNombre; ?>" title="Activar" aria-label="Activar usuario <?php echo $uId; ?>">
                                       <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                    </button>
                                 <?php endif; ?>
                                 <!-- Regenerar contraseña (gris/secondary): genera una nueva clave y la muestra una sola vez. -->
                                 <button type="button" class="btn btn-secondary" data-tcgx-action="clave" data-tcgx-id="<?php echo $uId; ?>" data-tcgx-nombre="<?php echo $uNombre; ?>" title="Regenerar contraseña" aria-label="Regenerar contraseña de <?php echo $uId; ?>">
                                    <i class="fa-solid fa-key" aria-hidden="true"></i>
                                 </button>
                              </div>
                           </td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
            <!-- FIN BLOQUE: TABLA DE USUARIOS (DATATABLES) -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST (EDITAR / BAJA)
        Un solo formulario por accion, completado y enviado por JS; evita repetir formularios por fila y mantiene POST + CSRF. -->
   <form id="tcgx-form-editar" method="post" action="usuario-editar.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxUsuariosCsrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="id_usuario" id="tcgx-form-editar-id" value="">
   </form>
   <form id="tcgx-form-estado" method="post" action="<?php echo htmlspecialchars($tcgxAdminScriptNombre, ENT_QUOTES, 'UTF-8'); ?>" class="d-none">
      <input type="hidden" name="tcgx_usuarios_estado" value="1">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxUsuariosCsrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="id_usuario" id="tcgx-form-estado-id" value="">
      <input type="hidden" name="nuevo_estado" id="tcgx-form-estado-valor" value="">
   </form>
   <form id="tcgx-form-clave" method="post" action="<?php echo htmlspecialchars($tcgxAdminScriptNombre, ENT_QUOTES, 'UTF-8'); ?>" class="d-none">
      <input type="hidden" name="tcgx_usuarios_clave" value="1">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxUsuariosCsrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="id_usuario" id="tcgx-form-clave-id" value="">
   </form>
   <!-- FIN BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST (EDITAR / BAJA) -->

   <!-- INICIO BLOQUE: CARGA UTIL DEL FLASH PARA SWEETALERT2
        Datos del resultado de la operacion previa, escapados como JSON para consumo del JS del modulo. -->
   <?php if ($tcgxUsuariosFlash !== null): ?>
      <script id="tcgx-usuarios-flash" type="application/json"><?php
         echo json_encode($tcgxUsuariosFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
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

   <script src="vendor/js/admin-panel.js?v=20260611"></script>
   <script src="vendor/js/usuarios.js?v=20260611b"></script>
</body>
</html>
