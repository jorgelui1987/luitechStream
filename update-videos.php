<?php
header('Content-Type: application/json');
$host = getenv('DB_HOST') ?: 'luitechstream-luitechstream-05puer';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USERNAME') ?: 'luitechStream';
$pass = getenv('DB_PASSWORD') ?: 'Castro161219@';
$dbname = getenv('DB_DATABASE') ?: 'luitechStream';
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $videos = ['https://www.w3schools.com/html/mov_bbb.mp4', 'https://www.w3schools.com/html/movie.mp4'];
    $count = 0;
    $stmt = $pdo->query("SELECT series_id, ep_num FROM episodes");
    $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $updateStmt = $pdo->prepare("UPDATE episodes SET video_url = ? WHERE series_id = ? AND ep_num = ?");
    foreach ($episodes as $i => $ep) {
        $newUrl = $videos[$i % count($videos)];
        $updateStmt->execute([$newUrl, $ep['series_id'], $ep['ep_num']]);
        $count++;
    }
    echo json_encode(['success' => true, 'message' => "Se actualizaron $count episodios"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}