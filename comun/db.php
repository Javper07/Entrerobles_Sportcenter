<?php
function getDbConnection() {
    static $pdo = null; // Variable estática para almacenar la conexión PDO

    if ($pdo !== null) {
        return $pdo; // Retorna la conexión existente si ya se ha creado
    }

    $host     = 'localhost';
    $db       = 'polideportivoEntrerobles';
    $user     = 'postgres';
    $password = '0000';
    $port     = '5432';

    $dsn = "pgsql:host=$host;port=$port;dbname=$db"; // Data Source Name
    $pdo = new PDO($dsn, $user, $password); //Crear nueva conexión PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;

    
    // que es pdo: 
    // pdo (PHP Data Objects) es una extensión de PHP que proporciona una interfaz uniforme para acceder a bases de datos.
    //como usar pdo:
    // $pdo = getDbConnection();     //hace que se cree la conexión y la almacena en la variable estática
    // que es stmt:
    // stmt (statement) es un objeto que representa una consulta preparada en PDO. Se utiliza para ejecutar consultas SQL de manera segura, evitando inyecciones SQL.
    //como usar stmt:
    // $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id"); //prepara la consulta SQL con un marcador de posición
    // $stmt->execute([':id' => $usuario_id]); //ejecuta la consulta pasando el valor del marcador de posición  
}