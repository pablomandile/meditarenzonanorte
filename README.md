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

`migrate:fresh --seed` deja el sitio **completo con contenido e imágenes reales**: **10 páginas** con sus secciones, 3 eventos, ~6 FAQs, los ajustes de contacto y un usuario admin. Todo el copy vive en `database/seeders/data/*.php` y las imágenes en `database/seeders/images/`.

### Páginas

`/` (home), `/clases-semanales`, `/eventos-especiales`, `/gratis`, `/quienes-somos` (incluye los maestros), `/voluntariado`, `/abonos`, `/programa-fundamental`, `/cursos-y-retiros` y `/calendario`.

`cursos-y-retiros` nació como clon de `eventos-especiales` (mismas secciones, mismo orden y mismo copy; solo cambian el título de la página y el encabezado del `page_header`).

`/calendario` muestra una **grilla mensual** armada sola: no se cargan actividades ahí. Ver [Calendario](#calendario).

Desde `/admin/pages` se **ocultan/muestran** las páginas y se **reordenan con flechas**: ese orden es el que adopta la barra de navegación, y una página oculta sale del menú y su URL pasa a responder 404. La página de inicio queda fuera de ambas acciones (es la raíz del sitio y no figura en el menú); el guard también está en el servidor, no solo en los botones.

Entrando a una página se edita su **descripción para buscadores** (`meta_description`): es el texto que se lee debajo del título en Google y en la vista previa de WhatsApp. El formulario muestra un contador con los 160 caracteres que recomiendan los buscadores (el máximo real es 500, el de la columna) y una vista previa con el título y la URL que la acompañan.

El título, el slug y la etiqueta del menú siguen definidos en `database/seeders/data/content.php` — el panel no crea ni renombra páginas. Ojo: `seedSinglePage()` reescribe la descripción junto con el resto de la página.

### Vista previa al compartir un enlace

WhatsApp, Facebook y los buscadores **no ejecutan JavaScript**: leen el HTML tal como sale del servidor. El `<Head>` de Inertia arma el título y la descripción en el navegador, así que no les llega. Por eso `resources/views/app.blade.php` emite las etiquetas desde el servidor, página por página, con lo que resuelve `App\Support\SiteMeta`: `title`, `meta description`, `og:type`, `og:site_name`, `og:title`, `og:url`, `og:locale`, `og:description`, `og:image` y `twitter:card`. La imagen es la **portada de la página** y si no tiene, el logo del menú, siempre en URL absoluta.

El nombre del sitio sale del ajuste **Nombre del sitio** del panel, no de `APP_NAME`. Antes había tres fuentes que podían discrepar —y discrepaban—: `APP_NAME` del `.env` del servidor (el título que veían WhatsApp y Google), `VITE_APP_NAME` **horneado en el bundle al compilar** (el título de la pestaña) y el ajuste del panel (el pie). Ahora manda el del panel en los tres lugares y las dos variables de entorno quedan de respaldo.

## Panel de administración

- URL: `/admin` (redirige a `/login` si no hay sesión).
- Usuario sembrado: `admin@meditarenrosario.org` / `password` — **cambiá la contraseña** desde el menú del usuario → Configuración → Contraseña. (En producción ya se cambió.)
- El registro público está deshabilitado: cualquier usuario autenticado es administrador. El panel y las páginas de auth están **en español**.
- Al guardar una sección o los ajustes, la **vista previa de las imágenes se refresca automáticamente** (sin recargar la página).
- **Ordenar tarjetas**: en las secciones `card_grid`, cada tarjeta tiene flechas para subirla o bajarla. El orden del array `content.cards` es el que renderiza `CardGridSection.vue`, así que es directamente el orden en la web. Al mover una tarjeta, su archivo pendiente de subida se mueve con ella (el mapa `files` va indexado por posición), y el guardado resuelve **qué archivo reemplaza cada imagen por la ruta de la tarjeta y no por su índice** — si no, reordenar y subir en el mismo guardado borra el archivo de otra tarjeta.
- **Elegir de galería**: además de subir un archivo, todo campo de imagen de una sección (incluidas las de tarjetas y galerías) puede reusar una foto ya cargada al sitio. El listado sale de `GET /admin/media` (`MediaController`) y junta `sections/`, `events/`, `settings/` y `seed/`, colapsando los archivos de bytes idénticos en una sola entrada. Al elegir una, el guardado **se queda con una copia propia** (`ImageStorage::adopt()`): sin eso, reemplazar la imagen en una sección borraría el archivo que otra sigue usando. Las sembradas (`seed/…`) se comparten tal cual, porque nunca se borran. El logo y el afiche de eventos no tienen el botón: usan el mismo componente pero su guardado todavía no se apropia de la copia.
- **El "Horario" de la ficha de clase se arma solo** con las “Fechas para el calendario”, y por eso es opcional: `Occurrences::schedule()` lo escribe como lo escribiría una persona (*“Miércoles de 19 a 20.15 hs”*, *“Martes y jueves de 18 a 18.30 hs”* agrupando los días con el mismo horario, *“Sábado 8 de agosto de 16 a 19 hs”*, *“(hasta el 31 de agosto)”* cuando hay vigencia). El campo a mano queda como excepción para lo que una fecha no puede expresar (*“consultar horario”*) y **manda sobre el automático**. Lo guardado no se toca: `PageController` completa el horario sólo al servir la página, y el formulario muestra debajo del campo el texto que se va a publicar.
- **Ancla para enlazar**: campo opcional de la ficha de clase, para apuntar un botón directo a una ficha (`/cursos-y-retiros#retiro-de-agosto`, o `#4` si preferís un número). Acepta letras, números, `-` y `_` empezando por letra o número —nada de espacios, acentos ni `#`, porque va dentro de una URL— y **tiene que ser única en la página**: si se repite, el navegador salta a la primera y la otra queda inalcanzable sin avisar, así que el guardado lo rechaza. El formulario muestra la URL ya armada debajo del campo. Las reglas propias del campo salen de una clave `rules` en el registro, que pisa las del tipo.
  **La URL va sin barra antes del `#`**: `/clases-semanales#2`, no `/clases-semanales/#2`. Con la barra, al hacer click Inertia pide `/clases-semanales/`, el servidor redirige a `/clases-semanales` y **el `#2` se pierde en la redirección** (escribiendo la URL a mano sí funciona, porque ahí el navegador reaplica el fragmento). El formulario muestra la URL correcta armada, así que lo más seguro es copiarla de ahí.

  Tres cosas que el ancla necesita y no son obvias: `scroll-mt-24` en la sección, porque el header es sticky (80 px) y sin eso el navegador deja la ficha justo debajo y el encabezado la tapa; el composable `useHashScroll()` en `Public/Page.vue`, porque **al ser una SPA el navegador no puede hacerlo solo** —cuando busca el ancla, Vue todavía no dibujó la sección, y al llegar por un enlace de Inertia no hay navegación de documento que lo dispare—; y que ese composable **siga corrigiendo el destino** mientras la página crece, porque los afiches no declaran alto y al cargarse empujan el contenido: apuntando una sola vez, una ficha que estaba más abajo quedaba 700 px desviada. Sigue con un `ResizeObserver` sobre el `body` hasta 2,5 s, y deja de insistir en cuanto la persona toca el scroll.
- **Maestr@**: campo opcional de la ficha de clase, **varios separados por coma**. Se muestra al lado del título, más chico y en naranja, como *“con Ana y Luis”* (`joinNames()` los enumera en castellano: coma entre los del medio y “y” antes del último). Vacío no muestra nada. Va dentro del `<h2>` para que fluya después del título —que puede tener varios renglones— con un espacio duro, así “con” no queda solo al final de un renglón y quien copie el texto o use un lector de pantalla no lee las dos cosas pegadas.
- **Clases del ciclo**: campo de texto de la ficha de clase, **una clase por renglón**. Si tiene contenido, el afiche se vuelve clickeable y **gira** (flip 3D) mostrando el listado numerado sobre un degradado celeste. **Se da vuelta desde cualquier parte del afiche y se cierra desde cualquier parte del dorso**, salvo cuando se está seleccionando texto (copiarse el nombre de una clase no debería taparla); además hay una píldora en el afiche que avisa que se puede girar y un "volver" en el dorso, que son los que dan el acceso por teclado. Vacío, la ficha se comporta como siempre y el afiche no gira. El giro respeta `prefers-reduced-motion`.
- **Fechas para el calendario**: las fichas de clase (`class_info`) tienen, debajo del "Horario", un repetidor de fechas que es lo que ubica la actividad en `/calendario` (ver [Calendario](#calendario)). Cada fila es semanal (día + hora, con vigencia opcional) o una fecha puntual. Si falta el día o la hora es inválida, el guardado se rechaza con un mensaje en castellano en lugar de descartar la fila en silencio; para descartarla está el tacho.
- **Clonar una sección** copia su contenido a una sección nueva del mismo tipo, ubicada justo debajo del original y **oculta** (para que la página pública no muestre el bloque duplicado hasta terminar de editarla). La key de la copia es `<key>-copia`, `<key>-copia-2`… y las imágenes subidas desde el panel se duplican en disco, así reemplazar la foto de la copia no afecta al original. Las imágenes sembradas (`seed/…`) se comparten, como ya hace el resto del sitio.
- **Eliminar una sección** la borra para siempre, junto con las imágenes que se hayan subido desde el panel para ella (`SectionRegistry::imagePaths()` + `ImageStorage::delete()`; las `seed/…` no se tocan). Pide confirmación. Un re-seed de esa página la vuelve a poner si estaba en el archivo de datos.
- **Plantilla de ficha** (`sections.is_template`): "Cursos y Retiros" y "Clases semanales" tienen cada una una `class_info` marcada como plantilla, **oculta y no eliminable** — es de donde el dueño clona la ficha de cada curso o clase. `Section::booted()` fuerza `visible = false` y `show_on_calendar = false` en cada guardado (con `setAttribute()`, porque dentro de un closure con scope de la clase `$section->visible` toca la propiedad reservada de Eloquent, no la columna), y `SectionController` corta con 403 el "mostrar" y el "eliminar". Clonarla sí se puede: la copia es una sección normal (`is_template = false`). Las siembran `CursosYRetirosFichaSeeder` y `ClasesSemanalesFichaSeeder` en el deploy; la columna la agrega una migración. En el archivo de datos, la sección lleva `'template' => true`.

Qué se puede administrar:

| Sección del panel | Permite |
|---|---|
| **Páginas** | Listado de las 9 páginas: **ocultar/mostrar** cada una (sale del menú y su URL da 404) y **reordenarlas con flechas** (ese es el orden de la barra nav). Entrando a una página: editar su **título** y su **nombre en el menú**, editar cada sección (textos, imágenes, enlaces, planes, personas…), ocultar/mostrar secciones, reordenarlas con flechas, **clonarlas** y **eliminarlas**. Las fichas plantilla de "Cursos y Retiros" y "Clases semanales" no se eliminan ni se muestran. La home no se oculta ni se reordena. |
| **Eventos** | CRUD de eventos especiales, con afiche, fecha, precio y enlace de inscripción. **Fecha de inicio y de fin, hora de inicio y de fin** (la fecha de fin ubica un retiro en todos sus días del calendario; el texto libre "Fecha y horario" sigue siendo lo que se lee en las tarjetas). Toggles "destacar en inicio" (la tira de eventos de la home), "mostrar en el calendario" y visible/oculto. Si hay un solo evento destacado se muestra grande y centrado. **Dos enlaces por evento**: la **URL del botón** (la inscripción) y la **URL de la imagen** (opcional, ej. la publicación de Instagram). En las tarjetas de la home el afiche y el título llevan a la de la imagen, y el botón a la suya; si la imagen no tiene URL propia sigue al botón, y sin ninguna de las dos va a `/eventos-especiales`. En la página de eventos el afiche es clickeable sólo si tiene URL de imagen. Con texto de botón pero sin URL todavía, el botón se muestra apagado y no se puede clickear. |
| **Calendario** | Elige **qué eventos aparecen en el calendario** del sitio: lista los eventos visibles con un tilde cada uno y un tilde maestro en el encabezado que marca o desmarca todos de una. Se guarda al instante. Los eventos sin fecha de inicio se listan pero no se pueden tildar (no hay día donde ubicarlos). Las clases **no** pasan por acá: entran solas con sus "Fechas para el calendario". |
| **Galería** | Grilla con **todas** las imágenes del disco (`sections/`, `events/`, `settings/`, `seed/`) y **dónde se usa cada una**. Permite borrar solo las que no usa nadie: si está en uso muestra el lugar y bloquea el botón, y las sembradas (`seed/…`) tampoco se borran porque el seeder las restaura. El servidor vuelve a comprobarlo al borrar, por si la vista quedó vieja. A diferencia del selector, no colapsa las copias de bytes idénticos: acá se administran archivos. |
| **Preguntas frecuentes** | Pool global de FAQs compartido entre páginas: editar una vez, se actualiza en todas. Cada sección FAQ elige qué preguntas muestra. |
| **Ajustes del sitio** | **Logo del menú** y **logo del pie** por separado (`logo_path` y `footer_logo_path`): pueden ser archivos distintos, y si el del pie está vacío el pie usa el del menú. El **ícono de la pestaña** (favicon) es el logo del pie, con el del menú como respaldo: `Setting::favicon()` lo resuelve y `app.blade.php` lo emite en el `<head>` con el tipo MIME deducido de la extensión. Se prefiere el del pie porque suele ser el isotipo cuadrado; el del menú es el logo ancho con el nombre al lado, que a 16 px no se lee. Como la ruta lleva un hash, cambiar el logo cambia la URL y no queda un ícono viejo en caché. Teléfono, WhatsApp, email, Instagram, dirección y los recursos (libros) del pie de página. La **fuente de los títulos** (tres de Google, o la de siempre), el **acceso con Google** (qué cuentas pueden entrar al panel, ver [Login con Google](#login-con-google-opcional)) y el **estado del sitio**: publicado o [en construcción](#modo-en-construcción). |

### Modo "En construcción"

Ajustes del sitio → **Estado del sitio** cierra el sitio público: las visitas ven un cartel con la grúa en lugar de las páginas. Pensado para un dominio nuevo que todavía no se quiere mostrar (por ejemplo, el entorno de prueba mientras se migra).

- **El interruptor no puede dejarte afuera.** `UnderConstruction` va colgado de las dos rutas públicas (`/` y `/{page:slug}`) y no del grupo `web`, así que `/admin`, `/login` y el recupero de contraseña quedan abiertos **por construcción** y no por una lista de excepciones que se pueda olvidar de actualizar. Hay un test que se cae si alguna vez se mueve al grupo web.
- **Con sesión iniciada seguís viendo el sitio real**, para poder revisarlo mientras las visitas ven el cartel. La contra es que desde adentro no se nota que está cerrado, así que `PublicLayout` muestra una cinta naranja abajo a la izquierda que lleva de vuelta a Ajustes — la red contra dejarlo puesto una semana sin enterarse. Y como por lo mismo el cartel no se puede ver navegando, el panel tiene `/admin/settings/construccion`, que lo dibuja tal cual lo ven las visitas.
- **Contesta 503 y no 200.** Es "el sitio existe pero hoy no está, volvé más tarde": el cartel no se indexa ni desplaza a las páginas de verdad en los resultados de Google, que es justo lo que hace falta en un dominio de prueba. Inertia lo dibuja igual porque el cliente decide por el header `X-Inertia`, no por el código de estado. Va además con `Cache-Control: no-store`, para que sacar el interruptor se vea al toque y ningún intermediario siga sirviendo el cartel con el sitio ya abierto.
- **El título y el mensaje** se editan en el mismo lugar; vacíos, caen en los textos de fábrica de `App\Support\Construction`, que es también de donde el panel los saca para pintarlos en gris dentro de los campos. El teléfono, el WhatsApp, el email, el Instagram y la dirección que muestra el cartel salen solos del resto de Ajustes: es la única salida que se ofrece, porque el menú llevaría a páginas que el propio cartel tapa.
- La **ilustración** (`ConstructionArt.vue`) es SVG dibujado a mano, no un archivo: pesa nada, se ve nítida en cualquier pantalla y toma los colores de la marca, así que sigue combinando si la paleta cambia. El bloque que cuelga de la grúa se hamaca apenas, y respeta `prefers-reduced-motion`.

### Calendario

`/calendario` (sección `event_calendar`) arma la grilla del mes sola, juntando dos fuentes. **No se cargan actividades en la página del calendario**: se cargan donde ya viven.

| Fuente | Cómo entra | Dónde se edita |
|---|---|---|
| **Clases, cursos y retiros, gratis** (`class_info`) | Necesitan dos cosas: el campo **“Fechas para el calendario”** de la ficha (día de la semana + hora, o una fecha puntual con fecha de fin si dura varios días, y vigencia opcional *desde/hasta*), y estar **tildadas en el panel → Calendario** (`sections.show_on_calendar`, que arranca en true). Sin fechas no aparece, aunque esté tildada. | Las fechas en Páginas → la página → la ficha. El tilde en Calendario. |
| **Eventos** | Igual: **tildados en el panel → Calendario** (`events.show_on_calendar`, que arranca en false) y con fecha de inicio. Con fecha de fin ocupan todos los días del rango. | Eventos, o el tilde en Calendario. |

La pantalla **Calendario** del panel lista las dos fuentes juntas, cada una con su tilde y un tilde maestro arriba que marca o desmarca todo. Sólo se lista lo que podría aparecer —visible, en una página visible—, porque lo que está oculto en el sitio tampoco está en el calendario. Al lado de cada ficha se lee el resumen de sus fechas (`Occurrences::describe()`, ej. *“miércoles de 19:00 a 20:15 hs”*), así se ve qué va a publicar sin abrirla. Sacar una ficha del calendario **no** la oculta de su página.

El campo **“Horario”** de la ficha de clase (`'Miércoles de 19 a 20.15 hs'`) sigue siendo texto libre y es lo que se lee en la tarjeta; las “Fechas para el calendario” son la versión que entiende el código. Son dos cosas separadas a propósito: el texto admite cualquier redacción (“a partir de septiembre, los miércoles”) y adivinarla sería frágil.

Qué se respeta solo:

- **Ocultar** una ficha de clase, o su página, la saca del calendario. Misma regla que el resto del sitio: lo que no se ve, no está.
- Una actividad cargada dos veces (misma fecha, título, hora y lugar) **aparece una sola vez por día** — pasa con las meditaciones que están tanto en Clases semanales como en Gratis, y al clonar una sección.
- **Es siempre el mes en curso**: no hay navegación a otros meses ni `?mes=`, y las celdas de los meses vecinos que completan la primera y la última fila van **vacías** (viajan como `null`, sin número ni actividades). Las filas conservan las 7 celdas para que cada día caiga bajo su columna.
- El “hoy” y el mes se calculan en **hora de Argentina**, no en UTC: `config/app.php` sigue en UTC y la zona vive en `EventCalendar::TIMEZONE`. Sin eso, a las 21 del 31 de agosto el calendario ya mostraría septiembre.
- Los nombres de meses y días están escritos a mano en `EventCalendar` (`APP_LOCALE` es `en`, así que Carbon devolvería inglés). Cada día viaja con su `weekday` ISO para que la vista no tenga que parsear fechas en el navegador.
- En **escritorio** es la grilla del mes con píldoras por día (hasta 2, después “+N más”) y el detalle del día en un modal; en **celular** es una semana por vez con las actividades desplegadas, moviéndose sólo entre las semanas del mes. El color y el ícono de cada píldora identifican de qué página sale la actividad (hay una referencia debajo).

### Login con Google (opcional)

Además del login con email/contraseña, el panel soporta **"Continuar con Google"** (Laravel Socialite). Solo entran las cuentas de una **lista blanca**, que se arma de dos lados que se suman (`App\Support\GoogleAccess`):

- **`GOOGLE_ALLOWED_EMAILS`** en el `.env` del servidor: el/los email(s) del dueño. Es la lista fija, no se edita desde el panel para que un error ahí no lo deje afuera.
- **Ajustes → Acceso con Google**: las cuentas que el dueño habilita después, sin tocar el servidor. Se guardan en el ajuste `google_allowed_emails` (una por línea, normalizadas a minúscula y sin repetir). Cualquiera de esas cuentas entra al panel **completo** (no hay roles); al entrar por primera vez, el `User` se crea solo.

1. Crear credenciales OAuth 2.0 en [Google Cloud Console](https://console.cloud.google.com/apis/credentials) → *Aplicación web*.
   - URI de redirección autorizado: `https://TU_DOMINIO/auth/google/callback` (y `http://meditazn.test/auth/google/callback` para local).
2. Completar en `.env`:
   ```env
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   GOOGLE_ALLOWED_EMAILS=persona@gmail.com   # el dueño; separados por coma
   ```
   El `GOOGLE_REDIRECT_URI` se deriva de `APP_URL` (podés fijarlo si querés uno distinto).

El botón "Continuar con Google" aparece en el login **solo si `GOOGLE_CLIENT_ID` está configurado**. Cualquier cuenta fuera de la lista blanca es rechazada aunque el login de Google sea exitoso.

## Arquitectura de contenido

- Cada página se compone de **secciones tipadas** (`sections.type` + `content` JSON). Los **17 tipos** están definidos en `app/Support/SectionRegistry.php` — la única fuente de verdad para los formularios del admin y la validación:

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
  | `class_info` | Ficha de clase (horario, lugar, precio, CTA, clases del ciclo) |
  | `person` | Maestro / persona (rol, nombre, cargo, bio, foto, lado) |
  | `figure` | Imagen destacada (título + imagen + pie) |
  | `pricing` | Planes / abonos (tarjetas de precios) |
  | `event_strip` | Eventos destacados en la home |
  | `event_list` | Listado de eventos |
  | `event_calendar` | Grilla mensual (mes en escritorio, semana en celular) |
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
  php artisan db:seed --class=CursosYRetirosFichaSeeder --force
  php artisan db:seed --class=VoluntariadoPortadaSeeder --force
  php artisan db:seed --class=CalendarioSeeder --force
  php artisan db:seed --class=CalendarioFechasSeeder --force   # una sola vez, ver abajo
  php artisan db:seed --class=AppKadampaSeeder --force          # tarjeta del pie, no una página
  ```

Hay tres primitivos, y la diferencia importa en producción:

| Primitivo | Qué hace | Cuándo |
|---|---|---|
| `ContentSeeder::seedSinglePage($slug)` | Upsert de la página **y de todas sus secciones** desde el archivo de datos: reescribe contenido, vuelve a poner `visible = true` y renumera posiciones. **Pisa lo editado desde el panel en esa página.** | Publicar una página nueva, o resetear una a su estado sembrado. Lo usan los 4 primeros seeders y `CalendarioSeeder`. |
| `ContentSeeder::seedMissingFooterResource($url)` | Agrega **una tarjeta a los recursos del pie** (el ajuste `footer_resources`, no una sección) si todavía no hay ninguna con esa url. Va al final, copia su imagen a storage y no toca las que ya están. | Sumar un libro o una app al pie sin pisar lo que el dueño haya editado en las otras tarjetas. Lo usa `AppKadampaSeeder`. |
| `ContentSeeder::seedMissingSection($slug, $key, $visible)` | Inserta **una sola sección** si esa página todavía no la tiene, justo debajo de la que la precede en el archivo de datos; el resto solo corre una posición. Repetible y no toca nada más. Con `visible: false` entra oculta; con `'template' => true` en el archivo de datos entra además como **plantilla** (`is_template`, siempre oculta y no eliminable). | Agregar un bloque a una página que el dueño ya editó. Lo usan `ClasesSemanalesPortadaSeeder`, `CursosYRetirosFichaSeeder` y `ClasesSemanalesFichaSeeder` (las fichas plantilla) y `VoluntariadoPortadaSeeder`. Si se agregan **dos** secciones seguidas, el orden de las llamadas importa: cada una se ubica debajo de la que la precede en el archivo de datos, así que la de arriba tiene que insertarse primero. |

`CalendarioSeeder` **no** siembra las “Fechas para el calendario” de las clases: en producción los horarios ya están editados y no coinciden con el archivo de datos, así que sembrarlas desde ahí publicaría clases en días equivocados. Una ficha sin fechas no aparece en el calendario, así que no hay estado intermedio roto.

De eso se ocupó `CalendarioFechasSeeder`, **una sola vez**: tiene los valores de las 9 fichas que había en producción al estrenar el calendario, escritos a partir del horario que cada una ya mostraba (el comentario de cada línea es el texto del que sale). Sólo completa las fichas con el campo vacío, así que no pisa lo que se cargue a mano, y es repetible. **No es el camino para cargar fechas nuevas** — eso se hace desde el panel; queda en el repo como registro de la carga inicial.

## Deploy a producción (Hostinger)

Hosting compartido de Hostinger con acceso SSH. Notas clave:

- **PHP 8.4** (el `composer.lock` lo requiere): usar `/opt/alt/php84/usr/bin/php` para `composer` y `artisan`.
- **Document root del subdominio → carpeta `public`**: como Hostinger no deja cambiar el docroot, se resuelve con un **symlink** dentro de `.../pablomandile/public/<subdominio> → ../../<carpeta-app>/public`.
- Flujo por actualización: compilar assets local (`npm run build`), subir la app por `scp` (sin `node_modules`, `vendor`, `.env` ni `bootstrap/cache`), en el server `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, correr los seeders puntuales que correspondan y `php artisan optimize` (precedido de `optimize:clear`: con la config cacheada, `env()` devuelve vacío).
- El `.env` de producción (con `APP_KEY`, DB y claves de Google) **no** está versionado.

## Tests

```bash
php artisan test
```

Incluye `tests/Feature/SiteContentTest.php`: render público, ocultar secciones/páginas, permisos del admin, edición de secciones con reemplazo de imagen, validaciones, eventos en la home, FAQs compartidas y el calendario (clases semanales en todos sus días, eventos de varios días, `?mes=`, el día argentino y no UTC, el tilde masivo del panel). En `tests/Unit/OccurrencesTest.php` está la expansión de las reglas de fecha a días concretos, con sus casos borde.

Las aserciones sobre el HTML usan **fragmentos sin acentos**: Inertia serializa los props con escapes unicode, así que `assertSee('tradición')` nunca coincide. Y el `<title>` depende de `APP_NAME`, que en CI es distinto.
