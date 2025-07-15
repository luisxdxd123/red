-- Script SQL para actualizar la base de datos con nuevas funcionalidades
-- Grupos y Mensajería
-- Ejecutar después del script principal database.sql

USE red_social;

-- Tabla de grupos
CREATE TABLE groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255) DEFAULT 'default-group.jpg',
    privacy ENUM('public', 'private') DEFAULT 'public',
    creator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de miembros de grupos
CREATE TABLE group_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin', 'moderator', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (group_id, user_id)
);

-- Tabla de posts en grupos
CREATE TABLE group_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de likes en posts de grupos
CREATE TABLE group_post_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    group_post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_post_id) REFERENCES group_posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_like (user_id, group_post_id)
);

-- Tabla de comentarios en posts de grupos
CREATE TABLE group_post_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    group_post_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_post_id) REFERENCES group_posts(id) ON DELETE CASCADE
);

-- Tabla de conversaciones
CREATE TABLE conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_conversation (user1_id, user2_id),
    CHECK (user1_id != user2_id)
);

-- Tabla de mensajes
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de páginas
CREATE TABLE pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    profile_image VARCHAR(255) DEFAULT 'default-page.jpg',
    cover_image VARCHAR(255) DEFAULT 'default-page-cover.jpg',
    creator_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de seguidores de páginas
CREATE TABLE page_followers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL,
    user_id INT NOT NULL,
    followed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follower (page_id, user_id)
);

-- Tabla de posts en páginas
CREATE TABLE page_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    media_type ENUM('image', 'video') NULL,
    media_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de likes en posts de páginas
CREATE TABLE page_post_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_post_id) REFERENCES page_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (page_post_id, user_id)
);

-- Tabla de comentarios en posts de páginas
CREATE TABLE page_post_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (page_post_id) REFERENCES page_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Actualizaciones para el sistema de solicitudes de páginas

-- Agregar campo is_admin a la tabla users
ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE;

-- Agregar campo can_create_pages a la tabla users
ALTER TABLE users ADD COLUMN can_create_pages BOOLEAN DEFAULT FALSE;

-- Añadir campo avatar_url a la tabla users
ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT NULL;

-- Actualizar el usuario admin existente
UPDATE users SET is_admin = TRUE WHERE username = 'admin';

-- Crear tabla para solicitudes de páginas
CREATE TABLE page_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    payment_proof VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_date TIMESTAMP NULL,
    admin_notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertar algunos grupos de ejemplo
INSERT INTO groups (name, description, creator_id, privacy) VALUES
('Desarrolladores PHP', 'Grupo para desarrolladores que trabajan con PHP y tecnologías web', 1, 'public'),
('Diseño Gráfico', 'Comunidad de diseñadores gráficos y artistas digitales', 3, 'public'),
('Fotografía', 'Grupo para amantes de la fotografía y técnicas fotográficas', 4, 'public'),
('Grupo Privado VIP', 'Grupo exclusivo para miembros VIP', 1, 'private');

-- Agregar miembros a los grupos
INSERT INTO group_members (group_id, user_id, role) VALUES
-- Grupo Desarrolladores PHP
(1, 1, 'admin'),
(1, 2, 'member'),
(1, 4, 'member'),
-- Grupo Diseño Gráfico
(2, 3, 'admin'),
(2, 1, 'member'),
(2, 2, 'member'),
-- Grupo Fotografía
(3, 4, 'admin'),
(3, 1, 'member'),
(3, 3, 'member'),
-- Grupo Privado VIP
(4, 1, 'admin'),
(4, 2, 'member');

-- Insertar algunos posts en grupos
INSERT INTO group_posts (group_id, user_id, content) VALUES
(1, 2, '¿Alguien ha trabajado con Laravel 10? Me gustaría conocer sus experiencias 🚀'),
(1, 1, 'Acabo de descubrir una nueva librería de PHP que hace maravillas con APIs REST'),
(2, 3, 'Nuevo proyecto de branding terminado. ¡El cliente quedó encantado! 🎨'),
(2, 1, 'Busco feedback sobre esta paleta de colores para un proyecto web'),
(3, 4, 'Capturé esta increíble puesta de sol en la montaña 📸'),
(3, 3, 'Tips para fotografía nocturna: configuración de cámara y técnicas');

-- Insertar algunos likes en posts de grupos
INSERT INTO group_post_likes (user_id, group_post_id) VALUES
(1, 1), (4, 1),
(2, 2), (4, 2),
(1, 3), (2, 3),
(3, 4), (1, 4),
(1, 5), (3, 5),
(4, 6), (1, 6);

-- Insertar algunos comentarios en posts de grupos
INSERT INTO group_post_comments (user_id, group_post_id, content) VALUES
(1, 1, 'Laravel 10 es increíble! La nueva sintaxis es muy limpia'),
(4, 1, 'Yo lo he usado en varios proyectos, totalmente recomendado'),
(2, 3, 'Se ve fantástico! ¿Qué herramientas usaste?'),
(1, 5, 'Wow, qué fotografía tan impresionante! 📸'),
(3, 6, 'Excelentes consejos, los aplicaré en mi próxima sesión');

-- Crear algunas conversaciones de ejemplo
INSERT INTO conversations (user1_id, user2_id, last_message_at) VALUES
(1, 2, NOW()),
(1, 3, NOW() - INTERVAL 1 HOUR),
(2, 3, NOW() - INTERVAL 2 HOUR),
(1, 4, NOW() - INTERVAL 3 HOUR);

-- Insertar algunos mensajes de ejemplo
INSERT INTO messages (conversation_id, sender_id, receiver_id, content, is_read, created_at) VALUES
-- Conversación entre user 1 y 2
(1, 1, 2, '¡Hola Juan! ¿Cómo estás?', TRUE, NOW() - INTERVAL 1 HOUR),
(1, 2, 1, '¡Hola Admin! Muy bien, gracias. ¿Y tú?', TRUE, NOW() - INTERVAL 50 MINUTE),
(1, 1, 2, 'Excelente, trabajando en nuevos proyectos', FALSE, NOW() - INTERVAL 30 MINUTE),

-- Conversación entre user 1 y 3
(2, 1, 3, 'Me encantó tu último diseño', TRUE, NOW() - INTERVAL 2 HOUR),
(2, 3, 1, '¡Muchas gracias! Me alegra que te haya gustado', TRUE, NOW() - INTERVAL 1 HOUR 30 MINUTE),

-- Conversación entre user 2 y 3
(3, 2, 3, '¿Tienes tiempo para un proyecto colaborativo?', TRUE, NOW() - INTERVAL 3 HOUR),
(3, 3, 2, 'Por supuesto! Cuéntame más detalles', FALSE, NOW() - INTERVAL 2 HOUR 30 MINUTE),

-- Conversación entre user 1 y 4
(4, 1, 4, 'Tus fotografías son increíbles', TRUE, NOW() - INTERVAL 4 HOUR),
(4, 4, 1, 'Gracias! Es mi pasión', TRUE, NOW() - INTERVAL 3 HOUR 30 MINUTE); 