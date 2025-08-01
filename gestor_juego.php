<?php
header('Content-Type: application/json');
require_once 'database.php';

try {
    $pdo = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    $session_id = $input['session_id'] ?? null;
    $player_id = $input['player_id'] ?? null;
    $numero_jugador_input = $input['numero_jugador'] ?? null;

    if (!$session_id || !$player_id || !$numero_jugador_input) {
        http_response_code(400);
        echo json_encode(['error' => 'Solicitud inválida: falta session_id, player_id o numero_jugador']);
        exit;
    }

    // Validar que el numero_jugador coincide con el player_id
    $stmt = $pdo->prepare("SELECT id FROM player WHERE session_id = ? ORDER BY id ASC");
    $stmt->execute([$session_id]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $esperado_player_id = $jugadores[$numero_jugador_input - 1] ?? null;

    if ((int)$esperado_player_id !== (int)$player_id) {
        http_response_code(400);
        $jugadores_disponibles = [];
        foreach ($jugadores as $i => $pid) {
            $jugadores_disponibles[] = ["numero_jugador" => $i + 1, "player_id" => (int)$pid];
        }

        echo json_encode([
            'error' => 'Desincronización entre player_id y numero_jugador',
            'session_id' => (int)$session_id,
            'player_id' => (int)$player_id,
            'numero_jugador' => (int)$numero_jugador_input,
            'esperado_player_id' => (int)$esperado_player_id,
            'jugadores_disponibles' => $jugadores_disponibles
        ]);
        exit;
    }

    $numero_jugador = $numero_jugador_input;

    // Verificar estado de la sesión
    $stmt = $pdo->prepare("SELECT status FROM session WHERE id = ?");
    $stmt->execute([$session_id]);
    $estado_sesion = $stmt->fetchColumn();

    if ($estado_sesion === 'en espera') {
        // Obtener el board_id
        $stmt = $pdo->prepare("SELECT board_id FROM math_session WHERE session_id = ?");
        $stmt->execute([$session_id]);
        $board_id = $stmt->fetchColumn();

        echo json_encode([
            "session_id" => (int) $session_id,
            "player_id" => (int) $player_id,
            "status" => "en espera",
            "board_id" => (int) $board_id,
            "numero_jugador" => (int) $numero_jugador
        ]);
        exit;
    }

    // Cargar datos del jugador
    $stmt = $pdo->prepare("SELECT * FROM player WHERE id = ?");
    $stmt->execute([$player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        echo json_encode(['error' => 'Jugador no encontrado en la sesión']);
        exit;
    }

    $board_id = $pdo->query("SELECT board_id FROM math_session WHERE session_id = $session_id")->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM board WHERE id = ?");
    $stmt->execute([$board_id]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);

    $ex_num = (int) ($input['ex_num'] ?? 1);
    $res = $input['res'] ?? null;
    $skip_request = $input['skip'] ?? false;

    $pregunta_actual = (int) $player['pregunta_actual'];
    $puntaje = (int) $player['puntaje'];
    $skips = (int) $player['skips'];

    // Validación de número de ejercicio
    if ($ex_num !== $pregunta_actual + 1) {
        echo json_encode([
            "error" => "Número de ejercicio desincronizado",
            "esperado" => $pregunta_actual + 1,
            "recibido" => $ex_num,
            "session_id" => (int) $session_id,
            "board_id" => (int) $board_id,
            "player_id" => (int) $player_id,
            "numero_jugador" => (int) $numero_jugador
        ]);
        exit;
    }

    $avance = false;

    if ($skip_request === true) {
        if ($skips < 3) {
            $skips += 1;
            $avance = true;
        }
    } elseif ($res !== null) {
        $math_id = $board["math_id_$ex_num"];
        $stmt = $pdo->prepare("SELECT op1, op2 FROM math WHERE id = ?");
        $stmt->execute([$math_id]);
        $ejercicio = $stmt->fetch(PDO::FETCH_ASSOC);
        $respuesta_correcta = $ejercicio['op1'] * $ejercicio['op2'];

        if ((int) $res === (int) $respuesta_correcta) {
            $puntaje += 1;
            $avance = true;
        }
    }

    if ($avance) {
        $pregunta_actual += 1;
        $stmt = $pdo->prepare("UPDATE player SET skips = ?, puntaje = ?, pregunta_actual = ? WHERE id = ?");
        $stmt->execute([$skips, $puntaje, $pregunta_actual, $player_id]);
    }

    // Verificar si alguien ganó
    if ($puntaje >= 10) {
        $stmt = $pdo->prepare("UPDATE session SET status = 'finalizada' WHERE id = ?");
        $stmt->execute([$session_id]);
    }

    $rival_index = $numero_jugador === 1 ? 2 : 1;
    $rival_id = $jugadores[$rival_index - 1] ?? null;

    $rival_puntaje = 0;
    if ($rival_id) {
        $stmt = $pdo->prepare("SELECT puntaje FROM player WHERE id = ?");
        $stmt->execute([$rival_id]);
        $rival_puntaje = (int) $stmt->fetchColumn();
    }

    if ($puntaje >= 10) {
        echo json_encode([
            "resultado" => "ganaste",
            "puntaje" => $puntaje,
            "rival" => $rival_puntaje,
            "session_id" => (int) $session_id,
            "board_id" => (int) $board_id,
            "player_id" => (int) $player_id,
            "numero_jugador" => (int) $numero_jugador
        ]);
        exit;
    }

    if ($rival_puntaje >= 10) {
        echo json_encode([
            "resultado" => "perdiste",
            "puntaje" => $puntaje,
            "rival" => $rival_puntaje,
            "session_id" => (int) $session_id,
            "board_id" => (int) $board_id,
            "player_id" => (int) $player_id,
            "numero_jugador" => (int) $numero_jugador
        ]);
        exit;
    }

    // Enviar el siguiente ejercicio
    $ex_num = $pregunta_actual + 1;
    $math_id = $board["math_id_$ex_num"];
    $stmt = $pdo->prepare("SELECT op1, op2 FROM math WHERE id = ?");
    $stmt->execute([$math_id]);
    $ejercicio = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "op1" => (int) $ejercicio['op1'],
        "op2" => (int) $ejercicio['op2'],
        "ex_num" => (int) $ex_num,
        "puntaje" => (int) $puntaje,
        "skips" => (int) $skips,
        "rival" => (int) $rival_puntaje,
        "session_id" => (int) $session_id,
        "board_id" => (int) $board_id,
        "player_id" => (int) $player_id,
        "numero_jugador" => (int) $numero_jugador
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
