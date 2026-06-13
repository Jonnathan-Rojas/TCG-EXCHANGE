<?php
declare(strict_types=1);

require_once __DIR__ . '/rutas_assets.php';

/**
 * Fragmento de plantilla: desde DOCTYPE hasta el comentario BLOQUE PAGE WRAP FIN (inclusive).
 * Motivo tecnico: separacion solicitada por el programador; lo consumen paginas raiz con require.
 *
 * Variables opcionales (definir en el PHP que incluye este archivo antes del require):
 * - $tcgxPageTitle (string): titulo documento.
 * - $tcgxMetaDescription (string): meta description.
 * - $tcgxMetaKeywords (string): meta keywords; si no se define, se usa el texto por defecto de portada.
 * - $tcgxOgTitle, $tcgxOgDescription, $tcgxOgUrl, $tcgxOgImage (string): Open Graph; mismos valores por defecto en todas las paginas si no se definen.
 * - $tcgxOcultarRastreo (bool): true omite el bloque de rastreo (p. ej. buscar-cartas.php).
 * - $tcgxRastreoGuiaValor (string): valor precargado en el campo de rastreo (p. ej. tras consulta en rastreo-envio.php).
 * - $tcgxCabeceraOscura (bool): true usa logo claro y estilos de cabecera oscura (p. ej. buscar-cartas.php).
 * - $tcgxBodyClass (string): clase adicional en body para estilos de pagina concretos.
 * Cabecera visible (navbar + bloque rastreo opcional) y cierre de page-wrap son identicos en todas las vistas que incluyen este archivo.
 * Favicon e icono de anclaje: images/logo512.png (definido en el bloque FAVICON E ICONO APP del head).
 */

$tcgxPageTitle = $tcgxPageTitle ?? 'TCG EXCHANGE | Envío y seguimiento de productos TCG en Costa Rica';
$tcgxMetaDescription = $tcgxMetaDescription ?? 'TCG EXCHANGE es una red nacional para el envío, recepción y seguimiento de productos TCG entre tiendas afiliadas en Costa Rica. Seguro, confiable y diseñado para la comunidad.';
$tcgxMetaKeywords = $tcgxMetaKeywords ?? 'TCG, intercambio TCG, envío cartas, trading card game, envío cartas Costa Rica, red TCG, seguimiento paquetes TCG, tiendas TCG Costa Rica, TCG GAMES STORE';
$tcgxOgTitle = $tcgxOgTitle ?? 'TCG EXCHANGE | Red de envío TCG';
$tcgxOgDescription = $tcgxOgDescription ?? 'Sistema de envío y seguimiento de productos TCG entre tiendas afiliadas en Costa Rica.';
$tcgxOgUrl = $tcgxOgUrl ?? 'https://www.tcgstorecr.com/tcgexchange';
$tcgxOgImage = $tcgxOgImage ?? '';
$tcgxOcultarRastreo = (bool) ($tcgxOcultarRastreo ?? false);
$tcgxRastreoGuiaValor = mb_strtoupper(trim((string) ($tcgxRastreoGuiaValor ?? '')), 'UTF-8');
$tcgxCabeceraOscura = (bool) ($tcgxCabeceraOscura ?? false);
$tcgxBodyClass = trim((string) ($tcgxBodyClass ?? ''));

$tcgxUrlLogoCabecera = tcgexchange_url_recurso_proyecto(
    $tcgxCabeceraOscura ? 'images/logo-on-dark.svg' : 'images/logo.svg'
);
$tcgxUrlFavicon = tcgexchange_url_recurso_proyecto('images/logo512.png');
?>
<!DOCTYPE html>
<html lang="es">

<!-- === BLOQUE HEAD: INICIO ===
     Define metadatos, tipografias y hojas de estilo compartidos por las paginas que incluyen este fragmento. -->

<head>

   <!-- Metadatos base de renderizado y compatibilidad -->

   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">

   <!-- === FAVICON E ICONO APP: INICIO ===
        Ruta relativa a la raiz del sitio; mismo recurso para pestaña del navegador y apple-touch-icon. -->
   <link rel="icon" href="<?php echo htmlspecialchars($tcgxUrlFavicon, ENT_QUOTES, 'UTF-8'); ?>" type="image/png" sizes="512x512">
   <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($tcgxUrlFavicon, ENT_QUOTES, 'UTF-8'); ?>" sizes="512x512">
   <!-- === FAVICON E ICONO APP: FIN === -->

   <!-- Metadatos SEO de pagina -->

   <title><?php echo htmlspecialchars($tcgxPageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
   <meta name="description" content="<?php echo htmlspecialchars($tcgxMetaDescription, ENT_QUOTES, 'UTF-8'); ?>">
   <meta name="keywords" content="<?php echo htmlspecialchars($tcgxMetaKeywords, ENT_QUOTES, 'UTF-8'); ?>">
   <meta name="author" content="ARVEX LABS">
   <meta name="robots" content="index, follow">

   <!-- Metadatos Open Graph para vista previa en redes/plataformas -->

   <meta property="og:title" content="<?php echo htmlspecialchars($tcgxOgTitle, ENT_QUOTES, 'UTF-8'); ?>">
   <meta property="og:description" content="<?php echo htmlspecialchars($tcgxOgDescription, ENT_QUOTES, 'UTF-8'); ?>">
   <meta property="og:type" content="website">
   <meta property="og:url" content="<?php echo htmlspecialchars($tcgxOgUrl, ENT_QUOTES, 'UTF-8'); ?>">
   <meta property="og:image" content="<?php echo htmlspecialchars($tcgxOgImage, ENT_QUOTES, 'UTF-8'); ?>">

   <!-- === BLOQUE SCHEMA ORG SERVICE: INICIO ===
        Datos estructurados JSON-LD para describir el servicio en motores de busqueda. -->

   <script type="application/ld+json">
      {
         "@context": "https://schema.org",
         "@type": "Service",
         "name": "TCG EXCHANGE",
         "description": "Servicio de envío, recepción y seguimiento de productos TCG entre tiendas afiliadas en Costa Rica.",
         "provider": {
            "@type": "Organization",
            "name": "TCG EXCHANGE",
            "url": "https://tcgstorecr.com/tcgexchange"
         },
         "areaServed": {
            "@type": "Country",
            "name": "Costa Rica"
         },
         "serviceType": "Logística y envío de paquetería TCG",
         "availableChannel": {
            "@type": "ServiceChannel",
            "serviceLocation": {
               "@type": "Place",
               "name": "Tiendas afiliadas TCG EXCHANGE"
            }
         }
      }
   </script>

   <!-- === BLOQUE SCHEMA ORG SERVICE: FIN === -->

   <!-- Google Fonts -->

   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Rethink+Sans:ital,wght@0,400..800;1,400..800&display=swap"
      rel="stylesheet">

   <!-- CSS -->

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
   <!-- INICIO BLOQUE: SWEETALERT2 CSS
        Estilos del unico canal de mensajes al usuario definido en diseño.md (plugins de interfaz). -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css" crossorigin="anonymous">
   <!-- FIN BLOQUE: SWEETALERT2 CSS -->
   <link rel="stylesheet" href="vendor/css/tcgexchange.css">
</head>

<!-- === BLOQUE HEAD: FIN === -->

<!-- === BLOQUE BODY: INICIO ===
     Estructura principal de cabecera, contenido de rastreo y pie de pagina. -->

<body<?php echo $tcgxBodyClass !== '' ? ' class="' . htmlspecialchars($tcgxBodyClass, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>

   <!-- === BLOQUE PAGE WRAP: INICIO ===
        Contenedor flex principal para separar contenido y footer. -->

   <div class="page-wrap">

      <!-- === BLOQUE CABECERA: INICIO ===
           Cabecera principal con navegacion desktop y offcanvas movil. -->

      <header class="header header-default<?php echo $tcgxOcultarRastreo ? '' : ' header-overlay'; ?><?php echo $tcgxCabeceraOscura ? ' tcgx-header-oscuro' : ''; ?>">
         <div class="sticky-height"></div>
         <div class="header-wrapper">
            <div class="header-nav-wrapper header-sticky">
               <nav class="navbar navbar-expand-xl">
                  <div class="container">
                     <a href="index.php" class="navbar-brand">
                        <img src="<?php echo htmlspecialchars($tcgxUrlLogoCabecera, ENT_QUOTES, 'UTF-8'); ?>" alt="TCG EXCHANGE — By TCG GAMES STORE" class="img-fluid" width="290" height="68" decoding="async">
                     </a>

                     <!-- Trigger tecnico de Bootstrap Offcanvas para menu movil -->

                     <button class="navbar-toggler offcanvas-nav-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainOffcanvasNav" aria-controls="mainOffcanvasNav" aria-label="Abrir menu de navegacion">
                        Menu <i class="fa fa-bars" aria-hidden="true"></i>
                     </button>

                     <!-- Contenedor offcanvas asociado al trigger del menu movil -->

                     <div class="offcanvas offcanvas-start offcanvas-nav" id="mainOffcanvasNav" tabindex="-1" aria-labelledby="mainOffcanvasNavLabel">
                        <div class="offcanvas-header">
                           <h2 class="visually-hidden" id="mainOffcanvasNavLabel">Menu principal</h2>
                           <a href="index.php"><img src="<?php echo htmlspecialchars($tcgxUrlLogoCabecera, ENT_QUOTES, 'UTF-8'); ?>" alt="TCG EXCHANGE — By TCG GAMES STORE" width="290" height="68" decoding="async"></a>
                           <button type="button" class="btn-close bg-primary" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body pt-0 align-items-center justify-content-between">
                           <ul class="navbar-nav ms-auto align-items-lg-center">
                              <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa fa-house me-2" aria-hidden="true"></i>Inicio</a></li>
                              <li class="nav-item"><a class="nav-link" href="buscar-cartas.php"><i class="fa fa-magnifying-glass me-2" aria-hidden="true"></i>Buscar Cartas</a></li>
                              <li class="nav-item"><a class="nav-link" href="calificacion_usuarios.php"><i class="fa fa-users me-2" aria-hidden="true"></i>Calificación de Usuarios</a></li>
                              <li class="nav-item"><a class="nav-link" href="red.php"><i class="fa fa-building me-2" aria-hidden="true"></i>Red de Envíos</a></li>
                              <li class="nav-item"><a class="nav-link" href="login.php"><i class="fa fa-right-to-bracket me-2" aria-hidden="true"></i>Login</a></li>
                           </ul>
                        </div>
                     </div>
                  </div>
               </nav>
            </div>
         </div>
      </header>

      <!-- === BLOQUE CABECERA: FIN === -->

      <?php if (!$tcgxOcultarRastreo): ?>
      <!-- === BLOQUE RASTREO: INICIO ===
           Formulario de consulta por numero de guia en portada. -->

      <section class="rastreo-sec rastreo-tracking tracking-page bg-cover tcgx-rastreo-bg">
         <div class="container">
            <div class="row">
               <div class="col-lg-12">
                  <div class="tracking-form2">
                     <div class="form-inner">
                        <form method="post" action="rastreo-envio.php">
                           <div class="row g-4 justify-content-center">
                              <div class="col-lg-6 col-md-8">
                                 <label for="guia">Rastrear Envío</label>
                                 <input type="text" class="form-control text-uppercase" id="guia" name="guia" maxlength="17" placeholder="Ingrese el número de envío" autocomplete="off" value="<?php echo htmlspecialchars($tcgxRastreoGuiaValor, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $tcgxRastreoGuiaValor === '' ? ' autofocus' : ''; ?>>
                              </div>
                              <div class="col-lg-4 col-md-4 d-flex align-items-end">
                                 <button type="submit" class="btn btn-primary w-100">Rastrear <i class="fa fa-arrow-right"></i><span></span></button>
                              </div>
                           </div>
                        </form>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>

      <!-- === BLOQUE RASTREO: FIN === -->
      <?php endif; ?>

   </div>

   <!-- === BLOQUE PAGE WRAP: FIN === -->
