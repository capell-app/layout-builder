<?php

declare(strict_types=1);

use Capell\BlockLibrary\Http\Controllers\ContentBlockDemoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->get('/block-library/{block}', ContentBlockDemoController::class)
    ->where('block', '[A-Za-z0-9_-]+')
    ->name('capell-block-library.demo');
