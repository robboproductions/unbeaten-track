<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\PoiAboutAiDraftController;
use App\Http\Controllers\Admin\PoiController;
use App\Http\Controllers\Admin\PoiNarrationController;
use App\Http\Controllers\Admin\PoiNarrationScriptDraftController;
use App\Http\Controllers\Admin\PoiPhotoController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\TownAboutAiDraftController;
use App\Http\Controllers\Admin\TownController;
use App\Http\Controllers\Admin\TownNarrationController;
use App\Http\Controllers\Admin\TownNarrationScriptDraftController;
use App\Http\Controllers\Admin\TownPhotoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->middleware(['auth', 'admin.panel'])->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    })->name('home');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('can:superAdmin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::get('maps/style.json', [MapController::class, 'style'])
        ->middleware('throttle:60,1')
        ->name('maps.style');
    Route::get('maps/proxy', [MapController::class, 'proxy'])
        ->middleware('throttle:300,1')
        ->name('maps.proxy');
    Route::post('maps/geocode', [MapController::class, 'geocode'])
        ->middleware('throttle:45,1')
        ->name('maps.geocode');

    Route::delete('towns/{town}/photos/{photo}', [TownPhotoController::class, 'destroy'])
        ->name('towns.photos.destroy');
    Route::patch('towns/{town}/photos/{photo}', [TownPhotoController::class, 'update'])
        ->name('towns.photos.update');
    Route::post('towns/{town}/photos/{photo}/primary', [TownPhotoController::class, 'primary'])
        ->name('towns.photos.primary');

    Route::get('towns/map', [TownController::class, 'map'])->name('towns.map');
    Route::post('towns/{town}/ai-about-draft', TownAboutAiDraftController::class)
        ->middleware('throttle:8,1,towns-ai-about-draft')
        ->name('towns.ai-about-draft');
    Route::post('towns/{town}/ai-narration-script-draft', TownNarrationScriptDraftController::class)
        ->middleware('throttle:8,1,towns-ai-narration-script-draft')
        ->name('towns.ai-narration-script-draft');
    Route::post('towns/{town}/narration/generate', [TownNarrationController::class, 'generate'])
        ->name('towns.narration.generate');
    Route::delete('towns/{town}/narration', [TownNarrationController::class, 'destroy'])
        ->name('towns.narration.destroy');
    Route::resource('towns', TownController::class)->except(['show']);

    Route::delete('pois/{poi}/photos/{photo}', [PoiPhotoController::class, 'destroy'])
        ->name('pois.photos.destroy');
    Route::patch('pois/{poi}/photos/{photo}', [PoiPhotoController::class, 'update'])
        ->name('pois.photos.update');
    Route::post('pois/{poi}/photos/{photo}/primary', [PoiPhotoController::class, 'primary'])
        ->name('pois.photos.primary');

    Route::get('pois/map', [PoiController::class, 'map'])->name('pois.map');
    Route::post('pois/{poi}/ai-about-draft', PoiAboutAiDraftController::class)
        ->middleware('throttle:8,1,pois-ai-about-draft')
        ->name('pois.ai-about-draft');
    Route::post('pois/{poi}/ai-narration-script-draft', PoiNarrationScriptDraftController::class)
        ->middleware('throttle:8,1,pois-ai-narration-script-draft')
        ->name('pois.ai-narration-script-draft');
    Route::post('pois/{poi}/narration/generate', [PoiNarrationController::class, 'generate'])
        ->name('pois.narration.generate');
    Route::delete('pois/{poi}/narration', [PoiNarrationController::class, 'destroy'])
        ->name('pois.narration.destroy');
    Route::resource('pois', PoiController::class)->except(['show']);
});
