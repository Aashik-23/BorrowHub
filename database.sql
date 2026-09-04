-- =========================================================
-- BorrowHub — database.sql
-- ICT1209 Web Technologies | Phase 3 - PHP & MySQL Integration
-- Import this file in phpMyAdmin, or run:
--   mysql -u root -p < database.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS borrowhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE borrowhub;

-- ---------------------------------------------------------
-- Table: users
-- ---------------------------------------------------------
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: messages  (Contact form submissions)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS messages;
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL,
    subject VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Table: items  (theme-specific — rental listings)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS items;
CREATE TABLE items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    category VARCHAR(30) NOT NULL,
    description TEXT,
    price_per_day DECIMAL(10,2) NOT NULL,
    location VARCHAR(100) NOT NULL,
    available TINYINT(1) NOT NULL DEFAULT 1,
    icon VARCHAR(40) DEFAULT 'bi-box-seam',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Seed data (optional — sample owner + items so Browse Items
-- shows real database rows immediately after import)
-- ---------------------------------------------------------
-- password for demo_owner is: Demo@1234
INSERT INTO users (username, email, password) VALUES
('demo_owner', 'demo@borrowhub.lk', '$2b$10$pjskq2ZH1J3.rWLmtw/qyO8ew9X3wLjCSh.t4STtmUqUZeuEbYhPW');

INSERT INTO items (user_id, title, category, description, price_per_day, location, available, icon) VALUES
(1, 'Laptop', 'electronics', 'A reliable everyday laptop, great for assignments, presentations and light editing. Charger included.', 600.00, 'Colombo', 1, 'bi-laptop'),
(1, 'Camera', 'photography', 'Mirrorless camera with a standard kit lens — ideal for events, portraits and short video shoots.', 600.00, 'Kandy', 1, 'bi-camera'),
(1, 'Power Drill', 'tools', 'Cordless power drill with two batteries and a bit set — great for small home projects.', 500.00, 'Kurunegala', 1, 'bi-tools'),
(1, 'Helmet', 'sports', 'Certified cycling/riding helmet, adjustable fit, lightly used.', 120.00, 'Colombo', 1, 'bi-shield-check'),
(1, 'Scientific Calculator', 'study', 'Exam-approved scientific calculator, all functions working — perfect for a single exam period.', 160.00, 'Galle', 1, 'bi-calculator'),
(1, 'Speaker', 'home', 'Portable Bluetooth party speaker with strong bass — good for small gatherings and events.', 800.00, 'Colombo', 0, 'bi-speaker'),
(1, 'Tent', 'sports', '4-person camping tent, waterproof, easy setup — comes with stakes and a carry bag.', 400.00, 'Kandy', 1, 'bi-triangle');
