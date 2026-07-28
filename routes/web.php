<?php

use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::redirect('/', '/admin/pages');

    Route::get('pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::get('pages/{page}', [AdminPageController::class, 'show'])->name('pages.show');

    Route::get('sections/{section}/edit', [SectionController::class, 'edit'])->name('sections.edit');
    Route::put('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::post('sections/{section}/duplicate', [SectionController::class, 'duplicate'])->name('sections.duplicate');
    Route::patch('sections/{section}/toggle', [SectionController::class, 'toggle'])->name('sections.toggle');
    Route::patch('sections/{section}/move', [SectionController::class, 'move'])->name('sections.move');

    Route::resource('events', EventController::class)->except(['show']);
    Route::patch('events/{event}/toggle', [EventController::class, 'toggle'])->name('events.toggle');
    Route::patch('events/{event}/toggle-home', [EventController::class, 'toggleHome'])->name('events.toggle-home');

    Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
    Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
    Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
    Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
    Route::patch('faqs/{faq}/move', [FaqController::class, 'move'])->name('faqs.move');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
});

Route::get('dashboard', fn () => redirect()->route('admin.pages.index'))
    ->middleware(['auth'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

// Public site — the slug catch-all must stay LAST.
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/{page:slug}', [PageController::class, 'show'])->name('page.show');
