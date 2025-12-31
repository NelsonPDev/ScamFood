<?php
session_start();

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Usuario por defecto de XAMPP
define('DB_PASS', '');          // Contraseña vacía por defecto
define('DB_NAME', 'scamfood_db'); // Nombre de tu base de datos

// API de Open Food Facts
define('OFF_API_URL', 'https://world.openfoodfacts.org/api/v0/product/');

// URL base de la aplicación
define('BASE_URL', 'http://localhost/SCAMFOOD/');

// Tiempo de expiración de sesión (en segundos)
define('SESSION_TIMEOUT', 3600); // 1 hora

// Conexión a la base de datos
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

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