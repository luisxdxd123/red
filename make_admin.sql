-- Convertir un usuario existente en administrador (reemplaza 'nombre_usuario' con el username del usuario)
UPDATE users 
SET is_admin = TRUE 
WHERE username = 'nombre_usuario';

-- O si prefieres hacerlo por ID (reemplaza 1 con el ID del usuario)
-- UPDATE users 
-- SET is_admin = TRUE 
-- WHERE id = 1; 