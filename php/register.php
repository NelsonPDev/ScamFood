<?php
require_once 'config.php';

// Establecer cabeceras JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Permitir errores durante desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit;
}

// Obtener datos del formulario
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Si json_decode falla, usar $_POST
if ($data === null) {
    $data = $_POST;
}

// Debug: Registrar datos recibidos
error_log("Datos recibidos: " . print_r($data, true));

// Validar datos obligatorios
if (empty($data['nombre']) || empty($data['email']) || empty($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Todos los campos marcados con * son obligatorios'
    ]);
    exit;
}

// Sanitizar y validar datos
$nombre = trim($data['nombre']);
$email = trim($data['email']);
$telefono = isset($data['telefono']) ? trim($data['telefono']) : '';
$fecha_nacimiento = isset($data['fecha_nacimiento']) && !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null;
$password = $data['password'];
$alergenos = isset($data['alergenos']) ? $data['alergenos'] : [];
$newsletter = isset($data['newsletter']) ? intval($data['newsletter']) : 0;

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'El correo electrónico no es válido'
    ]);
    exit;
}

// Validar contraseña
if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'La contraseña debe tener al menos 8 caracteres'
    ]);
    exit;
}

// Convertir fecha si está en formato DD/MM/YYYY
if ($fecha_nacimiento && strpos($fecha_nacimiento, '/') !== false) {
    $parts = explode('/', $fecha_nacimiento);
    if (count($parts) === 3) {
        $fecha_nacimiento = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
}

try {
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Este correo electrónico ya está registrado'
        ]);
        exit;
    }
    
    // Hash de la contraseña
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar usuario en la base de datos
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, fecha_nacimiento, password) VALUES (?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $nombre,
        $email,
        $telefono,
        $fecha_nacimiento,
        $passwordHash
    ]);
    
    $usuarioId = $pdo->lastInsertId();
    
    // Insertar alérgenos del usuario
    if (!empty($alergenos) && is_array($alergenos)) {
        $stmt = $pdo->prepare("INSERT INTO alergenos_usuario (usuario_id, alergeno) VALUES (?, ?)");
        
        foreach ($alergenos as $alergeno) {
            if (!empty(trim($alergeno))) {
                try {
                    $stmt->execute([$usuarioId, trim($alergeno)]);
                } catch (PDOException $e) {
                    // Ignorar errores de duplicados
                    if ($e->getCode() !== '23000') { // Código de violación de integridad
                        throw $e;
                    }
                }
            }
        }
    }
    
    // Iniciar sesión automáticamente
    $_SESSION['user_id'] = $usuarioId;
    $_SESSION['user_name'] = $nombre;
    $_SESSION['user_email'] = $email;
    
    echo json_encode([
        'success' => true,
        'message' => '¡Registro exitoso! Tu cuenta ha sido creada.',
        'user' => [
            'id' => $usuarioId,
            'name' => $nombre,
            'email' => $email
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en registro: " . $e->getMessage());
    error_log("Error SQL: " . $e->getCode());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage(),
        'debug' => [
            'code' => $e->getCode(),
            'info' => $e->errorInfo
        ]
    ]);
} catch (Exception $e) {
    error_log("Error general en registro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor. Por favor intenta nuevamente.'
    ]);
}
?>