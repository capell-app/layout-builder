<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('contents', 'collections');
    }

    public function down(): void
    {
        Schema::rename('collections', 'contents');
    }
};
