DROP DATABASE IF EXISTS potion_magique;
CREATE DATABASE potion_magique CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE potion_magique;

-- =====================================================================
--  Cocktails — Schéma MySQL/MariaDB (RBAC simple + public/private)
-- =====================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- ---------------------------------------------------------------------
-- Utilisateurs (+ rôles)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  username        VARCHAR(40)  NOT NULL UNIQUE,
  email           VARCHAR(190) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('user','premium','admin') NOT NULL DEFAULT 'user',
  display_name    VARCHAR(80)  NULL,
  bio             TEXT         NULL,
  avatar_url      VARCHAR(255) NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- (Optionnel) sessions/refresh tokens (si tu veux du refresh-jti plus tard)
CREATE TABLE user_sessions (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED NOT NULL,
  refresh_jti   CHAR(36)        NOT NULL,
  user_agent    VARCHAR(255)    NULL,
  ip_addr       VARBINARY(16)   NULL,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at    DATETIME        NOT NULL,
  UNIQUE KEY uq_refresh (refresh_jti),
  KEY idx_user (user_id),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Sessions "cookie sid" (utilisée par ton SecurityMiddleware + cookie)
-- ---------------------------------------------------------------------
CREATE TABLE sessions (
  sid        CHAR(64)        PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME        NOT NULL,
  KEY idx_user (user_id),
  KEY idx_expires (expires_at),
  CONSTRAINT fk_cookie_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Cocktails
-- ---------------------------------------------------------------------
CREATE TABLE cocktails (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  author_id      BIGINT UNSIGNED NOT NULL,
  slug           VARCHAR(140)    NOT NULL UNIQUE,
  name           VARCHAR(140)    NOT NULL,
  description    TEXT            NULL,
  instructions   TEXT            NULL,
  image_url      VARCHAR(255)    NULL,
  visibility     ENUM('public','private') NOT NULL DEFAULT 'public',
  deleted_at     DATETIME        NULL,
  created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FULLTEXT KEY ftx_cocktails (name, description, instructions),
  KEY idx_author (author_id),
  KEY idx_visibility (visibility),
  KEY idx_created_at (created_at),

  CONSTRAINT fk_cocktails_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Ingrédients + association
-- ---------------------------------------------------------------------
CREATE TABLE ingredients (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cocktail_ingredients (
  cocktail_id   BIGINT UNSIGNED NOT NULL,
  ingredient_id BIGINT UNSIGNED NOT NULL,
  quantity      VARCHAR(60)  NULL,
  unit          VARCHAR(40)  NULL,
  note          VARCHAR(120) NULL,
  position      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (cocktail_id, ingredient_id),
  KEY idx_ing (ingredient_id),
  CONSTRAINT fk_ci_cocktail   FOREIGN KEY (cocktail_id)   REFERENCES cocktails(id)   ON DELETE CASCADE,
  CONSTRAINT fk_ci_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tags
-- ---------------------------------------------------------------------
CREATE TABLE tags (
  id   BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cocktail_tags (
  cocktail_id BIGINT UNSIGNED NOT NULL,
  tag_id      BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (cocktail_id, tag_id),
  KEY idx_tag (tag_id),
  CONSTRAINT fk_ct_cocktail FOREIGN KEY (cocktail_id) REFERENCES cocktails(id) ON DELETE CASCADE,
  CONSTRAINT fk_ct_tag      FOREIGN KEY (tag_id)      REFERENCES tags(id)      ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Likes
-- ---------------------------------------------------------------------
CREATE TABLE cocktail_likes (
  user_id     BIGINT UNSIGNED NOT NULL,
  cocktail_id BIGINT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, cocktail_id),
  KEY idx_like_cocktail (cocktail_id),
  CONSTRAINT fk_like_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_like_cocktail FOREIGN KEY (cocktail_id) REFERENCES cocktails(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Notes
-- ---------------------------------------------------------------------
CREATE TABLE cocktail_ratings (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NOT NULL,
  cocktail_id BIGINT UNSIGNED NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL,
  comment     VARCHAR(500) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_cocktail (user_id, cocktail_id),
  KEY idx_rating_cocktail (cocktail_id),
  CONSTRAINT fk_rating_user     FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
  CONSTRAINT fk_rating_cocktail FOREIGN KEY (cocktail_id) REFERENCES cocktails(id) ON DELETE CASCADE,
  CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Vues de stats
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW cocktail_public_stats AS
SELECT
  c.id AS cocktail_id,
  COALESCE(l.likes_count, 0)    AS likes_count,
  COALESCE(r.avg_rating, 0)     AS avg_rating,
  COALESCE(r.ratings_count, 0)  AS ratings_count
FROM cocktails c
LEFT JOIN (
  SELECT cocktail_id, COUNT(*) AS likes_count
  FROM cocktail_likes
  GROUP BY cocktail_id
) l ON l.cocktail_id = c.id
LEFT JOIN (
  SELECT cocktail_id, AVG(rating) AS avg_rating, COUNT(*) AS ratings_count
  FROM cocktail_ratings
  GROUP BY cocktail_id
) r ON r.cocktail_id = c.id
WHERE c.deleted_at IS NULL
  AND c.visibility = 'public';

CREATE OR REPLACE VIEW cocktail_all_stats AS
SELECT
  c.id AS cocktail_id,
  COALESCE(l.likes_count, 0)    AS likes_count,
  COALESCE(r.avg_rating, 0)     AS avg_rating,
  COALESCE(r.ratings_count, 0)  AS ratings_count
FROM cocktails c
LEFT JOIN (
  SELECT cocktail_id, COUNT(*) AS likes_count
  FROM cocktail_likes
  GROUP BY cocktail_id
) l ON l.cocktail_id = c.id
LEFT JOIN (
  SELECT cocktail_id, AVG(rating) AS avg_rating, COUNT(*) AS ratings_count
  FROM cocktail_ratings
  GROUP BY cocktail_id
) r ON r.cocktail_id = c.id
WHERE c.deleted_at IS NULL;

-- ---------------------------------------------------------------------
-- DONNÉES DE DÉMO : UTILISATEURS
-- Mot de passe pour tous = 12345 (hash Argon2id)
-- ---------------------------------------------------------------------
INSERT INTO users (username, email, password_hash, role, display_name, bio)
VALUES
('alex', 'alex@example.com',
 '$argon2id$v=19$m=65536,t=4,p=1$oK+kOR72pKml3pWXddjz2w$MTLNY5/cR77WwtEHQXHO9v7gi1XQOr1aacQ9pktvAis',
 'user', 'Alex Dupont', 'Amateur de mojitos maison'),

('lea', 'lea@example.com',
 '$argon2id$v=19$m=65536,t=4,p=1$JpHYADJR4DPNCOesvC0qYA$HxfCsThPWZjJVchhJKHQpncFBBDF5KgS4I0bcgU4CpM',
 'user', 'Léa Martin', 'Passionnée de mixologie légère'),

('marc', 'marc@example.com',
 '$argon2id$v=19$m=65536,t=4,p=1$rj6/jAAY5/u5D2dqTiARaw$IFKU/IqCcnGxfkAfgtlB8sTnQZn4fbAf5OVMOyD+Nnw',
 'premium', 'Marc Lemoine', 'Créateur de recettes originales'),

('chloe', 'chloe@example.com',
 '$argon2id$v=19$m=65536,t=4,p=1$RaOfwqr+GmwOCp+p9JyX5w$CDR5igPcCygAGn8e9hF54q+w7CHgy6ZTRHdQRUWSrUU',
 'premium', 'Chloé Bernard', 'Fan de cocktails fruités et tropicaux'),

('admin', 'admin@cocktails.com',
 '$argon2id$v=19$m=65536,t=4,p=1$pr3jVjh6AYx9T6aEL0RxfQ$tjm5iMIIuxlegenuYdVcHBmoHydjSNIS4s7kxbdIbTA',
 'admin', 'Admin', 'Administrateur du site');

-- ---------------------------------------------------------------------
-- INGREDIENTS
-- ---------------------------------------------------------------------
INSERT INTO ingredients (name)
VALUES
('Rhum blanc'),
('Rhum ambré'),
('Gin'),
('Vodka'),
('Tequila'),
('Jus de citron vert'),
('Sirop de sucre'),
('Eau gazeuse'),
('Menthe fraîche'),
('Glace pilée'),
('Triple sec'),
('Vermouth rouge'),
('Campari'),
('Eau tonique');

-- ---------------------------------------------------------------------
-- COCKTAILS
-- ---------------------------------------------------------------------
INSERT INTO cocktails (author_id, slug, name, description, instructions, visibility)
VALUES
(1, 'mojito-classique', 'Mojito Classique', 'Le cocktail cubain par excellence.', 'Écraser la menthe, ajouter le rhum, le citron et le sucre, puis allonger avec de l’eau gazeuse.', 'public'),
(2, 'gin-tonic', 'Gin Tonic', 'Un grand classique, simple et frais.', 'Verser le gin sur la glace, compléter avec du tonic et garnir d’une rondelle de citron.', 'public'),
(3, 'daiquiri', 'Daiquiri', 'Un cocktail cubain à base de rhum, citron vert et sucre.', 'Secouer avec de la glace et servir filtré dans un verre à cocktail.', 'public'),
(4, 'pina-colada', 'Piña Colada', 'Un mélange exotique de rhum, ananas et coco.', 'Mixer les ingrédients avec de la glace et servir bien froid.', 'public'),
(3, 'old-fashioned', 'Old Fashioned', 'Un classique à base de whisky et bitters.', 'Dissoudre le sucre, ajouter whisky et bitters, puis garnir d’un zeste d’orange.', 'public'),
(3, 'rum-expresso', 'Rum Expresso', 'Une création caféinée au rhum brun.', 'Secouer rhum, café et sucre, servir dans un verre à martini.', 'private'),
(4, 'coconut-dream', 'Coconut Dream', 'Un cocktail doux et lacté à la noix de coco.', 'Mélanger lait de coco, rhum blanc et glace pilée.', 'private'),
(4, 'fraise-basilic', 'Fraise Basilic', 'Alliance fruitée et herbacée.', 'Écraser les fraises, ajouter basilic, gin et jus de citron.', 'private'),
(3, 'tequila-sunset', 'Tequila Sunset', 'Une variante du Tequila Sunrise avec un twist.', 'Superposer les couches et servir frais.', 'private'),
(3, 'spiced-ginger', 'Spiced Ginger', 'Rhum épicé, gingembre et citron.', 'Secouer et servir avec glace et zeste de citron.', 'private');

-- ---------------------------------------------------------------------
-- ASSOCIATIONS COCKTAIL ↔ INGRÉDIENTS
-- ---------------------------------------------------------------------
INSERT INTO cocktail_ingredients (cocktail_id, ingredient_id, quantity, unit)
VALUES
(1, 1, '50', 'ml'), (1, 6, '25', 'ml'), (1, 7, '20', 'ml'), (1, 8, '100', 'ml'), (1, 9, NULL, NULL),
(2, 3, '50', 'ml'), (2, 14, '100', 'ml'),
(3, 1, '45', 'ml'), (3, 6, '25', 'ml'), (3, 7, '15', 'ml'),
(4, 1, '40', 'ml'), (4, 6, '30', 'ml'),
(5, 1, '60', 'ml'),
(6, 2, '45', 'ml'), (6, 7, '10', 'ml'),
(7, 1, '40', 'ml'),
(8, 3, '50', 'ml'),
(9, 5, '50', 'ml'),
(10, 2, '40', 'ml');

-- ---------------------------------------------------------------------
-- LIKES
-- ---------------------------------------------------------------------
INSERT INTO cocktail_likes (user_id, cocktail_id)
VALUES
(1, 2), (1, 3), (2, 1), (2, 3), (3, 1), (3, 4), (4, 2), (4, 5), (4, 3);

-- ---------------------------------------------------------------------
-- NOTES (RATINGS)
-- ---------------------------------------------------------------------
INSERT INTO cocktail_ratings (user_id, cocktail_id, rating, comment)
VALUES
(1, 1, 5, 'Excellent équilibre !'),
(1, 3, 4, 'Très bon mais un peu sucré.'),
(2, 2, 5, 'Parfaitement frais.'),
(2, 3, 4, 'Un classique bien dosé.'),
(3, 4, 5, 'Goût tropical parfait.'),
(4, 5, 3, 'Un peu trop fort à mon goût.');
