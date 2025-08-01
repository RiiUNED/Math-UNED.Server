<?php
header('Content-Type: application/json');
require_once 'database.php';

try {
    $pdo = getDB();

    // Obtener el número de sesiones con estado 'en espera'
    $stmt = $pdo->prepare("SELECT id FROM session WHERE status = 'en espera' LIMIT 1");
    $stmt->execute();
    $session_id = $stmt->fetchColumn();

    if ($session_id) {
        // Hay una sesión en espera, se une como segundo jugador
        $stmt = $pdo->prepare("INSERT INTO player (session_id, skips, puntaje, pregunta_actual) VALUES (?, 0, 0, 0)");
        $stmt->execute([$session_id]);
        $player_id = $pdo->lastInsertId();

        // Obtener ID del nuevo tablero
        $stmt = $pdo->prepare("SELECT board_id FROM math_session WHERE session_id = ?");
        $stmt->execute([$session_id]);
        $board_id = $stmt->fetchColumn();

        // Calcular número_jugador según orden en la sesión
        $stmt = $pdo->prepare("SELECT id FROM player WHERE session_id = ? ORDER BY id ASC");
        $stmt->execute([$session_id]);
        $players = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $numero_jugador = array_search($player_id, $players) + 1;

        echo json_encode([
            "session_id" => (int) $session_id,
            "player_id" => (int) $player_id,
            "status" => "en espera",
            "board_id" => (int) $board_id,
            "numero_jugador" => (int) $numero_jugador
        ]);

        // Actualizar el estado de la sesión a "jugando" justo después de responder
        $stmt = $pdo->prepare("UPDATE session SET status = 'jugando' WHERE id = ?");
        $stmt->execute([$session_id]);

    } else {
        // Crear nueva sesión
        $stmt = $pdo->prepare("INSERT INTO session (status) VALUES ('en espera')");
        $stmt->execute();
        $session_id = $pdo->lastInsertId();

        // Crear nuevo tablero con 13 ejercicios únicos
        $stmt = $pdo->query("SELECT id FROM math");
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        shuffle($ids);
        $ejercicios = array_slice($ids, 0, 13);

        $query = "INSERT INTO board (";
        for ($i = 1; $i <= 13; $i++) {
            $query .= "math_id_$i" . ($i < 13 ? ", " : ") VALUES (");
        }
        for ($i = 0; $i < 13; $i++) {
            $query .= "?" . ($i < 12 ? ", " : ")");
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($ejercicios);
        $board_id = $pdo->lastInsertId();

        // Asociar sesión con el nuevo tablero
        $stmt = $pdo->prepare("INSERT INTO math_session (session_id, board_id) VALUES (?, ?)");
        $stmt->execute([$session_id, $board_id]);

        // Crear el primer jugador
        $stmt = $pdo->prepare("INSERT INTO player (session_id, skips, puntaje, pregunta_actual) VALUES (?, 0, 0, 0)");
        $stmt->execute([$session_id]);
        $player_id = $pdo->lastInsertId();

        // Calcular número_jugador
        $stmt = $pdo->prepare("SELECT id FROM player WHERE session_id = ? ORDER BY id ASC");
        $stmt->execute([$session_id]);
        $players = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $numero_jugador = array_search($player_id, $players) + 1;

        echo json_encode([
            "session_id" => (int) $session_id,
            "player_id" => (int) $player_id,
            "status" => "en espera",
            "board_id" => (int) $board_id,
            "numero_jugador" => (int) $numero_jugador
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
