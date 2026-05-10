<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Notes\Models\Note;
use Capell\Notes\Models\NoteAssignment;
use Capell\Notes\Models\NoteMention;
use Capell\Notes\Models\NoteReminder;
use Capell\Notes\Providers\AdminServiceProvider;
use Capell\Notes\Providers\NotesServiceProvider;
use Capell\Notes\Tests\NotesTestCase;
use Illuminate\Support\ServiceProvider;

require_once dirname(__DIR__, 2) . '/NotesTestCase.php';

uses(NotesTestCase::class);

it('declares provider classes and package metadata', function (): void {
    $package = CapellCore::getPackage(NotesServiceProvider::$packageName);

    expect(NotesServiceProvider::class)->toExtend(AbstractPackageServiceProvider::class)
        ->and(AdminServiceProvider::class)->toExtend(ServiceProvider::class)
        ->and(NotesServiceProvider::$name)->toBe('capell-notes')
        ->and(NotesServiceProvider::$packageName)->toBe('capell-app/notes')
        ->and($package->name)->toBe('capell-app/notes')
        ->and($package->serviceProviderClass)->toBe(NotesServiceProvider::class)
        ->and($package->path)->toBe(realpath(__DIR__ . '/../../../'))
        ->and($package->getDescription())->toBe('Contextual notes, assignments, mentions, and reminders for Capell admin records.');
});

it('registers notes metadata, models, and protected tables when installed', function (): void {
    CapellCore::forcePackageInstalled(NotesServiceProvider::$packageName);

    (new NotesServiceProvider(app()))->packageRegistered();

    expect(CapellCore::getModels())->toContain(Note::class)
        ->and(CapellCore::getModels())->toContain(NoteAssignment::class)
        ->and(CapellCore::getModels())->toContain(NoteMention::class)
        ->and(CapellCore::getModels())->toContain(NoteReminder::class)
        ->and(CapellCore::getProtectedTables())->toContain('notes')
        ->and(CapellCore::getProtectedTables())->toContain('note_assignments')
        ->and(CapellCore::getProtectedTables())->toContain('note_mentions')
        ->and(CapellCore::getProtectedTables())->toContain('note_reminders');
});

it('keeps admin provider boot guarded when notes is not installed', function (): void {
    CapellCore::forcePackageInstalled(NotesServiceProvider::$packageName, false);

    $provider = new AdminServiceProvider(app());
    $provider->boot();

    expect(CapellCore::isPackageInstalled(NotesServiceProvider::$packageName))->toBeFalse();

    CapellCore::forcePackageInstalled(NotesServiceProvider::$packageName);
});
