<?php
header('Content-Type: application/json');
require_once 'database.php';

try {
    $pdo = getDB();

    // Buscar sesión en espera
    $stmt = $pdo->prepare("SELECT id FROM session WHERE status = 'en espera' LIMIT 1");
    $stmt->execute();
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        // Sesión existente encontrada → registrar como jugador 2
        $session_id = $session['id'];

        // Insertar jugador 2
        $stmt = $pdo->prepare("INSERT INTO players (session_id, numero_jugador) VALUES (?, 2)");
        $stmt->execute([$session_id]);

        // Cambiar estado a "jugando"
        $pdo->prepare("UPDATE session SET status = 'jugando' WHERE id = ?")->execute([$session_id]);

        echo json_encode([
            'session_id' => $session_id,
            'numero_jugador' => 2,
            'status' => 'jugando'
        ]);
    } else {
        // No hay sesión en espera → crear nueva sesión y registrar jugador 1
        $stmt = $pdo->prepare("INSERT INTO session (status) VALUES ('en espera')");
        $stmt->execute();
        $session_id = $pdo->lastInsertId();

        // Insertar jugador 1
        $stmt = $pdo->prepare("INSERT INTO players (session_id, numero_jugador) VALUES (?, 1)");
        $stmt->execute([$session_id]);

        echo json_encode([
            'session_id' => $session_id,
            'numero_jugador' => 1,
            'status' => 'en espera'
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
