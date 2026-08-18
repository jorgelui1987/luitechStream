<?php
/**
 * ============================================
 * LuiTechStream - API Backend PHP + MySQL
 * ============================================
 * Este archivo conecta el frontend con la base
 * de datos MySQL usando Laragon.
 * 
 * URL: http://localhost/luitechstream/api.php
 * ============================================
 */

// Configuración de la base de datos (usa variables de entorno para producción)
define('DB_HOST', getenv('DB_HOST') ?: 'luitechstream-luitechstream-05puer');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USERNAME') ?: 'luitechStream');
define('DB_PASS', getenv('DB_PASSWORD') ?: 'Castro161219@');
define('DB_NAME', getenv('DB_DATABASE') ?: 'luitechStream');

// Configurar cabeceras para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conectar a la base de datos
function getDB() {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
        exit();
    }
}

// Obtener la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Leer cuerpo JSON para POST
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// ============================================
// ACCIÓN: Obtener catálogo completo de series
// ============================================
if ($action === 'series') {
    $db = getDB();
    
    // Obtener todas las series
    $seriesStmt = $db->query('SELECT * FROM series ORDER BY created_at DESC');
    $seriesList = $seriesStmt->fetchAll();
    
    // Obtener todos los episodios
    $epStmt = $db->query('SELECT * FROM episodes ORDER BY series_id, ep_num');
    $episodes = $epStmt->fetchAll();
    
    // Agrupar episodios por serie
    $episodesBySeries = [];
    foreach ($episodes as $ep) {
        $episodesBySeries[$ep['series_id']][] = [
            'ep_num' => (int)$ep['ep_num'],
            'title' => $ep['title'],
            'video_url' => $ep['video_url'],
            'is_free' => (bool)$ep['is_free'],
            'cost' => (int)$ep['cost'],
            'likes' => (int)$ep['likes']
        ];
    }
    
    // Construir respuesta final
    $result = [];
    foreach ($seriesList as $s) {
        $result[] = [
            'id' => $s['id'],
            'title' => $s['title'],
            'category' => $s['category'],
            'cover' => $s['cover'],
            'description' => $s['description'],
            'episodes' => isset($episodesBySeries[$s['id']]) ? $episodesBySeries[$s['id']] : []
        ];
    }
    
    echo json_encode($result);
    exit();
}

// ============================================
// ACCIÓN: Obtener datos del usuario
// ============================================
if ($action === 'user') {
    $db = getDB();
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
    
    // Obtener usuario
    $stmt = $db->prepare('SELECT id, username, coins FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit();
    }
    
    // Obtener episodios desbloqueados
    $unlockStmt = $db->prepare('SELECT series_id, ep_num FROM unlocks WHERE user_id = ?');
    $unlockStmt->execute([$userId]);
    $unlocked = $unlockStmt->fetchAll();
    
    echo json_encode([
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'coins' => (int)$user['coins'],
        'unlocked' => $unlocked
    ]);
    exit();
}

// ============================================
// ACCIÓN: Desbloquear episodio con monedas
// ============================================
if ($action === 'unlock') {
    $db = getDB();
    $userId = isset($input['userId']) ? (int)$input['userId'] : 1;
    $seriesId = isset($input['seriesId']) ? $input['seriesId'] : '';
    $epNum = isset($input['epNum']) ? (int)$input['epNum'] : 0;
    
    if (!$seriesId || $epNum <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        exit();
    }
    
    try {
        // 1. Obtener costo del episodio
        $epStmt = $db->prepare('SELECT cost, is_free FROM episodes WHERE series_id = ? AND ep_num = ?');
        $epStmt->execute([$seriesId, $epNum]);
        $episode = $epStmt->fetch();
        
        if (!$episode) {
            http_response_code(404);
            echo json_encode(['error' => 'Episodio no encontrado']);
            exit();
        }
        
        // Si es gratis, no cobrar
        $cost = (int)$episode['cost'];
        if ($episode['is_free']) {
            $cost = 0;
        }
        
        // 2. Verificar si ya está desbloqueado
        $checkStmt = $db->prepare('SELECT 1 FROM unlocks WHERE user_id = ? AND series_id = ? AND ep_num = ?');
        $checkStmt->execute([$userId, $seriesId, $epNum]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => true, 'message' => 'Ya estaba desbloqueado', 'coinsLeft' => getCoins($db, $userId)]);
            exit();
        }
        
        // 3. Verificar monedas del usuario
        $userStmt = $db->prepare('SELECT coins FROM users WHERE id = ?');
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Usuario no encontrado']);
            exit();
        }
        
        $userCoins = (int)$user['coins'];
        if ($userCoins < $cost) {
            http_response_code(402);
            echo json_encode(['error' => 'Monedas insuficientes']);
            exit();
        }
        
        // 4. Descontar monedas y registrar desbloqueo
        $db->beginTransaction();
        
        $updateStmt = $db->prepare('UPDATE users SET coins = coins - ? WHERE id = ?');
        $updateStmt->execute([$cost, $userId]);
        
        $insertStmt = $db->prepare('INSERT INTO unlocks (user_id, series_id, ep_num) VALUES (?, ?, ?)');
        $insertStmt->execute([$userId, $seriesId, $epNum]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Episodio desbloqueado con éxito',
            'coinsLeft' => $userCoins - $cost
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ============================================
// ACCIÓN: Añadir monedas al usuario
// ============================================
if ($action === 'add_coins') {
    $db = getDB();
    $userId = isset($input['userId']) ? (int)$input['userId'] : 1;
    $amount = isset($input['amount']) ? (int)$input['amount'] : 0;
    
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cantidad inválida']);
        exit();
    }
    
    try {
        $stmt = $db->prepare('UPDATE users SET coins = coins + ? WHERE id = ?');
        $stmt->execute([$amount, $userId]);
        
        // Obtener monedas actualizadas
        $getStmt = $db->prepare('SELECT coins FROM users WHERE id = ?');
        $getStmt->execute([$userId]);
        $user = $getStmt->fetch();
        
        echo json_encode([
            'success' => true,
            'coins' => (int)$user['coins']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ============================================
// ACCIÓN: Publicar nueva serie
// ============================================
if ($action === 'add_series') {
    $db = getDB();
    
    $title = isset($input['title']) ? trim($input['title']) : '';
    $category = isset($input['category']) ? trim($input['category']) : '';
    $cover = isset($input['cover']) ? trim($input['cover']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $episodes = isset($input['episodes']) ? $input['episodes'] : [];
    
    if (!$title || !$category || !$cover || empty($episodes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        exit();
    }
    
    try {
        $db->beginTransaction();
        
        // Generar ID único
        $seriesId = 'series-' . uniqid();
        
        // Insertar serie
        $seriesStmt = $db->prepare('INSERT INTO series (id, title, category, cover, description) VALUES (?, ?, ?, ?, ?)');
        $seriesStmt->execute([$seriesId, $title, $category, $cover, $description]);
        
        // Insertar episodios
        $epStmt = $db->prepare('INSERT INTO episodes (series_id, ep_num, title, video_url, is_free, cost) VALUES (?, ?, ?, ?, ?, ?)');
        
        foreach ($episodes as $index => $ep) {
            $epNum = $index + 1;
            $epTitle = isset($ep['title']) ? $ep['title'] : $title . ' - Cap ' . $epNum;
            $epUrl = isset($ep['url']) ? trim($ep['url']) : '';
            $isFree = $epNum === 1 ? 1 : 0; // Episodio 1 siempre gratis
            $cost = isset($ep['cost']) ? (int)$ep['cost'] : 10;
            
            if (!$epUrl) continue;
            
            $epStmt->execute([$seriesId, $epNum, $epTitle, $epUrl, $isFree, $cost]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Serie publicada con éxito',
            'seriesId' => $seriesId
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ============================================
// ACCIÓN: Eliminar serie
// ============================================
if ($action === 'delete_series') {
    $db = getDB();
    $seriesId = isset($input['id']) ? $input['id'] : '';
    
    if (!$seriesId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de serie requerido']);
        exit();
    }
    
    try {
        // Eliminar episodios primero (por la FK)
        $epStmt = $db->prepare('DELETE FROM episodes WHERE series_id = ?');
        $epStmt->execute([$seriesId]);
        
        // Eliminar desbloqueos
        $unlockStmt = $db->prepare('DELETE FROM unlocks WHERE series_id = ?');
        $unlockStmt->execute([$seriesId]);
        
        // Eliminar serie
        $seriesStmt = $db->prepare('DELETE FROM series WHERE id = ?');
        $seriesStmt->execute([$seriesId]);
        
        echo json_encode(['success' => true, 'message' => 'Serie eliminada']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ============================================
// ACCIÓN: Dar like a un episodio
// ============================================
if ($action === 'like') {
    $db = getDB();
    $seriesId = isset($input['seriesId']) ? $input['seriesId'] : '';
    $epNum = isset($input['epNum']) ? (int)$input['epNum'] : 0;
    $liked = isset($input['liked']) ? (bool)$input['liked'] : true;
    
    if (!$seriesId || $epNum <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos']);
        exit();
    }
    
    try {
        if ($liked) {
            $stmt = $db->prepare('UPDATE episodes SET likes = likes + 1 WHERE series_id = ? AND ep_num = ?');
        } else {
            $stmt = $db->prepare('UPDATE episodes SET likes = GREATEST(likes - 1, 0) WHERE series_id = ? AND ep_num = ?');
        }
        $stmt->execute([$seriesId, $epNum]);
        
        // Obtener likes actualizados
        $getStmt = $db->prepare('SELECT likes FROM episodes WHERE series_id = ? AND ep_num = ?');
        $getStmt->execute([$seriesId, $epNum]);
        $ep = $getStmt->fetch();
        
        echo json_encode([
            'success' => true,
            'likes' => (int)$ep['likes']
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}

// ============================================
// ACCIÓN: Health check (verificar conexión)
// ============================================
if ($action === 'health') {
    $db = getDB();
    echo json_encode(['status' => 'ok', 'database' => DB_NAME, 'time' => date('Y-m-d H:i:s')]);
    exit();
}

// ============================================
// Función auxiliar: Obtener monedas del usuario
// ============================================
function getCoins($db, $userId) {
    $stmt = $db->prepare('SELECT coins FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ? (int)$user['coins'] : 0;
}

// ============================================
// Si no se reconoce la acción
// ============================================
http_response_code(400);
echo json_encode(['error' => 'Acción no válida. Acciones disponibles: series, user, unlock, add_coins, add_series, delete_series, like, health']);