<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$reserva_id = $data['reserva_id'] ?? null;

if (!$reserva_id) {
    echo json_encode(['success' => false, 'error' => 'ID de reserva no proporcionado']);
    exit;
}

require 'db.php';

try {
    $pdo = getDbConnection();

    // Verificar que la reserva pertenece al usuario y está activa
    $stmt = $pdo->prepare("SELECT id, estado FROM reservas WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->execute([':id' => $reserva_id, ':usuario_id' => $_SESSION['usuario_id']]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reserva) {
        echo json_encode(['success' => false, 'error' => 'Reserva no encontrada']);
        exit;
    }

    if ($reserva['estado'] === 'cancelada') {
        echo json_encode(['success' => false, 'error' => 'La reserva ya está cancelada']);
        exit;
    }

    // Marcar como cancelada (no se borra, queda en el historial)
    $stmt = $pdo->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = :id AND usuario_id = :usuario_id");
    $stmt->execute([':id' => $reserva_id, ':usuario_id' => $_SESSION['usuario_id']]);

    echo json_encode(['success' => true, 'message' => 'Reserva cancelada correctamente']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al cancelar la reserva']);
}
