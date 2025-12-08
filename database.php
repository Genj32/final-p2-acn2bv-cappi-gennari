
<?php
/**
 * Configuración de conexión a la base de datos
 * Dragon Ball Z - Guerreros
 */

const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASS = "";
const DB_NAME = "dbz_guerreros";

/**
 * Función para conectar a la base de datos
 * Retorna una instancia de PDO configurada
 */
function conectar_db()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        
        // Configurar PDO para lanzar excepciones en errores
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Configurar para que devuelva arrays asociativos por defecto
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    } catch (PDOException $e) {
        // Registrar el error en el log del servidor
        error_log("Error de conexión a BD: " . $e->getMessage());
        
        // Mostrar mensaje genérico al usuario (no exponer detalles de seguridad)
        die('Error al conectar a la base de datos. Por favor, contacte al administrador.');
    }
}