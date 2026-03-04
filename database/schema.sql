-- FlatFinder schema (InfinityFree-compatible)
-- MySQL / MariaDB, utf8mb4.
-- Notes:
-- - On InfinityFree free hosting, foreign keys/triggers are typically unavailable; this schema avoids FKs.

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_username (username)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  display_name VARCHAR(120) NULL,
  name VARCHAR(120) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(50) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,

  nid_status ENUM('none','pending','approved','denied') NOT NULL DEFAULT 'none',
  nid_front_media_id INT UNSIGNED NULL,
  nid_back_media_id INT UNSIGNED NULL,
  nid_selfie_media_id INT UNSIGNED NULL,
  nid_reviewed_at DATETIME NULL,
  nid_denied_reason VARCHAR(255) NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_users_created (created_at),
  KEY idx_users_email (email),
  KEY idx_users_nid_status (nid_status)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS listings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,

  category_key VARCHAR(32) NULL,
  title VARCHAR(200) NULL,
  description TEXT NULL,

  lat DECIMAL(10,7) NULL,
  lng DECIMAL(10,7) NULL,
  price INT NULL,
  area VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  phone VARCHAR(50) NULL,

  is_hidden TINYINT(1) NOT NULL DEFAULT 0,
  hidden_reason VARCHAR(50) NULL,
  report_count INT UNSIGNED NOT NULL DEFAULT 0,
  likes_count INT UNSIGNED NOT NULL DEFAULT 0,
  spam_count INT UNSIGNED NOT NULL DEFAULT 0,

  status ENUM('pending','approved','published','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at DATETIME NULL,
  publish_at DATETIME NULL,
  rejected_at DATETIME NULL,
  rejected_reason VARCHAR(255) NULL,

  PRIMARY KEY (id),
  KEY idx_listings_status_created (status, created_at),
  KEY idx_listings_publish_at (publish_at),
  KEY idx_listings_user (user_id),
  KEY idx_listings_visible_lat_lng (status, is_hidden, lat, lng),
  KEY idx_listings_visible_category_price (status, is_hidden, category_key, price)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS listing_reports (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id INT UNSIGNED NOT NULL,
  reporter_user_id INT UNSIGNED NULL,
  reporter_ip_hash CHAR(64) NOT NULL,
  reason VARCHAR(80) NOT NULL,
  details VARCHAR(500) NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lr_listing_created (listing_id, created_at),
  KEY idx_lr_status_created (status, created_at),
  UNIQUE KEY uq_lr_listing_user_once (listing_id, reporter_user_id),
  UNIQUE KEY uq_lr_listing_ip_once (listing_id, reporter_ip_hash)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS listing_reactions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  reaction_type VARCHAR(20) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reactions (listing_id, user_id, reaction_type),
  KEY idx_reactions_listing_type (listing_id, reaction_type)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS listing_comments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  listing_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,
  body VARCHAR(1000) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_comments_listing_created (listing_id, created_at),
  KEY idx_comments_parent_id (parent_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NULL,
  email VARCHAR(190) NULL,
  subject VARCHAR(200) NULL,
  message TEXT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_messages_created (created_at),
  KEY idx_messages_read_created (is_read, created_at)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(64) NOT NULL,
  setting_value MEDIUMTEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS media (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  original_name VARCHAR(255) NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_media_uploaded (uploaded_at),
  KEY idx_media_active_uploaded (active, uploaded_at)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS media_links (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  media_id INT UNSIGNED NOT NULL,
  entity_type VARCHAR(60) NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  field_name VARCHAR(60) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ml_media (media_id),
  KEY idx_ml_entity (entity_type, entity_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
