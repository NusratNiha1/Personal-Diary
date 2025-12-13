-- =====================================================
-- MySQL schema for Diary App (Enhanced for DBMS Project)
-- Complete Database Setup - Single File Import
-- =====================================================
-- Instructions:
-- 1. Drop existing database if you want fresh start: DROP DATABASE IF EXISTS diary_app;
-- 2. Import this entire file in phpMyAdmin SQL tab
-- 3. All tables, data, views, procedures, triggers will be created
-- =====================================================

DROP DATABASE IF EXISTS `diary_app`;
CREATE DATABASE `diary_app` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `diary_app`;

-- ======================
-- 1. USER MANAGEMENT
-- ======================

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
  role_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default roles
INSERT IGNORE INTO roles (role_id, role_name, description) VALUES
(1, 'Admin', 'Full system access and user management'),
(2, 'Premium', 'Enhanced features with social sharing'),
(3, 'User', 'Standard user with basic features');

-- Enhanced Users table
CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(100) NULL,
  email VARCHAR(100) NULL UNIQUE,
  date_of_birth DATE NULL,
  profile_pic VARCHAR(255) NULL,
  bio TEXT NULL,
  password VARCHAR(255) NOT NULL,
  security_question VARCHAR(255) NULL,
  security_answer VARCHAR(255) NULL,
  role_id INT UNSIGNED DEFAULT 3,
  preferences JSON NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Permissions table
CREATE TABLE IF NOT EXISTS permissions (
  permission_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  permission_name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT NULL
) ENGINE=InnoDB;

-- Insert default permissions
INSERT IGNORE INTO permissions (permission_name, description) VALUES
('create_entry', 'Create diary entries'),
('edit_entry', 'Edit own entries'),
('delete_entry', 'Delete own entries'),
('view_entry', 'View own entries'),
('share_entry', 'Share entries with others'),
('comment_entry', 'Comment on shared entries'),
('manage_users', 'Manage user accounts'),
('view_analytics', 'View analytics dashboard'),
('manage_categories', 'Create and manage categories'),
('export_data', 'Export diary data');

-- Role-Permission mapping
CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT UNSIGNED,
  permission_id INT UNSIGNED,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Assign permissions to roles
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
-- Admin gets all permissions
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
-- Premium gets most permissions
(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 8), (2, 10),
-- User gets basic permissions
(3, 1), (3, 2), (3, 3), (3, 4);

-- User sessions (for tracking)
CREATE TABLE IF NOT EXISTS user_sessions (
  session_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  logout_time TIMESTAMP NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ======================
-- 2. ENTRY ORGANIZATION
-- ======================

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
  category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT NULL,
  color VARCHAR(7) DEFAULT '#6B7280',
  icon VARCHAR(50) NULL,
  created_by INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_categories_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert default categories
INSERT IGNORE INTO categories (category_name, description, color, icon) VALUES
('Personal', 'Personal thoughts and feelings', '#EC4899', '💭'),
('Work', 'Work-related entries', '#3B82F6', '💼'),
('Travel', 'Travel experiences and memories', '#10B981', '✈️'),
('Goals', 'Goals and achievements', '#F59E0B', '🎯'),
('Health', 'Health and wellness', '#EF4444', '❤️'),
('Gratitude', 'Things to be grateful for', '#8B5CF6', '🙏');

-- Tags table
CREATE TABLE IF NOT EXISTS tags (
  tag_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tag_name VARCHAR(50) NOT NULL UNIQUE,
  usage_count INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Enhanced Entries table
CREATE TABLE IF NOT EXISTS entries (
  entry_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  content TEXT NOT NULL,
  mood VARCHAR(30) NULL,
  music_link VARCHAR(500) NULL,
  location VARCHAR(100) NULL,
  weather VARCHAR(50) NULL,
  privacy_level ENUM('private', 'public') DEFAULT 'private',
  is_favorite BOOLEAN DEFAULT FALSE,
  is_deleted BOOLEAN DEFAULT FALSE,
  version INT UNSIGNED DEFAULT 1,
  word_count INT UNSIGNED DEFAULT 0,
  timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  CONSTRAINT fk_entries_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_entries_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
  FULLTEXT INDEX ft_entry_content (title, content)
) ENGINE=InnoDB;

-- Entry-Tag junction table
CREATE TABLE IF NOT EXISTS entry_tags (
  entry_id INT UNSIGNED,
  tag_id INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (entry_id, tag_id),
  CONSTRAINT fk_et_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE,
  CONSTRAINT fk_et_tag FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Media table (unchanged)
CREATE TABLE IF NOT EXISTS media (
  media_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  file_size INT UNSIGNED NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_media_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ======================
-- 3. SOCIAL FEATURES
-- ======================

-- Shared entries
CREATE TABLE IF NOT EXISTS shared_entries (
  share_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  shared_by INT UNSIGNED NOT NULL,
  shared_with INT UNSIGNED NOT NULL,
  can_comment BOOLEAN DEFAULT TRUE,
  shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL,
  CONSTRAINT fk_share_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE,
  CONSTRAINT fk_share_from FOREIGN KEY (shared_by) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_share_to FOREIGN KEY (shared_with) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_share (entry_id, shared_with)
) ENGINE=InnoDB;

-- Comments on entries
CREATE TABLE IF NOT EXISTS comments (
  comment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  comment_text TEXT NOT NULL,
  parent_comment_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_parent FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Reactions/Likes
CREATE TABLE IF NOT EXISTS reactions (
  reaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  reaction_type ENUM('like', 'love', 'insightful', 'inspiring') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reactions_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE,
  CONSTRAINT fk_reactions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_reaction (entry_id, user_id)
) ENGINE=InnoDB;

-- Followers system
CREATE TABLE IF NOT EXISTS followers (
  follower_id INT UNSIGNED,
  following_id INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (follower_id, following_id),
  CONSTRAINT fk_follower FOREIGN KEY (follower_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_following FOREIGN KEY (following_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ======================
-- 4. ANALYTICS & STATS
-- ======================

-- Entry statistics (summary table)
CREATE TABLE IF NOT EXISTS entry_stats (
  stat_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL UNIQUE,
  view_count INT UNSIGNED DEFAULT 0,
  share_count INT UNSIGNED DEFAULT 0,
  comment_count INT UNSIGNED DEFAULT 0,
  reaction_count INT UNSIGNED DEFAULT 0,
  last_viewed TIMESTAMP NULL,
  CONSTRAINT fk_stats_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User writing stats
CREATE TABLE IF NOT EXISTS user_stats (
  user_id INT UNSIGNED PRIMARY KEY,
  total_entries INT UNSIGNED DEFAULT 0,
  total_words INT UNSIGNED DEFAULT 0,
  longest_streak INT UNSIGNED DEFAULT 0,
  current_streak INT UNSIGNED DEFAULT 0,
  avg_words_per_entry DECIMAL(10, 2) DEFAULT 0,
  most_common_mood VARCHAR(30) NULL,
  last_entry_date DATE NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user_stats FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Mood tracking over time
CREATE TABLE IF NOT EXISTS mood_history (
  mood_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  mood VARCHAR(30) NOT NULL,
  entry_date DATE NOT NULL,
  entry_count INT UNSIGNED DEFAULT 1,
  CONSTRAINT fk_mood_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_mood_date (user_id, mood, entry_date)
) ENGINE=InnoDB;

-- ======================
-- 5. VERSION CONTROL
-- ======================

-- Entry versions (for history tracking)
CREATE TABLE IF NOT EXISTS entry_versions (
  version_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  version_number INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  content TEXT NOT NULL,
  mood VARCHAR(30) NULL,
  modified_by INT UNSIGNED NOT NULL,
  modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  change_description VARCHAR(255) NULL,
  CONSTRAINT fk_versions_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE,
  CONSTRAINT fk_versions_user FOREIGN KEY (modified_by) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_version (entry_id, version_number)
) ENGINE=InnoDB;

-- Audit log for tracking changes
CREATE TABLE IF NOT EXISTS audit_log (
  log_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action_type ENUM('CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'SHARE', 'COMMENT') NOT NULL,
  table_name VARCHAR(50) NOT NULL,
  record_id INT UNSIGNED NOT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ======================
-- 6. INDEXES FOR PERFORMANCE
-- ======================

-- User indexes
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_active ON users(is_active);

-- Entry indexes
CREATE INDEX idx_entries_user ON entries(user_id);
CREATE INDEX idx_entries_category ON entries(category_id);
CREATE INDEX idx_entries_timestamp ON entries(timestamp);
CREATE INDEX idx_entries_mood ON entries(mood);
CREATE INDEX idx_entries_privacy ON entries(privacy_level);
CREATE INDEX idx_entries_deleted ON entries(is_deleted);
CREATE INDEX idx_entries_favorite ON entries(is_favorite);
CREATE INDEX idx_entries_user_timestamp ON entries(user_id, timestamp DESC);

-- Tag indexes
CREATE INDEX idx_tags_name ON tags(tag_name);
CREATE INDEX idx_tags_usage ON tags(usage_count DESC);

-- Media indexes
CREATE INDEX idx_media_entry ON media(entry_id);
CREATE INDEX idx_media_type ON media(file_type);

-- Social feature indexes
CREATE INDEX idx_shared_to ON shared_entries(shared_with);
CREATE INDEX idx_shared_from ON shared_entries(shared_by);
CREATE INDEX idx_comments_entry ON comments(entry_id);
CREATE INDEX idx_comments_user ON comments(user_id);
CREATE INDEX idx_reactions_entry ON reactions(entry_id);

-- Analytics indexes
CREATE INDEX idx_mood_history_user ON mood_history(user_id, entry_date);
CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_audit_action ON audit_log(action_type, created_at);

-- Version indexes
CREATE INDEX idx_versions_entry ON entry_versions(entry_id);
CREATE INDEX idx_sessions_user ON user_sessions(user_id);

-- ==============================================
-- 7. SAMPLE DATA POPULATION
-- ==============================================

-- Insert sample users with various roles
-- Password for all users: "password123"
-- Security answer for all users: "blue"

INSERT INTO users (username, full_name, email, password, security_question, security_answer, role_id, is_active, created_at) VALUES
-- Admin User
('admin', 'Admin User', 'admin@diary.com', 
 'password123', 
 'What is your favorite color?', 
 'blue', 
 1, TRUE, '2024-01-15 10:00:00'),

-- Premium Users
('john_doe', 'John Doe', 'john@example.com', 
 'password123', 
 'What is your favorite color?', 
 'blue', 
 2, TRUE, '2024-02-20 14:30:00'),

('jane_smith', 'Jane Smith', 'jane@example.com', 
 'password123', 
 'What is your favorite color?', 
 'blue', 
 2, TRUE, '2024-03-10 09:15:00'),

-- Regular Users
('alice_wonder', 'Alice Wonder', 'alice@example.com', 
 'password123', 
 'What is your favorite color?', 
 'blue', 
 3, TRUE, '2024-04-05 16:45:00'),

('bob_builder', 'Bob Builder', 'bob@example.com', 
 'password123', 
 'What is your favorite color?', 
 'blue', 
 3, TRUE, '2024-05-12 11:20:00'),

('charlie_brown', 'Charlie Brown', 'charlie@example.com', 
 'password123', 
 'What is your favorite color?', 
 'blue', 
 3, TRUE, '2024-06-18 08:00:00'),

('diana_prince', 'Diana Prince', 'diana@example.com', 
 'password123', 
 'What is your favorite color?', 
 'blue', 
 3, TRUE, '2024-07-22 13:30:00'),

('emma_watson', 'Emma Watson', 'emma@example.com', 
 'password123', 
 'What is your favorite color?', 
 'blue', 
 3, TRUE, '2024-08-14 10:45:00');

-- Insert sample diary entries for demonstration
INSERT INTO entries (user_id, category_id, title, content, mood, location, privacy_level, timestamp) VALUES
-- Admin's entries
(1, 1, 'First Day as Admin', 'Today I set up the entire diary system. It feels great to have everything organized and ready for users.', 'Happy', 'New York, USA', 'private', '2024-01-15 18:00:00'),
(1, 2, 'System Launch', 'Successfully launched the diary application. All features are working perfectly!', 'Excited', 'New York, USA', 'public', '2024-01-20 14:30:00'),

-- John's entries
(2, 3, 'Trip to Paris', 'Amazing day exploring the Eiffel Tower and trying authentic French cuisine. The city is magical!', 'Excited', 'Paris, France', 'public', '2024-03-15 20:00:00'),
(2, 4, 'New Year Goals', 'Setting ambitious goals for 2024: Read 50 books, learn Spanish, and run a marathon. Let''s do this!', 'Reflective', 'Boston, USA', 'private', '2024-01-01 09:00:00'),
(2, 1, 'Sunday Thoughts', 'Spent the day reflecting on life and relationships. Feeling grateful for family and friends.', 'Calm', 'Boston, USA', 'private', '2024-02-25 16:00:00'),

-- Jane's entries
(3, 5, 'Morning Yoga', 'Started my day with 30 minutes of yoga. Feeling centered and energized!', 'Calm', 'Los Angeles, USA', 'public', '2024-04-10 07:00:00'),
(3, 6, 'Grateful Heart', 'Today I''m grateful for: my health, supportive friends, and this beautiful sunshine.', 'Happy', 'Los Angeles, USA', 'public', '2024-04-15 12:00:00'),
(3, 2, 'Project Deadline', 'Working late to finish the presentation. Stressful but I know I can do it!', 'Angry', 'Los Angeles, USA', 'private', '2024-04-20 22:00:00'),

-- Alice's entries
(4, 1, 'Childhood Memories', 'Found old photo albums today. So many wonderful memories flooding back!', 'Reflective', 'Seattle, USA', 'private', '2024-05-01 14:00:00'),
(4, 3, 'Beach Weekend', 'Perfect beach day with friends. Sun, sand, and laughter - what more could I ask for?', 'Happy', 'Santa Monica, USA', 'public', '2024-05-15 18:00:00'),

-- Bob's entries
(5, 2, 'Career Milestone', 'Got promoted today! All the hard work has paid off. Celebrating with the team tonight.', 'Excited', 'Chicago, USA', 'public', '2024-06-01 17:00:00'),
(5, 4, 'Learning Guitar', 'Week 3 of guitar lessons. My fingers hurt but I''m making progress!', 'Happy', 'Chicago, USA', 'private', '2024-06-10 20:00:00'),

-- Charlie's entries
(6, 1, 'Rainy Day Blues', 'Sometimes we all need a quiet day at home. Reading books and drinking tea.', 'Calm', 'Portland, USA', 'private', '2024-07-05 11:00:00'),
(6, 5, 'Marathon Training', 'Completed 10 miles today! My longest run yet. Exhausted but proud.', 'Excited', 'Portland, USA', 'public', '2024-07-20 07:00:00'),

-- Diana's entries
(7, 6, 'Thankful Thursday', 'Grateful for: morning coffee, good books, and cozy evenings at home.', 'Happy', 'Austin, USA', 'public', '2024-08-01 19:00:00'),
(7, 1, 'Family Visit', 'Parents came to visit. Nothing beats home-cooked meals and family stories.', 'Happy', 'Austin, USA', 'private', '2024-08-15 21:00:00'),

-- Emma's entries
(8, 3, 'Mountain Hiking', 'Conquered my first 14er today! The view from the summit was breathtaking.', 'Excited', 'Denver, USA', 'public', '2024-09-01 16:00:00'),
(8, 2, 'New Project Kickoff', 'Starting a new project at work. Nervous but excited about the challenges ahead.', 'Reflective', 'Denver, USA', 'private', '2024-09-10 09:00:00');

-- Create some sample tags
INSERT INTO tags (tag_name, usage_count) VALUES
('travel', 4),
('goals', 3),
('fitness', 3),
('gratitude', 3),
('work', 4),
('family', 2),
('adventure', 3),
('reflection', 4),
('health', 2),
('celebration', 2);

-- Associate tags with entries
INSERT INTO entry_tags (entry_id, tag_id) VALUES
-- Admin entries
(1, 8), (2, 10),
-- John entries
(3, 1), (3, 7), (4, 2), (5, 8),
-- Jane entries
(6, 4), (6, 9), (7, 4), (8, 5),
-- Alice entries
(9, 8), (9, 6), (10, 1), (10, 7),
-- Bob entries
(11, 5), (11, 10), (12, 2), (12, 4),
-- Charlie entries
(13, 8), (14, 4), (14, 9),
-- Diana entries
(15, 4), (15, 6), (16, 6),
-- Emma entries
(17, 1), (17, 7), (17, 4), (18, 5), (18, 8);

-- Add some media to entries (image URLs)
INSERT INTO media (entry_id, file_path, file_type, file_size) VALUES
(3, 'https://picsum.photos/seed/paris/800/600', 'image/jpeg', 102400),
(6, 'https://picsum.photos/seed/yoga/800/600', 'image/jpeg', 98304),
(10, 'https://picsum.photos/seed/beach/800/600', 'image/jpeg', 115200),
(14, 'https://picsum.photos/seed/marathon/800/600', 'image/jpeg', 108000),
(17, 'https://picsum.photos/seed/mountain/800/600', 'image/jpeg', 125000);

-- Initialize user stats for all users
INSERT INTO user_stats (user_id, total_entries, total_words, current_streak, longest_streak, avg_words_per_entry, most_common_mood, last_entry_date) VALUES
(1, 2, 250, 1, 2, 125.00, 'Happy', '2024-01-20'),
(2, 3, 400, 2, 3, 133.33, 'Excited', '2024-03-15'),
(3, 3, 320, 1, 2, 106.67, 'Calm', '2024-04-20'),
(4, 2, 280, 1, 1, 140.00, 'Happy', '2024-05-15'),
(5, 2, 260, 1, 2, 130.00, 'Excited', '2024-06-10'),
(6, 2, 240, 1, 1, 120.00, 'Calm', '2024-07-20'),
(7, 2, 220, 1, 2, 110.00, 'Happy', '2024-08-15'),
(8, 2, 300, 1, 1, 150.00, 'Excited', '2024-09-10');

-- Initialize entry stats
INSERT INTO entry_stats (entry_id, view_count, share_count, comment_count, reaction_count) VALUES
(1, 5, 0, 0, 0), (2, 12, 1, 0, 2), (3, 25, 3, 1, 5),
(4, 8, 0, 0, 1), (5, 6, 0, 0, 0), (6, 18, 2, 0, 3),
(7, 15, 1, 0, 2), (8, 7, 0, 0, 0), (9, 10, 0, 0, 1),
(10, 22, 2, 1, 4), (11, 30, 3, 2, 6), (12, 9, 0, 0, 1),
(13, 5, 0, 0, 0), (14, 20, 2, 0, 3), (15, 16, 1, 0, 2),
(16, 8, 0, 0, 1), (17, 35, 4, 2, 7), (18, 11, 0, 0, 1);

-- ======================
-- 8. VIEWS
-- ======================

-- View: User dashboard summary
CREATE OR REPLACE VIEW v_user_dashboard AS
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    r.role_name,
    us.total_entries,
    us.total_words,
    us.current_streak,
    us.most_common_mood,
    us.last_entry_date,
    COUNT(DISTINCT f1.following_id) AS following_count,
    COUNT(DISTINCT f2.follower_id) AS follower_count
FROM users u
LEFT JOIN roles r ON u.role_id = r.role_id
LEFT JOIN user_stats us ON u.user_id = us.user_id
LEFT JOIN followers f1 ON u.user_id = f1.follower_id
LEFT JOIN followers f2 ON u.user_id = f2.following_id
GROUP BY u.user_id;

-- View: Entry details with all related info
CREATE OR REPLACE VIEW v_entry_details AS
SELECT 
    e.entry_id,
    e.user_id,
    u.username,
    e.title,
    e.content,
    e.mood,
    e.music_link,
    e.location,
    e.weather,
    e.privacy_level,
    e.is_favorite,
    e.word_count,
    e.timestamp,
    c.category_name,
    c.color AS category_color,
    c.icon AS category_icon,
    GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags,
    COUNT(DISTINCT m.media_id) AS media_count,
    COALESCE(es.view_count, 0) AS view_count,
    COALESCE(es.share_count, 0) AS share_count,
    COALESCE(es.comment_count, 0) AS comment_count,
    COALESCE(es.reaction_count, 0) AS reaction_count
FROM entries e
JOIN users u ON e.user_id = u.user_id
LEFT JOIN categories c ON e.category_id = c.category_id
LEFT JOIN entry_tags et ON e.entry_id = et.entry_id
LEFT JOIN tags t ON et.tag_id = t.tag_id
LEFT JOIN media m ON e.entry_id = m.entry_id
LEFT JOIN entry_stats es ON e.entry_id = es.entry_id
WHERE e.is_deleted = FALSE
GROUP BY e.entry_id;

-- View: Mood trends over time
CREATE OR REPLACE VIEW v_mood_trends AS
SELECT 
    user_id,
    mood,
    DATE_FORMAT(entry_date, '%Y-%m') AS month,
    SUM(entry_count) AS total_entries,
    AVG(entry_count) AS avg_entries_per_day
FROM mood_history
GROUP BY user_id, mood, DATE_FORMAT(entry_date, '%Y-%m');

-- View: Popular tags
CREATE OR REPLACE VIEW v_popular_tags AS
SELECT 
    t.tag_id,
    t.tag_name,
    t.usage_count,
    COUNT(DISTINCT et.entry_id) AS active_entries,
    COUNT(DISTINCT e.user_id) AS unique_users
FROM tags t
LEFT JOIN entry_tags et ON t.tag_id = et.tag_id
LEFT JOIN entries e ON et.entry_id = e.entry_id AND e.is_deleted = FALSE
GROUP BY t.tag_id
ORDER BY t.usage_count DESC;

-- View: Shared entries summary
CREATE OR REPLACE VIEW v_shared_entries AS
SELECT 
    se.share_id,
    se.entry_id,
    e.title,
    e.mood,
    u1.username AS shared_by_username,
    u1.full_name AS shared_by_name,
    u2.username AS shared_with_username,
    u2.full_name AS shared_with_name,
    se.shared_at,
    se.can_comment,
    COUNT(c.comment_id) AS comment_count
FROM shared_entries se
JOIN entries e ON se.entry_id = e.entry_id
JOIN users u1 ON se.shared_by = u1.user_id
JOIN users u2 ON se.shared_with = u2.user_id
LEFT JOIN comments c ON e.entry_id = c.entry_id
WHERE e.is_deleted = FALSE
GROUP BY se.share_id;

-- ======================
-- 8. STORED PROCEDURES
-- ======================

DELIMITER //

-- Procedure: Calculate and update user statistics
CREATE PROCEDURE IF NOT EXISTS sp_update_user_stats(IN p_user_id INT UNSIGNED)
BEGIN
    DECLARE v_total_entries INT;
    DECLARE v_total_words INT;
    DECLARE v_avg_words DECIMAL(10, 2);
    DECLARE v_most_common_mood VARCHAR(30);
    DECLARE v_last_entry_date DATE;
    DECLARE v_current_streak INT DEFAULT 0;
    DECLARE v_longest_streak INT DEFAULT 0;
    
    -- Calculate basic stats
    SELECT 
        COUNT(*), 
        SUM(word_count), 
        AVG(word_count),
        MAX(DATE(timestamp))
    INTO v_total_entries, v_total_words, v_avg_words, v_last_entry_date
    FROM entries
    WHERE user_id = p_user_id AND is_deleted = FALSE;
    
    -- Find most common mood
    SELECT mood INTO v_most_common_mood
    FROM entries
    WHERE user_id = p_user_id AND is_deleted = FALSE AND mood IS NOT NULL
    GROUP BY mood
    ORDER BY COUNT(*) DESC
    LIMIT 1;
    
    -- Calculate writing streak
    CALL sp_calculate_streak(p_user_id, v_current_streak, v_longest_streak);
    
    -- Insert or update user stats
    INSERT INTO user_stats (
        user_id, total_entries, total_words, avg_words_per_entry,
        most_common_mood, last_entry_date, current_streak, longest_streak
    ) VALUES (
        p_user_id, v_total_entries, v_total_words, v_avg_words,
        v_most_common_mood, v_last_entry_date, v_current_streak, v_longest_streak
    )
    ON DUPLICATE KEY UPDATE
        total_entries = v_total_entries,
        total_words = v_total_words,
        avg_words_per_entry = v_avg_words,
        most_common_mood = v_most_common_mood,
        last_entry_date = v_last_entry_date,
        current_streak = v_current_streak,
        longest_streak = v_longest_streak;
END//

-- Procedure: Calculate writing streak
CREATE PROCEDURE IF NOT EXISTS sp_calculate_streak(
    IN p_user_id INT UNSIGNED,
    OUT p_current_streak INT,
    OUT p_longest_streak INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_entry_date DATE;
    DECLARE v_prev_date DATE DEFAULT NULL;
    DECLARE v_temp_streak INT DEFAULT 0;
    
    DECLARE date_cursor CURSOR FOR
        SELECT DISTINCT DATE(timestamp) AS entry_date
        FROM entries
        WHERE user_id = p_user_id AND is_deleted = FALSE
        ORDER BY entry_date DESC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    SET p_current_streak = 0;
    SET p_longest_streak = 0;
    
    OPEN date_cursor;
    
    read_loop: LOOP
        FETCH date_cursor INTO v_entry_date;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        IF v_prev_date IS NULL THEN
            -- First entry
            SET v_temp_streak = 1;
            IF v_entry_date = CURDATE() OR v_entry_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN
                SET p_current_streak = 1;
            END IF;
        ELSEIF DATEDIFF(v_prev_date, v_entry_date) = 1 THEN
            -- Consecutive day
            SET v_temp_streak = v_temp_streak + 1;
            IF p_current_streak > 0 THEN
                SET p_current_streak = p_current_streak + 1;
            END IF;
        ELSE
            -- Streak broken
            IF v_temp_streak > p_longest_streak THEN
                SET p_longest_streak = v_temp_streak;
            END IF;
            SET v_temp_streak = 1;
        END IF;
        
        SET v_prev_date = v_entry_date;
    END LOOP;
    
    CLOSE date_cursor;
    
    -- Final check for longest streak
    IF v_temp_streak > p_longest_streak THEN
        SET p_longest_streak = v_temp_streak;
    END IF;
END//

-- Procedure: Get mood distribution for a user
CREATE PROCEDURE IF NOT EXISTS sp_get_mood_distribution(
    IN p_user_id INT UNSIGNED,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT 
        mood,
        COUNT(*) AS entry_count,
        ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) AS percentage
    FROM entries
    WHERE user_id = p_user_id 
        AND is_deleted = FALSE
        AND mood IS NOT NULL
        AND DATE(timestamp) BETWEEN p_start_date AND p_end_date
    GROUP BY mood
    ORDER BY entry_count DESC;
END//

-- Procedure: Get writing activity calendar
CREATE PROCEDURE IF NOT EXISTS sp_get_writing_calendar(
    IN p_user_id INT UNSIGNED,
    IN p_year INT
)
BEGIN
    SELECT 
        DATE(timestamp) AS entry_date,
        COUNT(*) AS entry_count,
        SUM(word_count) AS total_words,
        GROUP_CONCAT(DISTINCT mood ORDER BY mood SEPARATOR ', ') AS moods
    FROM entries
    WHERE user_id = p_user_id
        AND YEAR(timestamp) = p_year
        AND is_deleted = FALSE
    GROUP BY DATE(timestamp)
    ORDER BY entry_date;
END//

-- Procedure: Soft delete entry with audit
CREATE PROCEDURE IF NOT EXISTS sp_soft_delete_entry(
    IN p_entry_id INT UNSIGNED,
    IN p_user_id INT UNSIGNED
)
BEGIN
    DECLARE v_old_data JSON;
    
    -- Get current entry data
    SELECT JSON_OBJECT(
        'title', title,
        'content', content,
        'mood', mood,
        'category_id', category_id
    ) INTO v_old_data
    FROM entries
    WHERE entry_id = p_entry_id AND user_id = p_user_id;
    
    -- Soft delete the entry
    UPDATE entries
    SET is_deleted = TRUE, deleted_at = NOW()
    WHERE entry_id = p_entry_id AND user_id = p_user_id;
    
    -- Log the deletion
    INSERT INTO audit_log (user_id, action_type, table_name, record_id, old_values)
    VALUES (p_user_id, 'DELETE', 'entries', p_entry_id, v_old_data);
END//

-- Procedure: Share entry with user
CREATE PROCEDURE IF NOT EXISTS sp_share_entry(
    IN p_entry_id INT UNSIGNED,
    IN p_shared_by INT UNSIGNED,
    IN p_shared_with INT UNSIGNED,
    IN p_can_comment BOOLEAN
)
BEGIN
    -- Insert share record
    INSERT INTO shared_entries (entry_id, shared_by, shared_with, can_comment)
    VALUES (p_entry_id, p_shared_by, p_shared_with, p_can_comment)
    ON DUPLICATE KEY UPDATE can_comment = p_can_comment;
    
    -- Update entry stats
    UPDATE entry_stats
    SET share_count = share_count + 1
    WHERE entry_id = p_entry_id;
    
    -- Insert if not exists
    INSERT IGNORE INTO entry_stats (entry_id, share_count)
    VALUES (p_entry_id, 1);
    
    -- Log the share action
    INSERT INTO audit_log (user_id, action_type, table_name, record_id, new_values)
    VALUES (p_shared_by, 'SHARE', 'entries', p_entry_id, 
            JSON_OBJECT('shared_with', p_shared_with));
END//

DELIMITER ;

-- ======================
-- 9. TRIGGERS
-- ======================

DELIMITER //

-- Trigger: Update word count before insert
CREATE TRIGGER IF NOT EXISTS trg_entry_word_count_insert
BEFORE INSERT ON entries
FOR EACH ROW
BEGIN
    SET NEW.word_count = (
        LENGTH(TRIM(NEW.content)) - LENGTH(REPLACE(TRIM(NEW.content), ' ', '')) + 1
    );
END//

-- Trigger: Update word count before update
CREATE TRIGGER IF NOT EXISTS trg_entry_word_count_update
BEFORE UPDATE ON entries
FOR EACH ROW
BEGIN
    SET NEW.word_count = (
        LENGTH(TRIM(NEW.content)) - LENGTH(REPLACE(TRIM(NEW.content), ' ', '')) + 1
    );
END//

-- Trigger: Increment version before update
CREATE TRIGGER IF NOT EXISTS trg_increment_version
BEFORE UPDATE ON entries
FOR EACH ROW
BEGIN
    IF OLD.content != NEW.content OR OLD.title != NEW.title OR OLD.mood != NEW.mood THEN
        SET NEW.version = OLD.version + 1;
    END IF;
END//

-- Trigger: Create entry version after update
CREATE TRIGGER IF NOT EXISTS trg_create_entry_version
AFTER UPDATE ON entries
FOR EACH ROW
BEGIN
    IF OLD.content != NEW.content OR OLD.title != NEW.title OR OLD.mood != NEW.mood THEN
        INSERT INTO entry_versions (
            entry_id, version_number, title, content, mood, modified_by
        ) VALUES (
            NEW.entry_id, OLD.version, OLD.title, OLD.content, OLD.mood, NEW.user_id
        );
    END IF;
END//

-- Trigger: Update mood history after entry insert
CREATE TRIGGER IF NOT EXISTS trg_mood_history_insert
AFTER INSERT ON entries
FOR EACH ROW
BEGIN
    IF NEW.mood IS NOT NULL THEN
        INSERT INTO mood_history (user_id, mood, entry_date, entry_count)
        VALUES (NEW.user_id, NEW.mood, DATE(NEW.timestamp), 1)
        ON DUPLICATE KEY UPDATE entry_count = entry_count + 1;
    END IF;
END//

-- Trigger: Initialize entry stats
CREATE TRIGGER IF NOT EXISTS trg_init_entry_stats
AFTER INSERT ON entries
FOR EACH ROW
BEGIN
    INSERT INTO entry_stats (entry_id, view_count)
    VALUES (NEW.entry_id, 0);
END//

-- Trigger: Update tag usage count
CREATE TRIGGER IF NOT EXISTS trg_update_tag_usage
AFTER INSERT ON entry_tags
FOR EACH ROW
BEGIN
    UPDATE tags
    SET usage_count = usage_count + 1
    WHERE tag_id = NEW.tag_id;
END//

-- Trigger: Decrease tag usage count
CREATE TRIGGER IF NOT EXISTS trg_decrease_tag_usage
AFTER DELETE ON entry_tags
FOR EACH ROW
BEGIN
    UPDATE tags
    SET usage_count = usage_count - 1
    WHERE tag_id = OLD.tag_id;
END//

-- Trigger: Update comment count in entry stats
CREATE TRIGGER IF NOT EXISTS trg_update_comment_count
AFTER INSERT ON comments
FOR EACH ROW
BEGIN
    UPDATE entry_stats
    SET comment_count = comment_count + 1
    WHERE entry_id = NEW.entry_id;
END//

-- Trigger: Update reaction count
CREATE TRIGGER IF NOT EXISTS trg_update_reaction_count
AFTER INSERT ON reactions
FOR EACH ROW
BEGIN
    UPDATE entry_stats
    SET reaction_count = reaction_count + 1
    WHERE entry_id = NEW.entry_id;
END//

-- Trigger: Log user login
CREATE TRIGGER IF NOT EXISTS trg_log_user_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.last_login != OLD.last_login THEN
        INSERT INTO audit_log (user_id, action_type, table_name, record_id)
        VALUES (NEW.user_id, 'LOGIN', 'users', NEW.user_id);
    END IF;
END//

DELIMITER ;

-- ======================
-- 10. FUNCTIONS
-- ======================

DELIMITER //

-- Function: Check if user has permission
CREATE FUNCTION IF NOT EXISTS fn_has_permission(
    p_user_id INT UNSIGNED,
    p_permission_name VARCHAR(50)
) RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_has_permission BOOLEAN;
    
    SELECT COUNT(*) > 0 INTO v_has_permission
    FROM users u
    JOIN role_permissions rp ON u.role_id = rp.role_id
    JOIN permissions p ON rp.permission_id = p.permission_id
    WHERE u.user_id = p_user_id 
        AND p.permission_name = p_permission_name
        AND u.is_active = TRUE;
    
    RETURN v_has_permission;
END//

-- Function: Get user's current writing streak
CREATE FUNCTION IF NOT EXISTS fn_get_current_streak(
    p_user_id INT UNSIGNED
) RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_streak INT DEFAULT 0;
    SELECT current_streak INTO v_streak
    FROM user_stats
    WHERE user_id = p_user_id;
    RETURN COALESCE(v_streak, 0);
END//

-- Function: Calculate entry readability score
CREATE FUNCTION IF NOT EXISTS fn_readability_score(
    p_content TEXT
) RETURNS DECIMAL(5, 2)
DETERMINISTIC
BEGIN
    DECLARE v_words INT;
    DECLARE v_sentences INT;
    DECLARE v_score DECIMAL(5, 2);
    
    SET v_words = LENGTH(TRIM(p_content)) - LENGTH(REPLACE(TRIM(p_content), ' ', '')) + 1;
    SET v_sentences = LENGTH(p_content) - LENGTH(REPLACE(p_content, '.', '')) + 
                      LENGTH(p_content) - LENGTH(REPLACE(p_content, '!', '')) +
                      LENGTH(p_content) - LENGTH(REPLACE(p_content, '?', ''));
    
    IF v_sentences = 0 THEN
        SET v_sentences = 1;
    END IF;
    
    -- Simple readability score based on average sentence length
    SET v_score = 100 - ((v_words / v_sentences) * 2);
    
    IF v_score < 0 THEN
        SET v_score = 0;
    ELSEIF v_score > 100 THEN
        SET v_score = 100;
    END IF;
    
    RETURN v_score;
END//

DELIMITER ;

