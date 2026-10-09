-- =====================================================================
--  Barangay Recipe Sharing Site — Database Schema
--  Target: MySQL 8.0.16+ (or MariaDB 10.4+)  ·  Engine: InnoDB  ·  utf8mb4
--  Run the whole file once (phpMyAdmin → Import, or `mysql -u root -p < recipe_db.sql`)
-- =====================================================================

DROP DATABASE IF EXISTS recipe_db;
CREATE DATABASE recipe_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE recipe_db;

-- ---------------------------------------------------------------------
-- 1. users — site members
--    password_hash stores the output of PHP password_hash() (never plain text)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  username       VARCHAR(30)   NOT NULL,
  email          VARCHAR(255)  NOT NULL,
  password_hash  VARCHAR(255)  NOT NULL,
  display_name   VARCHAR(60)   NOT NULL,
  created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email    (email),
  CONSTRAINT chk_users_username CHECK (CHAR_LENGTH(username) >= 3)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. categories — fixed list of recipe categories (seeded below)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name         VARCHAR(50)   NOT NULL,
  description  VARCHAR(255)  NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. recipes — one row per posted recipe
--    user_id     → who posted it (deleting a user deletes their recipes)
--    category_id → RESTRICT: a category in use cannot be deleted
--    updated_at  → NULL until the row is edited; MySQL fills it on UPDATE.
--                  "Edited" label = updated_at IS NOT NULL
-- ---------------------------------------------------------------------
CREATE TABLE recipes (
  id                 INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  user_id            INT UNSIGNED      NOT NULL,
  category_id        INT UNSIGNED      NOT NULL,
  title              VARCHAR(120)      NOT NULL,
  description        VARCHAR(500)      NOT NULL,
  steps              TEXT              NOT NULL,
  servings           TINYINT UNSIGNED  NULL,
  cook_time_minutes  SMALLINT UNSIGNED NULL,
  created_at         TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP         NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_recipes_user       (user_id),
  KEY idx_recipes_category   (category_id),
  KEY idx_recipes_created    (created_at),
  FULLTEXT KEY ft_recipes_search (title, description),

  CONSTRAINT fk_recipes_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_recipes_category
    FOREIGN KEY (category_id) REFERENCES categories (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  CONSTRAINT chk_recipes_title     CHECK (CHAR_LENGTH(title) >= 3),
  CONSTRAINT chk_recipes_servings  CHECK (servings IS NULL OR servings BETWEEN 1 AND 50),
  CONSTRAINT chk_recipes_cooktime  CHECK (cook_time_minutes IS NULL OR cook_time_minutes BETWEEN 1 AND 1440)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. ingredients — one row per ingredient line of a recipe
--    position keeps the order the member typed them in
--    deleting a recipe deletes its ingredients
-- ---------------------------------------------------------------------
CREATE TABLE ingredients (
  id         INT UNSIGNED       NOT NULL AUTO_INCREMENT,
  recipe_id  INT UNSIGNED       NOT NULL,
  position   SMALLINT UNSIGNED  NOT NULL,
  quantity   VARCHAR(50)        NULL,         -- e.g. "2 cups", "1/2 kilo", "to taste"
  name       VARCHAR(150)       NOT NULL,     -- e.g. "pork belly, cubed"

  PRIMARY KEY (id),
  UNIQUE KEY uq_ingredients_recipe_position (recipe_id, position),

  CONSTRAINT fk_ingredients_recipe
    FOREIGN KEY (recipe_id) REFERENCES recipes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT chk_ingredients_name CHECK (CHAR_LENGTH(name) >= 1)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. comments — members' feedback on a recipe
--    updated_at works like recipes.updated_at ("edited" label)
-- ---------------------------------------------------------------------
CREATE TABLE comments (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  recipe_id   INT UNSIGNED  NOT NULL,
  user_id     INT UNSIGNED  NOT NULL,
  body        VARCHAR(1000) NOT NULL,
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_comments_recipe_created (recipe_id, created_at),
  KEY idx_comments_user (user_id),

  CONSTRAINT fk_comments_recipe
    FOREIGN KEY (recipe_id) REFERENCES recipes (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comments_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,

  CONSTRAINT chk_comments_body CHECK (CHAR_LENGTH(body) >= 1)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. favorites — pivot table: which member saved which recipe (M:N)
--    Composite primary key = a member can save a recipe only once
-- ---------------------------------------------------------------------
CREATE TABLE favorites (
  user_id     INT UNSIGNED  NOT NULL,
  recipe_id   INT UNSIGNED  NOT NULL,
  name        VARCHAR(120)  NULL,
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id, recipe_id),
  KEY idx_favorites_recipe (recipe_id),

  CONSTRAINT fk_favorites_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_favorites_recipe
    FOREIGN KEY (recipe_id) REFERENCES recipes (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
--  SEED DATA — starting categories (required)
--  Theme: Filipino home cooking. Rename freely to fit your own theme.
-- =====================================================================
INSERT INTO categories (name, description) VALUES
  ('Ulam',        'Main dishes served with rice'),
  ('Sabaw',       'Soups and stews'),
  ('Gulay',       'Vegetable dishes'),
  ('Almusal',     'Breakfast favorites'),
  ('Merienda',    'Afternoon snacks'),
  ('Kakanin',     'Rice cakes and native delicacies'),
  ('Panghimagas', 'Desserts and sweets'),
  ('Inumin',      'Drinks and refreshments');

-- =====================================================================
--  DEMO DATA (optional — delete this section if you don't want it)
--  Both demo accounts use the password:  Lutong@2026
-- =====================================================================
INSERT INTO users (username, email, password_hash, display_name) VALUES
  ('aling_nena', 'nena@barangay.test',
   '$2y$10$c2D4Wp7FBSWfy6y3LylXWOC/MXosyURUkNZEhM3MBuDgwZFqsaozm', 'Aling Nena'),
  ('mang_tonyo', 'tonyo@barangay.test',
   '$2y$10$c2D4Wp7FBSWfy6y3LylXWOC/MXosyURUkNZEhM3MBuDgwZFqsaozm', 'Mang Tonyo');

INSERT INTO recipes (user_id, category_id, title, description, steps, servings, cook_time_minutes) VALUES
  (1, 1, 'Pork Adobo',
   'Classic soy-and-vinegar braised pork, the way Lola made it.',
   'Combine pork, soy sauce, vinegar, garlic, bay leaves and peppercorns.\nMarinate for 30 minutes.\nSimmer covered for 40 minutes until tender.\nUncover and reduce the sauce until slightly thick.',
   4, 60),
  (2, 2, 'Sinigang na Hipon',
   'Sour tamarind shrimp soup loaded with vegetables.',
   'Boil water with tomatoes and onions.\nAdd tamarind base and radish; simmer 5 minutes.\nAdd shrimp and long beans; cook 3 minutes.\nAdd kangkong, season with fish sauce, and serve hot.',
   5, 30);

INSERT INTO ingredients (recipe_id, position, quantity, name) VALUES
  (1, 1, '1 kilo',  'pork belly, cubed'),
  (1, 2, '1/2 cup', 'soy sauce'),
  (1, 3, '1/3 cup', 'cane vinegar'),
  (1, 4, '1 head',  'garlic, crushed'),
  (1, 5, '3 pcs',   'bay leaves'),
  (1, 6, '1 tsp',   'whole peppercorns'),
  (2, 1, '1/2 kilo','shrimp'),
  (2, 2, '2 pcs',   'tomatoes, quartered'),
  (2, 3, '1 pc',    'onion, sliced'),
  (2, 4, '1 pack',  'tamarind soup base'),
  (2, 5, '1 pc',    'radish, sliced'),
  (2, 6, '1 bunch', 'kangkong'),
  (2, 7, 'to taste','fish sauce');

INSERT INTO comments (recipe_id, user_id, body) VALUES
  (1, 2, 'Tried this last Sunday, sobrang sarap! Added a bit of brown sugar.'),
  (2, 1, 'Perfect for the rainy season. Salamat, Mang Tonyo!');

INSERT INTO favorites (user_id, recipe_id, name)
SELECT 2, id, title FROM recipes WHERE id = 1
UNION ALL
SELECT 1, id, title FROM recipes WHERE id = 2;
