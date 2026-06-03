<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.html');
    exit;
}

$name = trim($_POST['usuario'] ?? '');
$email = trim($_POST['mail'] ?? '');
$prefijo = trim($_POST['prefijo'] ?? '+34');
$telefono = trim($_POST['telefono'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!$name || !$email || !$password || !$confirmPassword || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../register.html?error=invalid&email=' . urlencode($email));
    exit;
}

if ($password !== $confirmPassword) {
    header('Location: ../register.html?error=password_mismatch&email=' . urlencode($email));
    exit;
}

require '../comun/db.php';

try {
    $pdo = getDbConnection();
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS password TEXT");

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        header('Location: ../register.html?error=exists&email=' . urlencode($email));
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $fullPhone = trim($prefijo . ' ' . $telefono);

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, telefono, password) VALUES (:nombre, :email, :telefono, :password)");
    $stmt->execute([
        ':nombre' => $name,
        ':email' => $email,
        ':telefono' => $fullPhone,
        ':password' => $hashedPassword,
    ]);

    header('Location: ../login.html?email=' . urlencode($email) . '&registered=1');
    exit;
} catch (PDOException $e) {
    header('Location: ../register.html?error=server&email=' . urlencode($email));
    exit;
}
