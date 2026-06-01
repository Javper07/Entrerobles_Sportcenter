<?php
ini_set('session.save_handler', 'files');
ini_set('session.save_path', 'C:\\xampp\\tmp');
session_start();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';


if (!$email || !$password || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: login.html?error=invalid&email=' . urlencode($email));
    exit;
}

require 'db.php';

try {
    $pdo = getDbConnection();
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS password TEXT");

    $stmt = $pdo->prepare("SELECT id, nombre, email, password, es_admin FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: login.html?error=invalid&email=' . urlencode($email));
        exit;
    }

    if (empty($user['password']) || !password_verify($password, $user['password'])) {
        header('Location: login.html?error=invalid&email=' . urlencode($email));
        exit;
    }

    // ✓ Crear sesión
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_email'] = $user['email'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    $_SESSION['es_admin'] = $user['es_admin'] ?? false;

    // Redirigir según rol
    if (!empty($user['es_admin'])) {
        header('Location: admin.php');
    } else {
        header('Location: reservations.php');
    }
    exit;

} catch (PDOException $e) {
    header('Location: login.html?error=server&email=' . urlencode($email));
    exit;
}
