<?php
header('Content-Type: application/json');
require_once 'database.php';

try {
    $pdo = getDB();

    $input = json_decode(file_get_contents('php://input'), true);

    // Aceptar ambos formatos de nombres de campos
    $session_id = $input['session_id'] ?? $input['session'] ?? null;
    $player_id = $input['player_id'] ?? $input['jugador'] ?? null;

    if (!$session_id || !$player_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Solicitud inválida: falta session_id o player_id']);
        exit;
    }

    $res = $input['res'] ?? null;
    $skip_request = $input['skip'] ?? false;

    // Obtener jugador
    $stmt = $pdo->prepare("SELECT * FROM player WHERE id = ? AND session_id = ?");
    $stmt->execute([$player_id, $session_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        http_response_code(404);
        echo json_encode(['error' => 'Jugador no encontrado en la sesión']);
        exit;
    }

    // Verificar estado de la sesión
    $stmt = $pdo->prepare("SELECT status FROM session WHERE id = ?");
    $stmt->execute([$session_id]);
    $session_status = $stmt->fetchColumn();

    if ($session_status !== 'jugando') {
        echo json_encode(['status' => $session_status, 'message' => 'La sesión no está activa.']);
        exit;
    }

    // Obtener board_id de la sesión
    $stmt = $pdo->prepare("SELECT board_id FROM math_session WHERE session_id = ?");
    $stmt->execute([$session_id]);
    $board_id = $stmt->fetchColumn();

    if (!$board_id) {
        http_response_code(500);
        echo json_encode(['error' => 'No hay tablero asignado a esta sesión']);
        exit;
    }

    // Obtener contenido del tablero
    $stmt = $pdo->prepare("
        SELECT 
            math_id_1, math_id_2, math_id_3, math_id_4, math_id_5,
            math_id_6, math_id_7, math_id_8, math_id_9, math_id_10,
            math_id_11, math_id_12, math_id_13
        FROM board WHERE id = ?");
    $stmt->execute([$board_id]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);

    $index = (int) $player['pregunta_actual'];
    $progreso = $player['progreso'];
    $puntaje = (int) $player['puntaje'];
    $skips = (int) $player['skips'];
    $avance = false;

    // Procesar acción
    if ($skip_request === true) {
        if ($skips >= 3) {
            echo json_encode([
                'error' => 'Límite de skips alcanzado',
                'progreso' => $progreso,
                'skips' => $skips,
                'puntaje' => $puntaje
            ]);
            exit;
        }

        $progreso .= '-';
        $skips += 1;
        $avance = true;

    } elseif ($res !== null) {
        $current_math_id = $board["math_id_" . ($index + 1)];
        $stmt = $pdo->prepare("SELECT res FROM math WHERE id = ?");
        $stmt->execute([$current_math_id]);
        $correct = $stmt->fetchColumn();

        if ((int)$res === (int)$correct) {
            $progreso .= 'X';
            $puntaje += 1;
        } else {
            $progreso .= 'O';
        }
        $avance = true;
    }

    // Si el jugador avanza
    if ($avance) {
        $stmt = $pdo->prepare("
            UPDATE player 
            SET progreso = ?, skips = ?, puntaje = ?, pregunta_actual = pregunta_actual + 1 
            WHERE id = ?");
        $stmt->execute([$progreso, $skips, $puntaje, $player_id]);

        $index += 1;
    }

    // Si terminó todos los ejercicios
    if ($index >= 13) {
        echo json_encode(['message' => 'Juego finalizado para este jugador.']);
        exit;
    }

    // Obtener nuevo ejercicio tras avance
    $math_id = $board["math_id_" . ($index + 1)];
    $stmt = $pdo->prepare("SELECT op1, op2, res FROM math WHERE id = ?");
    $stmt->execute([$math_id]);
    $ejercicio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ejercicio) {
        http_response_code(500);
        echo json_encode(['error' => 'Ejercicio no encontrado']);
        exit;
    }

    // Verificar victoria
    if ($puntaje >= 10) {
        $pdo->prepare("UPDATE session SET status = 'finalizada' WHERE id = ?")->execute([$session_id]);
        $pdo->prepare("UPDATE board SET ganador = ? WHERE id = ?")->execute([$player_id, $board_id]);

        echo json_encode([
            'ganador' => true,
            'mensaje' => '¡Has ganado!',
            'puntaje' => $puntaje
        ]);
        exit;
    }

    // Obtener puntos del rival
    $stmt = $pdo->prepare("SELECT puntaje FROM player WHERE session_id = ? AND id != ?");
    $stmt->execute([$session_id, $player_id]);
    $puntaje_rival = $stmt->fetchColumn() ?? 0;

    // Respuesta final
    echo json_encode([
        'op1' => $ejercicio['op1'],
        'op2' => $ejercicio['op2'],
        'ex_num' => $index + 1,
        'progreso' => strlen($progreso),
        'puntaje' => $puntaje,
        'skips' => $skips,
        'rival' => $puntaje_rival
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
