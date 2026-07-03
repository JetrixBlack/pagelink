# PageLink ⚡

**Link-in-bio personalizable** — 100% frontend, sin dependencias, sin backend.

PageLink es una página tipo Linktree que puedes personalizar editando solo un archivo JS. Ideal para creadores de contenido, emprendedores y cualquier persona que quiera tener todos sus enlaces en un solo lugar con un diseño premium oscuro.

## Características

- Diseño oscuro premium con glassmorphism
- Iconos SVG oficiales de redes sociales (Simple Icons)
- Animaciones suaves de entrada (fadeIn + slideUp)
- Responsive (móvil y escritorio)
- Sin dependencias externas (solo Google Fonts)
- Sin backend — funciona en cualquier hosting estático
- Personalizable editando `assets/js/script.js`

## Personalización

Edita el objeto `CONFIG` en `assets/js/script.js`:

```js
const CONFIG = {
  name: 'Tu Nombre',
  bio: 'Tu descripción corta',
  avatar: 'https://url-de-tu-foto.jpg',
};
```

Para cambiar los enlaces, modifica el array `DEFAULT_LINKS`:

```js
const DEFAULT_LINKS = [
  { label: 'Instagram', subtitle: 'Sígueme', url: 'https://instagram.com/tuuser' },
  { label: 'WhatsApp', subtitle: 'Escríbeme', url: 'https://wa.me/584141234567' },
];
```

## Estructura

```
PageLink/
├── index.html              # HTML principal
├── assets/
│   ├── css/
│   │   └── style.css       # Estilos
│   └── js/
│       └── script.js       # Lógica (config + iconos + render)
└── README.md
```

## Iconos disponibles

Los iconos SVG están en `ICONS` dentro de `script.js`. Actualmente incluye:

- Telegram, GitHub, Instagram, Facebook, Twitter/X
- OnlyFans, TikTok, Threads, YouTube, WhatsApp
- Link (genérico)

Para agregar un nuevo icono, solo añade una nueva entrada al objeto `ICONS` con la llave en minúsculas (ej: `tiktok: \`<svg>...\``).

## Licencia

MIT — haz lo que quieras con esto.
