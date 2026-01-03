<?php
session_start();

// API de Open Food Facts
define('OFF_API_URL', 'https://world.openfoodfacts.org/api/v0/product/');


define('BASE_URL', 'http://localhost/SCAMFOOD/');

// Tiempo de expiración de sesión (en segundos)
define('SESSION_TIMEOUT', 3600); // 1 hora

class Database {
    private $host = "localhost:3307";
    private $db_name = "scamfood_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            error_log("✅ Conexión a MySQL exitosa en puerto 3307");
        } catch(PDOException $exception) {
            error_log("Error de conexión: " . $exception->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Crear instancia de la base de datos
$database = new Database();
$pdo = $database->getConnection();

// Verificar si el usuario está autenticado
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }
    
    // Verificar tiempo de expiración
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        return false;
    }
    
    // Actualizar tiempo de última actividad
    $_SESSION['last_activity'] = time();
    return true;
}

// Obtener información del usuario actual
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT id, nombre, email, telefono, fecha_nacimiento, fecha_registro FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener usuario: " . $e->getMessage());
        return null;
    }
}

// Obtener alérgenos del usuario
function getUserAllergens($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT alergeno FROM alergenos_usuario WHERE usuario_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (PDOException $e) {
        error_log("Error al obtener alérgenos: " . $e->getMessage());
        return [];
    }
}

// Función para sanitizar entradas
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Inicializar sesión si es necesario
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}
?>