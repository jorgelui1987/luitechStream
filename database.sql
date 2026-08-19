-- ============================================
-- LuiTechStream - Base de Datos MySQL
-- Ejecutar en phpMyAdmin: http://localhost/phpmyadmin
-- ============================================

CREATE DATABASE IF NOT EXISTS luitechStream CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luitechStream;

-- ============================================
-- Tabla de series
-- ============================================
CREATE TABLE IF NOT EXISTS series (
  id VARCHAR(50) PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(50) NOT NULL,
  cover VARCHAR(500) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Tabla de episodios
-- ============================================
CREATE TABLE IF NOT EXISTS episodes (
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
);

-- ============================================
-- Tabla de usuarios
-- ============================================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  coins INT DEFAULT 150,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- Tabla de desbloqueos
-- ============================================
CREATE TABLE IF NOT EXISTS unlocks (
  user_id INT,
  series_id VARCHAR(50),
  ep_num INT,
  unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, series_id, ep_num),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE
);

-- ============================================
-- Insertar usuario demo
-- ============================================
INSERT INTO users (id, username, coins) VALUES (1, 'demo', 150)
ON DUPLICATE KEY UPDATE username = 'demo';

-- ============================================
-- Insertar datos de ejemplo
-- ============================================
INSERT INTO series (id, title, category, cover, description) VALUES
('series-1', 'El Secreto del CEO Multimillonario', 'CEO', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&q=80', 'Un heredero de negocios se hace pasar por chofer para descubrir quién intenta destruir su empresa.'),
('series-2', 'La Venganza de la Esposa Rechazada', 'Venganza', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=500&q=80', 'Traicionada por su familia, regresa 5 años después como la inversora más poderosa de la ciudad.'),
('series-3', 'Bajo la Lluvia de Seúl', 'Romance', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500&q=80', 'Dos desconocidos se conocen en un café durante una tormenta y descubren que sus destinos están conectados.'),
('series-4', 'El Reino de las Sombras', 'Fantasía', 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=500&q=80', 'Una joven heredera descubre que puede ver criaturas mágicas ocultas en el mundo moderno.')
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO episodes (series_id, ep_num, title, video_url, is_free, cost, likes) VALUES
('series-1', 1, 'Capítulo 1: El Chofer Misterioso', 'https://www.w3schools.com/html/mov_bbb.mp4', TRUE, 0, 1240),
('series-1', 2, 'Capítulo 2: El Encuentro en la Gala', 'https://www.w3schools.com/html/movie.mp4', TRUE, 0, 980),
('series-1', 3, 'Capítulo 3: Revelación Inesperada', 'https://www.w3schools.com/html/mov_bbb.mp4', FALSE, 10, 1420),
('series-1', 4, 'Capítulo 4: El Contrato Falso', 'https://www.w3schools.com/html/movie.mp4', FALSE, 10, 890),
('series-1', 5, 'Capítulo 5: Venganza en el Imperio', 'https://www.w3schools.com/html/mov_bbb.mp4', FALSE, 10, 2100),
('series-2', 1, 'Capítulo 1: La Traición', 'https://www.w3schools.com/html/movie.mp4', TRUE, 0, 3100),
('series-2', 2, 'Capítulo 2: El Regreso de la Reina', 'https://www.w3schools.com/html/mov_bbb.mp4', TRUE, 0, 2750),
('series-2', 3, 'Capítulo 3: Caída del Imperio Traidor', 'https://www.w3schools.com/html/movie.mp4', FALSE, 10, 1980),
('series-3', 1, 'Capítulo 1: El Café y la Tormenta', 'https://www.w3schools.com/html/mov_bbb.mp4', TRUE, 0, 4560),
('series-3', 2, 'Capítulo 2: La Cita Doblada', 'https://www.w3schools.com/html/movie.mp4', FALSE, 10, 3100),
('series-4', 1, 'Capítulo 1: La Dama de la Niebla', 'https://www.w3schools.com/html/movie.mp4', TRUE, 0, 5670),
('series-4', 2, 'Capítulo 2: El Pacto Prohibido', 'https://www.w3schools.com/html/mov_bbb.mp4', TRUE, 0, 4320),
('series-4', 3, 'Capítulo 3: El Portal en el Bosque', 'https://www.w3schools.com/html/movie.mp4', FALSE, 10, 2210)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- ============================================
-- Desbloqueos iniciales del usuario demo
-- ============================================
INSERT IGNORE INTO unlocks (user_id, series_id, ep_num) VALUES
(1, 'series-1', 1),
(1, 'series-1', 2),
(1, 'series-2', 1),
(1, 'series-2', 2),
(1, 'series-3', 1),
(1, 'series-4', 1),
(1, 'series-4', 2);