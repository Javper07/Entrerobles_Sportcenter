<?php
session_start();
header('Content-Type: application/json');

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

require 'db.php';

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

    $data       = json_decode(file_get_contents('php://input'), true);
    $usuario_id = $data['usuario_id'] ?? null;

    if (!$usuario_id) {
        echo json_encode(['success' => false, 'error' => 'ID de usuario no proporcionado']);
        exit;
    }

    // Evitar que el admin se elimine a sí mismo
    if ((int)$usuario_id === (int)$_SESSION['usuario_id']) {
        echo json_encode(['success' => false, 'error' => 'No puedes eliminar tu propia cuenta']);
        exit;
    }

    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $usuario_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }

    // Eliminar reservas del usuario primero (integridad referencial)
    $stmt = $pdo->prepare("DELETE FROM reservas WHERE usuario_id = :id");
    $stmt->execute([':id' => $usuario_id]);

    // Eliminar el usuario
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $usuario_id]);

    echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al eliminar el usuario']);
}
