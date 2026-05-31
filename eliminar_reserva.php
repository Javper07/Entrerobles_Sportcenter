<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$reserva_id = $data['reserva_id'] ?? null;

if (!$reserva_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de reserva no proporcionado']);
    exit;
}

require 'db.php';

try {
    $pdo = getDbConnection();
    
    // Verificar que la reserva pertenece al usuario
    $stmt = $pdo->prepare("SELECT id FROM reservas WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->execute([
        ':id' => $reserva_id,
        ':usuario_id' => $_SESSION['usuario_id']
    ]);
    
    if (!$stmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Reserva no encontrada']);
        exit;
    }
    
    // Eliminar la reserva
    $stmt = $pdo->prepare("DELETE FROM reservas WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->execute([
        ':id' => $reserva_id,
        ':usuario_id' => $_SESSION['usuario_id']
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Reserva eliminada correctamente']);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error al eliminar la reserva']);
    exit;
}
