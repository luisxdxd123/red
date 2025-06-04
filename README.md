# Red Social - Proyecto PHP

Una red social completa desarrollada en PHP con MySQL y Tailwind CSS.

## 🚀 Características

### Funcionalidades Principales
- **Autenticación de usuarios**: Registro, login y logout seguro
- **Gestión de perfil**: Editar información personal y biografía
- **Timeline de posts**: Crear, ver y interactuar con publicaciones
- **Sistema de likes**: Dar y quitar likes a las publicaciones
- **Sistema de comentarios**: Comentar en las publicaciones
- **Sistema de seguimiento**: Seguir y dejar de seguir usuarios
- **User discovery**: Página para descubrir nuevos usuarios

### 🆕 Nuevas Funcionalidades
- **Sistema de Grupos**: 
  - Crear grupos públicos y privados
  - Unirse/salir de grupos
  - Posts específicos por grupo
  - Gestión de miembros (admin, moderador, miembro)
  - Timeline de posts por grupo
- **Mensajería Privada**:
  - Chat en tiempo real entre usuarios
  - Notificaciones de mensajes no leídos
  - Historial de conversaciones
  - Indicadores de mensajes leídos/no leídos
- **Interfaz Mejorada**:
  - Navegación intuitiva con iconos
  - Contador de notificaciones
  - Responsive design optimizado

## 📋 Requisitos

- **XAMPP/WAMP/LAMP** o servidor web con:
  - PHP 7.4 o superior
  - MySQL 5.7 o superior
  - Apache (incluido en XAMPP)

## 🛠️ Instalación

### 1. Clonar/Descargar el proyecto
Coloca todos los archivos en la carpeta `htdocs` de XAMPP:
```
C:\xampp\htdocs\red_prueba\
```

### 2. Configurar la base de datos

1. **Iniciar XAMPP**:
   - Abre XAMPP Control Panel
   - Inicia Apache y MySQL

2. **Crear la base de datos**:
   - Ve a http://localhost/phpmyadmin
   - Ejecuta el script `database.sql` para crear la estructura básica
   - **IMPORTANTE**: Ejecuta también `database_updates.sql` para las nuevas funcionalidades

3. **Configurar conexión** (opcional):
   - Edita `config/database.php` si necesitas cambiar:
     - Usuario de MySQL (por defecto: `root`)
     - Contraseña de MySQL (por defecto: vacía)
     - Host (por defecto: `localhost`)

### 3. Acceder a la aplicación

Ve a: **http://localhost/red_prueba**

## 👥 Usuarios de Prueba

El sistema incluye usuarios de prueba con contraseña `password`:

| Usuario | Email | Contraseña | Descripción |
|---------|-------|------------|-------------|
| admin | admin@redsocial.com | password | Administrador del sistema |
| juan_perez | juan@email.com | password | Desarrollador |
| maria_garcia | maria@email.com | password | Diseñadora |
| carlos_lopez | carlos@email.com | password | Fotógrafo |

## 📁 Estructura del Proyecto

```
red_prueba/
├── auth/                        # Autenticación
│   ├── login.php               # Página de login
│   ├── register.php            # Página de registro
│   └── logout.php              # Cerrar sesión
├── config/                      # Configuración
│   └── database.php            # Conexión a BD
├── dashboard/                   # Panel principal
│   ├── index.php               # Timeline principal
│   ├── profile.php             # Perfil del usuario
│   ├── users.php               # Lista de usuarios
│   ├── groups.php              # 🆕 Gestión de grupos
│   ├── group_detail.php        # 🆕 Detalle de grupo
│   ├── messages.php            # 🆕 Sistema de mensajería
│   ├── create_post.php         # Crear posts
│   ├── create_group.php        # 🆕 Crear grupos
│   ├── join_group.php          # 🆕 Unirse a grupos
│   ├── leave_group.php         # 🆕 Salir de grupos
│   ├── get_messages.php        # 🆕 Obtener mensajes
│   ├── send_message.php        # 🆕 Enviar mensajes
│   ├── toggle_like.php         # Gestión de likes
│   ├── get_comments.php        # Obtener comentarios
│   ├── add_comment.php         # Agregar comentarios
│   ├── toggle_follow.php       # Seguir/no seguir
│   └── update_profile.php      # Actualizar perfil
├── includes/                    # Funciones auxiliares
│   └── functions.php           # Funciones generales (actualizado)
├── database.sql                # Script de base de datos original
├── database_updates.sql        # 🆕 Script con nuevas tablas
├── index.php                  # Página de inicio
└── README.md                  # Este archivo
```

## 🔧 Funcionalidades Principales

### Autenticación
- Registro de nuevos usuarios con validación
- Login con usuario/email y contraseña
- Logout seguro con destrucción de sesión

### Posts y Timeline
- Crear publicaciones de texto
- Ver timeline con posts de todos los usuarios
- Mostrar información del autor y fecha
- Sistema de likes en tiempo real (AJAX)
- Sistema de comentarios dinámico

### Perfil de Usuario
- Ver y editar información personal
- Estadísticas (posts, seguidores, siguiendo)
- Historial de posts del usuario

### Sistema Social
- Descubrir otros usuarios
- Seguir/dejar de seguir usuarios
- Ver perfiles de otros usuarios

### 🆕 Sistema de Grupos
- **Crear grupos**: Públicos (cualquiera se une) o privados (solo por invitación)
- **Gestión de miembros**: Roles de admin, moderador y miembro
- **Posts de grupo**: Timeline específico por grupo con likes y comentarios
- **Filtros avanzados**: Ver todos los grupos, solo mis grupos, o solo públicos
- **Estadísticas**: Contador de miembros y posts por grupo

### 🆕 Sistema de Mensajería
- **Chat privado**: Mensajes en tiempo real entre usuarios
- **Gestión de conversaciones**: Lista de todas las conversaciones activas
- **Notificaciones**: Contador de mensajes no leídos en navbar
- **Estados de lectura**: Indicadores de mensajes leídos/no leídos
- **Interfaz intuitiva**: Chat similar a WhatsApp/Telegram
- **🔒 Restricción de seguimiento**: Solo puedes enviar mensajes a usuarios que sigues

## 🎨 Tecnologías Utilizadas

- **Backend**: PHP 7.4+
- **Base de Datos**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Tailwind CSS (CDN)
- **Iconos**: Font Awesome
- **AJAX**: Vanilla JavaScript (Fetch API)

## 🔒 Seguridad

- Contraseñas hasheadas con `password_hash()`
- Preparación de consultas SQL (prepared statements)
- Sanitización de datos de entrada
- Validación de sesiones
- Protección contra XSS con `htmlspecialchars()`
- Control de acceso a conversaciones y grupos

## 📱 Responsive Design

La aplicación está completamente optimizada para:
- Dispositivos móviles
- Tablets
- Escritorio

## 🚀 Características Avanzadas

- **Interfaz moderna**: Diseño limpio y profesional con Tailwind CSS
- **Interacciones en tiempo real**: Likes, comentarios y mensajes sin recargar página
- **Navegación fluida**: SPA-like experience
- **Avatares dinámicos**: Generados automáticamente con iniciales
- **Timestamps relativos**: "hace 5 minutos", "hace 2 horas", etc.
- **Notificaciones en tiempo real**: Contadores de mensajes no leídos
- **Filtros dinámicos**: Filtrado de grupos en tiempo real

## 🆕 Nuevas Bases de Datos

### Tablas para Grupos
- `groups`: Información de grupos (nombre, descripción, privacidad)
- `group_members`: Miembros y roles en grupos
- `group_posts`: Posts específicos de grupos
- `group_post_likes`: Likes en posts de grupos
- `group_post_comments`: Comentarios en posts de grupos

### Tablas para Mensajería
- `conversations`: Conversaciones entre usuarios
- `messages`: Mensajes individuales con estado de lectura

## 🔧 Personalización

### Cambiar colores del tema
Edita las clases de Tailwind CSS en los archivos PHP:
- `text-indigo-600` → Color principal
- `bg-indigo-600` → Fondo de botones
- `from-purple-400 to-pink-400` → Gradientes de avatares

### Agregar nuevas funcionalidades
- Subida de imágenes en posts y grupos
- Notificaciones push
- Sistema de menciones (@usuario)
- Videollamadas
- Historias temporales

## 🐛 Solución de Problemas

### Error de conexión a base de datos
1. Verifica que MySQL esté funcionando en XAMPP
2. Confirma que has ejecutado ambos scripts SQL
3. Revisa usuario y contraseña de MySQL en `config/database.php`

### Funcionalidades nuevas no funcionan
1. Asegúrate de haber ejecutado `database_updates.sql`
2. Verifica que todas las tablas se crearon correctamente
3. Revisa que el archivo `includes/functions.php` se actualizó

### Página en blanco
1. Activa la visualización de errores en PHP
2. Revisa los logs de Apache en XAMPP
3. Verifica que todos los archivos estén en la ubicación correcta

### Mensajes no se envían
1. Verifica que las tablas `conversations` y `messages` existen
2. Comprueba permisos de base de datos
3. Revisa la consola del navegador para errores JavaScript

## 📄 Licencia

Este proyecto es de código abierto y está disponible bajo la Licencia MIT.

## 👨‍💻 Desarrollador

Creado como proyecto educativo de red social con PHP y MySQL.

## 🎉 Novedades v2.0

- ✅ Sistema completo de grupos
- ✅ Mensajería privada en tiempo real
- ✅ Interfaz de usuario mejorada
- ✅ Notificaciones visuales
- ✅ Base de datos expandida
- ✅ Funciones de seguridad mejoradas

---

¡Disfruta explorando la red social mejorada! 🚀💬👥 