<?php
// DB connection helper.
// Llama a getDbConnection() desde cualquier archivo PHP para acceder a la base de datos.
function getDbConnection() {
    // Una vez creada, se guarda en esta variable estática para reutilizar la misma conexión.
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo; // Retorna la conexión existente.
    }

    $host     = 'localhost';
    $db       = 'polideportivoEntrerobles';
    $user     = 'postgres';
    $password = '0000';
    $port     = '5432';

    // DSN para PostgreSQL.
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
}

// Uso típico en otros archivos PHP:
// $pdo = getDbConnection();
// $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
// $stmt->execute([':id' => $usuario_id]);
