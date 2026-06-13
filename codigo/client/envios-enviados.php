<?php
declare(strict_types=1);

// INICIO BLOQUE: REDIRECCION LEGACY A ENVIOS
require __DIR__ . '/includes/carga_sesion_client.php';
header('Location: envios.php', true, 302);
exit;
// FIN BLOQUE: REDIRECCION LEGACY A ENVIOS
