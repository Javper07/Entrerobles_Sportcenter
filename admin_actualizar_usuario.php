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
    $nombre     = trim($data['nombre']    ?? '');
    $email      = trim($data['email']     ?? '');
    $telefono   = trim($data['telefono']  ?? '');
    $es_admin   = isset($data['es_admin']) ? (bool)$data['es_admin'] : false;

    if (!$usuario_id || !$nombre || !$email) {
        echo json_encode(['success' => false, 'error' => 'Nombre y email son obligatorios']);
        exit;
    }

    // Evitar que el admin se quite su propio rol
    if ((int)$usuario_id === (int)$_SESSION['usuario_id'] && !$es_admin) {
        echo json_encode(['success' => false, 'error' => 'No puedes quitarte el rol de administrador']);
        exit;
    }

    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $usuario_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }

    // Verificar email único (excepto el propio usuario)
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
    $stmt->execute([':email' => $email, ':id' => $usuario_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ya existe otro usuario con ese email']);
        exit;
    }

    // Actualizar
    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET nombre    = :nombre,
            email     = :email,
            telefono  = :telefono,
            es_admin  = :es_admin
        WHERE id = :id
    ");
    $stmt->execute([
        ':nombre'   => $nombre,
        ':email'    => $email,
        ':telefono' => $telefono ?: null,
        ':es_admin' => $es_admin,
        ':id'       => $usuario_id,
    ]);

    echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el usuario']);
}
