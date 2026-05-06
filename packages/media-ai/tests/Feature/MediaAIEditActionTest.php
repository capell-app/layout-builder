<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\MediaEditActionExtender;
use Capell\Admin\Filament\Resources\Media\Pages\EditMedia;
use Capell\Core\Models\Media as CapellMedia;
use Capell\Core\Models\Page;
use Capell\MediaAI\Contracts\ImageDoctor;
use Capell\MediaAI\Filament\MediaAIEditActionExtender;
use Capell\MediaAI\Support\NullImageDoctor;
use Capell\MediaAI\Tests\Fixtures\RecordingImageDoctor;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    test()->actingAs(createMediaAIGlobalUser());

    Queue::fake();
    Storage::fake('public');
    config()->set('capell.media.model', CapellMedia::class);
    config()->set('media-library.media_model', CapellMedia::class);
});

function createMediaAIGlobalUser(): Authenticatable
{
    $user = new class extends Authenticatable implements FilamentUser
    {
        use HasFactory;

        protected $table = 'users';

        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }

        public function isGlobalAdmin(): bool
        {
            return true;
        }

        public function hasRole(string $role): bool
        {
            return true;
        }

        /** @return Collection<int, int> */
        public function getAssignedSiteIds(): Collection
        {
            return collect();
        }
    };

    $user->forceFill([
        'name' => 'Media AIOrchestrator Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ])->save();

    Relation::morphMap(['test-media-ai-admin-user' => $user::class], merge: true);

    return $user;
}

function createMediaAIImage(): CapellMedia
{
    $page = Page::factory()->create();

    /** @var CapellMedia $media */
    $media = $page
        ->addMedia(UploadedFile::fake()->image('ai-orchestrator.jpg', 32, 32))
        ->toMediaCollection('image');

    return $media;
}

it('registers a media edit action extender when enabled', function (): void {
    expect(collect(app()->tagged(MediaEditActionExtender::TAG))
        ->contains(fn (object $extender): bool => $extender instanceof MediaAIEditActionExtender))
        ->toBeTrue();
});

it('keeps the doctor action hidden until an ai-orchestrator-backed image doctor is bound', function (): void {
    expect(resolve(ImageDoctor::class))->toBeInstanceOf(NullImageDoctor::class);

    Livewire::test(EditMedia::class, [
        'record' => createMediaAIImage()->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertActionHidden('doctor-image');
});

it('passes image doctor requests to the configured ai-orchestrator implementation', function (): void {
    $doctor = new RecordingImageDoctor;
    app()->instance(ImageDoctor::class, $doctor);

    $media = createMediaAIImage();

    Livewire::test(EditMedia::class, [
        'record' => $media->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction('doctor-image', [
            'operation' => 'remove_background',
            'instructions' => 'Remove the background and keep the subject sharp.',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($doctor->media?->is($media))->toBeTrue()
        ->and($doctor->request?->operation)->toBe('remove_background')
        ->and($doctor->request?->instructions)->toBe('Remove the background and keep the subject sharp.');
});
