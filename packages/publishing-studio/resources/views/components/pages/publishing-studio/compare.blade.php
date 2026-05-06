@php
    use Capell\PublishingStudio\Checks\PublishCheckResult;
    use Capell\PublishingStudio\Checks\PublishCheckSeverity;
    use Capell\PublishingStudio\Models\Workspace;

    /** @var Workspace $workspace */
    /** @var array<int, PublishCheckResult> $checkResults */
@endphp

<x-filament-panels::page>
    @livewire('capell-publishing-studio::diff-panel', ['workspaceId' => $workspace->id])

    @if ($checkResults)
        <div class="mt-6">
            <h3 class="mb-3 text-lg font-semibold">
                {{ __('capell-admin::workspace.compare.checks_heading') }}
            </h3>
            <div class="space-y-3">
                @foreach ($checkResults as $checkResult)
                    @php
                        $severityColor = match ($checkResult->severity) {
                            PublishCheckSeverity::Error => 'danger',
                            PublishCheckSeverity::Warn => 'warning',
                            PublishCheckSeverity::Info => 'info',
                        };
                    @endphp

                    <div
                        class="{{ $checkResult->isClean() ? 'border-success-200 bg-success-50' : 'border-' . $severityColor . '-200 bg-' . $severityColor . '-50' }} rounded-lg border p-4"
                    >
                        <div class="flex items-center gap-2">
                            <span class="font-medium">
                                {{ $checkResult->label }}
                            </span>
                            <x-filament::badge :color="$severityColor">
                                {{ $checkResult->severity->value }}
                            </x-filament::badge>
                            @if ($checkResult->isClean())
                                <x-filament::badge color="success">
                                    {{ __('capell-admin::workspace.compare.check_passed') }}
                                </x-filament::badge>
                            @endif
                        </div>
                        @if (! $checkResult->isClean())
                            <ul class="mt-2 space-y-1 text-sm">
                                @foreach ($checkResult->messages as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                            @if ($checkResult->entityRefs)
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ trans_choice('capell-admin::workspace.compare.check_entity_refs', count($checkResult->entityRefs)) }}
                                </p>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
