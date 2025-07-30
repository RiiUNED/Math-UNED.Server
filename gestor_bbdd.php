<?php
require_once 'database.php';

try {
    $pdo = getDB();

    // Crear tabla session
    $pdo->exec("CREATE TABLE IF NOT EXISTS session (
        id INT AUTO_INCREMENT PRIMARY KEY,
        status ENUM('en espera', 'jugando', 'finalizada') NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Crear tabla player
    $pdo->exec("CREATE TABLE IF NOT EXISTS player (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        progreso VARCHAR(13) DEFAULT '',
        puntaje INT DEFAULT 0,
        skips TINYINT DEFAULT 0,
        pregunta_actual TINYINT DEFAULT 0,
        FOREIGN KEY (session_id) REFERENCES session(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Crear tabla math (multiplicaciones del 2 al 9)
    $pdo->exec("CREATE TABLE IF NOT EXISTS math (
        id INT AUTO_INCREMENT PRIMARY KEY,
        op1 TINYINT NOT NULL,
        op2 TINYINT NOT NULL,
        res INT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Crear tabla board con 13 posiciones
    $pdo->exec("CREATE TABLE IF NOT EXISTS board (
        id INT AUTO_INCREMENT PRIMARY KEY,
        math_id_1 INT, math_id_2 INT, math_id_3 INT, math_id_4 INT, math_id_5 INT,
        math_id_6 INT, math_id_7 INT, math_id_8 INT, math_id_9 INT, math_id_10 INT,
        math_id_11 INT, math_id_12 INT, math_id_13 INT,
        ganador INT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Relación session ↔ board
    $pdo->exec("CREATE TABLE IF NOT EXISTS math_session (
        session_id INT PRIMARY KEY,
        board_id INT,
        FOREIGN KEY (session_id) REFERENCES session(id) ON DELETE CASCADE,
        FOREIGN KEY (board_id) REFERENCES board(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insertar ejercicios base (solo si no hay ninguno)
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

    // No se devuelve nada: el flujo sigue en index.php hacia gestor_sesiones.php

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error creando BBDD: ' . $e->getMessage()]);
    exit;
}
