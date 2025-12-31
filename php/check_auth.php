<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isLoggedIn()) {
    $user = getCurrentUser($pdo);
    
    if ($user) {
        $allergens = getUserAllergens($pdo, $user['id']);
        
        echo json_encode([
            'loggedIn' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['nombre'],
                'email' => $user['email']
            ],
            'allergens' => $allergens
        ]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
} else {
    echo json_encode(['loggedIn' => false]);
}
?>