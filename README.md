# PageLink

**Link-in-bio personalizable** con panel de administracion completo. Tema oscuro negro + rosado apagado (`#c47a8a`).

PageLink es una pagina tipo Linktree con backend **PHP serverless (front controller)** sobre **Turso (libSQL)** en la nube: panel admin, tracking de clics, CRUD de enlaces y testimonios, subida de avatar/portada, recuperacion de contrasena por pregunta de seguridad, historial de actividad paginado, carrusel de testimonios y mas.

## Demo en produccion

| Recurso | URL |
|---------|-----|
| **Pagina publica** | https://pagelink-josue.vercel.app |
| **Panel admin** | https://pagelink-josue.vercel.app/admin |
| **Setup BD** | https://pagelink-josue.vercel.app/setup |

> Acceso demo: **Usuario** `admin` · **Contrasena** `admin123` (cambiar en Perfil → Seguridad).

## Caracteristicas

### Pagina Publica
- Tema oscuro negro + rosado apagado (no deslumbrante)
- Cover + avatar con bordes rosados (carga directa, sin preloader)
- Botones de enlaces con iconos SVG oficiales de redes sociales
- Carrusel automatico de testimonios (rotacion cada 4 segundos)
- Modal para dejar comentarios (con honeypot anti-bots)
- Footer con marca y copyright
- Open Graph + Twitter Cards para SEO
- Totalmente responsive (mobile-first)

### Panel Admin
- Dashboard: cards resumen, top enlaces, historial paginado
- Auto-refresh cada 30 segundos + boton de refresh manual
- CRUD de enlaces con modales (crear, editar, eliminar)
- Gestion de testimonios (aprobar, eliminar)
- Exportar clics a CSV
- Perfil unificado con tabs (Perfil + Seguridad)
- Vista previa de imagenes (avatar y portada) antes de subir
- Selector de pregunta de seguridad + campo custom
- Recuperacion de contrasena por pregunta de seguridad (2 pasos)
- Notificaciones flotantes toast
- Session timeout (1 hora) + proteccion CSRF
- Pagina de sesion expirada con estilo del login

### Seguridad
- Contrasenas hasheadas con `password_hash()` (bcrypt)
- Token CSRF en todos los formularios
- Rate limiting en recuperacion de contrasena (max 5 intentos/hora)
- Validacion de MIME type real en subida (no solo extension), max 5 MB
- Session handler persistente en Turso (tabla `sessions`) para serverless
- Sin dependencias externas (zero attack surface)

## Arquitectura (serverless / front controller)

- **Front controller:** `api/index.php` despacha todas las rutas (Vercel `vercel-php@0.7.2`)
- **BD en produccion:** Turso/libSQL via HTTP (`config/turso.php`) — `TursoPDO`
- **BD en local:** SQLite (`database.sqlite`) con PDO estandar — misma interfaz
- **Sesiones:** `session_set_save_handler()` con tabla `sessions` en Turso (persisten en serverless)

```
PageLink/
├── api/index.php               # Front controller (home, admin, api, setup, uploads)
├── app/
│   ├── api.php                 # Handlers JSON (get-profile, get-links, ...)
│   ├── auth.php                # Auth + permisos admin
│   ├── helpers.php             # base_url, base_path, redirect, ROOT_PATH
│   ├── session.php             # Session handler (Turso/SQLite)
│   └── views/                  # Vistas (home + vistas admin)
├── config/
│   ├── database.php            # getDB(): Turso (prod) / SQLite (local)
│   ├── turso.php               # Conexion Turso/libSQL HTTP
│   └── upload.php              # Subida validada de imagenes
├── assets/                     # css/, js/ (publica + admin)
├── uploads/                    # Perfil.jpg, Portada.jpg, default.svg
├── vercel.json                 # Funciones + rutas + VERCEL=1
├── database.sqlite             # BD local (desarrollo)
└── iniciar.bat                 # Dev server local
```

## Base de Datos (Turso)

Tablas: `profile`, `links`, `clicks`, `testimonials` (aprobacion), `admin`, `security_question`, `sessions`.

## Instalacion y deploy

### Local (XAMPP)
```bash
php -S localhost:8000
# o usar Apache en :8000 apuntando a la carpeta
```

### Vercel (produccion)
1. Conectar el repo `JetrixBlack/pagelink` (rama `main`) a Vercel
2. Configurar env vars **Production**:
   - `TURSO_DATABASE_URL=https://pagelink-jetrix.aws-us-east-1.turso.io`
   - `TURSO_AUTH_TOKEN=<token de Turso>`
3. Push a `main` → deploy automatico

Accesos locales:
- **Pagina publica**: `http://localhost:8000`
- **Panel admin**: `/admin` · Usuario `admin` · Contrasena `admin123`
- Pregunta de seguridad: "Cual es el nombre de tu primera mascota?" → respuesta `pagelink`

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