<?php
session_start();

header('Content-Type: application/json');

// Debug

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'No autenticado',
        'debug_session_id' => session_id(),
        'debug_session_vars' => $_SESSION
    ]);
    exit;
}

require 'db.php';

try {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT id, nombre, email FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        echo json_encode([
            'success' => true,
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Usuario no encontrado en base de datos'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Error al obtener información del usuario',
        'db_error' => $e->getMessage()
    ]);
}
