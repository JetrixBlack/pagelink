# PageLink ⚡

**Link-in-bio personalizable** con panel de administracion completo, tema oscuro negro + rosado apagado.

PageLink es una pagina tipo Linktree con backend PHP + SQLite: panel admin, tracking de clics, CRUD de enlaces y testimonios, subida de avatar/portada con vista previa, recuperacion de contrasena por pregunta de seguridad, historial de actividad paginado, carrusel de testimonios y mas.

## Caracteristicas

### Pagina Publica
- Tema oscuro negro + rosado apagado (no deslumbrante)
- Cover + avatar con bordes rosados
- Botones de enlaces con iconos SVG oficiales de redes sociales
- Carrusel automatico de testimonios (rotacion cada 4 segundos)
- Modal para dejar comentarios (con honeypot anti-bots)
- Footer con marca y copyright
- Open Graph + Twitter Cards para SEO
- Totalmente responsive (mobile-first)

### Panel Admin
- Dashboard robusto: cards resumen, top enlaces, historial paginado
- Auto-refresh cada 30 segundos + boton de refresh manual
- CRUD de enlaces con modales (crear, editar, eliminar)
- Gestion de testimonios (aprobar, eliminar)
- Exportar clics a CSV
- Perfil unificado con tabs (Perfil + Seguridad)
- Vista previa de imagenes (avatar y portada) antes de subir
- Recuperacion de contrasena por pregunta de seguridad (2 pasos)
- Ojo para visualizar/ocultar contrasenas en todos los formularios
- Session timeout (1 hora) + proteccion CSRF

### Seguridad
- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Token CSRF en todos los formularios
- Rate limiting en recuperacion de contrasena (max 5 intentos/hora)
- Validacion de MIME type real en subida de archivos (no solo extension)
- Tamano maximo de archivos: 5 MB
- Session timeout automatico
- Sin dependencias externas (zero attack surface)

## Requisitos

- PHP 8.0+
- Extensiones: PDO SQLite, GD, fileinfo, session
- Apache/Nginx o PHP built-in server

## Instalacion

```bash
# 1. Clonar o copiar el proyecto
# 2. Ejecutar el setup (crea tablas + datos por defecto)
php setup.php

# 3. Iniciar servidor de desarrollo
php -S localhost:8000
```

Accesos:
- **Pagina publica**: `http://localhost:8000`
- **Panel admin**: `http://localhost:8000/admin/`
  - Usuario: `admin`
  - Contrasena: `admin123`
  - Pregunta de seguridad: "Cual es el nombre de tu primera mascota?"
  - Respuesta: `pagelink`

## Estructura

```
PageLink/
├── index.php                 # Pagina principal (PHP dinamico)
├── index.html                # Fallback estatico
├── setup.php                 # Inicializador de base de datos
├── database.sqlite           # Base de datos SQLite
├── .gitignore                # Reglas de Git
├── README.md                 # Esta documentacion
├── config/
│   └── database.php          # PDO + helpers (CSRF, flash, session)
├── assets/
│   ├── css/style.css         # Estilos de pagina publica (tema oscuro)
│   └── js/script.js          # Logica frontend (fetch, iconos SVG, carrusel)
├── api/                      # Endpoints publicos JSON
│   ├── get-profile.php       # Obtener datos del perfil
│   ├── get-links.php         # Obtener enlaces activos
│   ├── get-testimonials.php  # Obtener testimonios aprobados
│   ├── submit-testimonial.php# Enviar comentario (con honeypot)
│   ├── track-click.php       # Registrar clic + redirigir
│   ├── get-clicks.php        # Paginacion de clics (para admin)
│   └── avatar-fallback.php   # Generar avatar con iniciales (GD)
├── admin/                    # Panel de administracion
│   ├── style.css             # Estilos compartidos admin (tema oscuro)
│   ├── _nav.php              # Navbar unificado
│   ├── login.php             # Login con ojo de contrasena
│   ├── logout.php            # Cerrar sesion
│   ├── index.php             # Dashboard (stats + historial paginado)
│   ├── links.php             # CRUD enlaces con modales
│   ├── testimonials.php      # Gestion de testimonios
│   ├── profile.php           # Perfil unificado (tabs: Perfil + Seguridad)
│   ├── forgot-password.php   # Recuperacion por pregunta de seguridad
│   └── export-csv.php        # Exportar clics a CSV
└── uploads/                  # Imagenes subidas (avatars, portadas)
    └── default.jpg           # Avatar por defecto
```

## Iconos SVG Disponibles

Telegram, GitHub, Instagram, Facebook, Twitter/X, OnlyFans, TikTok, Threads, YouTube, WhatsApp, Link (generico)

## Base de Datos

6 tablas SQLite:
- `profile` — Datos del perfil (nombre, bio, avatar, cover, footer)
- `links` — Enlaces del usuario
- `clicks` — Registro de clics por enlace
- `testimonials` — Testimonios (con sistema de aprobacion)
- `admin` — Credenciales del administrador
- `security_question` — Pregunta de seguridad para recuperacion

## Tema de Colores

| Token | Valor | Uso |
|-------|-------|-----|
| `--bg` | `#0f0f0f` | Fondo principal |
| `--surface` | `#1a1a1a` | Cards y secciones |
| `--fg` | `#e0d8d0` | Texto principal |
| `--accent` | `#c47a8a` | Rosado apagado |
| `--border` | `#2a2a2a` | Bordes sutiles |

## Licencia

MIT
