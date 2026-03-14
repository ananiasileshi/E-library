-- Upgrade schema for advanced features
-- Run this after the base schema.sql

USE elibrary;

-- Update user roles to include more types
ALTER TABLE users MODIFY COLUMN role ENUM('reader','premium','moderator','admin') NOT NULL DEFAULT 'reader';

-- Add user profile fields
ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER password_hash;
ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER bio;
ALTER TABLE users ADD COLUMN website VARCHAR(255) NULL AFTER avatar_path;

-- User shelves (Want to Read, Currently Reading, Read, Custom)
CREATE TABLE IF NOT EXISTS shelves (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_slug (user_id, slug),
    CONSTRAINT fk_shelves_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Book-Shelf relationship (a book can be in multiple shelves)
CREATE TABLE IF NOT EXISTS book_shelf (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    shelf_id INT UNSIGNED NOT NULL,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_book_shelf (book_id, shelf_id),
    CONSTRAINT fk_book_shelf_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_book_shelf_shelf FOREIGN KEY (shelf_id) REFERENCES shelves(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Reading progress tracking
CREATE TABLE IF NOT EXISTS reading_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    current_page INT UNSIGNED NOT NULL DEFAULT 0,
    total_pages INT UNSIGNED NOT NULL DEFAULT 0,
    percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_book (user_id, book_id),
    CONSTRAINT fk_reading_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reading_progress_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Book highlights and notes
CREATE TABLE IF NOT EXISTS highlights (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    note TEXT NULL,
    page_number INT UNSIGNED NULL,
    color VARCHAR(20) NOT NULL DEFAULT 'yellow',
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_highlights_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_highlights_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Book reviews and ratings
CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    book_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200) NULL,
    content TEXT NULL,
    has_spoilers TINYINT(1) NOT NULL DEFAULT 0,
    is_approved TINYINT(1) NOT NULL DEFAULT 1,
    helpful_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_book (user_id, book_id),
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Review helpful votes
CREATE TABLE IF NOT EXISTS review_votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    is_helpful TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_review_user (review_id, user_id),
    CONSTRAINT fk_review_votes_review FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_votes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User follows (social)
CREATE TABLE IF NOT EXISTS user_follows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id INT UNSIGNED NOT NULL,
    following_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_follower_following (follower_id, following_id),
    CONSTRAINT fk_user_follows_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_follows_following FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Activity feed
CREATE TABLE IF NOT EXISTS activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('read','review','shelf_add','follow','achievement','favorite') NOT NULL,
    book_id INT UNSIGNED NULL,
    target_user_id INT UNSIGNED NULL,
    data JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activities_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_activities_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL,
    CONSTRAINT fk_activities_target_user FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Book tags for recommendations
CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(60) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Book-tag relationship
CREATE TABLE IF NOT EXISTS book_tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uk_book_tag (book_id, tag_id),
    CONSTRAINT fk_book_tags_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    CONSTRAINT fk_book_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User achievements/badges
CREATE TABLE IF NOT EXISTS achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    icon VARCHAR(100) NOT NULL,
    condition_type VARCHAR(50) NOT NULL,
    condition_value INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User-achievement relationship
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_achievement (user_id, achievement_id),
    CONSTRAINT fk_user_achievements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_achievements_achievement FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Add author field to books
ALTER TABLE books ADD COLUMN author VARCHAR(255) NULL AFTER title;
ALTER TABLE books ADD COLUMN isbn VARCHAR(20) NULL AFTER author;
ALTER TABLE books ADD COLUMN publication_year YEAR NULL AFTER isbn;
ALTER TABLE books ADD COLUMN language VARCHAR(10) NOT NULL DEFAULT 'en' AFTER publication_year;
ALTER TABLE books ADD COLUMN page_count INT UNSIGNED NULL AFTER language;

-- Add average rating to books (denormalized for performance)
ALTER TABLE books ADD COLUMN avg_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER downloads;
ALTER TABLE books ADD COLUMN review_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER avg_rating;

-- Insert default achievements
INSERT INTO achievements (name, slug, description, icon, condition_type, condition_value) VALUES
    ('First Book', 'first-book', 'Read your first book', 'bi-book', 'books_read', 1),
    ('Bookworm', 'bookworm', 'Read 10 books', 'bi-journal-text', 'books_read', 10),
    ('Binge Reader', 'binge-reader', 'Read 25 books', 'bi-fire', 'books_read', 25),
    ('Library Master', 'library-master', 'Read 50 books', 'bi-trophy', 'books_read', 50),
    ('First Review', 'first-review', 'Write your first review', 'bi-chat-quote', 'reviews_written', 1),
    ('Critic', 'critic', 'Write 10 reviews', 'bi-pen', 'reviews_written', 10),
    ('Social Butterfly', 'social-butterfly', 'Follow 10 users', 'bi-people', 'following', 10)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert default shelves for existing users (run separately in PHP)
-- This would be done in a migration script
