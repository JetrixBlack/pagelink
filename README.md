# PageLink ⚡

**Link-in-bio personalizable** con panel de administración PHP + SQLite.

PageLink es una página tipo Linktree con backend completo: admin panel, tracking de clics, CRUD de enlaces y testimonios, subida de avatar/portada, estadísticas y más.

## Características

- Diseño responsive claro con glassmorphism
- Iconos SVG oficiales de redes sociales
- Panel admin protegido con login
- CRUD de enlaces y testimonios
- Contador de clics por enlace
- Estadísticas con gráfico de barras
- Exportar clics a CSV
- Subir foto de perfil y portada
- Open Graph tags para SEO/compartir en redes
- Session timeout + CSRF protection
- Sin dependencias externas (PHP + SQLite nativo)

## Requisitos

- PHP 8.0+
- Extensiones: PDO SQLite, GD, fileinfo, session

## Instalación

```bash
php setup.php
php -S localhost:8000
```

Luego abre:

- **Página pública**: http://localhost:8000
- **Panel admin**: http://localhost:8000/admin/ (usuario: `admin`, contraseña: `admin123`)

## Estructura

```
PageLink/
├── index.php                 # Página principal (PHP dinámico)
├── setup.php                 # Inicializador de base de datos
├── config/database.php       # PDO + helpers (CSRF, flash, session)
├── assets/
│   ├── css/style.css         # Estilos frontend
│   └── js/script.js          # Lógica frontend (fetch + iconos SVG)
├── api/                      # Endpoints públicos JSON
│   ├── get-profile.php
│   ├── get-links.php
│   ├── get-testimonials.php
│   ├── get-clicks.php        # Paginación de clics
│   └── track-click.php       # Redirección + registro de clic
├── admin/                    # Panel de administración
│   ├── style.css             # Estilos compartidos admin
│   ├── _nav.php              # Navbar unificado
│   ├── login.php / logout.php
│   ├── index.php             # Dashboard
│   ├── links.php             # CRUD enlaces
│   ├── testimonials.php      # CRUD testimonios
│   ├── stats.php             # Estadísticas + paginación
│   ├── profile.php           # Perfil + avatar + portada
│   ├── change-password.php   # Seguridad
│   └── export-csv.php        # Exportar clics
├── setup.php
├── uploads/                  # Imágenes subidas
└── database.sqlite           # Base de datos
```

## Iconos disponibles

Los iconos SVG están en `ICONS` dentro de `assets/js/script.js`:

Telegram, GitHub, Instagram, Facebook, Twitter/X, OnlyFans, TikTok, Threads, YouTube, WhatsApp, Link (genérico)

## Licencia

MIT
