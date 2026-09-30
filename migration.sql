-- Geek Club migration - Add Laser Tag pack and Laser Tag places
-- Run: mysql -u root -p bdgeek < migration.sql

USE bdgeek;

-- 1. ADD type COLUMN TO place TABLE
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'bdgeek' AND TABLE_NAME = 'place' AND COLUMN_NAME = 'type'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `place` ADD COLUMN `type` VARCHAR(20) NOT NULL DEFAULT 'standard' AFTER `statut`',
  'SELECT 'type column already exists''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. ADD statut COLUMN IF NOT EXISTS
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'bdgeek' AND TABLE_NAME = 'place' AND COLUMN_NAME = 'statut'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `place` ADD COLUMN `statut` ENUM('libre','reserve') NOT NULL DEFAULT 'libre' AFTER `source`',
  'SELECT 'statut column already exists''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. ADD duree COLUMN TO pack IF NOT EXISTS
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'bdgeek' AND TABLE_NAME = 'pack' AND COLUMN_NAME = 'duree'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `pack` ADD COLUMN `duree` INT(2) NOT NULL DEFAULT 3',
  'SELECT 'duree column already exists''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. ADD 4th Pack (Anniversaire) if not exists
INSERT INTO `pack` (`nom`, `description`, `prix`, `image`, `activites`, `duree`, `actif`)
SELECT * FROM (SELECT
    'Pack Anniversaire' AS nom,
    'PS5 + Parc + decoration standard + gateau offert.' AS description,
    120.00 AS prix,
    'img/neon10.png' AS image,
    'ps5-parc' AS activites,
    3 AS duree,
    1 AS actif
) AS new_pack
WHERE NOT EXISTS (SELECT 1 FROM `pack` WHERE `nom` = 'Pack Anniversaire');

-- Update existing packs with correct images
UPDATE `pack` SET `image` = 'img/pack-ps5.jpg' WHERE `nom` = 'Pack PS5';
UPDATE `pack` SET `image` = 'img/pack-parc.jpg' WHERE `nom` = 'Pack Parc';
UPDATE `pack` SET `image` = 'img/neon1.png' WHERE `nom` = 'Pack Laser';

-- 5. ADD/UPDATE 4 Laser Tag Places with UNIQUE photos
INSERT INTO `place` (`nom`, `source`, `statut`, `type`) VALUES
  ('Laser Arena 1', 'img/neon5.png', 'libre', 'laser'),
  ('Laser Arena 2', 'img/neon6.png', 'libre', 'laser'),
  ('Laser Arena 3', 'img/neon7.png', 'libre', 'laser'),
  ('Laser Arena 4', 'img/neon8.png', 'libre', 'laser')
ON DUPLICATE KEY UPDATE `source` = VALUES(`source`), `type` = 'laser';

-- 6. Ensure standard places exist
INSERT INTO `place` (`nom`, `source`, `statut`, `type`)
SELECT * FROM (SELECT 'place 1' AS nom, 'img/place1.jpeg' AS source, 'libre' AS statut, 'standard' AS type
  UNION ALL SELECT 'place 2', 'img/place2.jpeg', 'libre', 'standard'
  UNION ALL SELECT 'place 3', 'img/place3.jpeg', 'libre', 'standard'
  UNION ALL SELECT 'place 4', 'img/place4.jpeg', 'libre', 'standard'
  UNION ALL SELECT 'place 5', 'img/place5.jpeg', 'libre', 'standard'
  UNION ALL SELECT 'place 6', 'img/place6.jpeg', 'libre', 'standard'
) AS new_places
WHERE NOT EXISTS (SELECT 1 FROM `place` WHERE `nom` = new_places.nom);

SELECT 'Migration complete! 4 Packs + 10 Places (6 standard + 4 laser) updated.' AS status;
