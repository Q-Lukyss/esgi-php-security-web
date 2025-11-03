DROP DATABASE IF EXISTS potion_magique;
CREATE DATABASE potion_magique CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE potion_magique;


-- =====================================================================
--  Cocktails — Schéma MySQL/MariaDB (RBAC simple + public/private)
-- =====================================================================

-- Charset/collation recommandés
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_520_ci;

-- ---------------------------------------------------------------------
-- Utilisateurs (+ rôles)
-- Rôles gérés via ENUM simple : 'user' | 'premium' | 'admin'
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

-- (Optionnel) sessions/refresh tokens
CREATE TABLE user_sessions (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED NOT NULL,
  refresh_jti   CHAR(36)        NOT NULL,  -- UUID
  user_agent    VARCHAR(255)    NULL,
  ip_addr       VARBINARY(16)   NULL,      -- IPv4/IPv6
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at    DATETIME        NOT NULL,
  UNIQUE KEY uq_refresh (refresh_jti),
  KEY idx_user (user_id),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Cocktails
-- visibility: 'public' (visible à tous) | 'private' (visible auteur + admin)
-- Tri par date = created_at; updated_at pour modifs
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
  deleted_at     DATETIME        NULL,  -- soft delete (admin peut hard delete si tu veux)
  created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FULLTEXT KEY ftx_cocktails (name, description, instructions),
  KEY idx_author (author_id),
  KEY idx_visibility (visibility),
  KEY idx_created_at (created_at),

  CONSTRAINT fk_cocktails_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Ingrédients + association (quantité/ordre)
-- ---------------------------------------------------------------------
CREATE TABLE ingredients (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name        VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE cocktail_ingredients (
  cocktail_id   BIGINT UNSIGNED NOT NULL,
  ingredient_id BIGINT UNSIGNED NOT NULL,
  quantity      VARCHAR(60)  NULL,   -- "45", "1/2", etc.
  unit          VARCHAR(40)  NULL,   -- "ml", "dash", "barspoon"
  note          VARCHAR(120) NULL,   -- "pressé", "zeste", etc.
  position      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (cocktail_id, ingredient_id),
  KEY idx_ing (ingredient_id),
  CONSTRAINT fk_ci_cocktail   FOREIGN KEY (cocktail_id)   REFERENCES cocktails(id)   ON DELETE CASCADE,
  CONSTRAINT fk_ci_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tags (facultatif mais utile pour filtrer)
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
-- Likes (1 par user/cocktail)
-- NB: À appliquer côté app : ne pas autoriser like sur cocktail private
--     sauf éventuellement par l'auteur (selon ta règle produit).
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
-- Notes (ratings) — 1 par user/cocktail; modifiable/supprimable
-- NB: Même remarque que pour likes vis-à-vis des cocktails private.
-- ---------------------------------------------------------------------
CREATE TABLE cocktail_ratings (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NOT NULL,
  cocktail_id BIGINT UNSIGNED NOT NULL,
  rating      TINYINT UNSIGNED NOT NULL,      -- 1..5
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
-- Vues de stats — utiles pour tri par likes/note
-- public seulement (pour l’expo publique) + all (pour back-office)
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
-- Index full-text : recherche nom/description/instructions
-- (déjà défini plus haut via FULLTEXT)
-- ---------------------------------------------------------------------

-- =====================================================================
-- NOTES D’USAGE (côté application)
-- =====================================================================
-- * Rôles:
--   - visitor: pas de compte (lecture publique seulement)
--   - user: peut créer/éditer/supprimer SES cocktails (toujours visibility='public')
--   - premium: peut créer/éditer/supprimer SES cocktails (visibility='public' ou 'private')
--   - admin: accès total; peut supprimer/archiver/éditer n’importe quel cocktail
--
-- * Visibilité:
--   - public: visible par tous
--   - private: visible par l'auteur et les admins uniquement
--   - Likes/ratings: en pratique, n’autoriser que sur les cocktails PUBLICS
--     (enforcé côté API/service; la DB garde la flexibilité)
--
-- * Tri:
--   - Date: ORDER BY c.created_at DESC
--   - Likes: JOIN cocktail_public_stats ORDER BY likes_count DESC
--   - Note: JOIN cocktail_public_stats ORDER BY avg_rating DESC, ratings_count DESC
--
-- * Soft delete:
--   - Prévu via deleted_at sur cocktails. Admin peut hard-delete si besoin.
--
-- * Sécurité:
--   - Ownership check côté API: user/premium ne modifient/suppriment QUE leurs cocktails.
--   - Admin bypass ownership. Filtrer l’accès aux private (owner/admin).

-- ---------------------------------------------------------------------
-- DONNÉES DE DÉMO : UTILISATEURS
-- ---------------------------------------------------------------------

INSERT INTO users (username, email, password_hash, role, display_name, bio)
VALUES
('alex', 'alex@example.com', '12345', 'user', 'Alex Dupont', 'Amateur de mojitos maison'),
('lea', 'lea@example.com', '12345', 'user', 'Léa Martin', 'Passionnée de mixologie légère'),
('marc', 'marc@example.com', '12345', 'premium', 'Marc Lemoine', 'Créateur de recettes originales'),
('chloe', 'chloe@example.com', '12345', 'premium', 'Chloé Bernard', 'Fan de cocktails fruités et tropicaux'),
('admin', 'admin@cocktails.com', '12345', 'admin', 'Admin', 'Administrateur du site');

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
-- COCKTAILS (10 exemples)
-- 1-5 publics / 6-10 privés (premium)
-- ---------------------------------------------------------------------

INSERT INTO cocktails (author_id, slug, name, description, instructions, visibility)
VALUES
(1, 'mojito-classique', 'Mojito Classique', 'Le cocktail cubain par excellence.', 'Écraser la menthe, ajouter le rhum, le citron et le sucre, puis allonger avec de l’eau gazeuse.', 'public'),
(2, 'gin-tonic', 'Gin Tonic', 'Un grand classique, simple et frais.', 'Verser le gin sur la glace, compléter avec du tonic et garnir d’une rondelle de citron.', 'public'),
(3, 'daiquiri', 'Daiquiri', 'Un cocktail cubain à base de rhum, citron vert et sucre.', 'Secouer avec de la glace et servir filtré dans un verre à cocktail.', 'public'),
(4, 'pina-colada', 'Piña Colada', 'Un mélange exotique de rhum, ananas et coco.', 'Mixer les ingrédients avec de la glace et servir bien froid.', 'public'),
(3, 'old-fashioned', 'Old Fashioned', 'Un classique à base de whisky et bitters.', 'Dissoudre le sucre, ajouter whisky et bitters, puis garnir d’un zeste d’orange.', 'public'),

-- cocktails privés (premium seulement)
(3, 'rum-expresso', 'Rum Expresso', 'Une création caféinée au rhum brun.', 'Secouer rhum, café et sucre, servir dans un verre à martini.', 'private'),
(4, 'coconut-dream', 'Coconut Dream', 'Un cocktail doux et lacté à la noix de coco.', 'Mélanger lait de coco, rhum blanc et glace pilée.', 'private'),
(4, 'fraise-basilic', 'Fraise Basilic', 'Alliance fruitée et herbacée.', 'Écraser les fraises, ajouter basilic, gin et jus de citron.', 'private'),
(3, 'tequila-sunset', 'Tequila Sunset', 'Une variante du Tequila Sunrise avec un twist.', 'Superposer les couches et servir frais.', 'private'),
(3, 'spiced-ginger', 'Spiced Ginger', 'Rhum épicé, gingembre et citron.', 'Secouer et servir avec glace et zeste de citron.', 'private');

-- ---------------------------------------------------------------------
-- ASSOCIATIONS COCKTAIL ↔ INGRÉDIENTS
-- (simplifiées pour la démo)
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
