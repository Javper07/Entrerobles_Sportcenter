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
$fecha = $data['fecha'] ?? null;
$hora_inicio = $data['hora_inicio'] ?? null;
$hora_fin = $data['hora_fin'] ?? null;
$participantes = $data['participantes'] ?? null;
$observaciones = $data['observaciones'] ?? '';

if (!$reserva_id || !$fecha || !$hora_inicio || !$hora_fin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

require '../comun/db.php';

try {
    $pdo = getDbConnection();
    
    // Obtener la reserva original
    $stmt = $pdo->prepare("
        SELECT instalacion_id FROM reservas 
        WHERE id = :id AND usuario_id = :usuario_id
    ");
    $stmt->execute([
        ':id' => $reserva_id,
        ':usuario_id' => $_SESSION['usuario_id']
    ]);
    
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reserva) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Reserva no encontrada']);
        exit;
    }
    
    // Validación
    if ($hora_fin <= $hora_inicio) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'La hora de fin debe ser posterior a la hora de inicio']);
        exit;
    }
    
    // Comprobar que no hay conflicto de horarios
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
        ':inst' => $reserva['instalacion_id'],
        ':fecha' => $fecha,
        ':hora_inicio' => $hora_inicio,
        ':hora_fin' => $hora_fin,
        ':reserva_id' => $reserva_id
    ]);
    
    if ($stmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Ya existe una reserva en ese horario']);
        exit;
    }
    
    // Actualizar la reserva
    $stmt = $pdo->prepare("
        UPDATE reservas
        SET fecha = :fecha,
            hora_inicio = :hora_inicio,
            hora_fin = :hora_fin,
            participantes = :participantes,
            observaciones = :observaciones
        WHERE id = :id AND usuario_id = :usuario_id
    ");
    
    $stmt->execute([
        ':fecha' => $fecha,
        ':hora_inicio' => $hora_inicio,
        ':hora_fin' => $hora_fin,
        ':participantes' => $participantes,
        ':observaciones' => $observaciones,
        ':id' => $reserva_id,
        ':usuario_id' => $_SESSION['usuario_id']
    ]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Reserva actualizada correctamente']);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error al actualizar la reserva']);
    exit;
}
