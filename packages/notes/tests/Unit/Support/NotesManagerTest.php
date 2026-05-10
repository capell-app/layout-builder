<?php

declare(strict_types=1);

use Capell\Notes\Support\NotesManager;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

require_once dirname(__DIR__, 2) . '/NotesTestCase.php';

it('only allows registered note subjects and participants', function (): void {
    $notes = resolve(NotesManager::class);
    $user = User::factory()->create();
    $unsupportedModel = new class extends Model
    {
        use HasFactory;

        protected $table = 'unsupported_notes';
    };

    $notes->ensureSubject($user);
    $notes->ensureParticipant($user);

    expect(fn () => $notes->ensureSubject($unsupportedModel))
        ->toThrow(InvalidArgumentException::class, 'not been registered as a note subject')
        ->and(fn () => $notes->ensureParticipant($unsupportedModel))
        ->toThrow(InvalidArgumentException::class, 'not been registered as a note participant');
});
