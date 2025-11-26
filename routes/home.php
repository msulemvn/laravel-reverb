<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/search', HomeController::class)->name('search');

Route::prefix('/blog')->name('home.')->group(function () {
    Route::get('/', HomeController::class)->name('blog');
    Route::get('/search', HomeController::class)->name('search');
});
