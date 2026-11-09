<?php

use Illuminate\Support\Facades\Route;
use KraftInk\Plugins\Faq\Http\Controllers\DocumentController;
use KraftInk\Plugins\Faq\Http\Controllers\ImageController;

Route::prefix('faq')
    ->name('plugins.faq.public.')
    ->middleware(['web', 'permission:faq.view'])
    ->group(function () {
        Route::prefix('images')
            ->name('images.')
            ->group(function () {
                Route::get('/{image}', [ImageController::class, 'show'])
                    ->whereUuid('image')
                    ->name('show');
            });

        Route::name('documents.')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])
                ->middleware('capability:extension,faq')
                ->name('index');
            Route::get('/{locale}/questions/{slug}', [DocumentController::class, 'localized'])
                ->where(['locale' => '[A-Za-z0-9_-]{2,12}', 'slug' => '[a-z0-9-]+'])
                ->name('localized');
            Route::get('/{slug}', [DocumentController::class, 'show'])->name('show');
        });
    });
