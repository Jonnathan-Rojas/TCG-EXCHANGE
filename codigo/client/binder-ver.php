<?php
declare(strict_types=1);

// INICIO BLOQUE: ARRANQUE, LOGICA, AUDITORIA Y TOKEN CSRF DEL DETALLE DE BINDER
require __DIR__ . '/includes/carga_sesion_client.php';
require_once __DIR__ . '/includes/client_binders_logica.php';
require __DIR__ . '/../admin/includes/auditorias_logica.php';

if (empty($_SESSION['tcgx_binders_csrf'])) {
    $_SESSION['tcgx_binders_csrf'] = bin2hex(random_bytes(32));
}
$tcgxBindersCsrf = $_SESSION['tcgx_binders_csrf'];
$pdo = Bd::getPdo();
// FIN BLOQUE: ARRANQUE, LOGICA, AUDITORIA Y TOKEN CSRF DEL DETALLE DE BINDER


// INICIO BLOQUE: REPLAY PRG TRAS MUTACION (SIN ID EN GET)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && !empty($_SESSION['tcgx_binders_ver_replay'])) {
    $tcgxReplayBinderId = trim((string) $_SESSION['tcgx_binders_ver_replay']);
    unset($_SESSION['tcgx_binders_ver_replay']);
    if ($tcgxReplayBinderId !== '' && ctype_digit($tcgxReplayBinderId)) {
        $tcgxPageTitleReplay = 'Detalle de binder | TCG EXCHANGE';
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <title><?php echo htmlspecialchars($tcgxPageTitleReplay, ENT_QUOTES, 'UTF-8'); ?></title>
   <meta name="robots" content="noindex, nofollow">
</head>
<body>
   <!-- INICIO BLOQUE: AUTO-POST TRAS PRG (ID EN SESION, NO EN URL) -->
   <form id="tcgx-replay-binder-ver" method="post" action="binder-ver.php">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo htmlspecialchars($tcgxBindersCsrf, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="id_binder" value="<?php echo htmlspecialchars($tcgxReplayBinderId, ENT_QUOTES, 'UTF-8'); ?>">
   </form>
   <script>document.getElementById('tcgx-replay-binder-ver').submit();</script>
   <!-- FIN BLOQUE: AUTO-POST TRAS PRG -->
</body>
</html>
        <?php
        exit;
    }
}
// FIN BLOQUE: REPLAY PRG TRAS MUTACION


// INICIO BLOQUE: ACCESO POR POST (SIN GET CON DATOS)
$tokenPost = (string) ($_POST['tcgx_csrf_token'] ?? '');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || $tcgxBindersCsrf === '' || $tokenPost === '' || !hash_equals($tcgxBindersCsrf, $tokenPost)) {
    header('Location: binders.php', true, 303);
    exit;
}

$idBinderRaw = trim((string) ($_POST['id_binder'] ?? ''));
if ($idBinderRaw === '' || !ctype_digit($idBinderRaw)) {
    $_SESSION['tcgx_binders_flash'] = ['tipo' => 'error', 'texto' => 'BINDER NO INDICADO.'];
    header('Location: binders.php', true, 303);
    exit;
}
$idBinder = (int) $idBinderRaw;

$tcgxBinder = tcgx_client_binders_obtener($pdo, $idBinder, $idUsuarioVista);
if ($tcgxBinder === null) {
    $_SESSION['tcgx_binders_flash'] = ['tipo' => 'error', 'texto' => 'EL BINDER NO EXISTE O NO LE PERTENECE.'];
    header('Location: binders.php', true, 303);
    exit;
}
// FIN BLOQUE: ACCESO POR POST


// INICIO BLOQUE: PROCESAMIENTO DE ACCIONES SOBRE BINDER Y PRODUCTOS
if (isset($_POST['tcgx_binders_accion'])) {
    $errorRevalidacion = tcgx_client_revalidar_operacion($pdo, $idUsuarioVista);
    if ($errorRevalidacion !== null) {
        $_SESSION['tcgx_binders_flash'] = ['tipo' => 'error', 'texto' => $errorRevalidacion];
        header('Location: binders.php', true, 303);
        exit;
    }

    $accion = (string) $_POST['tcgx_binders_accion'];
    $resultado = ['ok' => false, 'error' => 'ACCION NO RECONOCIDA.'];

    if ($accion === 'eliminar_binder') {
        $resultado = tcgx_client_binders_eliminar($pdo, $idBinder, $idUsuarioVista, $idUsuarioVista);
        if ($resultado['ok']) {
            $_SESSION['tcgx_binders_flash'] = ['tipo' => 'ok', 'texto' => 'BINDER ELIMINADO CORRECTAMENTE.'];
            header('Location: binders.php', true, 303);
            exit;
        }
    } elseif ($accion === 'eliminar_producto') {
        $idProductoRaw = trim((string) ($_POST['id_producto'] ?? ''));
        if ($idProductoRaw !== '' && ctype_digit($idProductoRaw)) {
            $resultado = tcgx_client_productos_eliminar($pdo, (int) $idProductoRaw, $idUsuarioVista, $idUsuarioVista);
        } else {
            $resultado = ['ok' => false, 'error' => 'PRODUCTO NO INDICADO.'];
        }
    } elseif ($accion === 'toggle_publicado') {
        $idProductoRaw = trim((string) ($_POST['id_producto'] ?? ''));
        if ($idProductoRaw !== '' && ctype_digit($idProductoRaw)) {
            $resultado = tcgx_client_productos_toggle_publicado($pdo, (int) $idProductoRaw, $idUsuarioVista, $idUsuarioVista);
        } else {
            $resultado = ['ok' => false, 'error' => 'PRODUCTO NO INDICADO.'];
        }
    }

    $_SESSION['tcgx_binders_flash'] = $resultado['ok']
        ? ['tipo' => 'ok', 'texto' => 'OPERACION REALIZADA CORRECTAMENTE.']
        : ['tipo' => 'error', 'texto' => $resultado['error']];
    $_SESSION['tcgx_binders_ver_replay'] = (string) $idBinder;
    header('Location: binder-ver.php', true, 303);
    exit;
}
// FIN BLOQUE: PROCESAMIENTO DE ACCIONES


// INICIO BLOQUE: LECTURA DE MENSAJE FLASH (PRG)
$tcgxBindersFlash = null;
if (isset($_SESSION['tcgx_binders_flash']) && is_array($_SESSION['tcgx_binders_flash'])) {
    $tcgxBindersFlash = $_SESSION['tcgx_binders_flash'];
    unset($_SESSION['tcgx_binders_flash']);
}
// FIN BLOQUE: LECTURA DE MENSAJE FLASH (PRG)


// INICIO BLOQUE: AUDITORIA DE LECTURA DEL DETALLE (ACCION LEER)
if (!isset($_POST['tcgx_binders_accion'])) {
    tcgx_auditorias_registrar_lectura($pdo, $idUsuarioVista, 'binders', (string) $idBinder);
}
// FIN BLOQUE: AUDITORIA DE LECTURA DEL DETALLE


// INICIO BLOQUE: CARGA DE PRODUCTOS DEL BINDER CON IMAGEN PRINCIPAL PARA CATALOGO
$tcgxProductos = tcgx_client_productos_listar($pdo, $idBinder, $idUsuarioVista);
foreach ($tcgxProductos as $tcgxIdxProd => $tcgxProdFila) {
    $tcgxIdProdCat = (int) $tcgxProdFila['id'];
    $tcgxImgsProd = tcgx_client_productos_imagenes($pdo, $tcgxIdProdCat, $idUsuarioVista);
    $tcgxProductos[$tcgxIdxProd]['url_imagen'] = null;
    if ($tcgxImgsProd !== []) {
        $tcgxProductos[$tcgxIdxProd]['url_imagen'] = tcgx_client_binder_img_url(
            $idBinder,
            (string) $tcgxImgsProd[0]['nombreimagen']
        );
    }
}
// FIN BLOQUE: CARGA DE PRODUCTOS DEL BINDER CON IMAGEN PRINCIPAL PARA CATALOGO

// INICIO BLOQUE: VALORES UNICOS PARA FILTROS DINAMICOS DEL CATALOGO
$tcgxFiltroExpansiones = [];
$tcgxFiltroRarezas = [];
$tcgxFiltroCondiciones = [];
$tcgxFiltroIdiomas = [];
$tcgxFiltroMonedas = [];
foreach ($tcgxProductos as $tcgxProdFiltro) {
    $tcgxTxtExpansion = mb_strtoupper(trim((string) ($tcgxProdFiltro['expansion'] ?? '')), 'UTF-8');
    if ($tcgxTxtExpansion !== '' && !in_array($tcgxTxtExpansion, $tcgxFiltroExpansiones, true)) {
        $tcgxFiltroExpansiones[] = $tcgxTxtExpansion;
    }
    $tcgxTxtRareza = mb_strtoupper(trim((string) ($tcgxProdFiltro['rareza'] ?? '')), 'UTF-8');
    if ($tcgxTxtRareza !== '' && !in_array($tcgxTxtRareza, $tcgxFiltroRarezas, true)) {
        $tcgxFiltroRarezas[] = $tcgxTxtRareza;
    }
    $tcgxTxtCondicion = mb_strtoupper(trim((string) ($tcgxProdFiltro['condicion'] ?? '')), 'UTF-8');
    if ($tcgxTxtCondicion !== '' && !in_array($tcgxTxtCondicion, $tcgxFiltroCondiciones, true)) {
        $tcgxFiltroCondiciones[] = $tcgxTxtCondicion;
    }
    $tcgxTxtIdioma = mb_strtoupper(trim((string) ($tcgxProdFiltro['idioma'] ?? '')), 'UTF-8');
    if ($tcgxTxtIdioma !== '' && !in_array($tcgxTxtIdioma, $tcgxFiltroIdiomas, true)) {
        $tcgxFiltroIdiomas[] = $tcgxTxtIdioma;
    }
    $tcgxTxtMoneda = mb_strtoupper(trim((string) ($tcgxProdFiltro['tipomoneda'] ?? '')), 'UTF-8');
    if ($tcgxTxtMoneda !== '' && !in_array($tcgxTxtMoneda, $tcgxFiltroMonedas, true)) {
        $tcgxFiltroMonedas[] = $tcgxTxtMoneda;
    }
}
sort($tcgxFiltroExpansiones, SORT_STRING);
sort($tcgxFiltroRarezas, SORT_STRING);
sort($tcgxFiltroCondiciones, SORT_STRING);
sort($tcgxFiltroIdiomas, SORT_STRING);
sort($tcgxFiltroMonedas, SORT_STRING);
// FIN BLOQUE: VALORES UNICOS PARA FILTROS DINAMICOS DEL CATALOGO

$tcgxPageTitle = 'Detalle de binder | TCG EXCHANGE';
$esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$idBinderEsc = $esc((string) $idBinder);
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

   <link rel="stylesheet" href="vendor/css/client-panel.css?v=20260612g">
</head>

<body class="tcgx-client-app" id="tcgx-client-app-root">

   <div class="tcgx-client-app__body">

      <?php require __DIR__ . '/includes/sidebar.php'; ?>

      <div class="tcgx-client-main">

         <?php require __DIR__ . '/includes/header.php'; ?>

         <main class="tcgx-client-content" id="tcgx-client-main">

            <!-- INICIO BLOQUE: BARRA DE HERRAMIENTAS DEL BINDER -->
            <div class="tcgx-client-tabla-toolbar">
               <form method="post" action="binders.php" class="tcgx-client-tabla-toolbar__item">
                  <button type="submit" class="btn btn-outline-secondary">
                     <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Listado de binders
                  </button>
               </form>
               <form method="post" action="producto-crear.php" class="tcgx-client-tabla-toolbar__item">
                  <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxBindersCsrf); ?>">
                  <input type="hidden" name="id_binder" value="<?php echo $idBinderEsc; ?>">
                  <button type="submit" class="btn btn-success">
                     <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Agregar producto
                  </button>
               </form>
               <button type="button" class="btn btn-warning tcgx-client-tabla-toolbar__item" id="tcgx-btn-editar-binder" data-tcgx-id="<?php echo $idBinderEsc; ?>">
                  <i class="fa-solid fa-pen me-2" aria-hidden="true"></i>Editar binder
               </button>
               <button type="button" class="btn btn-danger tcgx-client-tabla-toolbar__item" id="tcgx-btn-eliminar-binder" data-tcgx-id="<?php echo $idBinderEsc; ?>" data-tcgx-nombre="<?php echo $esc($tcgxBinder['nombre']); ?>">
                  <i class="fa-solid fa-trash me-2" aria-hidden="true"></i>Eliminar binder
               </button>
            </div>
            <!-- FIN BLOQUE: BARRA DE HERRAMIENTAS DEL BINDER -->

            <!-- INICIO BLOQUE: CABECERA COMPACTA DEL BINDER (META EN FILA) -->
            <div class="tcgx-client-meta-fila mb-3">
               <span class="tcgx-client-meta-fila__titulo"><i class="fa-solid fa-book me-1" aria-hidden="true"></i><?php echo $esc($tcgxBinder['nombre']); ?></span>
               <span class="badge text-bg-success"><?php echo $esc($tcgxBinder['estado']); ?></span>
               <span class="tcgx-client-meta-fila__sep" aria-hidden="true">·</span>
               <span class="tcgx-client-meta-fila__item"><span class="tcgx-client-meta-fila__lbl">ID</span><?php echo $idBinderEsc; ?></span>
               <span class="tcgx-client-meta-fila__sep" aria-hidden="true">·</span>
               <span class="tcgx-client-meta-fila__item"><span class="tcgx-client-meta-fila__lbl">TCG</span><?php echo $esc($tcgxBinder['juego']); ?></span>
               <span class="tcgx-client-meta-fila__sep" aria-hidden="true">·</span>
               <span class="tcgx-client-meta-fila__item"><span class="tcgx-client-meta-fila__lbl">Descripción</span><?php echo $esc($tcgxBinder['descripcion'] ?? '—'); ?></span>
               <span class="tcgx-client-meta-fila__sep" aria-hidden="true">·</span>
               <span class="tcgx-client-meta-fila__item"><span class="tcgx-client-meta-fila__lbl">Registro</span><?php echo $esc($tcgxBinder['fecharegistro']); ?></span>
            </div>
            <!-- FIN BLOQUE: CABECERA COMPACTA DEL BINDER (META EN FILA) -->

            <!-- INICIO BLOQUE: CATALOGO VISUAL DE PRODUCTOS DEL BINDER -->
            <div class="tcgx-client-table-card">
               <h3 class="h6 mb-3"><i class="fa-solid fa-table-cells me-2" aria-hidden="true"></i>Catálogo</h3>
               <?php if ($tcgxProductos === []): ?>
                  <p class="text-secondary mb-0">SIN PRODUCTOS EN ESTE BINDER.</p>
               <?php else: ?>
                  <!-- INICIO BLOQUE: FILTROS Y BUSQUEDA DINAMICOS DEL CATALOGO (SIN GET) -->
                  <div class="tcgx-client-catalogo-filtro mb-3" id="tcgx-catalogo-filtros">
                     <div class="tcgx-client-catalogo-filtro__barra">
                        <div class="tcgx-client-catalogo-filtro__campo tcgx-client-catalogo-filtro__campo--buscar">
                           <label class="tcgx-client-catalogo-filtro__lbl" for="tcgx-catalogo-buscar">Buscar</label>
                           <input type="search" class="form-control form-control-sm text-uppercase" id="tcgx-catalogo-buscar" placeholder="NOMBRE, NÚMERO, ID…" autocomplete="off" autofocus>
                        </div>
                        <div class="tcgx-client-catalogo-filtro__campo">
                           <label class="tcgx-client-catalogo-filtro__lbl" for="tcgx-catalogo-publicado">Pub.</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-publicado" data-tcgx-filtro-dinamico>
                              <option value="">TODOS</option>
                              <option value="1">PUBLICADO</option>
                              <option value="0">NO PUBLICADO</option>
                           </select>
                        </div>
                        <div class="tcgx-client-catalogo-filtro__campo">
                           <label class="tcgx-client-catalogo-filtro__lbl" for="tcgx-catalogo-expansion">Exp.</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-expansion" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxFiltroExpansiones as $tcgxOptExpansion): ?>
                                 <option value="<?php echo $esc($tcgxOptExpansion); ?>"><?php echo $esc($tcgxOptExpansion); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-client-catalogo-filtro__campo">
                           <label class="tcgx-client-catalogo-filtro__lbl" for="tcgx-catalogo-rareza">Rareza</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-rareza" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxFiltroRarezas as $tcgxOptRareza): ?>
                                 <option value="<?php echo $esc($tcgxOptRareza); ?>"><?php echo $esc($tcgxOptRareza); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-client-catalogo-filtro__campo">
                           <label class="tcgx-client-catalogo-filtro__lbl" for="tcgx-catalogo-condicion">Cond.</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-condicion" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxFiltroCondiciones as $tcgxOptCondicion): ?>
                                 <option value="<?php echo $esc($tcgxOptCondicion); ?>"><?php echo $esc($tcgxOptCondicion); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-client-catalogo-filtro__campo">
                           <label class="tcgx-client-catalogo-filtro__lbl" for="tcgx-catalogo-idioma">Idioma</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-idioma" data-tcgx-filtro-dinamico>
                              <option value="">TODOS</option>
                              <?php foreach ($tcgxFiltroIdiomas as $tcgxOptIdioma): ?>
                                 <option value="<?php echo $esc($tcgxOptIdioma); ?>"><?php echo $esc($tcgxOptIdioma); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-client-catalogo-filtro__campo">
                           <label class="tcgx-client-catalogo-filtro__lbl" for="tcgx-catalogo-moneda">Moneda</label>
                           <select class="form-select form-select-sm" id="tcgx-catalogo-moneda" data-tcgx-filtro-dinamico>
                              <option value="">TODAS</option>
                              <?php foreach ($tcgxFiltroMonedas as $tcgxOptMoneda): ?>
                                 <option value="<?php echo $esc($tcgxOptMoneda); ?>"><?php echo $esc($tcgxOptMoneda); ?></option>
                              <?php endforeach; ?>
                           </select>
                        </div>
                        <div class="tcgx-client-catalogo-filtro__campo tcgx-client-catalogo-filtro__campo--precio">
                           <span class="tcgx-client-catalogo-filtro__lbl">Precio</span>
                           <input type="number" min="0" step="0.01" class="form-control form-control-sm" id="tcgx-catalogo-precio-min" placeholder="Mín." aria-label="Precio mínimo" data-tcgx-filtro-dinamico>
                           <span class="tcgx-client-catalogo-filtro__sep" aria-hidden="true">–</span>
                           <input type="number" min="0" step="0.01" class="form-control form-control-sm" id="tcgx-catalogo-precio-max" placeholder="Máx." aria-label="Precio máximo" data-tcgx-filtro-dinamico>
                        </div>
                        <div class="tcgx-client-catalogo-filtro__acciones">
                           <span class="tcgx-client-catalogo-filtro__contador" id="tcgx-catalogo-contador"><?php echo $esc((string) count($tcgxProductos)); ?> / <?php echo $esc((string) count($tcgxProductos)); ?></span>
                           <button type="button" class="btn btn-outline-secondary btn-sm" id="tcgx-catalogo-limpiar">Limpiar</button>
                        </div>
                     </div>
                  </div>
                  <p class="text-secondary d-none mb-3" id="tcgx-catalogo-sin-resultados">NINGÚN PRODUCTO COINCIDE CON EL FILTRO.</p>
                  <!-- FIN BLOQUE: FILTROS Y BUSQUEDA DINAMICOS DEL CATALOGO -->
                  <div class="tcgx-client-catalogo-grid" id="tcgx-catalogo-binder">
                     <?php foreach ($tcgxProductos as $prod): ?>
                        <?php
                        $pId = $esc($prod['id']);
                        $publicado = (int) ($prod['publicado'] ?? 0) === 1;
                        $urlImagen = isset($prod['url_imagen']) && $prod['url_imagen'] !== null
                            ? $esc((string) $prod['url_imagen'])
                            : '';
                        $tcgxBuscarCarta = mb_strtoupper(implode(' ', array_filter([
                            (string) ($prod['id'] ?? ''),
                            (string) ($prod['nombrecarta'] ?? ''),
                            (string) ($prod['expansion'] ?? ''),
                            (string) ($prod['numerocarta'] ?? ''),
                            (string) ($prod['rareza'] ?? ''),
                            (string) ($prod['idioma'] ?? ''),
                            (string) ($prod['condicion'] ?? ''),
                            (string) ($prod['tipomoneda'] ?? ''),
                        ], static fn ($v): bool => trim($v) !== '')), 'UTF-8');
                        ?>
                        <article class="tcgx-client-catalogo-carta"
                           data-tcgx-publicado="<?php echo $publicado ? '1' : '0'; ?>"
                           data-tcgx-buscar="<?php echo $esc($tcgxBuscarCarta); ?>"
                           data-tcgx-expansion="<?php echo $esc(mb_strtoupper(trim((string) ($prod['expansion'] ?? '')), 'UTF-8')); ?>"
                           data-tcgx-rareza="<?php echo $esc(mb_strtoupper(trim((string) ($prod['rareza'] ?? '')), 'UTF-8')); ?>"
                           data-tcgx-condicion="<?php echo $esc(mb_strtoupper(trim((string) ($prod['condicion'] ?? '')), 'UTF-8')); ?>"
                           data-tcgx-idioma="<?php echo $esc(mb_strtoupper(trim((string) ($prod['idioma'] ?? '')), 'UTF-8')); ?>"
                           data-tcgx-moneda="<?php echo $esc(mb_strtoupper(trim((string) ($prod['tipomoneda'] ?? '')), 'UTF-8')); ?>"
                           data-tcgx-precio="<?php echo $esc(number_format((float) ($prod['precioventa'] ?? 0), 2, '.', '')); ?>">
                           <div class="tcgx-client-catalogo-carta__img">
                              <?php if ($urlImagen !== ''): ?>
                                 <img src="<?php echo $urlImagen; ?>" alt="<?php echo $esc($prod['nombrecarta']); ?>" loading="lazy">
                              <?php else: ?>
                                 <span class="tcgx-client-catalogo-carta__placeholder" aria-hidden="true"><i class="fa-solid fa-image"></i></span>
                              <?php endif; ?>
                           </div>
                           <div class="tcgx-client-catalogo-carta__body">
                              <div class="tcgx-client-catalogo-carta__nombre"><?php echo $esc($prod['nombrecarta']); ?></div>
                              <div class="tcgx-client-catalogo-carta__meta">
                                 <?php echo $esc($prod['expansion'] ?? '—'); ?>
                                 · <?php echo $esc($prod['condicion'] ?? '—'); ?>
                                 · x<?php echo $esc($prod['cantidad']); ?>
                              </div>
                              <div class="tcgx-client-catalogo-carta__precio">
                                 <?php echo $esc(number_format((float) $prod['precioventa'], 2, '.', ',')); ?>
                                 <?php echo $esc($prod['tipomoneda']); ?>
                              </div>
                              <span class="badge <?php echo $publicado ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?php echo $publicado ? 'PUBLICADO' : 'NO PUBLICADO'; ?></span>
                           </div>
                           <div class="tcgx-client-catalogo-carta__acciones tcgx-client-actions">
                              <button type="button" class="btn btn-warning btn-sm" data-tcgx-action="editar" data-tcgx-id="<?php echo $pId; ?>" title="Editar" aria-label="Editar producto <?php echo $pId; ?>">
                                 <i class="fa-solid fa-pen" aria-hidden="true"></i>
                              </button>
                              <button type="button" class="btn btn-<?php echo $publicado ? 'secondary' : 'success'; ?> btn-sm" data-tcgx-action="toggle" data-tcgx-id="<?php echo $pId; ?>" title="<?php echo $publicado ? 'Ocultar' : 'Publicar'; ?>" aria-label="Cambiar publicación producto <?php echo $pId; ?>">
                                 <i class="fa-solid fa-<?php echo $publicado ? 'eye-slash' : 'eye'; ?>" aria-hidden="true"></i>
                              </button>
                              <button type="button" class="btn btn-danger btn-sm" data-tcgx-action="eliminar" data-tcgx-id="<?php echo $pId; ?>" data-tcgx-nombre="<?php echo $esc($prod['nombrecarta']); ?>" title="Eliminar" aria-label="Eliminar producto <?php echo $pId; ?>">
                                 <i class="fa-solid fa-trash" aria-hidden="true"></i>
                              </button>
                           </div>
                        </article>
                     <?php endforeach; ?>
                  </div>
               <?php endif; ?>
            </div>
            <!-- FIN BLOQUE: CATALOGO VISUAL DE PRODUCTOS DEL BINDER -->

         </main>

         <?php require __DIR__ . '/includes/footer.php'; ?>

      </div>
   </div>

   <?php
   $tcgxClientSidebarModo = 'offcanvas';
   require __DIR__ . '/includes/sidebar.php';
   unset($tcgxClientSidebarModo);
   ?>

   <!-- INICIO BLOQUE: FORMULARIOS OCULTOS PARA ACCIONES POST -->
   <form id="tcgx-form-editar-binder" method="post" action="binder-editar.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxBindersCsrf); ?>">
      <input type="hidden" name="id_binder" id="tcgx-form-editar-binder-id" value="<?php echo $idBinderEsc; ?>">
   </form>
   <form id="tcgx-form-accion-binder" method="post" action="binder-ver.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxBindersCsrf); ?>">
      <input type="hidden" name="id_binder" value="<?php echo $idBinderEsc; ?>">
      <input type="hidden" name="tcgx_binders_accion" id="tcgx-form-accion-binder-tipo" value="">
      <input type="hidden" name="id_producto" id="tcgx-form-accion-producto-id" value="">
   </form>
   <form id="tcgx-form-editar-producto" method="post" action="producto-editar.php" class="d-none">
      <input type="hidden" name="tcgx_csrf_token" value="<?php echo $esc($tcgxBindersCsrf); ?>">
      <input type="hidden" name="id_producto" id="tcgx-form-editar-producto-id" value="">
      <input type="hidden" name="id_binder" value="<?php echo $idBinderEsc; ?>">
   </form>
   <!-- FIN BLOQUE: FORMULARIOS OCULTOS -->

   <?php if ($tcgxBindersFlash !== null): ?>
      <script id="tcgx-binders-flash" type="application/json"><?php
         echo json_encode($tcgxBindersFlash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
      ?></script>
   <?php endif; ?>

   <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfAwRwa/8RTZXw0qPkhUjKtH+7kb7l857zCX9Rk=" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js" crossorigin="anonymous"></script>
   <script src="vendor/js/client-panel.js?v=20260612a"></script>
   <script src="vendor/js/binder-ver.js?v=20260612d"></script>
</body>
</html>
