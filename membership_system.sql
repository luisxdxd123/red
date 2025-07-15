-- Script SQL para implementar el sistema de membresías
-- Ejecutar después de database.sql y database_updates.sql

USE red_social;

-- Agregar campo de membresía a la tabla users
ALTER TABLE users ADD COLUMN membership_type ENUM('basico', 'premium', 'vip') DEFAULT 'basico';
ALTER TABLE users ADD COLUMN membership_expires_at DATETIME NULL;
ALTER TABLE users ADD COLUMN membership_created_at DATETIME NULL;

-- Tabla para los tipos de membresías
CREATE TABLE membership_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MXN',
    description TEXT,
    features JSON,
    duration_months INT DEFAULT 12,  -- Cambiado a 12 meses por defecto
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para el historial de pagos de membresías
CREATE TABLE membership_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    membership_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MXN',
    payment_method ENUM('stripe', 'paypal', 'transfer', 'cash') DEFAULT 'cash',
    payment_reference VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla para solicitudes de membresía
CREATE TABLE membership_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    membership_type VARCHAR(50) NOT NULL,
    payment_proof VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_date TIMESTAMP NULL,
    admin_notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertar los tipos de membresías
INSERT INTO membership_types (name, price, description, features, duration_months) VALUES
('basico', 0.00, 'Membresía básica gratuita', 
'["Acceso al timeline principal", "Ver y editar perfil", "Ver otros usuarios", "Publicar posts básicos"]', 
12),
('premium', 2000.00, 'Membresía Premium - Acceso a grupos y mensajes', 
'["Todas las funciones básicas", "Acceso a grupos", "Sistema de mensajería", "Crear y unirse a grupos", "Chat privado con usuarios"]', 
12),  -- Cambiado a 12 meses
('vip', 5000.00, 'Membresía VIP - Acceso completo a todas las funciones', 
'["Todas las funciones Premium", "Acceso a páginas", "Crear y administrar páginas", "Funciones administrativas", "Soporte prioritario"]', 
12);  -- Cambiado a 12 meses

-- Actualizar usuarios existentes con membresía básica
UPDATE users SET membership_type = 'basico', membership_created_at = NOW() WHERE membership_type IS NULL;

-- ALTER para actualizar la duración de membresías existentes
UPDATE membership_types SET duration_months = 12 WHERE name IN ('premium', 'vip'); 