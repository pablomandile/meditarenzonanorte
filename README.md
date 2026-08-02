# Meditar en Zona Norte — sitio administrable

Sitio de un centro de meditación Kadampa construido con **Laravel 12 + Vue 3 + Inertia.js + MySQL**, con un panel de administración para editar todo el contenido sin tocar código.

Partió como réplica de [meditarenrosario.org](https://meditarenrosario.org/) (de ahí el contenido sembrado) y hoy se publica como **Meditar en Zona Norte** en `https://meditarenzonanorte.pablomandile.com.ar`. La marca (logo, teléfono, email, dirección) se ajusta desde **Ajustes del sitio**, sin tocar el seed.

## Stack

- Laravel 12 (starter kit oficial Vue): Inertia 2, Vue 3, TypeScript, Tailwind CSS, Vite.
- MySQL (base `meditazn` en local).
- Imágenes en `storage/app/public` (`php artisan storage:link`).
- Fuentes auto-hospedadas (`@fontsource`: Anton, Roboto, Roboto Slab).
- **Laravel Socialite** para el login con Google.

## Puesta en marcha

```bash
composer install
npm install
cp .env.example .env   # completar DB, APP_URL, y (opcional) las claves de Google
php artisan key:generate
# crear la base: CREATE DATABASE meditazn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan migrate:fresh --seed
php artisan storage:link
npm run dev            # o npm run build para producción
```

Con Laragon el sitio queda disponible en `http://meditazn.test`. Alternativa: `php artisan serve`.

`migrate:fresh --seed` deja el sitio **completo con contenido e imágenes reales**: **9 páginas** con sus secciones, 3 eventos, ~6 FAQs, los ajustes de contacto y un usuario admin. Todo el copy vive en `database/seeders/data/*.php` y las imágenes en `database/seeders/images/`.

### Páginas

`/` (home), `/clases-semanales`, `/eventos-especiales`, `/gratis`, `/quienes-somos` (incluye los maestros), `/voluntariado`, `/abonos`, `/programa-fundamental` y `/cursos-y-retiros`.

`cursos-y-retiros` nació como clon de `eventos-especiales` (mismas secciones, mismo orden y mismo copy; solo cambian el título de la página y el encabezado del `page_header`).

Desde `/admin/pages` se **ocultan/muestran** las páginas y se **reordenan con flechas**: ese orden es el que adopta la barra de navegación, y una página oculta sale del menú y su URL pasa a responder 404. La página de inicio queda fuera de ambas acciones (es la raíz del sitio y no figura en el menú); el guard también está en el servidor, no solo en los botones.

El título, el slug y la etiqueta del menú siguen definidos en `database/seeders/data/content.php` — el panel no crea ni renombra páginas.

## Panel de administración

- URL: `/admin` (redirige a `/login` si no hay sesión).
- Usuario sembrado: `admin@meditarenrosario.org` / `password` — **cambiá la contraseña** desde el menú del usuario → Configuración → Contraseña. (En producción ya se cambió.)
- El registro público está deshabilitado: cualquier usuario autenticado es administrador. El panel y las páginas de auth están **en español**.
- Al guardar una sección o los ajustes, la **vista previa de las imágenes se refresca automáticamente** (sin recargar la página).
- **Ordenar tarjetas**: en las secciones `card_grid`, cada tarjeta tiene flechas para subirla o bajarla. El orden del array `content.cards` es el que renderiza `CardGridSection.vue`, así que es directamente el orden en la web. Al mover una tarjeta, su archivo pendiente de subida se mueve con ella (el mapa `files` va indexado por posición), y el guardado resuelve **qué archivo reemplaza cada imagen por la ruta de la tarjeta y no por su índice** — si no, reordenar y subir en el mismo guardado borra el archivo de otra tarjeta.
- **Elegir de galería**: además de subir un archivo, todo campo de imagen de una sección (incluidas las de tarjetas y galerías) puede reusar una foto ya cargada al sitio. El listado sale de `GET /admin/media` (`MediaController`) y junta `sections/`, `events/`, `settings/` y `seed/`, colapsando los archivos de bytes idénticos en una sola entrada. Al elegir una, el guardado **se queda con una copia propia** (`ImageStorage::adopt()`): sin eso, reemplazar la imagen en una sección borraría el archivo que otra sigue usando. Las sembradas (`seed/…`) se comparten tal cual, porque nunca se borran. El logo y el afiche de eventos no tienen el botón: usan el mismo componente pero su guardado todavía no se apropia de la copia.
- **Clonar una sección** copia su contenido a una sección nueva del mismo tipo, ubicada justo debajo del original y **oculta** (para que la página pública no muestre el bloque duplicado hasta terminar de editarla). La key de la copia es `<key>-copia`, `<key>-copia-2`… y las imágenes subidas desde el panel se duplican en disco, así reemplazar la foto de la copia no afecta al original. Las imágenes sembradas (`seed/…`) se comparten, como ya hace el resto del sitio.

Qué se puede administrar:

| Sección del panel | Permite |
|---|---|
| **Páginas** | Listado de las 9 páginas: **ocultar/mostrar** cada una (sale del menú y su URL da 404) y **reordenarlas con flechas** (ese es el orden de la barra nav). Entrando a una página: editar cada sección (textos, imágenes, enlaces, planes, personas…), ocultar/mostrar secciones, reordenarlas con flechas y **clonarlas**. La home no se oculta ni se reordena. |
| **Eventos** | CRUD de eventos especiales, con afiche, fecha, precio y enlace de inscripción. Toggle "destacar en inicio" (strip *próximamente* de la home) y visible/oculto. Si hay un solo evento destacado se muestra grande y centrado. |
| **Preguntas frecuentes** | Pool global de FAQs compartido entre páginas: editar una vez, se actualiza en todas. Cada sección FAQ elige qué preguntas muestra. |
| **Ajustes del sitio** | **Logo del menú** y **logo del pie** por separado (`logo_path` y `footer_logo_path`): pueden ser archivos distintos, y si el del pie está vacío el pie usa el del menú. Teléfono, WhatsApp, email, Instagram, dirección y los recursos (libros) del pie de página. |

### Login con Google (opcional)

Además del login con email/contraseña, el panel soporta **"Continuar con Google"** (Laravel Socialite). Por seguridad —el `/admin` es de un solo dueño— solo entran los emails de una **lista blanca**.

1. Crear credenciales OAuth 2.0 en [Google Cloud Console](https://console.cloud.google.com/apis/credentials) → *Aplicación web*.
   - URI de redirección autorizado: `https://TU_DOMINIO/auth/google/callback` (y `http://meditazn.test/auth/google/callback` para local).
2. Completar en `.env`:
   ```env
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   GOOGLE_ALLOWED_EMAILS=persona@gmail.com   # separados por coma
   ```
   El `GOOGLE_REDIRECT_URI` se deriva de `APP_URL` (podés fijarlo si querés uno distinto).

El botón "Continuar con Google" aparece en el login **solo si `GOOGLE_CLIENT_ID` está configurado**. Cualquier cuenta fuera de la lista blanca es rechazada aunque el login de Google sea exitoso.

## Arquitectura de contenido

- Cada página se compone de **secciones tipadas** (`sections.type` + `content` JSON). Los **16 tipos** están definidos en `app/Support/SectionRegistry.php` — la única fuente de verdad para los formularios del admin y la validación:

  | Tipo | Uso |
  |---|---|
  | `hero` | Banner/portada de ancho completo |
  | `page_header` | Encabezado con banda de color (naranja/celeste) |
  | `text_block` | Bloque de texto (con imagen y enlaces opcionales) |
  | `text_image` | Texto con imagen a un lado |
  | `quote` | Testimonio / cita |
  | `card_grid` | Grilla de tarjetas |
  | `bullet_list` | Lista de ítems |
  | `gallery` | Galería de imágenes |
  | `class_info` | Ficha de clase (horario, lugar, precio, CTA) |
  | `person` | Maestro / persona (rol, nombre, cargo, bio, foto, lado) |
  | `figure` | Imagen destacada (título + imagen + pie) |
  | `pricing` | Planes / abonos (tarjetas de precios) |
  | `event_strip` | Eventos destacados en la home |
  | `event_list` | Listado de eventos |
  | `map` | Mapa embebido de Google Maps |
  | `faq` | Preguntas frecuentes (elige del pool global) |

- El frontend público renderiza con `resources/js/components/public/SectionRenderer.vue` (mapa tipo → componente en `.../public/sections/`). El admin arma el formulario de cada sección dinámicamente desde el registro (`resources/js/components/admin/SectionForm.vue` + `.../fields/`).
- La paleta y tipografías replican el CSS real del sitio original (Astra + Elementor): celeste `#259ACF`, naranja `#CD6023`, crema `#FFF1E6`, Anton para títulos display; ver `tailwind.config.js`.

## Seeders

- `ContentSeeder` — siembra el sitio completo (todas las páginas, FAQs, eventos, ajustes) desde `database/seeders/data/*.php`. Lo llama `DatabaseSeeder` junto con el usuario admin.
- **Seeders puntuales** (tocan **una sola página** sin pisar el resto del contenido ya editado — pensados para producción):

  ```bash
  php artisan db:seed --class=AbonosSeeder --force
  php artisan db:seed --class=MaestrosSeeder --force          # página ¿Quienes somos?
  php artisan db:seed --class=ProgramaFundamentalSeeder --force
  php artisan db:seed --class=CursosYRetirosSeeder --force
  php artisan db:seed --class=ClasesSemanalesPortadaSeeder --force
  ```

Hay dos primitivos, y la diferencia importa en producción:

| Primitivo | Qué hace | Cuándo |
|---|---|---|
| `ContentSeeder::seedSinglePage($slug)` | Upsert de la página **y de todas sus secciones** desde el archivo de datos: reescribe contenido, vuelve a poner `visible = true` y renumera posiciones. **Pisa lo editado desde el panel en esa página.** | Publicar una página nueva, o resetear una a su estado sembrado. Lo usan los 4 primeros seeders. |
| `ContentSeeder::seedMissingSection($slug, $key)` | Inserta **una sola sección** si esa página todavía no la tiene, justo debajo de la que la precede en el archivo de datos; el resto solo corre una posición. Repetible y no toca nada más. | Agregar un bloque a una página que el dueño ya editó. Lo usa `ClasesSemanalesPortadaSeeder`. |

## Deploy a producción (Hostinger)

Hosting compartido de Hostinger con acceso SSH. Notas clave:

- **PHP 8.4** (el `composer.lock` lo requiere): usar `/opt/alt/php84/usr/bin/php` para `composer` y `artisan`.
- **Document root del subdominio → carpeta `public`**: como Hostinger no deja cambiar el docroot, se resuelve con un **symlink** dentro de `.../pablomandile/public/<subdominio> → ../../<carpeta-app>/public`.
- Flujo por actualización: compilar assets local (`npm run build`), subir la app por `scp` (sin `node_modules`, `vendor`, `.env` ni `bootstrap/cache`), en el server `composer install --no-dev --optimize-autoloader`, correr las migraciones/seeders puntuales que correspondan y `php artisan optimize`.
- El `.env` de producción (con `APP_KEY`, DB y claves de Google) **no** está versionado.

## Tests

```bash
php artisan test
```

Incluye `tests/Feature/SiteContentTest.php`: render público, ocultar secciones/páginas, permisos del admin, edición de secciones con reemplazo de imagen, validaciones, eventos en la home y FAQs compartidas.
