<?php
function getDB() {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $db = 'math_game';

    $dsn = "mysql:host=$host;charset=$charset";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`
                    CHARACTER SET $charset
                    COLLATE utf8mb4_general_ci");

        $dsnDb = "mysql:host=$host;dbname=$db;charset=$charset";
        $pdo = new PDO($dsnDb, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Tabla de sesiones
        $pdo->exec("CREATE TABLE IF NOT EXISTS session (
            id INT AUTO_INCREMENT PRIMARY KEY,
            status ENUM('en espera', 'jugando', 'finalizada') NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Jugadores por sesión (sin progreso)
        $pdo->exec("CREATE TABLE IF NOT EXISTS player (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            puntaje INT DEFAULT 0,
            skips TINYINT DEFAULT 0,
            pregunta_actual TINYINT DEFAULT 0,
            FOREIGN KEY (session_id) REFERENCES session(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Tabla de multiplicaciones base (2×1 a 9×10)
        $pdo->exec("CREATE TABLE IF NOT EXISTS math (
            id INT AUTO_INCREMENT PRIMARY KEY,
            op1 TINYINT NOT NULL,
            op2 TINYINT NOT NULL,
            res INT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Tablero con 13 ejercicios de math
        $pdo->exec("CREATE TABLE IF NOT EXISTS board (
            id INT AUTO_INCREMENT PRIMARY KEY,
            math_id_1 INT, math_id_2 INT, math_id_3 INT, math_id_4 INT, math_id_5 INT,
            math_id_6 INT, math_id_7 INT, math_id_8 INT, math_id_9 INT, math_id_10 INT,
            math_id_11 INT, math_id_12 INT, math_id_13 INT,
            ganador INT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Relación sesión–tablero
        $pdo->exec("CREATE TABLE IF NOT EXISTS math_session (
            session_id INT PRIMARY KEY,
            board_id INT,
            FOREIGN KEY (session_id) REFERENCES session(id) ON DELETE CASCADE,
            FOREIGN KEY (board_id) REFERENCES board(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Insertar las multiplicaciones si la tabla está vacía
        $stmt = $pdo->query("SELECT COUNT(*) FROM math");
        if ($stmt->fetchColumn() == 0) {
            for ($i = 2; $i <= 9; $i++) {
                for ($j = 1; $j <= 10; $j++) {
                    $res = $i * $j;
                    $pdo->prepare("INSERT INTO math (op1, op2, res) VALUES (?, ?, ?)")
                        ->execute([$i, $j, $res]);
                }
            }
        }

        return $pdo;
    } catch (PDOException $e) {
        echo "❌ Error de conexión: " . $e->getMessage();
        exit;
    }
}
