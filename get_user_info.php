<?php
session_start();

header('Content-Type: application/json');

// Debug
error_log('[get_user_info.php] Session ID: ' . session_id());
error_log('[get_user_info.php] Session status: ' . session_status());
error_log('[get_user_info.php] $_SESSION: ' . json_encode($_SESSION));

if (!isset($_SESSION['usuario_id'])) {
    error_log('[get_user_info.php] No user ID in session');
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
        error_log('[get_user_info.php] User found: ' . $usuario['email']);
        echo json_encode([
            'success' => true,
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email']
        ]);
    } else {
        error_log('[get_user_info.php] User not found in database');
        echo json_encode([
            'success' => false, 
            'error' => 'Usuario no encontrado en base de datos'
        ]);
    }
} catch (PDOException $e) {
    error_log('[get_user_info.php] Database error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error al obtener información del usuario',
        'db_error' => $e->getMessage()
    ]);
}
