# Meditación Kadampa Rosario — sitio administrable

Réplica de [meditarenrosario.org](https://meditarenrosario.org/) construida con **Laravel 12 + Vue 3 + Inertia.js + MySQL**, con un panel de administración para editar todo el contenido sin tocar código.

## Stack

- Laravel 12 (starter kit oficial Vue): Inertia 2, Vue 3, TypeScript, Tailwind CSS, Vite.
- MySQL (base `meditazn`).
- Imágenes en `storage/app/public` (`php artisan storage:link`).
- Fuentes auto-hospedadas (`@fontsource`: Anton, Roboto, Roboto Slab).

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env   # ya configurado si clonás este repo con su .env
php artisan key:generate
# crear la base: CREATE DATABASE meditazn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan migrate:fresh --seed
php artisan storage:link
npm run dev            # o npm run build para producción
```

Con Laragon el sitio queda disponible en `http://meditazn.test`. Alternativa: `php artisan serve`.

El seeder (`database/seeders/ContentSeeder.php`) deja el sitio **completo con el contenido e imágenes reales** clonados del sitio original: 6 páginas, 44 secciones, 3 eventos, 6 preguntas frecuentes y los ajustes de contacto. Los datos viven en `database/seeders/data/*.php` y las imágenes en `database/seeders/images/`.

## Panel de administración

- URL: `/admin` (redirige a `/login` si no hay sesión).
- Usuario sembrado: `admin@meditarenrosario.org` / `password` — **cambiá la contraseña** desde el menú del usuario → Settings → Password.
- El registro público está deshabilitado: cualquier usuario autenticado es administrador.

Qué se puede administrar:

| Sección del panel | Permite |
|---|---|
| **Páginas** | Ver las 6 páginas → editar cada sección (textos, imágenes, enlaces), ocultar/mostrar secciones y reordenarlas con flechas. |
| **Eventos** | CRUD de eventos especiales, con afiche, fecha, precio y enlace de inscripción. Toggle "destacar en inicio" (strip *próximamente* de la home) y visible/oculto. |
| **Preguntas frecuentes** | Pool global de FAQs compartido entre páginas: editar una vez, se actualiza en todas. Cada sección FAQ elige qué preguntas muestra. |
| **Ajustes del sitio** | Logo, teléfono, WhatsApp, email, Instagram, dirección y los 3 recursos (libros) del pie de página. |

## Arquitectura de contenido

- Cada página se compone de **secciones tipadas** (`sections.type` + `content` JSON). Los 13 tipos (hero, encabezado, texto, texto+imagen, cita, grilla de tarjetas, lista, galería, info de clase, eventos destacados, listado de eventos, mapa, FAQ) están definidos en `app/Support/SectionRegistry.php`, la única fuente de verdad para los formularios del admin y la validación.
- El frontend público renderiza con `resources/js/components/public/SectionRenderer.vue` (mapa tipo → componente en `.../public/sections/`).
- La paleta y tipografías replican el CSS real del sitio original (Astra + Elementor): celeste `#259ACF`, naranja `#CD6023`, crema `#FFF1E6`, Anton para títulos display; ver `tailwind.config.js`.

## Tests

```bash
php artisan test
```

Incluye `tests/Feature/SiteContentTest.php`: render público, ocultar secciones/páginas, permisos del admin, edición de secciones con reemplazo de imagen, validaciones, eventos en la home y FAQs compartidas.
