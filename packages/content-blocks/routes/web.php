<?php

declare(strict_types=1);

use Capell\ContentBlocks\Http\Controllers\ContentBlockDemoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->get('/content-blocks/{block}', ContentBlockDemoController::class)
    ->where('block', '[A-Za-z0-9_-]+')
    ->name('capell-content-blocks.demo');
