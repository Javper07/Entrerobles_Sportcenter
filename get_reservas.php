<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

require 'db.php';

try {
    $pdo = getDbConnection();
    
    // Obtener las reservas del usuario
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.fecha,
            r.hora_inicio,
            r.hora_fin,
            r.participantes,
            r.observaciones,
            r.estado,
            r.fecha_creacion,
            i.nombre as instalacion_nombre
        FROM reservas r
        JOIN instalaciones i ON r.instalacion_id = i.id
        WHERE r.usuario_id = :usuario_id
        ORDER BY r.fecha DESC, r.hora_inicio DESC
    ");
    
    $stmt->execute([':usuario_id' => $_SESSION['usuario_id']]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($reservas);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error al obtener las reservas']);
    exit;
}
