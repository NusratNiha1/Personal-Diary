-- =====================================================
-- Simplified MySQL schema for Diary App
-- Covers only basic SQL topics (no stored procedures, triggers, functions)
-- All frontend features preserved using basic SQL queries
-- =====================================================

DROP DATABASE IF EXISTS `diary_app`;
CREATE DATABASE `diary_app` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `diary_app`;

-- ======================
-- 1. USER MANAGEMENT (Simplified)
-- ======================

-- Users table (simplified - removed roles, JSON, complex features)
CREATE TABLE users (
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
  user_role VARCHAR(20) DEFAULT 'User',
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL
) ENGINE=InnoDB;

-- ======================
-- 2. CATEGORIES
-- ======================

CREATE TABLE categories (
  category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT NULL,
  color VARCHAR(7) DEFAULT '#6B7280',
  icon VARCHAR(50) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default categories
INSERT INTO categories (category_name, description, color, icon) VALUES
('Personal', 'Personal thoughts and feelings', '#EC4899', '💭'),
('Work', 'Work-related entries', '#3B82F6', '💼'),
('Travel', 'Travel experiences and memories', '#10B981', '✈️'),
('Goals', 'Goals and achievements', '#F59E0B', '🎯'),
('Health', 'Health and wellness', '#EF4444', '❤️'),
('Gratitude', 'Things to be grateful for', '#8B5CF6', '🙏');

-- ======================
-- 3. TAGS
-- ======================

CREATE TABLE tags (
  tag_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tag_name VARCHAR(50) NOT NULL UNIQUE,
  usage_count INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ======================
-- 4. DIARY ENTRIES
-- ======================

CREATE TABLE entries (
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
  word_count INT UNSIGNED DEFAULT 0,
  timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  CONSTRAINT fk_entries_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_entries_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Indexes for performance
CREATE INDEX idx_entries_user ON entries(user_id);
CREATE INDEX idx_entries_category ON entries(category_id);
CREATE INDEX idx_entries_timestamp ON entries(timestamp);
CREATE INDEX idx_entries_mood ON entries(mood);
CREATE INDEX idx_entries_privacy ON entries(privacy_level);

-- ======================
-- 5. ENTRY-TAG JUNCTION TABLE
-- ======================

CREATE TABLE entry_tags (
  entry_id INT UNSIGNED,
  tag_id INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (entry_id, tag_id),
  CONSTRAINT fk_et_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE,
  CONSTRAINT fk_et_tag FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ======================
-- 6. MEDIA FILES
-- ======================

CREATE TABLE media (
  media_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_type VARCHAR(50) NOT NULL,
  file_size INT UNSIGNED NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_media_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ======================
-- 7. SOCIAL FEATURES (Simplified)
-- ======================

-- Shared entries
CREATE TABLE shared_entries (
  share_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  shared_by INT UNSIGNED NOT NULL,
  shared_with INT UNSIGNED NOT NULL,
  can_comment BOOLEAN DEFAULT TRUE,
  shared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_share_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE,
  CONSTRAINT fk_share_from FOREIGN KEY (shared_by) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_share_to FOREIGN KEY (shared_with) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_share (entry_id, shared_with)
) ENGINE=InnoDB;

CREATE TABLE reactions (
CREATE TABLE reactions (
  reaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  reaction_type ENUM('like', 'love', 'insightful', 'inspiring') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reactions_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE,
  CONSTRAINT fk_reactions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_reaction (entry_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE entry_stats (

-- Entry statistics
CREATE TABLE entry_stats (
  stat_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entry_id INT UNSIGNED NOT NULL UNIQUE,
  view_count INT UNSIGNED DEFAULT 0,
  share_count INT UNSIGNED DEFAULT 0,
  reaction_count INT UNSIGNED DEFAULT 0,
  last_viewed TIMESTAMP NULL,
  CONSTRAINT fk_stats_entry FOREIGN KEY (entry_id) REFERENCES entries(entry_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User writing stats (simplified)
CREATE TABLE user_stats (
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

-- Mood tracking (simplified)
CREATE TABLE mood_history (
  mood_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  mood VARCHAR(30) NOT NULL,
  entry_date DATE NOT NULL,
  entry_count INT UNSIGNED DEFAULT 1,
  CONSTRAINT fk_mood_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_mood_date (user_id, mood, entry_date)
) ENGINE=InnoDB;

-- ======================
-- 9. VIEWS (using basic SQL)
-- ======================

-- View: User dashboard summary
CREATE VIEW v_user_dashboard AS
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    u.user_role,
    COALESCE(us.total_entries, 0) AS total_entries,
    COALESCE(us.total_words, 0) AS total_words,
    COALESCE(us.current_streak, 0) AS current_streak,
    us.most_common_mood,
    us.last_entry_date
FROM users u
LEFT JOIN user_stats us ON u.user_id = us.user_id;

-- View: Entry details with all related info
CREATE VIEW v_entry_details AS
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
    (SELECT GROUP_CONCAT(t.tag_name ORDER BY t.tag_name SEPARATOR ', ') 
     FROM entry_tags et 
     JOIN tags t ON et.tag_id = t.tag_id 
     WHERE et.entry_id = e.entry_id) AS tags,
    (SELECT COUNT(*) FROM media WHERE entry_id = e.entry_id) AS media_count,
    COALESCE(es.view_count, 0) AS view_count,
    COALESCE(es.share_count, 0) AS share_count,
    COALESCE(es.reaction_count, 0) AS reaction_count
FROM entries e
JOIN users u ON e.user_id = u.user_id
LEFT JOIN categories c ON e.category_id = c.category_id
LEFT JOIN entry_stats es ON e.entry_id = es.entry_id
WHERE e.is_deleted = FALSE;

-- View: Mood trends (simplified)
CREATE VIEW v_mood_trends AS
SELECT 
    user_id,
    mood,
    DATE_FORMAT(entry_date, '%Y-%m') AS month,
    SUM(entry_count) AS total_entries
FROM mood_history
GROUP BY user_id, mood, DATE_FORMAT(entry_date, '%Y-%m');

-- View: Popular tags
CREATE VIEW v_popular_tags AS
SELECT 
    t.tag_id,
    t.tag_name,
    t.usage_count,
    (SELECT COUNT(*) FROM entry_tags et WHERE et.tag_id = t.tag_id) AS active_entries,
    (SELECT COUNT(DISTINCT e.user_id) 
     FROM entry_tags et 
     JOIN entries e ON et.entry_id = e.entry_id 
     WHERE et.tag_id = t.tag_id AND e.is_deleted = FALSE) AS unique_users
FROM tags t
ORDER BY t.usage_count DESC;

-- ======================
-- 10. SAMPLE DATA
-- ======================

-- Insert sample users (only Admin and User roles)
INSERT INTO users (username, full_name, email, password, security_question, security_answer, user_role, is_active, created_at) VALUES
('admin', 'Admin User', 'admin@diary.com', 'password123', 'What is your favorite color?', 'blue', 'Admin', TRUE, '2024-01-15 10:00:00'),
('john_doe', 'John Doe', 'john@example.com', 'password123', 'What is your favorite color?', 'blue', 'User', TRUE, '2024-02-20 14:30:00'),
('jane_smith', 'Jane Smith', 'jane@example.com', 'password123', 'What is your favorite color?', 'blue', 'User', TRUE, '2024-03-10 09:15:00'),
('alice_wonder', 'Alice Wonder', 'alice@example.com', 'password123', 'What is your favorite color?', 'blue', 'User', TRUE, '2024-04-05 16:45:00'),
('bob_builder', 'Bob Builder', 'bob@example.com', 'password123', 'What is your favorite color?', 'blue', 'User', TRUE, '2024-05-12 11:20:00'),
('charlie_brown', 'Charlie Brown', 'charlie@example.com', 'password123', 'What is your favorite color?', 'blue', 'User', TRUE, '2024-06-18 08:00:00'),
('diana_prince', 'Diana Prince', 'diana@example.com', 'password123', 'What is your favorite color?', 'blue', 'User', TRUE, '2024-07-22 13:30:00'),
('emma_watson', 'Emma Watson', 'emma@example.com', 'password123', 'What is your favorite color?', 'blue', 'User', TRUE, '2024-08-14 10:45:00');

-- Insert sample diary entries
INSERT INTO entries (user_id, category_id, title, content, mood, location, privacy_level, word_count, timestamp) VALUES
(1, 1, 'First Day as Admin', 'Today I set up the entire diary system. It feels great to have everything organized and ready for users.', 'Happy', 'New York, USA', 'private', 20, '2024-01-15 18:00:00'),
(1, 2, 'System Launch', 'Successfully launched the diary application. All features are working perfectly!', 'Excited', 'New York, USA', 'public', 10, '2024-01-20 14:30:00'),
(2, 3, 'Trip to Paris', 'Amazing day exploring the Eiffel Tower and trying authentic French cuisine. The city is magical!', 'Excited', 'Paris, France', 'public', 16, '2024-03-15 20:00:00'),
(2, 4, 'New Year Goals', 'Setting ambitious goals for 2024: Read 50 books, learn Spanish, and run a marathon. Let''s do this!', 'Reflective', 'Boston, USA', 'private', 17, '2024-01-01 09:00:00'),
(2, 1, 'Sunday Thoughts', 'Spent the day reflecting on life and relationships. Feeling grateful for family and friends.', 'Calm', 'Boston, USA', 'private', 14, '2024-02-25 16:00:00'),
(3, 5, 'Morning Yoga', 'Started my day with 30 minutes of yoga. Feeling centered and energized!', 'Calm', 'Los Angeles, USA', 'public', 12, '2024-04-10 07:00:00'),
(3, 6, 'Grateful Heart', 'Today I''m grateful for: my health, supportive friends, and this beautiful sunshine.', 'Happy', 'Los Angeles, USA', 'public', 12, '2024-04-15 12:00:00'),
(3, 2, 'Project Deadline', 'Working late to finish the presentation. Stressful but I know I can do it!', 'Angry', 'Los Angeles, USA', 'private', 13, '2024-04-20 22:00:00'),
(4, 1, 'Childhood Memories', 'Found old photo albums today. So many wonderful memories flooding back!', 'Reflective', 'Seattle, USA', 'private', 11, '2024-05-01 14:00:00'),
(4, 3, 'Beach Weekend', 'Perfect beach day with friends. Sun, sand, and laughter - what more could I ask for?', 'Happy', 'Santa Monica, USA', 'public', 16, '2024-05-15 18:00:00'),
(5, 2, 'Career Milestone', 'Got promoted today! All the hard work has paid off. Celebrating with the team tonight.', 'Excited', 'Chicago, USA', 'public', 15, '2024-06-01 17:00:00'),
(5, 4, 'Learning Guitar', 'Week 3 of guitar lessons. My fingers hurt but I''m making progress!', 'Happy', 'Chicago, USA', 'private', 12, '2024-06-10 20:00:00'),
(6, 1, 'Rainy Day Blues', 'Sometimes we all need a quiet day at home. Reading books and drinking tea.', 'Calm', 'Portland, USA', 'private', 14, '2024-07-05 11:00:00'),
(6, 5, 'Marathon Training', 'Completed 10 miles today! My longest run yet. Exhausted but proud.', 'Excited', 'Portland, USA', 'public', 11, '2024-07-20 07:00:00'),
(7, 6, 'Thankful Thursday', 'Grateful for: morning coffee, good books, and cozy evenings at home.', 'Happy', 'Austin, USA', 'public', 11, '2024-08-01 19:00:00'),
(7, 1, 'Family Visit', 'Parents came to visit. Nothing beats home-cooked meals and family stories.', 'Happy', 'Austin, USA', 'private', 11, '2024-08-15 21:00:00'),
(8, 3, 'Mountain Hiking', 'Conquered my first 14er today! The view from the summit was breathtaking.', 'Excited', 'Denver, USA', 'public', 12, '2024-09-01 16:00:00'),
(8, 2, 'New Project Kickoff', 'Starting a new project at work. Nervous but excited about the challenges ahead.', 'Reflective', 'Denver, USA', 'private', 13, '2024-09-10 09:00:00');

-- Insert sample tags
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
(1, 8), (2, 10),
(3, 1), (3, 7), (4, 2), (5, 8),
(6, 4), (6, 9), (7, 4), (8, 5),
(9, 8), (9, 6), (10, 1), (10, 7),
(11, 5), (11, 10), (12, 2), (12, 4),
(13, 8), (14, 4), (14, 9),
(15, 4), (15, 6), (16, 6),
(17, 1), (17, 7), (17, 4), (18, 5), (18, 8);

-- Add some media
INSERT INTO media (entry_id, file_path, file_type, file_size) VALUES
(3, 'https://picsum.photos/seed/paris/800/600', 'image/jpeg', 102400),
(6, 'https://picsum.photos/seed/yoga/800/600', 'image/jpeg', 98304),
(10, 'https://picsum.photos/seed/beach/800/600', 'image/jpeg', 115200),
(14, 'https://picsum.photos/seed/marathon/800/600', 'image/jpeg', 108000),
(17, 'https://picsum.photos/seed/mountain/800/600', 'image/jpeg', 125000);

-- Initialize user stats
INSERT INTO user_stats (user_id, total_entries, total_words, current_streak, longest_streak, avg_words_per_entry, most_common_mood, last_entry_date) VALUES
(1, 2, 30, 1, 2, 15.00, 'Happy', '2024-01-20'),
(2, 3, 47, 2, 3, 15.67, 'Excited', '2024-03-15'),
(3, 3, 37, 1, 2, 12.33, 'Calm', '2024-04-20'),
(4, 2, 27, 1, 1, 13.50, 'Happy', '2024-05-15'),
(5, 2, 27, 1, 2, 13.50, 'Excited', '2024-06-10'),
(6, 2, 25, 1, 1, 12.50, 'Calm', '2024-07-20'),
(7, 2, 22, 1, 2, 11.00, 'Happy', '2024-08-15'),
(8, 2, 25, 1, 1, 12.50, 'Excited', '2024-09-10');

INSERT INTO entry_stats (entry_id, view_count, share_count, reaction_count) VALUES
(1, 5, 0, 0), (2, 12, 1, 2), (3, 25, 3, 5),
(4, 8, 0, 1), (5, 6, 0, 0), (6, 18, 2, 3),
(7, 15, 1, 2), (8, 7, 0, 0), (9, 10, 0, 1),
(10, 22, 2, 4), (11, 30, 3, 6), (12, 9, 0, 1),
(13, 5, 0, 0), (14, 20, 2, 3), (15, 16, 1, 2),
(16, 8, 0, 1), (17, 35, 4, 7), (18, 11, 0, 1);

-- Initialize mood history
INSERT INTO mood_history (user_id, mood, entry_date, entry_count) VALUES
(1, 'Happy', '2024-01-15', 1),
(1, 'Excited', '2024-01-20', 1),
(2, 'Excited', '2024-03-15', 1),
(2, 'Reflective', '2024-01-01', 1),
(2, 'Calm', '2024-02-25', 1),
(3, 'Calm', '2024-04-10', 1),
(3, 'Happy', '2024-04-15', 1),
(3, 'Angry', '2024-04-20', 1),
(4, 'Reflective', '2024-05-01', 1),
(4, 'Happy', '2024-05-15', 1),
(5, 'Excited', '2024-06-01', 1),
(5, 'Happy', '2024-06-10', 1),
(6, 'Calm', '2024-07-05', 1),
(6, 'Excited', '2024-07-20', 1),
(7, 'Happy', '2024-08-01', 1),
(7, 'Happy', '2024-08-15', 1),
(8, 'Excited', '2024-09-01', 1),
(8, 'Reflective', '2024-09-10', 1);

-- =====================================================
-- DATABASE SETUP COMPLETE!
-- Password for all users: "password123"
-- Security answer: "blue"
-- =====================================================
