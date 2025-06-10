-- Script SQL para agregar soporte de múltiples medios (imágenes y videos) a las publicaciones
-- Ejecutar en MySQL/MariaDB

USE red_social;

-- Crear tabla para almacenar archivos multimedia de las publicaciones
CREATE TABLE post_media (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id)
);

-- Agregar un campo para indicar si el post tiene medios
ALTER TABLE posts ADD COLUMN has_media BOOLEAN DEFAULT FALSE;

-- Crear directorio para uploads (esto se hará mediante PHP)
-- Los archivos se almacenarán en: uploads/posts/YYYY/MM/

-- Opcional: Migrar datos existentes si hay posts con imágenes
-- UPDATE posts SET has_media = TRUE WHERE image IS NOT NULL AND image != '';
-- INSERT INTO post_media (post_id, file_name, file_type, file_path, mime_type)
-- SELECT id, image, 'image', CONCAT('uploads/posts/legacy/', image), 'image/jpeg'
-- FROM posts WHERE image IS NOT NULL AND image != '';

-- Nota: Después de migrar los datos, se puede eliminar la columna image:
-- ALTER TABLE posts DROP COLUMN image; 