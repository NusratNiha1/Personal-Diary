-- MySQL schema for Diary App
-- Run this in phpMyAdmin or MySQL client

CREATE DATABASE IF NOT EXISTS `diary_app` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `diary_app`;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(100) NULL,
  date_of_birth DATE NULL,
  profile_pic VARCHAR(255) NULL,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Entries table
CREATE TABLE IF NOT EXISTS entries (
  entry_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  content TEXT NOT NULL,
  mood VARCHAR(30) NULL,
  timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_entries_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Media table
CREATE TABLE IF NOT EXISTS media (
  media_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  CONSTRAINT fk_media_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Indexes for performance
CREATE INDEX idx_entries_user ON entries(user_id);
CREATE INDEX idx_entries_timestamp ON entries(timestamp);
CREATE INDEX idx_entries_title ON entries(title);
CREATE INDEX idx_media_entry ON media(entry_id);
