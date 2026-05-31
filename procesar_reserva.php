<?php
session_start();

$host     = 'localhost';
$db       = 'polideportivoEntrerobles';
$user     = 'postgres';
$password = '0000';   
$port     = '5432';

// =====================================================
// CONEXIÓN A POSTGRESQL
// =====================================================
try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$db",
        $user,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Si falla la conexión redirige con error
    header('Location: reservations.php?reserva=error');
    exit;
}

// =====================================================
// SOLO ACEPTAR PETICIONES POST
// =====================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reservations.php?reserva=error');
    exit;
}

// =====================================================
// RECOGER Y LIMPIAR LOS DATOS DEL FORMULARIO
// =====================================================
$instalacion   = $_POST['instalacion']   ?? '';
$fecha         = $_POST['fecha']         ?? '';
$hora_inicio   = $_POST['hora_inicio']   ?? '';
$hora_fin      = $_POST['hora_fin']      ?? '';
$nombre        = trim($_POST['nombre']        ?? '');
$telefono      = trim($_POST['telefono']      ?? '');
$email         = trim($_POST['email']         ?? '');
$participantes = (int)($_POST['participantes'] ?? 1);
$observaciones = trim($_POST['observaciones'] ?? '');

// =====================================================
// VALIDACIÓN BÁSICA
// =====================================================
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

// =====================================================
// OBTENER EL ID DE LA INSTALACIÓN
// =====================================================
$stmt = $pdo->prepare("SELECT id FROM instalaciones WHERE valor = :valor");
$stmt->execute([':valor' => $instalacion]);
$inst = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inst) {
    header('Location: reservations.php?reserva=error');
    exit;
}
$instalacion_id = $inst['id'];

// =====================================================
// COMPROBAR QUE NO HAY RESERVA EN ESE HORARIO
// =====================================================
$stmt = $pdo->prepare("
    SELECT id FROM reservas
    WHERE instalacion_id = :inst
      AND fecha          = :fecha
      AND estado        != 'cancelada'
      AND hora_inicio    < :hora_fin
      AND hora_fin       > :hora_inicio
");
$stmt->execute([
    ':inst'        => $instalacion_id,
    ':fecha'       => $fecha,
    ':hora_inicio' => $hora_inicio,
    ':hora_fin'    => $hora_fin,
]);

if ($stmt->fetch()) {
    // Ya existe una reserva que se solapa
    header('Location: reservations.php?reserva=ocupado');
    exit;
}

// =====================================================
// BUSCAR O CREAR EL USUARIO
// =====================================================
// Si hay sesión activa, usar el usuario_id de la sesión
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
} else {
    // Si no hay sesión, buscar o crear el usuario
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

// =====================================================
// GUARDAR LA RESERVA
// =====================================================
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

// =====================================================
// TODO OK — Redirigir con mensaje de éxito
// =====================================================
header('Location: reservations.php?reserva=ok');
exit;
?>