@php
    use Capell\Workspaces\Filament\Resources\Workspaces\WorkspaceResource;
@endphp

<table class="w-full text-sm">
    <thead
        class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800"
    >
        <tr>
            <th class="px-4 py-2 text-left">
                {{ __('capell-admin::message.revisions_column_workspace') }}
            </th>
            <th class="px-4 py-2 text-left">
                {{ __('capell-admin::message.revisions_column_status') }}
            </th>
            <th class="px-4 py-2 text-left">
                {{ __('capell-admin::message.revisions_column_last_updated') }}
            </th>
            <th class="px-4 py-2 text-right">
                {{ __('capell-admin::message.revisions_column_actions') }}
            </th>
        </tr>
    </thead>
    <tbody>
        @foreach ($copies as $copy)
            @if ($copy->isLive())
                @continue
            @endif

            <tr class="border-b border-gray-100 dark:border-gray-800">
                <td class="px-4 py-2">{{ $copy->workspace?->name ?? '—' }}</td>
                <td class="px-4 py-2">
                    {{ $copy->workspace?->status?->getLabel() ?? '—' }}
                </td>
                <td class="px-4 py-2 text-gray-500">
                    {{ $copy->updated_at?->diffForHumans() }}
                </td>
                <td class="space-x-2 px-4 py-2 text-right">
                    @php($previewUrl = rescue(fn (): ?string => $copy->pageUrl?->full_url, null, false))
                    @if ($previewUrl)
                        <a
                            href="{{ $previewUrl }}"
                            target="_blank"
                            class="text-primary-600 hover:underline"
                        >
                            {{ __('capell-admin::message.revisions_preview') }}
                        </a>
                    @endif

                    @if ($copy->workspace)
                        <a
                            href="{{ WorkspaceResource::getUrl('compare', ['record' => $copy->workspace]) }}"
                            class="text-gray-600 hover:underline"
                        >
                            {{ __('capell-admin::message.revisions_open_workspace') }}
                        </a>
                        <button
                            type="button"
                            wire:click="deletePageDraft({{ $copy->id }})"
                            wire:confirm="{{ __('capell-admin::message.revisions_delete_confirm') }}"
                            class="text-red-600 hover:underline"
                        >
                            {{ __('capell-admin::message.revisions_delete') }}
                        </button>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
