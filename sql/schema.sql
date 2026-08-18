-- ============================================================
--  11x11 — Football Portal :: Database schema (MySQL / MariaDB)
--  Charset: utf8mb4 for full unicode + emoji support
-- ============================================================

CREATE DATABASE IF NOT EXISTS `db11x11`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db11x11`;

-- ------------------------------------------------------------
--  USERS — иерархия ролей: owner / admin / author
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100)    NOT NULL DEFAULT 'Гость',
  `email`         VARCHAR(190)    NOT NULL,
  `password_hash` VARCHAR(255)    NOT NULL,
  `role`          ENUM('owner','admin','author') NOT NULL DEFAULT 'author',
  `avatar`        VARCHAR(255)    DEFAULT NULL,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  POSTS — статьи; статус draft / pending / published / rejected
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `posts` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(200)  NOT NULL,                 -- RU
  `title_en`    VARCHAR(200)  DEFAULT NULL,             -- EN
  `slug`        VARCHAR(220)  DEFAULT NULL,
  `excerpt`     VARCHAR(300)  DEFAULT NULL,             -- RU
  `excerpt_en`  VARCHAR(300)  DEFAULT NULL,             -- EN
  `body`        MEDIUMTEXT    NOT NULL,                 -- RU
  `body_en`     MEDIUMTEXT    DEFAULT NULL,             -- EN
  `category`    VARCHAR(60)   NOT NULL DEFAULT 'Новости',
  `cover_url`   VARCHAR(255)  DEFAULT NULL,
  `status`      ENUM('draft','pending','published','rejected') NOT NULL DEFAULT 'draft',
  `reject_note` VARCHAR(255)  DEFAULT NULL,
  `author_id`   INT UNSIGNED  NOT NULL,
  `views`       INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `published_at`TIMESTAMP     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_posts_status`   (`status`),
  KEY `idx_posts_author`   (`author_id`),
  KEY `idx_posts_category` (`category`),
  FULLTEXT KEY `ft_posts_search` (`title`,`excerpt`,`body`,`title_en`,`excerpt_en`,`body_en`),
  CONSTRAINT `fk_posts_author` FOREIGN KEY (`author_id`)
    REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  CATEGORIES — категории (двуязычные, с иконками); владелец редактирует
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(40)  NOT NULL,
  `name_ru`    VARCHAR(60)  NOT NULL,
  `name_en`    VARCHAR(60)  NOT NULL,
  `icon`       VARCHAR(16)  NOT NULL DEFAULT '⚽',
  `keywords`   TEXT         DEFAULT NULL,     -- для авто-подбора категории
  `sort_order` INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categories` (slug,name_ru,name_en,icon,keywords,sort_order) VALUES
 ('news','Новости','News','📰','',1),
 ('transfers','Трансферы','Transfers','🔄','transfer|move|signed|joins|loan|перешел|перешёл|подписал|трансфер|аренда',2),
 ('ucl','Лига чемпионов','Champions League','🏆','champions league|лига чемпионов',3),
 ('epl','АПЛ','Premier League','🦁','manchester|chelsea|arsenal|liverpool|tottenham|premier|манчестер|челси|арсенал|ливерпуль|апл',4),
 ('laliga','Ла Лига','La Liga','🇪🇸','barcelona|real madrid|atletico|sevilla|la liga|барселон|реал|атлетико|ла лига|родри|бускетс',5),
 ('seriea','Серия А','Serie A','🇮🇹','juventus|milan|inter|napoli|roma|serie a|ювентус|милан|интер|наполи|серия а',6),
 ('ligue1','Лига 1','Ligue 1','🇫🇷','psg|lens|marseille|monaco|ligue 1|псж|марсель|монако|лига 1|луис энрике',7),
 ('national','Сборные','National Teams','🌍','national team|manager|senegal|scotland|colombia|сборн|сенегал|шотланд|виейра',8),
 ('armenia','Армянский футбол','Armenian Football','🇦🇲','ararat|alashkert|noah|tiknizyan|spertsyan|armenia|арарат|алашкерт|ноа|тикнизян|сперцян|армени',9);

-- ------------------------------------------------------------
--  TRANSFERS — трансферный рынок
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transfers` (
  `id`         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `player`     VARCHAR(120)   NOT NULL,
  `from_club`  VARCHAR(120)   NOT NULL,
  `to_club`    VARCHAR(120)   NOT NULL,
  `fee`        DECIMAL(12,2)  NOT NULL DEFAULT 0.00,   -- сумма в млн €
  `status`     ENUM('rumour','negotiation','done','failed') NOT NULL DEFAULT 'rumour',
  `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transfers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Демо-данные для трансферов (можно удалить)
-- ------------------------------------------------------------
INSERT INTO `transfers` (`player`,`from_club`,`to_club`,`fee`,`status`) VALUES
  ('Florian Wirtz',   'Bayer 04',      'Real Madrid',   140.00, 'negotiation'),
  ('Victor Osimhen',  'Napoli',        'Chelsea',        95.00, 'rumour'),
  ('Alphonso Davies', 'Bayern',        'Real Madrid',    60.00, 'done'),
  ('Rafael Leão',     'AC Milan',      'PSG',            80.00, 'failed');

-- ------------------------------------------------------------
--  ВЛАДЕЛЕЦ создаётся автоматически при первом запуске
--  скриптом config/db.php (генерируется сложный пароль).
--  Здесь мы лишь резервируем email как заготовку —
--  реальный хэш проставит PHP-инициализатор.
-- ------------------------------------------------------------
