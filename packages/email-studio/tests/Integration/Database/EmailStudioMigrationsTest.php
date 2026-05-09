<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the email studio tables', function (): void {
    foreach (config('capell-email-studio.tables') as $tableName) {
        expect(Schema::hasTable($tableName))->toBeTrue();
    }
});
