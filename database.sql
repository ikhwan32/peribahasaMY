-- Create database
CREATE DATABASE IF NOT EXISTS peribahasa_db;
USE peribahasa_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'moderator', 'admin') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Peribahasa table
CREATE TABLE IF NOT EXISTS peribahasa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    meaning TEXT NOT NULL,
    example_usage TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    contributor_id INT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contributor_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peribahasa_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peribahasa_id) REFERENCES peribahasa(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Daily Peribahasa table
CREATE TABLE IF NOT EXISTS daily_peribahasa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peribahasa_id INT NOT NULL,
    display_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (display_date),
    FOREIGN KEY (peribahasa_id) REFERENCES peribahasa(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@peribahasa.com', '$2y$10$8KyAH0jrYqXg3nGD6x5EE.6VqR0tR7W9M5V5D8jJzx5ZHwB5XvGPi', 'admin');
