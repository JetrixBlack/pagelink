# PageLink 

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
- Preloader con spinner al cargar
- Totalmente responsive (mobile-first)

### Panel Admin
- Dashboard robusto: cards resumen, top enlaces, historial paginado
- Auto-refresh cada 30 segundos + boton de refresh manual
- CRUD de enlaces con modales (crear, editar, eliminar)
- Gestion de testimonios (aprobar, eliminar)
- Exportar clics a CSV
- Perfil unificado con tabs (Perfil + Seguridad)
- Vista previa de imagenes (avatar y portada) antes de subir
- Selector de pregunta de seguridad (8 opciones + campo custom)
- Indicadores de estado (contrasena configurada, pregunta configurada)
- Recuperacion de contrasena por pregunta de seguridad (2 pasos)
- Ojo para visualizar/ocultar contrasenas en todos los formularios
- Notificaciones flotantes toast (verde exito, rojo error)
- Preloader con spinner en todas las paginas
- Pagina de sesion expirada con estilo del login
- Session timeout (1 hora) + proteccion CSRF

### Seguridad
- Contrasenas hasheadas con `password_hash()` (bcrypt)
- Validacion: solo letras (a-z, A-Z), numeros (0-9) y caracteres especiales
- Token CSRF en todos los formularios
- Rate limiting en recuperacion de contrasena (max 5 intentos/hora)
- Validacion de MIME type real en subida de archivos (no solo extension)
- Tamano maximo de archivos: 5 MB
- Session timeout automatico
- Sin dependencias externas (zero attack surface)

## Estructura

```
PageLink/
├── index.php                       # Pagina principal (PHP dinamico)
├── setup.php                       # Inicializador de base de datos
├── database.sqlite                 # Base de datos SQLite
├── .gitignore                      # Reglas de Git
├── README.md                       # Esta documentacion
├── iniciar.bat                     # Iniciar servidor de desarrollo
├── config/
│   └── database.php                # PDO + helpers (CSRF, flash, session)
├── admin/
│   ├── pages/                      # Paginas del panel de administracion
│   │   ├── index.php               # Dashboard (stats + historial paginado)
│   │   ├── login.php               # Login con ojo de recontrasena
│   │   ├── logout.php              # Cerrar sesion
│   │   ├── links.php               # CRUD enlaces con modales
│   │   ├── testimonials.php        # Gestion de testimonios
│   │   ├── profile.php             # Perfil unificado (tabs: Perfil + Seguridad)
│   │   ├── forgot-password.php     # Recuperacion por pregunta de seguridad
│   │   ├── session-expired.php     # Vista de sesion expirada
│   │   └── export-csv.php          # Exportar clics a CSV
│   ├── partials/
│   │   └── _nav.php                # Navbar unificado
│   ├── css/
│   │   └── admin.css               # Estilos del panel admin (tema oscuro)
│   └── js/
│       └── admin.js                # Utilidades JS (toggle contrasena)
├── assets/
│   ├── css/
│   │   └── style.css               # Estilos de pagina publica (tema oscuro)
│   └── js/
│       └── script.js               # Logica frontend (fetch, iconos SVG, carrusel)
├── api/                            # Endpoints publicos JSON
│   ├── get-profile.php             # Obtener datos del perfil
│   ├── get-links.php               # Obtener enlaces activos
│   ├── get-testimonials.php        # Obtener testimonios aprobados
│   ├── submit-testimonial.php      # Enviar comentario (con honeypot)
│   ├── track-click.php             # Registrar clic + redirigir
│   ├── get-clicks.php              # Paginacion de clics (para admin)
│   └── avatar-fallback.php         # Generar avatar con iniciales (GD)
└── uploads/                        # Imagenes subidas (avatars, portadas)
    └── default.jpg                 # Avatar por defecto
```

## Base de Datos

6 tablas SQLite:
- `profile` — Datos del perfil (nombre, bio, avatar, cover, footer)
- `links` — Enlaces del usuario
- `clicks` — Registro de clics por enlace
- `testimonials` — Testimonios (con sistema de aprobacion)
- `admin` — Credenciales del administrador
- `security_question` — Pregunta de seguridad para recuperacion

## Instalacion

```bash
# 1. Ejecutar el setup (crea tablas + datos por defecto)
php setup.php

# 2. Iniciar servidor de desarrollo
php -S localhost:8000
```

Accesos:
- **Pagina publica**: `http://localhost:8000`
- **Panel admin**: `http://localhost:8000/admin/pages/login.php`
  - Usuario: `admin`
  - Contrasena: `admin123`
  - Pregunta de seguridad: "Cual es el nombre de tu primera mascota?"
  - Respuesta: `pagelink`

## Iconos SVG Disponibles

Telegram, GitHub, Instagram, Facebook, Twitter/X, OnlyFans, TikTok, Threads, YouTube, WhatsApp, Link (generico)

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
