<?php
session_start();
header('Content-Type: application/json');

$instalacion = $_GET['instalacion'] ?? '';
$fecha       = $_GET['fecha']       ?? '';

if (!$instalacion || !$fecha) {
    echo json_encode(['error' => 'Parámetros requeridos']);
    exit;
}

require '../comun/db.php';

try {
    $pdo = getDbConnection();

    // Obtener ID de instalación
    $stmt = $pdo->prepare("SELECT id FROM instalaciones WHERE valor = :valor");
    $stmt->execute([':valor' => $instalacion]);
    $inst = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inst) {
        echo json_encode(['ocupadas' => []]);
        exit;
    }

    // Obtener reservas activas para esa instalación y fecha
    $stmt = $pdo->prepare("
        SELECT hora_inicio, hora_fin
        FROM reservas
        WHERE instalacion_id = :inst
          AND fecha = :fecha
          AND estado != 'cancelada'
    ");
    $stmt->execute([':inst' => $inst['id'], ':fecha' => $fecha]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ocupadas' => $reservas]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de base de datos', 'ocupadas' => []]);
}
