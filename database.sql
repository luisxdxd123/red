-- Script SQL para crear la base de datos de la Red Social
-- Ejecutar en MySQL/MariaDB

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS red_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE red_social;

-- Tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT 'default-avatar.jpg',
    bio TEXT,
    birth_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Tabla de posts
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de likes en posts
CREATE TABLE post_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (user_id, post_id)
);

-- Tabla de comentarios
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Tabla de seguidores
CREATE TABLE follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    CHECK (follower_id != following_id)
);

-- Insertar algunos datos de prueba
INSERT INTO users (username, email, password, first_name, last_name, bio) VALUES
('admin', 'admin@redsocial.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Sistema', 'Administrador del sistema'),
('juan_perez', 'juan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan', 'Pérez', 'Desarrollador apasionado por la tecnología'),
('maria_garcia', 'maria@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María', 'García', 'Diseñadora gráfica y amante del arte'),
('carlos_lopez', 'carlos@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos', 'López', 'Fotógrafo profesional');

-- Contraseña por defecto para todos: "password"

INSERT INTO posts (user_id, content) VALUES
(2, '¡Hola mundo! Este es mi primer post en la red social 🚀'),
(3, 'Trabajando en un nuevo proyecto de diseño. ¡Estoy emocionada! 🎨'),
(4, 'Hermoso atardecer desde la montaña 📸🌅'),
(2, 'Aprendiendo nuevas tecnologías web. PHP y MySQL son increíbles 💻');

INSERT INTO post_likes (user_id, post_id) VALUES
(1, 1), (3, 1), (4, 1),
(1, 2), (2, 2), (4, 2),
(1, 3), (2, 3), (3, 3),
(1, 4), (3, 4), (4, 4);

INSERT INTO comments (user_id, post_id, content) VALUES
(3, 1, '¡Bienvenido a la plataforma!'),
(4, 1, 'Excelente primer post 👍'),
(2, 2, 'Se ve increíble tu diseño'),
(1, 3, 'Qué hermosa fotografía'),
(2, 4, 'Gracias por compartir tus conocimientos');

INSERT INTO follows (follower_id, following_id) VALUES
(1, 2), (1, 3), (1, 4),
(2, 1), (2, 3), (2, 4),
(3, 1), (3, 2), (3, 4),
(4, 1), (4, 2), (4, 3); 