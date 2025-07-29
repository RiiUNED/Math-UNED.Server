<?php
header('Content-Type: application/json');
require_once 'database.php';

try {
    $pdo = getDB();

    // Detectar si el contenido es JSON y decodificarlo
    $session_id = null;

    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        $input = json_decode(file_get_contents('php://input'), true);
        $session_id = $input['session_id'] ?? null;
    } else {
        $session_id = $_POST['session_id'] ?? $_GET['session_id'] ?? null;
    }

    if (!$session_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta session_id']);
        exit;
    }

    // Obtener board_id desde math_session
    $stmt = $pdo->prepare("SELECT board_id FROM math_session WHERE session_id = ?");
    $stmt->execute([$session_id]);
    $board_id = $stmt->fetchColumn();

    if (!$board_id) {
        http_response_code(404);
        echo json_encode(['error' => 'Sesión no tiene tablero asignado']);
        exit;
    }

    // Obtener los 13 IDs del board
    $stmt = $pdo->prepare("SELECT 
        math_id_1, math_id_2, math_id_3, math_id_4, math_id_5,
        math_id_6, math_id_7, math_id_8, math_id_9, math_id_10,
        math_id_11, math_id_12, math_id_13
        FROM board WHERE id = ?");
    $stmt->execute([$board_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Tablero no encontrado']);
        exit;
    }

    // Construir lista de IDs únicos en orden
    $math_ids = array_values(array_filter($row));

    // Construir placeholders para consulta SQL
    $placeholders = implode(',', array_fill(0, count($math_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, op1, op2, res FROM math WHERE id IN ($placeholders)");
    $stmt->execute($math_ids);
    $ejercicios_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ordenar los ejercicios en el mismo orden que los math_id_X
    $map = [];
    foreach ($ejercicios_raw as $ej) {
        $map[$ej['id']] = $ej;
    }
    
    $ejercicios = [];
    foreach ($math_ids as $index => $id) {
        if (isset($map[$id])) {
            $ejercicio = $map[$id];
            $ejercicio['ex_num'] = $index + 1;  // del 1 al 13
            $ejercicios[] = $ejercicio;
        }
    }

    // Devolver JSON
    echo json_encode([
        'session_id' => $session_id,
        'board_id' => $board_id,
        'ejercicios' => $ejercicios
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
