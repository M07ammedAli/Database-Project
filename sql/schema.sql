-- =====================================================================
-- IT8415 Database Programming 2 - Group Project
-- Theme: Movie Review System
-- Table prefix rule: dbProj_
--
-- HOW TO USE:
--   1. Open phpMyAdmin on the uni server.
--   2. Select your SHARED project database (the one in DBconn.php).
--   3. Import this file (or paste into the SQL tab) and run.
--
-- Notes for markers / teammates:
--   * Three roles: viewer, creator, admin (Task 3 / Functional 1.1)
--   * dbProj_movies uses MyISAM so we can add a FULLTEXT index (Unit 7)
--   * Other tables use InnoDB for foreign keys
--   * 1 stored procedure + 1 trigger included (Task 3 / Task 4)
--   * >15 test records, with >5 in one category (Action) (Task 3)
-- =====================================================================

-- ---------------------------------------------------------------------
-- Clean start (safe to re-run). Order matters because of foreign keys.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS dbProj_comments;
DROP TABLE IF EXISTS dbProj_ratings;
DROP TABLE IF EXISTS dbProj_movies;
DROP TABLE IF EXISTS dbProj_categories;
DROP TABLE IF EXISTS dbProj_users;
DROP PROCEDURE IF EXISTS dbProj_most_popular_in_range;
DROP PROCEDURE IF EXISTS dbProj_content_by_creator;

-- =====================================================================
-- TABLE: users  (roles, encrypted passwords)
-- =====================================================================
CREATE TABLE dbProj_users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,          -- password_hash() output
    role          ENUM('viewer','creator','admin') NOT NULL DEFAULT 'viewer',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- TABLE: categories  (navigation menu sections)
-- =====================================================================
CREATE TABLE dbProj_categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- TABLE: movies  (the main content)
--   MyISAM + FULLTEXT for engine-based search (Unit 7 requirement)
--   view_count is used by the popularity search + reports
-- =====================================================================
CREATE TABLE dbProj_movies (
    movie_id      INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(150) NOT NULL,
    description   TEXT NOT NULL,
    category_id   INT NOT NULL,
    creator_id    INT NOT NULL,                   -- FK in app logic (MyISAM ignores FK)
    poster_image  VARCHAR(255) DEFAULT NULL,      -- uploaded file name in /images
    trailer_url   VARCHAR(255) DEFAULT NULL,      -- media file (YouTube/video link)
    release_date  DATE DEFAULT NULL,
    status        ENUM('draft','published') NOT NULL DEFAULT 'draft',
    view_count    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT KEY ft_title_desc (title, description)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- TABLE: ratings  (like/dislike OR star; we use 1-5 star)
--   one rating per user per movie (enforced by UNIQUE)
-- =====================================================================
CREATE TABLE dbProj_ratings (
    rating_id     INT AUTO_INCREMENT PRIMARY KEY,
    movie_id      INT NOT NULL,
    user_id       INT NOT NULL,
    stars         TINYINT NOT NULL,               -- 1..5
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_movie (movie_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- TABLE: comments  (logged-in users comment; admin can delete)
-- =====================================================================
CREATE TABLE dbProj_comments (
    comment_id    INT AUTO_INCREMENT PRIMARY KEY,
    movie_id      INT NOT NULL,
    user_id       INT NOT NULL,
    body          TEXT NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- STORED PROCEDURE 1: most popular content in a date range
--   Used by Admin report 1.7 (most popular within a date range)
-- =====================================================================
DELIMITER //
CREATE PROCEDURE dbProj_most_popular_in_range(
    IN p_start DATE,
    IN p_end   DATE
)
BEGIN
    SELECT  m.movie_id,
            m.title,
            m.view_count,
            COUNT(DISTINCT c.comment_id)      AS comment_count,
            IFNULL(ROUND(AVG(r.stars),2), 0)  AS avg_rating
    FROM dbProj_movies m
    LEFT JOIN dbProj_comments c ON c.movie_id = m.movie_id
    LEFT JOIN dbProj_ratings  r ON r.movie_id = m.movie_id
    WHERE m.status = 'published'
      AND DATE(m.created_at) BETWEEN p_start AND p_end
    GROUP BY m.movie_id, m.title, m.view_count
    ORDER BY m.view_count DESC, avg_rating DESC;
END //
DELIMITER ;

-- =====================================================================
-- STORED PROCEDURE 2: content created by a specific user
--   Used by Admin report 1.7 (content by creator)
-- =====================================================================
DELIMITER //
CREATE PROCEDURE dbProj_content_by_creator(
    IN p_creator_id INT
)
BEGIN
    SELECT  m.movie_id,
            m.title,
            m.status,
            m.view_count,
            m.created_at
    FROM dbProj_movies m
    WHERE m.creator_id = p_creator_id
    ORDER BY m.created_at DESC;
END //
DELIMITER ;

-- =====================================================================
-- TRIGGER: stops impossible star ratings reaching the table.
--   Demonstrates a BEFORE INSERT trigger (Task 4 advanced feature).
--   Clamps any value outside 1..5 to the nearest valid bound.
-- =====================================================================
DELIMITER //
CREATE TRIGGER dbProj_before_rating_insert
BEFORE INSERT ON dbProj_ratings
FOR EACH ROW
BEGIN
    IF NEW.stars < 1 THEN
        SET NEW.stars = 1;
    ELSEIF NEW.stars > 5 THEN
        SET NEW.stars = 5;
    END IF;
END //
DELIMITER ;

-- =====================================================================
-- TEST DATA
-- =====================================================================

-- Categories
INSERT INTO dbProj_categories (name) VALUES
('Action'), ('Drama'), ('Comedy'), ('Sci-Fi'), ('Horror');

-- Users
-- NOTE: password_hash strings below are bcrypt for the plain passwords:
--   admin    -> Admin123
--   creator1 -> Creator123
--   creator2 -> Creator123
--   viewer1  -> Viewer123
-- These hashes are generated with PASSWORD_DEFAULT and are valid for login.
INSERT INTO dbProj_users (username, email, password_hash, role) VALUES
('admin',    'admin@movie.test',    '$2y$10$VQKcoy.p4SrfbFB00ziOauRWOFXgztDRXWpPf8bWy1lCU0KbDwyIa', 'admin'),
('creator1', 'creator1@movie.test', '$2y$10$sfzZ4AycGgyrYJsTQkmS9usbvIMVYYFu8m6j5Srxs8omj0PzSrdTm', 'creator'),
('creator2', 'creator2@movie.test', '$2y$10$VHbkiSb7s6GnWPXZoZ7bAe1caupGf9yrB2Ll3v.P/tX53uHIRrvEO', 'creator'),
('viewer1',  'viewer1@movie.test',  '$2y$10$u2IkFLQ4QSmG6QkYaugsC.Z1rKjjdjMf1hwXL.19IUhLzAhK/qFWy', 'viewer');

-- Movies (>=15 total, >=5 in the Action category which is category_id = 1)
-- creator_id 2 = creator1, 3 = creator2
INSERT INTO dbProj_movies
(title, description, category_id, creator_id, poster_image, trailer_url, release_date, status, view_count, created_at) VALUES
-- Action (5+)
('Steel Horizon','A retired pilot is pulled back for one last mission across hostile skies.',1,2,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-01-10','published',320,'2025-01-12 10:00:00'),
('Midnight Pursuit','A detective chases a thief through a city that never sleeps.',1,2,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-02-05','published',410,'2025-02-06 14:30:00'),
('Iron Valley','Soldiers defend a remote outpost against overwhelming odds.',1,3,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-03-01','published',150,'2025-03-02 09:15:00'),
('Last Stand Ridge','A small team holds a mountain pass in a desperate fight.',1,3,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-03-20','published',270,'2025-03-21 18:00:00'),
('Velocity','An undercover racer infiltrates a dangerous street gang.',1,2,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-04-11','published',505,'2025-04-12 11:45:00'),
('Crimson Strike','A covert unit races to stop a global threat.',1,3,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-05-02','published',95,'2025-05-03 08:20:00'),
-- Drama
('Quiet Letters','Two strangers connect through letters left in an old library.',2,2,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-01-25','published',180,'2025-01-26 16:00:00'),
('The Long Road Home','A family reunites after years apart during a harsh winter.',2,3,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-02-18','published',220,'2025-02-19 13:10:00'),
('Glass Houses','Secrets unravel in a wealthy neighbourhood over one summer.',2,2,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-03-30','published',140,'2025-03-31 19:25:00'),
-- Comedy
('Office Chaos','A new intern accidentally becomes the boss for a day.',3,3,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-02-10','published',300,'2025-02-11 12:00:00'),
('Wedding Wars','Two best friends plan competing weddings on the same day.',3,2,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-04-01','published',260,'2025-04-02 10:30:00'),
-- Sci-Fi
('Orbital','A space station crew discovers a signal from deep space.',4,3,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-01-05','published',480,'2025-01-07 09:00:00'),
('The Ninth Colony','Settlers on a distant world face an unexplained phenomenon.',4,2,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-03-15','published',360,'2025-03-16 15:45:00'),
-- Horror
('Hollow Manor','A film crew spends a night in a house with a dark past.',5,3,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-02-28','published',410,'2025-03-01 21:00:00'),
('Whisper Lake','Campers hear voices coming from a lake at midnight.',5,2,'placeholder.jpg','https://www.youtube.com/watch?v=dQw4w9WgXcQ','2025-04-20','published',330,'2025-04-21 22:15:00'),
-- one draft to demonstrate draft vs published
('Untitled Project','Work in progress, not yet published.',1,2,'placeholder.jpg',NULL,NULL,'draft',0,'2025-05-10 08:00:00');

-- Ratings (spread across movies; trigger will clamp bad values if any)
INSERT INTO dbProj_ratings (movie_id, user_id, stars) VALUES
(1,4,5),(2,4,4),(5,4,5),(12,4,4),(14,4,3),
(1,3,4),(2,2,5),(10,4,5),(7,4,4),(13,4,4);

-- Comments
INSERT INTO dbProj_comments (movie_id, user_id, body) VALUES
(1,4,'Loved the opening sequence!'),
(2,4,'Great pacing, kept me hooked.'),
(5,4,'Best action movie this year.'),
(12,4,'The visuals were stunning.'),
(14,4,'Genuinely creepy, well done.');