<x-filament-panels::page>
    <section class="space-y-4">
        <div>
            <h2 class="text-xl font-semibold tracking-tight">
                {{ __('capell-theme-studio-admin::studio.gallery_heading') }}
            </h2>
            <p class="text-sm text-gray-500">
                {{ __('capell-theme-studio-admin::studio.gallery_intro') }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($this->themeCards() as $theme)
                @php
                    $previewPath = parse_url((string) $theme['previewImage'], PHP_URL_PATH) ?: '';
                    $previewAvailable = $previewPath !== '' && file_exists(public_path(ltrim($previewPath, '/')));
                    $firstPreset = $theme['presets'][0] ?? null;
                    $primaryColor = $firstPreset?->values['primaryColor'] ?? '#111827';
                    $accentColor = $firstPreset?->values['accentColor'] ?? '#6b7280';
                @endphp

                <article
                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="aspect-video bg-gray-100 dark:bg-gray-800">
                        @if ($previewAvailable)
                            <img
                                src="{{ $theme['previewImage'] }}"
                                alt="{{ $theme['name'] }}"
                                class="h-full w-full object-cover"
                            />
                        @else
                            <div
                                class="flex h-full items-end justify-between p-5 text-white"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        {{ $primaryColor }},
                                        {{ $accentColor }}
                                    );
                                "
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold uppercase tracking-widest text-white/70"
                                    >
                                        {{ $theme['package'] }}
                                    </p>
                                    <p class="mt-2 text-2xl font-bold">
                                        {{ $theme['name'] }}
                                    </p>
                                </div>
                                <span
                                    class="h-12 w-12 rounded-full border border-white/40 bg-white/20"
                                ></span>
                            </div>
                        @endif
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">
                                    {{ $theme['name'] }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $theme['description'] }}
                                </p>
                            </div>

                            @if ($theme['active'])
                                <span
                                    class="bg-success-100 text-success-700 rounded-full px-2 py-1 text-xs font-medium"
                                >
                                    {{ __('capell-theme-studio-admin::studio.active') }}
                                </span>
                            @elseif ($theme['draft'])
                                <span
                                    class="bg-warning-100 text-warning-700 rounded-full px-2 py-1 text-xs font-medium"
                                >
                                    {{ __('capell-theme-studio-admin::studio.draft') }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($theme['tags'] as $tag)
                                <span
                                    class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>

                        <dl class="mt-4 grid gap-3 text-sm">
                            <div>
                                <dt class="font-medium">
                                    {{ __('capell-theme-studio-admin::studio.best_fit') }}
                                </dt>
                                <dd class="mt-1 text-gray-500">
                                    {{ implode(', ', $theme['bestFit']) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium">
                                    {{ __('capell-theme-studio-admin::studio.included_sections') }}
                                </dt>
                                <dd class="mt-1 text-gray-500">
                                    {{ implode(', ', $theme['includedSections']) }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-5 space-y-3">
                            @foreach ($theme['presets'] as $preset)
                                @php
                                    $presetPrimary = $preset->values['primaryColor'] ?? $primaryColor;
                                    $presetAccent = $preset->values['accentColor'] ?? $accentColor;
                                @endphp

                                <div
                                    class="rounded-md border border-gray-200 p-3 dark:border-gray-800"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="h-4 w-4 rounded-full border border-white shadow-sm ring-1 ring-gray-200"
                                                    style="
                                                        background: linear-gradient(
                                                            135deg,
                                                            {{ $presetPrimary }},
                                                            {{ $presetAccent }}
                                                        );
                                                    "
                                                ></span>
                                                <p class="text-sm font-medium">
                                                    {{ $preset->name }}
                                                </p>
                                            </div>
                                            <p
                                                class="mt-1 text-xs text-gray-500"
                                            >
                                                {{ $preset->description }}
                                            </p>
                                        </div>

                                        @if ($theme['active'] && $theme['activePreset'] === $preset->key)
                                            <span
                                                class="bg-success-100 text-success-700 rounded-full px-2 py-1 text-xs font-medium"
                                            >
                                                {{ __('capell-theme-studio-admin::studio.active') }}
                                            </span>
                                        @elseif ($theme['draft'] && $theme['draftPreset'] === $preset->key)
                                            <span
                                                class="bg-warning-100 text-warning-700 rounded-full px-2 py-1 text-xs font-medium"
                                            >
                                                {{ __('capell-theme-studio-admin::studio.draft') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <x-filament::link
                                            :href="$this->previewUrl($theme['key'], $preset->key)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {{ __('capell-theme-studio-admin::studio.preview') }}
                                        </x-filament::link>

                                        <x-filament::button
                                            size="xs"
                                            wire:click="stageTheme('{{ $theme['key'] }}', '{{ $preset->key }}')"
                                        >
                                            {{ __('capell-theme-studio-admin::studio.stage') }}
                                        </x-filament::button>

                                        @if ($theme['draft'] && $theme['draftPreset'] === $preset->key)
                                            <x-filament::button
                                                color="success"
                                                size="xs"
                                                wire:click="publishDraft"
                                            >
                                                {{ $this->publishLabel() }}
                                            </x-filament::button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section
        class="mt-8 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
    >
        <h2 class="text-lg font-semibold">
            {{ __('capell-theme-studio-admin::studio.readiness_heading') }}
        </h2>

        <div class="mt-4 grid gap-3 md:grid-cols-3">
            @foreach ($this->readinessItems() as $item)
                <div
                    class="rounded-md border border-gray-200 p-3 dark:border-gray-800"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="{{ $item['complete'] ? 'bg-success-500' : 'bg-warning-500' }} h-2 w-2 rounded-full"
                        ></span>
                        <p class="font-medium">{{ $item['label'] }}</p>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ $item['description'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </section>
</x-filament-panels::page>
