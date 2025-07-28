<?php
//header('Content-Type: text/plain');
echo "🔁 Inicio de index.php\n"; flush();

// Incluir archivo de conexión
require_once 'database.php';

try {
    $pdo = getDB(); // función definida en database.php
    echo "✅ Conexión exitosa desde index.php\n";

    // Crear tabla de ejemplo si no existe
    $sql = "CREATE TABLE IF NOT EXISTS players (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50),
                score INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    echo "✅ Tabla 'players' creada o ya existía\n";
} catch (PDOException $e) {
    echo "❌ Error en index.php: " . $e->getMessage();
}
echo "\n🔁 Fin de index.php\n";