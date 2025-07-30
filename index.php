<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$lock_file = __DIR__ . '/db_initialized.lock';

try {
    // Solicitud inicial vacía
    if (empty($input)) {

        // Si no existe la BBDD → crearla
        if (!file_exists($lock_file)) {
            require 'gestor_bbdd.php';
            file_put_contents($lock_file, "BBDD inicializada el " . date('Y-m-d H:i:s'));
        }

        // Siempre continuar con la gestión de sesión (jugador 1 o 2)
        require 'gestor_sesiones.php';

    } elseif (isset($input['session_id'], $input['player_id'])) {
        // Flujo de juego activo
        require 'gestor_juego.php';

    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Solicitud inválida: falta session_id o player_id']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
