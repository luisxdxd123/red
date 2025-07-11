-- Crear un nuevo usuario administrador
INSERT INTO users (
    username,
    email,
    password,
    first_name,
    last_name,
    is_admin,
    can_create_pages
) VALUES (
    'admin2',  -- username
    'admin2@redsocial.com',  -- email
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password hash para 'password'
    'Admin',  -- first_name
    'Sistema',  -- last_name
    TRUE,  -- is_admin
    TRUE   -- can_create_pages
); 