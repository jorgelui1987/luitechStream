<?php
/**
 * Script de inicialización de la base de datos
 * Se ejecuta automáticamente al iniciar el contenedor
 */

$host = getenv('DB_HOST') ?: 'luitechstream-luitechstream-05puer';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'luitechStream';
$pass = getenv('DB_PASSWORD') ?: 'Castro161219@';
$dbname = getenv('DB_DATABASE') ?: 'luitechStream';

echo "Inicializando base de datos...\n";

try {
    // Conectar sin seleccionar base de datos
    $pdo = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    // Crear tablas
    $pdo->exec("CREATE TABLE IF NOT EXISTS series (
        id VARCHAR(50) PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        category VARCHAR(50) NOT NULL,
        cover VARCHAR(500) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS episodes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        series_id VARCHAR(50) NOT NULL,
        ep_num INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        video_url VARCHAR(500) NOT NULL,
        is_free BOOLEAN DEFAULT TRUE,
        cost INT DEFAULT 10,
        likes INT DEFAULT 0,
        FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
        UNIQUE KEY unique_episode (series_id, ep_num)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        coins INT DEFAULT 150,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS unlocks (
        user_id INT,
        series_id VARCHAR(50),
        ep_num INT,
        unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, series_id, ep_num),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE
    )");
    
    // Insertar usuario demo
    $pdo->exec("INSERT INTO users (id, username, coins) VALUES (1, 'demo', 150)
        ON DUPLICATE KEY UPDATE username = 'demo'");
    
    // Insertar series de ejemplo
    $pdo->exec("INSERT INTO series (id, title, category, cover, description) VALUES
        ('series-1', 'El Secreto del CEO Multimillonario', 'CEO', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&q=80', 'Un heredero de negocios se hace pasar por chofer para descubrir quién intenta destruir su empresa.'),
        ('series-2', 'La Venganza de la Esposa Rechazada', 'Venganza', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&q=80', 'Traicionada por su familia, regresa 5 años después como la inversora más poderosa de la ciudad.'),
        ('series-3', 'Bajo la Lluvia de Seúl', 'Romance', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500&q=80', 'Dos desconocidos se conocen en un café durante una tormenta y descubren que sus destinos están conectados.'),
        ('series-4', 'El Reino de las Sombras', 'Fantasía', 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=500&q=80', 'Una joven heredera descubre que puede ver criaturas mágicas ocultas en el mundo moderno.')
        ON DUPLICATE KEY UPDATE title = VALUES(title)");
    
    // Insertar episodios
    $pdo->exec("INSERT INTO episodes (series_id, ep_num, title, video_url, is_free, cost, likes) VALUES
        ('series-1', 1, 'Capítulo 1: El Chofer Misterioso', 'https://assets.mixkit.co/videos/preview/mixkit-vertical-shot-of-a-waterfall-in-a-forest-42894-large.mp4', TRUE, 0, 1240),
        ('series-1', 2, 'Capítulo 2: El Encuentro en la Gala', 'https://assets.mixkit.co/videos/preview/mixkit-tree-branches-in-the-breeze-1187-large.mp4', TRUE, 0, 980),
        ('series-1', 3, 'Capítulo 3: Revelación Inesperada', 'https://assets.mixkit.co/videos/preview/mixkit-vertical-shot-of-a-waterfall-in-a-forest-42894-large.mp4', FALSE, 10, 1420),
        ('series-1', 4, 'Capítulo 4: El Contrato Falso', 'https://assets.mixkit.co/videos/preview/mixkit-tree-branches-in-the-breeze-1187-large.mp4', FALSE, 10, 890),
        ('series-1', 5, 'Capítulo 5: Venganza en el Imperio', 'https://assets.mixkit.co/videos/preview/mixkit-vertical-shot-of-a-waterfall-in-a-forest-42894-large.mp4', FALSE, 10, 2100),
        ('series-2', 1, 'Capítulo 1: La Traición', 'https://assets.mixkit.co/videos/preview/mixkit-tree-branches-in-the-breeze-1187-large.mp4', TRUE, 0, 3100),
        ('series-2', 2, 'Capítulo 2: El Regreso de la Reina', 'https://assets.mixkit.co/videos/preview/mixkit-vertical-shot-of-a-waterfall-in-a-forest-42894-large.mp4', TRUE, 0, 2750),
        ('series-2', 3, 'Capítulo 3: Caída del Imperio Traidor', 'https://assets.mixkit.co/videos/preview/mixkit-tree-branches-in-the-breeze-1187-large.mp4', FALSE, 10, 1980),
        ('series-3', 1, 'Capítulo 1: El Café y la Tormenta', 'https://assets.mixkit.co/videos/preview/mixkit-vertical-shot-of-a-waterfall-in-a-forest-42894-large.mp4', TRUE, 0, 4560),
        ('series-3', 2, 'Capítulo 2: La Cita Doblada', 'https://assets.mixkit.co/videos/preview/mixkit-tree-branches-in-the-breeze-1187-large.mp4', FALSE, 10, 3100),
        ('series-4', 1, 'Capítulo 1: La Dama de la Niebla', 'https://assets.mixkit.co/videos/preview/mixkit-vertical-shot-of-a-waterfall-in-a-forest-42894-large.mp4', TRUE, 0, 5670),
        ('series-4', 2, 'Capítulo 2: El Pacto Prohibido', 'https://assets.mixkit.co/videos/preview/mixkit-tree-branches-in-the-breeze-1187-large.mp4', TRUE, 0, 4320),
        ('series-4', 3, 'Capítulo 3: El Portal en el Bosque', 'https://assets.mixkit.co/videos/preview/mixkit-vertical-shot-of-a-waterfall-in-a-forest-42894-large.mp4', FALSE, 10, 2210)
        ON DUPLICATE KEY UPDATE title = VALUES(title)");
    
    // Insertar desbloqueos iniciales
    $pdo->exec("INSERT IGNORE INTO unlocks (user_id, series_id, ep_num) VALUES
        (1, 'series-1', 1),
        (1, 'series-1', 2),
        (1, 'series-2', 1),
        (1, 'series-2', 2),
        (1, 'series-3', 1),
        (1, 'series-4', 1),
        (1, 'series-4', 2)");
    
    echo "Base de datos inicializada correctamente con las series de ejemplo.\n";
} catch (PDOException $e) {
    echo "Error al inicializar la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}