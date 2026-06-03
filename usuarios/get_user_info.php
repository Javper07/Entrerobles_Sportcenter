<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

require '../comun/db.php';

try {
    $pdo  = getDbConnection();
    $stmt = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        echo json_encode(['success' => true, 'nombre' => $usuario['nombre'], 'email' => $usuario['email']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
}
