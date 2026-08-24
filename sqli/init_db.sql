-- Create database if not exists
CREATE DATABASE IF NOT EXISTS blog_db;
USE blog_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create posts table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Insert sample users (passwords are plain text for simplicity in lab)
INSERT INTO users (username, password, email) VALUES
('admin', 'admin123', 'admin@blog.com'),
('john', 'john123', 'john@blog.com'),
('alice', 'alice123', 'alice@blog.com');

-- Insert sample blog posts
INSERT INTO posts (title, content, author_id) VALUES
('Welcome to Our Blog', 'This is the first post on our blog. Welcome everyone!', 1),
('PHP Security Best Practices', 'In this post, we will discuss various PHP security practices...', 1),
('Introduction to SQL', 'SQL is a powerful language for managing databases...', 2),
('Web Development Tips', 'Here are some useful tips for web developers...', 2),
('Database Design Patterns', 'Good database design is crucial for application performance...', 3),
('HTML and CSS Basics', 'Let''s learn the fundamentals of HTML and CSS...', 3),
('JavaScript Fundamentals', 'JavaScript is the language of the web...', 1),
('Building REST APIs', 'REST APIs are essential for modern web applications...', 2);

