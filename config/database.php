<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'red_social');
define('DB_USER', 'root');  // Cambiar por tu usuario de MySQL
define('DB_PASS', '');      // Cambiar por tu contraseña de MySQL

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Opciones básicas de conexión
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                $options
            );
            
            // Configurar variables de sesión de manera segura
            try {
                // Intentar configurar timeouts más largos
                $this->conn->exec("SET SESSION wait_timeout = 300");           // 5 minutos
                $this->conn->exec("SET SESSION innodb_lock_wait_timeout = 50"); // 50 segundos
            } catch (PDOException $e) {
                // Si falla la configuración de timeouts, continuar de todos modos
                error_log("Advertencia: No se pudieron configurar los timeouts personalizados: " . $e->getMessage());
            }
            
        } catch(PDOException $exception) {
            error_log("Error de conexión a la base de datos: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos. Por favor, inténtalo de nuevo más tarde.");
        }
        
        return $this->conn;
    }
}
?> 