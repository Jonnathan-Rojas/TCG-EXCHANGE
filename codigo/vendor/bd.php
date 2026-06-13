<?php
declare(strict_types=1);

// INICIO BLOQUE: CONFIGURACION DE CONEXION MYSQL POR PDO
// Centraliza acceso a la base con charset utf8mb4 y zona horaria de sesion alineada a diseño.md.
// Si cambia el servidor o el motor MySQL no esta en el mismo equipo que PHP, ajustar BD_HOST al host o IP que corresponda en ese despliegue; si cambian base o credenciales, ajustar BD_NOMBRE, BD_USUARIO y BD_CLAVE.
const BD_HOST = 'localhost';
const BD_NOMBRE = 'tcgexchange';
const BD_USUARIO = 'test';
const BD_CLAVE = 'test';
// FIN BLOQUE: CONFIGURACION DE CONEXION MYSQL POR PDO

// INICIO BLOQUE: CLASE SINGLETON PARA INSTANCIA PDO
// Evita abrir multiples conexiones por peticion y aplica atributos seguros para consultas preparadas reales.
final class Bd
{
    private static ?PDO $pdo = null;

    /**
     * Entrega la instancia unica de PDO; la crea en la primera llamada dentro del mismo ciclo de vida de la peticion HTTP.
     */
    public static function getPdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // Alinea funciones de fecha de PHP con Costa Rica antes de cualquier lectura o escritura relacionada en esta peticion.
        date_default_timezone_set('America/Costa_Rica');

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            BD_HOST,
            BD_NOMBRE
        );

        // INICIO BLOQUE: OPCIONES PDO PARA CONSULTAS PREPARADAS Y ERRORES
        // ERRMODE_EXCEPTION fuerza fallos visibles como excepcion en lugar de silencios.
        // FETCH_ASSOC evita columnas duplicadas por indice numerico en resultados.
        // EMULATE_PREPARES false delega el prepare al motor MySQL para enlazar parametros con mayor fidelidad.
        self::$pdo = new PDO($dsn, BD_USUARIO, BD_CLAVE, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        // FIN BLOQUE: OPCIONES PDO PARA CONSULTAS PREPARADAS Y ERRORES

        // INICIO BLOQUE: ZONA HORARIA SESION MYSQL
        // Offset UTC-6 fijo (Costa Rica, sin DST): valido en todo motor sin tablas time_zone; alineado a diseño.md con date_default_timezone_set arriba.
        self::$pdo->exec("SET time_zone = '-06:00'");
        // FIN BLOQUE: ZONA HORARIA SESION MYSQL

        return self::$pdo;
    }
}
// FIN BLOQUE: CLASE SINGLETON PARA INSTANCIA PDO
