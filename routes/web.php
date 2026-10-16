<?php

use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PopupController;
use App\Http\Controllers\Admin\RecurringDataController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TutorialController;
use App\Http\Controllers\PageController;
use App\Http\Middleware\UnderConstruction;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::redirect('/', '/admin/pages');

    Route::get('pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::get('pages/{page}', [AdminPageController::class, 'show'])->name('pages.show');
    Route::patch('pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
    Route::patch('pages/{page}/toggle', [AdminPageController::class, 'toggle'])->name('pages.toggle');
    Route::patch('pages/{page}/move', [AdminPageController::class, 'move'])->name('pages.move');

    Route::get('media', [MediaController::class, 'index'])->name('media.index');

    Route::get('gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Route::delete('gallery', [GalleryController::class, 'destroy'])->name('gallery.destroy');

    Route::get('sections/{section}/edit', [SectionController::class, 'edit'])->name('sections.edit');
    Route::put('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');
    Route::post('sections/{section}/duplicate', [SectionController::class, 'duplicate'])->name('sections.duplicate');
    Route::patch('sections/{section}/toggle', [SectionController::class, 'toggle'])->name('sections.toggle');
    Route::patch('sections/{section}/move', [SectionController::class, 'move'])->name('sections.move');

    Route::resource('events', EventController::class)->except(['show']);
    Route::patch('events/{event}/toggle', [EventController::class, 'toggle'])->name('events.toggle');
    Route::patch('events/{event}/toggle-home', [EventController::class, 'toggleHome'])->name('events.toggle-home');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::patch('calendar', [CalendarController::class, 'bulk'])->name('calendar.bulk');
    Route::patch('calendar/sections/{section}', [CalendarController::class, 'toggleSection'])->name('calendar.sections.toggle');
    Route::patch('calendar/events/{event}', [CalendarController::class, 'toggleEvent'])->name('calendar.events.toggle');

    Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
    Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
    Route::patch('faqs/{faq}/move', [FaqController::class, 'move'])->name('faqs.move');

    Route::get('tutorials', [TutorialController::class, 'index'])->name('tutorials.index');
    Route::post('tutorials', [TutorialController::class, 'store'])->name('tutorials.store');
    Route::put('tutorials/{tutorial}', [TutorialController::class, 'update'])->name('tutorials.update');
    Route::delete('tutorials/{tutorial}', [TutorialController::class, 'destroy'])->name('tutorials.destroy');
    Route::patch('tutorials/{tutorial}/move', [TutorialController::class, 'move'])->name('tutorials.move');

    Route::get('newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
    Route::post('newsletters', [NewsletterController::class, 'store'])->name('newsletters.store');
    Route::get('newsletters/{newsletter}/edit', [NewsletterController::class, 'edit'])->name('newsletters.edit');
    Route::put('newsletters/{newsletter}', [NewsletterController::class, 'update'])->name('newsletters.update');
    Route::delete('newsletters/{newsletter}', [NewsletterController::class, 'destroy'])->name('newsletters.destroy');
    Route::get('newsletters/{newsletter}/preview', [NewsletterController::class, 'preview'])->name('newsletters.preview');
    Route::post('newsletters/{newsletter}/duplicate', [NewsletterController::class, 'duplicate'])->name('newsletters.duplicate');
    Route::post('newsletters/{newsletter}/test', [NewsletterController::class, 'sendTest'])->name('newsletters.test');
    Route::patch('newsletters/{newsletter}/schedule', [NewsletterController::class, 'schedule'])->name('newsletters.schedule');
    Route::patch('newsletters/{newsletter}/unschedule', [NewsletterController::class, 'unschedule'])->name('newsletters.unschedule');
    Route::post('newsletters/{newsletter}/send', [NewsletterController::class, 'sendNow'])->name('newsletters.send');

    Route::get('datos-recurrentes', [RecurringDataController::class, 'index'])->name('recurring.index');
    Route::post('datos-recurrentes/maestros', [RecurringDataController::class, 'storeTeacher'])->name('recurring.teachers.store');
    Route::delete('datos-recurrentes/maestros/{teacher}', [RecurringDataController::class, 'destroyTeacher'])->name('recurring.teachers.destroy');
    Route::post('datos-recurrentes/lugares', [RecurringDataController::class, 'storeVenue'])->name('recurring.venues.store');
    Route::delete('datos-recurrentes/lugares/{venue}', [RecurringDataController::class, 'destroyVenue'])->name('recurring.venues.destroy');

    Route::get('popup', [PopupController::class, 'edit'])->name('popup.edit');
    Route::post('popup', [PopupController::class, 'update'])->name('popup.update');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    // El cartel tal cual lo ven las visitas: con sesión iniciada el sitio público
    // no lo muestra, así que sin esta vista el dueño no puede revisarlo.
    Route::get('settings/construccion', [SettingController::class, 'construction'])->name('settings.construction');
});

Route::get('dashboard', fn () => redirect()->route('admin.pages.index'))
    ->middleware(['auth'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// PWA manifest dinámico — usa el logo real cargado en Ajustes.
// El archivo estático public/manifest.webmanifest fue eliminado para que el
// rewrite de .htaccess pase la petición acá y se sirva el ícono correcto.
Route::get('/manifest.webmanifest', function () {
    $favicon = rescue(fn () => \App\Models\Setting::favicon(), null, false);
    $name = rescue(fn () => \App\Models\Setting::get('site_name'), null, false) ?? 'Meditar en Zona Norte';
    $shortName = mb_strlen($name) <= 12 ? $name : 'Meditar ZN';

    $icons = [];

    // Si hay un logo cargado en PNG/WebP/JPEG lo usamos como ícono principal,
    // pero pasado por /pwa-icon: opaco y con margen. Un logo con fondo
    // transparente declarado "maskable" se ve como un cuadrado negro en Android.
    if ($favicon && in_array($favicon['type'], ['image/png', 'image/webp', 'image/jpeg'])) {
        $v = substr(md5($favicon['path']), 0, 8);
        foreach ([192, 512] as $px) {
            foreach (['any', 'maskable'] as $purpose) {
                $icons[] = ['src' => "/pwa-icon/{$px}-{$purpose}.png?v={$v}", 'sizes' => "{$px}x{$px}", 'type' => 'image/png', 'purpose' => $purpose];
            }
        }
    }

    // Íconos estáticos de respaldo con tamaños concretos (requeridos por Chrome).
    $icons[] = ['src' => '/icons/icon-192.png?v=1', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'];
    $icons[] = ['src' => '/icons/icon-512.png?v=1', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'];
    $icons[] = ['src' => '/icons/icon-512.png?v=1', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'];

    return response()->json([
        'name'             => $name,
        'short_name'       => $shortName,
        'start_url'        => '/',
        'scope'            => '/',
        'display'          => 'standalone',
        'background_color' => '#ffffff',
        'theme_color'      => '#ffffff',
        'lang'             => 'es-AR',
        'icons'            => $icons,
    ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ->header('Content-Type', 'application/manifest+json')
        ->header('Cache-Control', 'no-cache, must-revalidate, max-age=0');
})->name('pwa.manifest');

// Ícono de la PWA derivado del logo: fondo blanco opaco y logo centrado dentro
// de la zona segura (maskable: 60% del lado; any: 80%).
Route::get('/pwa-icon/{size}-{purpose}.png', function (int $size, string $purpose) {
    abort_unless(in_array($size, [192, 512], true) && in_array($purpose, ['any', 'maskable'], true), 404);

    $favicon = rescue(fn () => \App\Models\Setting::favicon(), null, false);
    $file = $favicon ? storage_path('app/public/'.$favicon['path']) : null;
    abort_unless($file && is_file($file) && function_exists('imagecreatefromstring'), 404);

    $src = @imagecreatefromstring((string) file_get_contents($file));
    abort_unless($src, 404);

    $canvas = imagecreatetruecolor($size, $size);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

    $box = (int) round($size * ($purpose === 'maskable' ? 0.6 : 0.8));
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min($box / $w, $box / $h);
    $dw = max(1, (int) round($w * $scale));
    $dh = max(1, (int) round($h * $scale));
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $src, intdiv($size - $dw, 2), intdiv($size - $dh, 2), 0, 0, $dw, $dh, $w, $h);

    ob_start();
    imagepng($canvas);
    $png = ob_get_clean();

    return response($png, 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->whereNumber('size')->name('pwa.icon');

// Public site — the slug catch-all must stay LAST.
//
// El interruptor de "En construcción" se cuelga acá y no del grupo web: tapa el
// sitio público y nada más, así ponerlo no puede dejar al dueño afuera del panel.
Route::middleware(UnderConstruction::class)->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/{page:slug}', [PageController::class, 'show'])->name('page.show');
});
