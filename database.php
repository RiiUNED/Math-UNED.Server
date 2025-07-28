<?php
//header('Content-Type: text/plain');
echo "🛠️ Inicio de database.php\n"; flush();

function getDB() {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $db = 'math_game';

    // Paso 1: conectar sin especificar base de datos
    $dsn = "mysql:host=$host;charset=$charset";

    try {
        echo "Intentado conectar al servidor MySQL\n"; flush();
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "🗂️ Conectado al servidor MySQL\n"; flush();

        // Crear la base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` 
                    CHARACTER SET $charset 
                    COLLATE utf8mb4_general_ci");
        echo "📦 Base de datos '$db' verificada o creada\n"; flush();

        // Conectarse a la base de datos recién creada
        $dsnDb = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsnDb, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "🔗 Conectado a la base de datos '$db'\n"; flush();

        return $pdo;
    } catch (PDOException $e) {
        echo "❌ Error en database.php: " . $e->getMessage(); flush();
        exit;
    }
}
