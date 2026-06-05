<?php
session_start();
header('Content-Type: application/json');

// Solo admins autenticados
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

require '../comun/db.php';

try {
    $pdo = getDbConnection();

    // Verificar que es admin
    $stmt = $pdo->prepare("SELECT es_admin FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u || !$u['es_admin']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $reserva_id = $data['reserva_id'] ?? null;

    if (!$reserva_id) {
        echo json_encode(['success' => false, 'error' => 'ID de reserva no proporcionado']);
        exit;
    }

    // Verificar que la reserva existe
    $stmt = $pdo->prepare("SELECT id FROM reservas WHERE id = :id");
    $stmt->execute([':id' => $reserva_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Reserva no encontrada']);
        exit;
    }

    // Eliminar la reserva (el admin puede eliminar cualquiera)
    $stmt = $pdo->prepare("DELETE FROM reservas WHERE id = :id");
    $stmt->execute([':id' => $reserva_id]);

    echo json_encode(['success' => true, 'message' => 'Reserva eliminada correctamente']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al eliminar la reserva']);
}
