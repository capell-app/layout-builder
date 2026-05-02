<?php

declare(strict_types=1);

use Capell\Workspaces\Http\Controllers\ExitWorkspacePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('capell/preview/exit', ExitWorkspacePreviewController::class)
    ->name('capell-frontend.preview.exit');
