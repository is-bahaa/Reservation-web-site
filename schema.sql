-- Geek Club fresh install schema
CREATE DATABASE IF NOT EXISTS bdgeek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bdgeek;

CREATE TABLE IF NOT EXISTS `client` (
  `id`     INT(11) NOT NULL AUTO_INCREMENT,
  `cn`     VARCHAR(8)  NOT NULL,
  `nom`    VARCHAR(50) NOT NULL,
  `prenom` VARCHAR(50) NOT NULL,
  `email`  VARCHAR(100) NOT NULL,
  `phone`  VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `place` (
  `id`     INT(11) NOT NULL AUTO_INCREMENT,
  `nom`    VARCHAR(30) NOT NULL,
  `source` VARCHAR(120) NOT NULL,
  `statut` ENUM('libre','reserve') NOT NULL DEFAULT 'libre',
  `type`   VARCHAR(20) NOT NULL DEFAULT 'standard',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pack` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(60)  NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `prix`        DECIMAL(8,2) NOT NULL,
  `image`       VARCHAR(120) NOT NULL,
  `activites`   VARCHAR(20)  NOT NULL DEFAULT '',
  `actif`       TINYINT(1)   NOT NULL DEFAULT 1,
  `duree`       INT(2)       NOT NULL DEFAULT 3,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reserver` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `nomparent`   VARCHAR(50) NOT NULL,
  `dateres`     DATE NOT NULL,
  `heureres`    TIME NOT NULL,
  `dateaniv`    DATE NOT NULL,
  `nomenfant`   VARCHAR(50) NOT NULL,
  `geekclub`    VARCHAR(20) NOT NULL DEFAULT '',
  `nbpersone`   INT(3) NOT NULL,
  `activite`    VARCHAR(20) NOT NULL,
  `deecoration` TEXT NOT NULL,
  `pack_id`     INT(11) NULL DEFAULT NULL,
  `place`       INT(11) NULL DEFAULT NULL,
  `client_id`   INT(11) NULL DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `place` (`place`),
  CONSTRAINT `pl` FOREIGN KEY (`place`) REFERENCES `place` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reserver_pack` FOREIGN KEY (`pack_id`) REFERENCES `pack` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reserver_client` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6 standard places (photos existantes)
INSERT INTO `place` (`nom`, `source`, `statut`, `type`) VALUES
  ('place 1',  'img/place1.jpeg',  'libre', 'standard'),
  ('place 2',  'img/place2.jpeg',  'libre', 'standard'),
  ('place 3',  'img/place3.jpeg',  'libre', 'standard'),
  ('place 4',  'img/place4.jpeg',  'libre', 'standard'),
  ('place 5',  'img/place5.jpeg',  'libre', 'standard'),
  ('place 6',  'img/place6.jpeg',  'libre', 'standard');

-- 4 laser tag places with UNIQUE neon photos
INSERT INTO `place` (`nom`, `source`, `statut`, `type`) VALUES
  ('Laser Arena 1', 'img/neon5.png', 'libre', 'laser'),
  ('Laser Arena 2', 'img/neon6.png', 'libre', 'laser'),
  ('Laser Arena 3', 'img/neon7.png', 'libre', 'laser'),
  ('Laser Arena 4', 'img/neon8.png', 'libre', 'laser');

-- 4 packs with unique images
INSERT INTO `pack` (`nom`, `description`, `prix`, `image`, `activites`, `duree`) VALUES
  ('Pack PS5',       'Console PS5, 2 manettes, ecran geant, 2h de jeu.',         60.00, 'img/pack-ps5.jpg',   'ps5',       3),
  ('Pack Parc',      'Acces illimite au parc d aventure pendant toute la fete.', 50.00, 'img/pack-parc.jpg',  'parc',      3),
  ('Pack Laser',     'Laser tag + PS5 + decoration neon + 2h de jeu.',           95.00, 'img/neon1.png', 'ps5-laser', 3),
  ('Pack Anniversaire', 'PS5 + Parc + decoration standard + gateau offert.',      120.00, 'img/neon10.png', 'ps5-parc', 3);
