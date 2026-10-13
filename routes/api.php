<?php

use Illuminate\Support\Facades\Route;
use Library\Adapters\Inbound\Http\LibraryController;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [LibraryController::class, 'login']);

    Route::middleware('api.token')->group(function (): void {
        Route::get('/categories', [LibraryController::class, 'categories']);
        Route::get('/books', [LibraryController::class, 'books']);
        Route::get('/books/{id}', [LibraryController::class, 'book']);
        Route::get('/members', [LibraryController::class, 'members']);
        Route::get('/loans', [LibraryController::class, 'loans']);
        Route::get('/loans/overdue', [LibraryController::class, 'overdue']);
        Route::get('/loans/{id}', [LibraryController::class, 'showLoan']);
        Route::post('/loans', [LibraryController::class, 'createLoan']);
        Route::post('/loans/{loanId}/items/{itemId}/return', [LibraryController::class, 'returnItem']);
        Route::post('/members', [LibraryController::class, 'createMember'])->middleware('api.token:admin,librarian');
        Route::get('/reports/most-borrowed', [LibraryController::class, 'report'])->middleware('api.token:admin');
        Route::post('/users', [LibraryController::class, 'createUser'])->middleware('api.token:admin');
        Route::post('/books', [LibraryController::class, 'createBook'])->middleware('api.token:admin');
        Route::put('/books/{id}', [LibraryController::class, 'updateBook'])->middleware('api.token:admin');
        Route::delete('/books/{id}', [LibraryController::class, 'deleteBook'])->middleware('api.token:admin');
    });
});
