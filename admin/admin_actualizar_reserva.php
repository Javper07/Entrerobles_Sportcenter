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
    $reserva_id    = $data['reserva_id']    ?? null;
    $fecha         = $data['fecha']         ?? null;
    $hora_inicio   = $data['hora_inicio']   ?? null;
    $hora_fin      = $data['hora_fin']      ?? null;
    $participantes = $data['participantes'] ?? null;
    $observaciones = $data['observaciones'] ?? '';
    $estado        = $data['estado']        ?? null;

    if (!$reserva_id || !$fecha || !$hora_inicio || !$hora_fin) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }

    // Validar estado
    $estados_validos = ['activa', 'cancelada', 'completada'];
    if ($estado && !in_array($estado, $estados_validos)) {
        echo json_encode(['success' => false, 'error' => 'Estado no válido']);
        exit;
    }

    // Obtener la reserva original
    $stmt = $pdo->prepare("SELECT instalacion_id FROM reservas WHERE id = :id");
    $stmt->execute([':id' => $reserva_id]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reserva) {
        echo json_encode(['success' => false, 'error' => 'Reserva no encontrada']);
        exit;
    }

    // Validación de horas
    if ($hora_fin <= $hora_inicio) {
        echo json_encode(['success' => false, 'error' => 'La hora de fin debe ser posterior a la de inicio']);
        exit;
    }

    // Comprobar conflicto de horarios (solo si el estado no es cancelada)
    if ($estado !== 'cancelada') {
        $stmt = $pdo->prepare("
            SELECT id FROM reservas
            WHERE instalacion_id = :inst
              AND fecha = :fecha
              AND id != :reserva_id
              AND estado != 'cancelada'
              AND hora_inicio < :hora_fin
              AND hora_fin > :hora_inicio
        ");
        $stmt->execute([
            ':inst'       => $reserva['instalacion_id'],
            ':fecha'      => $fecha,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin'   => $hora_fin,
            ':reserva_id' => $reserva_id
        ]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Ya existe una reserva en ese horario']);
            exit;
        }
    }

    // Actualizar la reserva (el admin puede modificar cualquiera)
    $stmt = $pdo->prepare("
        UPDATE reservas
        SET fecha         = :fecha,
            hora_inicio   = :hora_inicio,
            hora_fin      = :hora_fin,
            participantes = :participantes,
            observaciones = :observaciones
            " . ($estado ? ", estado = :estado" : "") . "
        WHERE id = :id
    ");

    $params = [
        ':fecha'         => $fecha,
        ':hora_inicio'   => $hora_inicio,
        ':hora_fin'      => $hora_fin,
        ':participantes' => $participantes,
        ':observaciones' => $observaciones,
        ':id'            => $reserva_id
    ];
    if ($estado) $params[':estado'] = $estado;

    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'Reserva actualizada correctamente']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al actualizar la reserva']);
}
