<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO AUDITORIAS
require __DIR__ . '/includes/carga_sesion_admin.php';
require __DIR__ . '/includes/auditorias_logica.php';

if (empty($_SESSION['tcgx_auditorias_csrf'])) {
    $_SESSION['tcgx_auditorias_csrf'] = bin2hex(random_bytes(32));
}
$tcgxAudCsrf = $_SESSION['tcgx_auditorias_csrf'];
// FIN BLOQUE: ARRANQUE, LOGICA Y TOKEN CSRF DEL MODULO AUDITORIAS


// INICIO BLOQUE: ACCESO POR POST (SIN GET CON DATOS) Y CARGA DEL EVENTO
$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $tcgxAudCsrf === '' || $tokenPost === '' || !hash_equals($tcgxAudCsrf, $tokenPost)) {
    header('Location: auditorias.php', true, 303);
    exit;
}

$idRaw = trim((string) ($_POST['id_auditoria'] ?? ''));
$idAuditoria = ($idRaw !== '' && ctype_digit($idRaw)) ? (int) $idRaw : 0;
$tcgxAud = $idAuditoria > 0 ? tcgx_auditorias_obtener(Bd::getPdo(), $idAuditoria) : null;
if ($tcgxAud === null) {
    $_SESSION['tcgx_auditorias_flash'] = ['tipo' => 'error', 'texto' => 'EL EVENTO DE AUDITORIA INDICADO NO EXISTE.'];
    header('Location: auditorias.php', true, 303);
    exit;
}

// INICIO BLOQUE: AUDITORIA DE LECTURA DEL DETALLE (ACCION LEER)
tcgx_auditorias_registrar_lectura(Bd::getPdo(), $idUsuarioVista, 'auditorias', (string) $idAuditoria);
// FIN BLOQUE: AUDITORIA DE LECTURA DEL DETALLE
// FIN BLOQUE: ACCESO POR POST Y CARGA DEL EVENTO


// INICIO BLOQUE: FORMATEO DE JSON PARA VISTA DE SOLO LECTURA
$tcgxJsonFormatear = static function (?string $json): string {
    if ($json === null || trim($json) === '') {
        return '—';
    }
    $dec = json_decode($json, true);
    if ($dec === null && json_last_error() !== JSON_ERROR_NONE) {
        return $json;
    }
    return json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
};

$tcgxDatosAntes = $tcgxJsonFormatear(isset($tcgxAud['datosantes']) ? (string) $tcgxAud['datosantes'] : null);
$tcgxDatosDespues = $tcgxJsonFormatear(isset($tcgxAud['datosdespues']) ? (string) $tcgxAud['datosdespues'] : null);
// FIN BLOQUE: FORMATEO DE JSON PARA VISTA DE SOLO LECTURA

$tcgxPageTitle = 'Detalle de auditoría | TCG EXCHANGE';
$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$nombreUsuario = trim((string) ($tcgxAud['nombreusuario'] ?? ''));
if ($nombreUsuario === '') {
    $nombreUsuario = $tcgxAud['idusuario'] !== null ? (string) $tcgxAud['idusuario'] : '—';
}
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

   <link rel="stylesheet" href="vendor/css/admin-panel.css?v=20260612c">
</head>

<body class="tcgx-admin-app" id="tcgx-admin-app-root">

   <div class="tcgx-admin-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-admin-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-admin-content" id="tcgx-admin-main">

            <!-- INICIO BLOQUE: DETALLE DE EVENTO DE AUDITORIA (SOLO LECTURA) -->
            <section class="contenedor-central-sec">
               <div class="tcgx-admin-form-card">
                  <div class="row g-3 mb-4">
                     <div class="col-12 col-md-3">
                        <label class="form-label">ID</label>
                        <input type="text" class="form-control" value="<?php echo $esc($tcgxAud['id']); ?>" readonly disabled>
                     </div>
                     <div class="col-12 col-md-3">
                        <label class="form-label">Fecha</label>
                        <input type="text" class="form-control" value="<?php echo $esc($tcgxAud['fechaevento']); ?>" readonly disabled>
                     </div>
                     <div class="col-12 col-md-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" class="form-control" value="<?php echo $esc($nombreUsuario); ?>" readonly disabled>
                     </div>
                     <div class="col-12 col-md-3">
                        <label class="form-label">Acción</label>
                        <input type="text" class="form-control text-uppercase" value="<?php echo $esc($tcgxAud['accion']); ?>" readonly disabled>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label">Tabla</label>
                        <input type="text" class="form-control text-uppercase" value="<?php echo $tcgxAud['tablaafectada'] !== null ? $esc($tcgxAud['tablaafectada']) : '—'; ?>" readonly disabled>
                     </div>
                     <div class="col-12 col-md-4">
                        <label class="form-label">Registro</label>
                        <input type="text" class="form-control" value="<?php echo $tcgxAud['idregistro'] !== null ? $esc($tcgxAud['idregistro']) : '—'; ?>" readonly disabled>
                     </div>
                  </div>

                  <p class="tcgx-admin-form-subtitle">Datos anteriores</p>
                  <pre class="bg-light border rounded p-3 small mb-4" style="max-height: 320px; overflow: auto;"><?php echo $esc($tcgxDatosAntes); ?></pre>

                  <p class="tcgx-admin-form-subtitle">Datos posteriores</p>
                  <pre class="bg-light border rounded p-3 small mb-4" style="max-height: 320px; overflow: auto;"><?php echo $esc($tcgxDatosDespues); ?></pre>

                  <div class="tcgx-admin-form-actions">
                     <a href="auditorias.php" class="btn btn-outline-secondary">Regresar al listado</a>
                  </div>
               </div>
            </section>
            <!-- FIN BLOQUE: DETALLE DE EVENTO DE AUDITORIA -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxAdminSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxAdminSidebarModo);
   ?>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="vendor/js/admin-panel.js?v=20260612a"></script>
</body>
</html>
