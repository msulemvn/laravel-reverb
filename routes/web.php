<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/home.php';
require __DIR__ . '/tags.php';
require __DIR__ . '/posts.php';
require __DIR__ . '/comments.php';
require __DIR__ . '/users.php';
require __DIR__ . '/roles.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
