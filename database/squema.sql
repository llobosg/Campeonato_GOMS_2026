-- ============================================
-- CAMPEONATO GOMS 2026 - Schema MySQL 8.0
-- Optimizado para Railway + Índices
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if exist
DROP TABLE IF EXISTS goles;
DROP TABLE IF EXISTS fixture;
DROP TABLE IF EXISTS jugadores;
DROP TABLE IF EXISTS equipos;

-- ============================================
-- TABLA: equipos
-- ============================================
CREATE TABLE equipos (
    id_equipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    grupo ENUM('A', 'B') NOT NULL,
    qr_code TEXT,
    link_registro VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_grupo (grupo),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: jugadores
-- ============================================
CREATE TABLE jugadores (
    id_jugador INT AUTO_INCREMENT PRIMARY KEY,
    id_equipo INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    area VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_equipo) REFERENCES equipos(id_equipo) ON DELETE CASCADE,
    INDEX idx_equipo (id_equipo),
    INDEX idx_correo (correo),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: fixture
-- ============================================
CREATE TABLE fixture (
    id_fixture INT AUTO_INCREMENT PRIMARY KEY,
    nro_fecha INT NOT NULL COMMENT '1-5',
    equipo_a INT NOT NULL,
    equipo_b INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    goles_a INT DEFAULT 0,
    goles_b INT DEFAULT 0,
    estado ENUM('pendiente', 'en_vivo', 'finalizado') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_a) REFERENCES equipos(id_equipo) ON DELETE RESTRICT,
    FOREIGN KEY (equipo_b) REFERENCES equipos(id_equipo) ON DELETE RESTRICT,
    INDEX idx_fecha (fecha),
    INDEX idx_nro_fecha (nro_fecha),
    INDEX idx_estado (estado),
    INDEX idx_equipos_fecha (equipo_a, equipo_b, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: goles
-- ============================================
CREATE TABLE goles (
    id_gol INT AUTO_INCREMENT PRIMARY KEY,
    id_fixture INT NOT NULL,
    id_jugador INT NOT NULL,
    minuto INT COMMENT 'Minuto del gol (opcional)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_fixture) REFERENCES fixture(id_fixture) ON DELETE CASCADE,
    FOREIGN KEY (id_jugador) REFERENCES jugadores(id_jugador) ON DELETE RESTRICT,
    INDEX idx_fixture (id_fixture),
    INDEX idx_jugador (id_jugador),
    INDEX idx_fixture_jugador (id_fixture, id_jugador)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VISTA: v_posiciones (Tabla de posiciones dinámica)
-- ============================================
CREATE VIEW v_posiciones AS
SELECT 
    e.id_equipo,
    e.nombre AS equipo,
    e.grupo,
    COUNT(CASE WHEN f.estado = 'finalizado' AND 
        ((f.equipo_a = e.id_equipo AND f.goles_a > f.goles_b) OR 
         (f.equipo_b = e.id_equipo AND f.goles_b > f.goles_a)) THEN 1 END) AS ganados,
    COUNT(CASE WHEN f.estado = 'finalizado' AND 
        ((f.equipo_a = e.id_equipo AND f.goles_a = f.goles_b) OR 
         (f.equipo_b = e.id_equipo AND f.goles_b = f.goles_a)) THEN 1 END) AS empatados,
    COUNT(CASE WHEN f.estado = 'finalizado' AND 
        ((f.equipo_a = e.id_equipo AND f.goles_a < f.goles_b) OR 
         (f.equipo_b = e.id_equipo AND f.goles_b < f.goles_a)) THEN 1 END) AS perdidos,
    SUM(CASE 
        WHEN f.equipo_a = e.id_equipo THEN f.goles_a 
        WHEN f.equipo_b = e.id_equipo THEN f.goles_b 
        ELSE 0 
    END) AS goles_favor,
    SUM(CASE 
        WHEN f.equipo_a = e.id_equipo THEN f.goles_b 
        WHEN f.equipo_b = e.id_equipo THEN f.goles_a 
        ELSE 0 
    END) AS goles_contra,
    (COUNT(CASE WHEN f.estado = 'finalizado' AND 
        ((f.equipo_a = e.id_equipo AND f.goles_a > f.goles_b) OR 
         (f.equipo_b = e.id_equipo AND f.goles_b > f.goles_a)) THEN 1 END) * 2 +
     COUNT(CASE WHEN f.estado = 'finalizado' AND 
        ((f.equipo_a = e.id_equipo AND f.goles_a = f.goles_b) OR 
         (f.equipo_b = e.id_equipo AND f.goles_b = f.goles_a)) THEN 1 END)) AS puntos
FROM equipos e
LEFT JOIN fixture f ON (e.id_equipo = f.equipo_a OR e.id_equipo = f.equipo_b) 
    AND f.estado = 'finalizado'
GROUP BY e.id_equipo, e.nombre, e.grupo
ORDER BY e.grupo, puntos DESC, goles_favor DESC;

-- ============================================
-- VISTA: v_goleadores (Top goleadores por grupo)
-- ============================================
CREATE VIEW v_goleadores AS
SELECT 
    j.id_jugador,
    j.nombre AS jugador,
    e.nombre AS equipo,
    e.grupo,
    COUNT(g.id_gol) AS goles
FROM goles g
JOIN jugadores j ON g.id_jugador = j.id_jugador
JOIN equipos e ON j.id_equipo = e.id_equipo
JOIN fixture f ON g.id_fixture = f.id_fixture
WHERE f.estado = 'finalizado'
GROUP BY j.id_jugador, j.nombre, e.nombre, e.grupo
HAVING goles > 0
ORDER BY e.grupo, goles DESC, j.nombre ASC;

-- ============================================
-- INSERTAR EQUIPOS OFICIALES
-- ============================================
INSERT INTO equipos (nombre, grupo, qr_code, link_registro) VALUES
('Pem-K-Zo', 'A', NULL, NULL),
('Trancapelotas FC', 'A', NULL, NULL),
('Los Mundiales', 'A', NULL, NULL),
('Mas Menos 1 Metro FC', 'A', NULL, NULL),
('Los Galácticos', 'A', NULL, NULL),
('Calidad Prime', 'B', NULL, NULL),
('Los Desquinchadores', 'B', NULL, NULL),
('Macizo United', 'B', NULL, NULL),
('Deportivo NdC', 'B', NULL, NULL),
('Jaque Boys', 'B', NULL, NULL);

-- ============================================
-- INSERTAR FIXTURE COMPLETO (5 fechas)
-- ============================================

-- FECHA 1: 02/06/2026
INSERT INTO fixture (nro_fecha, equipo_a, equipo_b, fecha, hora) VALUES
(1, 1, 5, '2026-06-02', '20:00:00'),
(1, 3, 4, '2026-06-02', '21:00:00'),
(1, 9, 8, '2026-06-02', '22:00:00'),
(1, 6, 7, '2026-06-02', '23:00:00');

-- FECHA 2: 02/06/2026
INSERT INTO fixture (nro_fecha, equipo_a, equipo_b, fecha, hora) VALUES
(2, 1, 4, '2026-06-02', '20:00:00'),
(2, 2, 5, '2026-06-02', '21:00:00'),
(2, 9, 6, '2026-06-02', '22:00:00'),
(2, 10, 8, '2026-06-02', '23:00:00');

-- FECHA 3: 09/06/2026
INSERT INTO fixture (nro_fecha, equipo_a, equipo_b, fecha, hora) VALUES
(3, 1, 2, '2026-06-09', '20:00:00'),
(3, 3, 5, '2026-06-09', '21:00:00'),
(3, 9, 7, '2026-06-09', '22:00:00'),
(3, 10, 6, '2026-06-09', '23:00:00');

-- FECHA 4: 10/06/2026
INSERT INTO fixture (nro_fecha, equipo_a, equipo_b, fecha, hora) VALUES
(4, 1, 3, '2026-06-10', '20:00:00'),
(4, 4, 2, '2026-06-10', '21:00:00'),
(4, 9, 10, '2026-06-10', '22:00:00'),
(4, 8, 7, '2026-06-10', '23:00:00');

-- FECHA 5: 17/06/2026
INSERT INTO fixture (nro_fecha, equipo_a, equipo_b, fecha, hora) VALUES
(5, 5, 4, '2026-06-17', '20:00:00'),
(5, 2, 3, '2026-06-17', '21:00:00'),
(5, 8, 6, '2026-06-17', '22:00:00'),
(5, 7, 10, '2026-06-17', '23:00:00');

SET FOREIGN_KEY_CHECKS = 1;

SELECT '✅ Schema creado exitosamente' AS status;
SELECT COUNT(*) AS total_equipos FROM equipos;
SELECT COUNT(*) AS total_fixture FROM fixture;