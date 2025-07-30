<?php
header('Content-Type: application/json');
require_once 'database.php';

try {
    $pdo = getDB();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['session_id']) || !is_numeric($input['session_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta session_id']);
        exit;
    }

    $session_id = (int) $input['session_id'];

    // Obtener el board_id asociado a la sesión
    $stmt = $pdo->prepare("SELECT board_id FROM math_session WHERE session_id = ?");
    $stmt->execute([$session_id]);
    $board_id = $stmt->fetchColumn();

    if (!$board_id) {
        http_response_code(404);
        echo json_encode(['error' => 'No hay tablero asociado a la sesión']);
        exit;
    }

    // Obtener los IDs de ejercicios del tablero
    $stmt = $pdo->prepare("
        SELECT 
            math_id_1, math_id_2, math_id_3, math_id_4, math_id_5,
            math_id_6, math_id_7, math_id_8, math_id_9, math_id_10,
            math_id_11, math_id_12, math_id_13
        FROM board WHERE id = ?");
    $stmt->execute([$board_id]);
    $math_ids = $stmt->fetch(PDO::FETCH_NUM);

    if (!$math_ids) {
        http_response_code(404);
        echo json_encode(['error' => 'No se encontraron ejercicios para el tablero']);
        exit;
    }

    // Cargar los ejercicios desde math
    $placeholders = implode(',', array_fill(0, count($math_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, op1, op2, res FROM math WHERE id IN ($placeholders)");
    $stmt->execute($math_ids);
    $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapear por ID para ordenarlos
    $by_id = [];
    foreach ($raw as $ej) {
        $by_id[$ej['id']] = $ej;
    }

    $ejercicios = [];
    foreach ($math_ids as $index => $id) {
        if (isset($by_id[$id])) {
            $ejercicio = $by_id[$id];
            $ejercicio['ex_num'] = $index + 1;
            $ejercicios[] = $ejercicio;
        }
    }

    echo json_encode([
        'session_id' => $session_id,
        'board_id' => $board_id,
        'ejercicios' => $ejercicios
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
