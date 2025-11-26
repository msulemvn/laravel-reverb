<?php

use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

Route::prefix('comments')->name('comments.')->middleware('auth')->group(function () {
    Route::post('/', [CommentController::class, 'store'])->name('store');
    Route::put('/{comment}', [CommentController::class, 'update'])->name('update');
    Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('destroy');
});
