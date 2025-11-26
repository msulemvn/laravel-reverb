<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;


Route::middleware('auth')->prefix('roles')->name('permissions.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/', [RoleController::class, 'destroy'])->name('destroy');
});
