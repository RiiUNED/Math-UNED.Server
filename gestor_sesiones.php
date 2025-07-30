<?php
require_once 'database.php';

try {
    $pdo = getDB();

    // Buscar sesión en espera
    $stmt = $pdo->prepare("SELECT id FROM session WHERE status = 'en espera' LIMIT 1");
    $stmt->execute();
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        // Jugador 2 entra a sesión existente
        $session_id = $session['id'];

        // Insertar jugador 2
        $pdo->prepare("INSERT INTO player (session_id) VALUES (?)")->execute([$session_id]);
        $player_id = $pdo->lastInsertId();
        $numero_jugador = 2;

        // Cambiar estado a 'jugando'
        $pdo->prepare("UPDATE session SET status = 'jugando' WHERE id = ?")->execute([$session_id]);

        // Crear nuevo board con 13 ejercicios únicos
        $max_id = $pdo->query("SELECT MAX(id) FROM math")->fetchColumn();

        $ids = [];
        while (count($ids) < 13) {
            $rand = random_int(1, $max_id);
            if (!in_array($rand, $ids)) {
                $ids[] = $rand;
            }
        }

        $columns = implode(',', array_map(fn($i) => "math_id_$i", range(1, 13)));
        $placeholders = implode(',', array_fill(0, 13, '?'));

        $stmt = $pdo->prepare("INSERT INTO board ($columns) VALUES ($placeholders)");
        $stmt->execute($ids);
        $board_id = $pdo->lastInsertId();

        // Relacionar sesión con tablero
        $pdo->prepare("INSERT INTO math_session (session_id, board_id) VALUES (?, ?)")
            ->execute([$session_id, $board_id]);

        echo json_encode([
            'session_id' => $session_id,
            'player_id' => $player_id,
            'numero_jugador' => $numero_jugador,
            'status' => 'jugando'
        ]);

    } else {
        // Crear nueva sesión y jugador 1
        $pdo->prepare("INSERT INTO session (status) VALUES ('en espera')")->execute();
        $session_id = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO player (session_id) VALUES (?)")->execute([$session_id]);
        $player_id = $pdo->lastInsertId();
        $numero_jugador = 1;

        echo json_encode([
            'session_id' => $session_id,
            'player_id' => $player_id,
            'numero_jugador' => $numero_jugador,
            'status' => 'en espera'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en gestor_sesiones: ' . $e->getMessage()
    ]);
}
