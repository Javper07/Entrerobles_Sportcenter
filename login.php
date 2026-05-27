<?php
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

    $stmt = $pdo->prepare("SELECT id, password FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
        header('Location: login.html?error=invalid&email=' . urlencode($email));
        exit;
    }

    header('Location: reservations.html?email=' . urlencode($email));
    exit;
} catch (PDOException $e) {
    header('Location: login.html?error=server&email=' . urlencode($email));
    exit;
}
