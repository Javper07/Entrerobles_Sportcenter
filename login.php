<?php
// Configurar sesión antes de cualquier output
ini_set('session.cache_limiter', 'public');
ini_set('session.cache_expire', 180);
session_start();

error_log('[login.php] Sesión iniciada. Session ID: ' . session_id());

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

error_log('[login.php] Intento de login con email: ' . $email);

if (!$email || !$password || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log('[login.php] Validación fallida');
    header('Location: login.html?error=invalid&email=' . urlencode($email));
    exit;
}

require 'db.php';

try {
    $pdo = getDbConnection();
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS password TEXT");

    $stmt = $pdo->prepare("SELECT id, nombre, email, password FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log('[login.php] Usuario no encontrado: ' . $email);
        header('Location: login.html?error=invalid&email=' . urlencode($email));
        exit;
    }

    if (empty($user['password']) || !password_verify($password, $user['password'])) {
        error_log('[login.php] Contraseña incorrecta para: ' . $email);
        header('Location: login.html?error=invalid&email=' . urlencode($email));
        exit;
    }

    // ✓ Crear sesión
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_email'] = $user['email'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    
    error_log('[login.php] ✓ Sesión creada para usuario: ' . $user['nombre'] . ' (ID: ' . $user['id'] . ')');
    error_log('[login.php] Variables de sesión: ' . json_encode($_SESSION));
    
    // Guardar sesión explícitamente
    session_write_close();
    
    error_log('[login.php] ✓ Sesión guardada. Redirigiendo a reservations.php');
    
    header('Location: reservations.php');
    exit;

} catch (PDOException $e) {
    error_log('[login.php] Error en base de datos: ' . $e->getMessage());
    header('Location: login.html?error=server&email=' . urlencode($email));
    exit;
}
