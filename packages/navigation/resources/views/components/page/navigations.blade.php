<?php

declare(strict_types=1);

?>

@php
    use Capell\Admin\Facades\CapellAdmin;
    use Capell\Core\Contracts\Pageable;
    use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
    use Capell\Navigation\Models\Navigation;
    use Illuminate\Support\Facades\DB;

    /* @var Pageable $record */
    $record = $getRecord();

    /* @var class-string<\Capell\Navigation\Models\Navigation> $model */
    $model = Navigation::class;

    $navigations = $model::with('language')
        ->when(
            DB::getDriverName() === 'sqlite',
            fn ($query) => $query->whereRaw(
                'EXISTS (SELECT 1 FROM json_each(items) WHERE json_each.value = ?)',
                [$record->id],
            ),
            fn ($query) => $query->whereRaw(
                "JSON_SEARCH(JSON_EXTRACT(items, '$.*'), 'one', ?) IS NOT NULL",
                [$record->id],
            ),
        )
        ->orderBy('site_id')
        ->orderBy('name')
        ->orderBy('language_id')
        ->get();
@endphp

<div>
    @if ($navigations->isNotEmpty())
        <div class="mb-2 text-lg font-semibold leading-tight">
            {{ __('capell-admin::generic.page_navigations') }}
        </div>
        <div
            class="grid grid-cols-1 gap-4 divide-y divide-white/50 md:grid-cols-2"
        >
            @foreach ($navigations as $navigation)
                @php
                    $previous = ! $loop->first ? $navigations->get($loop->index - 1) : null;
                    $next = ! $loop->last ? $navigations->get($loop->index + 1) : null;
                @endphp

                @if ($loop->first || ($previous && $navigation->name !== $previous->name))
                    {{-- format-ignore-start --}}
                    <ul class="max-w-md space-y-1 text-gray-500 dark:text-gray-300">
                        {{-- format-ignore-end --}}
                @endif

                <li>
                    <x-filament::link
                        :href="
                            NavigationResource::getUrl(
                                'edit',
                                ['record' => $navigation],
                            )
                        "
                        tag="a"
                        size="xs"
                    >
                        {{ $navigation->name }}
                        @if ($navigation->language && $navigation->language->flag)
                            <x-dynamic-component
                                class="ml-2 h-4 w-5"
                                :component="'flag-4x3-' . $navigation->language->flag"
                            />
                        @endif
                    </x-filament::link>
                </li>
                @if ($loop->last || ($next && $navigation->name !== $next->name))
                    {{-- format-ignore-start --}}</ul>{{-- format-ignore-end --}}
                @endif
            @endforeach
        </div>
    @endif
</div>

<?php
