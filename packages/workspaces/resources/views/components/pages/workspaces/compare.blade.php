@php
    use Capell\Workspaces\Models\Workspace;

    /** @var Workspace $workspace */
@endphp

<x-filament-panels::page>
    @livewire('capell-workspaces::diff-panel', ['workspaceId' => $workspace->id])
</x-filament-panels::page>
