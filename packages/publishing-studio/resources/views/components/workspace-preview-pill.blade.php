<?php
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;

$workspace = WorkspaceContext::current();

if (! $workspace instanceof Workspace) {
    return;
}

$currentUrl = request()->fullUrl();
$exitUrl = route('capell-frontend.preview.exit', ['redirect' => $currentUrl]);
?>

<div
    class="workspace-preview-pill"
    data-workspace-preview="{{ $workspace->uuid }}"
    style="
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 2147483647;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.875rem;
        border-radius: 9999px;
        background: rgba(17, 24, 39, 0.92);
        color: #fff;
        font-family: system-ui, sans-serif;
        font-size: 0.8125rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    "
>
    @if ($workspace->color !== null && $workspace->color !== '')
        <span
            aria-hidden="true"
            style="
                display: inline-block;
                width: 0.5rem;
                height: 0.5rem;
                border-radius: 9999px;
                background-color: {{ $workspace->color }};
            "
        ></span>
    @endif

    <span>
        {{ __('capell-frontend::workspace.preview.previewing', ['name' => $workspace->name]) }}
    </span>

    <a
        href="{{ $exitUrl }}"
        style="
            color: #fff;
            text-decoration: underline;
            text-decoration-style: dotted;
        "
    >
        {{ __('capell-frontend::workspace.preview.exit') }}
    </a>
</div>
