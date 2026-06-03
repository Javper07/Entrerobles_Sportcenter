<?php
require_once '../comun/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../reservas/reservations.php');
    exit;
}

// Recoger y limpiar datos del formulario
$instalacion   = $_POST['instalacion']   ?? '';
$fecha         = $_POST['fecha']         ?? '';
$hora_inicio   = $_POST['hora_inicio']   ?? '';
$hora_fin      = $_POST['hora_fin']      ?? '';
$nombre        = trim($_POST['nombre']        ?? '');
$telefono      = trim($_POST['telefono']      ?? '');
$email         = trim($_POST['email']         ?? '');
$participantes = (int)($_POST['participantes'] ?? 1);
$observaciones = trim($_POST['observaciones'] ?? '');

// Validación básica
if (!$instalacion || !$fecha || !$hora_inicio || !$hora_fin || !$nombre || !$email) {
    header('Location: reservations.php?reserva=error');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: reservations.php?reserva=error');
    exit;
}

if ($hora_fin <= $hora_inicio) {
    header('Location: reservations.php?reserva=error');
    exit;
}

try {
    $pdo = getDbConnection();

    // Obtener ID de la instalación
    $stmt = $pdo->prepare("SELECT id FROM instalaciones WHERE valor = :valor");
    $stmt->execute([':valor' => $instalacion]);
    $inst = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inst) {
        header('Location: ../reservas/reservations.php?reserva=error');
        exit;
    }
    $instalacion_id = $inst['id'];

    // Comprobar que no hay conflicto de horarios
    $stmt = $pdo->prepare("
        SELECT id FROM reservas
        WHERE instalacion_id = :inst
          AND fecha           = :fecha
          AND estado         != 'cancelada'
          AND hora_inicio     < :hora_fin
          AND hora_fin        > :hora_inicio
    ");
    $stmt->execute([
        ':inst'        => $instalacion_id,
        ':fecha'       => $fecha,
        ':hora_inicio' => $hora_inicio,
        ':hora_fin'    => $hora_fin,
    ]);

    if ($stmt->fetch()) {
        header('Location: ../reservas/reservations.php?reserva=ocupado');
        exit;
    }

    // Determinar usuario_id
    if (isset($_SESSION['usuario_id'])) {
        $usuario_id = $_SESSION['usuario_id'];
    } else {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $usuario_id = $usuario['id'];
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, telefono)
                VALUES (:nombre, :email, :telefono)
                RETURNING id
            ");
            $stmt->execute([
                ':nombre'   => $nombre,
                ':email'    => $email,
                ':telefono' => $telefono,
            ]);
            $usuario_id = $stmt->fetchColumn();
        }
    }

    // Guardar la reserva
    $stmt = $pdo->prepare("
        INSERT INTO reservas
            (instalacion_id, usuario_id, fecha, hora_inicio, hora_fin, participantes, observaciones)
        VALUES
            (:instalacion_id, :usuario_id, :fecha, :hora_inicio, :hora_fin, :participantes, :observaciones)
    ");
    $stmt->execute([
        ':instalacion_id' => $instalacion_id,
        ':usuario_id'     => $usuario_id,
        ':fecha'          => $fecha,
        ':hora_inicio'    => $hora_inicio,
        ':hora_fin'       => $hora_fin,
        ':participantes'  => $participantes,
        ':observaciones'  => $observaciones,
    ]);

    header('Location: ../reservas/reservations.php?reserva=ok');
    exit;

} catch (PDOException $e) {
    header('Location: reservations.php?reserva=error');
    exit;
}
