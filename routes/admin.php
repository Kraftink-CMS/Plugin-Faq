<?php

use Illuminate\Support\Facades\Route;
use KraftInk\Plugins\Faq\Http\Controllers\DocumentController;
use KraftInk\Plugins\Faq\Http\Controllers\ImageController;

Route::prefix('admin/plugins/faq')
    ->name('plugins.faq.admin.')
    ->middleware(['web', 'auth', 'permission:admin.access', 'permission:faq.manage'])
    ->group(function () {
        Route::prefix('images')
            ->name('images.')
            ->group(function () {
                Route::post('/', [ImageController::class, 'store'])
                    ->middleware('throttle:30,1')
                    ->name('store');
            });

        Route::prefix('documents')
            ->name('documents.')
            ->group(function () {
                Route::get('/', [DocumentController::class, 'admin'])->name('index');
                Route::get('/create', [DocumentController::class, 'edit'])->name('create');
                Route::post('/', [DocumentController::class, 'save'])->name('store');
                Route::get('/{id}/revisions', [DocumentController::class, 'revisions'])
                    ->whereNumber('id')
                    ->name('revisions.index');
                Route::post('/{id}/revisions/{revision}/restore', [DocumentController::class, 'restore'])
                    ->whereNumber(['id', 'revision'])
                    ->name('revisions.restore');
                Route::get('/{id}/edit', [DocumentController::class, 'edit'])
                    ->whereNumber('id')
                    ->name('edit');
                Route::put('/{id}', [DocumentController::class, 'save'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('/{id}', [DocumentController::class, 'destroy'])
                    ->whereNumber('id')
                    ->name('destroy');
            });

        Route::prefix('categories')
            ->name('categories.')
            ->group(function () {
                Route::get('/', [DocumentController::class, 'admin'])
                    ->defaults('section', 'categories')
                    ->name('index');
                Route::post('/', [DocumentController::class, 'saveCategory'])->name('store');
                Route::put('/{id}', [DocumentController::class, 'saveCategory'])
                    ->whereNumber('id')
                    ->name('update');
                Route::delete('/{id}', [DocumentController::class, 'destroyCategory'])
                    ->whereNumber('id')
                    ->name('destroy');
            });
    });
