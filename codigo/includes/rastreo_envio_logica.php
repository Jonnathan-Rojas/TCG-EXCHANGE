<?php
declare(strict_types=1);

/**
 * Rastreo publico de envio individual por codigo CRE (sin consolidado CRC).
 */

require_once __DIR__ . '/../admin/includes/envios_logica.php';

// INICIO BLOQUE: NORMALIZACION Y VALIDACION DE CODIGO DE ENVIO PUBLICO
/**
 * Normaliza el codigo ingresado: recorte y MAYUSCULAS.
 */
function tcgx_rastreo_envio_normalizar(string $raw): string
{
    return mb_strtoupper(trim($raw), 'UTF-8');
}

/**
 * Valida formato de envio individual (CRE + 14 digitos). Retorna mensaje de error o null si es valido.
 */
function tcgx_rastreo_envio_validar(string $codigo): ?string
{
    if ($codigo === '') {
        return 'DEBE INGRESAR EL NUMERO DE ENVIO.';
    }
    if (str_starts_with($codigo, 'CRC')) {
        return 'ESE CODIGO CORRESPONDE A UN CONSOLIDADO. USE EL NUMERO DE ENVIO INDIVIDUAL (CRE).';
    }
    if (!preg_match('/^CRE[0-9]{14}$/', $codigo)) {
        return 'EL FORMATO DEL NUMERO DE ENVIO NO ES VALIDO.';
    }

    return null;
}
// FIN BLOQUE: NORMALIZACION Y VALIDACION DE CODIGO DE ENVIO PUBLICO


// INICIO BLOQUE: CONSULTA PUBLICA DE ENVIO INDIVIDUAL
/**
 * Consulta un envio individual y su trazabilidad para vista publica (sin datos sensibles de usuarios).
 */
function tcgx_rastreo_envio_consultar(PDO $pdo, string $codigo): array
{
    $envio = tcgx_envios_obtener($pdo, $codigo);
    if ($envio === null) {
        return [
            'ok' => false,
            'error' => 'NO SE ENCONTRO INFORMACION PARA ESE NUMERO DE ENVIO.',
        ];
    }

    return [
        'ok' => true,
        'envio' => $envio,
        'paquetes' => tcgx_envios_paquetes($pdo, $codigo),
        'movimientos' => tcgx_envios_movimientos($pdo, $codigo),
    ];
}

/**
 * Formatea fecha de movimiento para pantalla publica (MAYUSCULAS en salida textual fija).
 */
function tcgx_rastreo_envio_formatear_fecha(?string $fechaRaw): string
{
    if ($fechaRaw === null || trim($fechaRaw) === '') {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($fechaRaw);
    } catch (Exception $e) {
        return mb_strtoupper(trim($fechaRaw), 'UTF-8');
    }

    return mb_strtoupper($dt->format('d/m/Y H:i'), 'UTF-8');
}
// FIN BLOQUE: CONSULTA PUBLICA DE ENVIO INDIVIDUAL
