<?php

use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\TownAboutAiDraftController;
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

    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');

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

    Route::delete('towns/{town}/photos/{photo}', [\App\Http\Controllers\Admin\TownPhotoController::class, 'destroy'])
        ->name('towns.photos.destroy');
    Route::patch('towns/{town}/photos/{photo}', [\App\Http\Controllers\Admin\TownPhotoController::class, 'update'])
        ->name('towns.photos.update');
    Route::post('towns/{town}/photos/{photo}/primary', [\App\Http\Controllers\Admin\TownPhotoController::class, 'primary'])
        ->name('towns.photos.primary');

    Route::get('towns/map', [\App\Http\Controllers\Admin\TownController::class, 'map'])->name('towns.map');
    Route::post('towns/{town}/ai-about-draft', TownAboutAiDraftController::class)
        ->middleware('throttle:8,1')
        ->name('towns.ai-about-draft');
    Route::resource('towns', \App\Http\Controllers\Admin\TownController::class)->except(['show']);
    Route::resource('pois', \App\Http\Controllers\Admin\PoiController::class)->except(['show']);
});
