<?php
declare(strict_types=1);

// INICIO BLOQUE: URL DE RECURSOS BAJO LA RAIZ DEL PROYECTO
// Calcula ruta absoluta en el sitio hacia images/, vendor/, etc., desde cualquier PHP en raiz o en admin/, client/, store/, cd/.

/**
 * @param string $rutaRelativaProyecto p. ej. images/logo.svg
 */
function tcgexchange_url_recurso_proyecto(string $rutaRelativaProyecto): string
{
    $rutaRelativaProyecto = ltrim(str_replace('\\', '/', $rutaRelativaProyecto), '/');
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dirPagina = dirname($script);
    if ($dirPagina === '/' || $dirPagina === '\\' || $dirPagina === '.') {
        return '/' . $rutaRelativaProyecto;
    }
    $carpetaActual = basename($dirPagina);
    $modulos = ['admin', 'client', 'store', 'cd'];
    $raizProyecto = in_array($carpetaActual, $modulos, true) ? dirname($dirPagina) : $dirPagina;
    if ($raizProyecto === '/' || $raizProyecto === '\\' || $raizProyecto === '.') {
        return '/' . $rutaRelativaProyecto;
    }

    return rtrim($raizProyecto, '/') . '/' . $rutaRelativaProyecto;
}
// FIN BLOQUE: URL DE RECURSOS BAJO LA RAIZ DEL PROYECTO
