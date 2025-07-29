<?php
function getDB() {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $db = 'math_game';

    // Conexión inicial sin especificar base de datos
    $dsn = "mysql:host=$host;charset=$charset";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Crear base de datos si no existe
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`
                    CHARACTER SET $charset
                    COLLATE utf8mb4_general_ci");

        // Conexión con base de datos
        $dsnDb = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsnDb, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Crear tabla session
        $pdo->exec("CREATE TABLE IF NOT EXISTS session (
            id INT AUTO_INCREMENT PRIMARY KEY,
            status ENUM('en espera', 'jugando', 'finalizada') NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Crear tabla players
        $pdo->exec("CREATE TABLE IF NOT EXISTS players (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            numero_jugador TINYINT CHECK (numero_jugador IN (1,2)),
            FOREIGN KEY (session_id) REFERENCES session(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Crear tabla math
        $pdo->exec("CREATE TABLE IF NOT EXISTS math (
            session_id INT NOT NULL,
            board_id INT NOT NULL,
            PRIMARY KEY (session_id, board_id),
            FOREIGN KEY (session_id) REFERENCES session(id)
                ON DELETE CASCADE
            -- FOREIGN KEY (board_id) REFERENCES board(id) -- activar cuando exista la tabla board
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        return $pdo;
    } catch (PDOException $e) {
        echo "❌ Error de conexión: " . $e->getMessage();
        exit;
    }
}
