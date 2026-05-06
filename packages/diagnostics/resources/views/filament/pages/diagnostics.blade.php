<x-filament-panels::page>
    <div class="space-y-6">
        <section
            class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="min-w-0">
                    <p
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                    >
                        {{ __('capell-diagnostics::package.extension_authoring_eyebrow') }}
                    </p>
                    <h2
                        class="mt-1 text-base font-semibold text-gray-950 dark:text-white"
                    >
                        {{ __('capell-diagnostics::package.extension_authoring_heading') }}
                    </h2>
                    <p
                        class="mt-1 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-400"
                    >
                        {{ __('capell-diagnostics::package.extension_authoring_description') }}
                    </p>
                </div>

                <a
                    href="https://docs.capell.app/packages/how-to-create-a-capell-extension"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="fi-btn fi-btn-size-sm fi-color-gray fi-btn-color-gray shrink-0"
                >
                    {{ __('capell-diagnostics::package.extension_authoring_docs_link') }}
                </a>
            </div>
        </section>

        <section>
            <h2 class="text-lg font-semibold">
                {{ __('capell-diagnostics::package.diagnostics') }}
            </h2>
            <dl class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($this->safety() as $key => $value)
                    <div class="rounded-lg border border-gray-200 p-3">
                        <dt class="text-sm font-medium">{{ $key }}</dt>
                        <dd class="text-sm text-gray-600">
                            @if (is_array($value))
                                {{ implode(', ', $value) }}
                            @else
                                {{ is_bool($value) ? ($value ? 'yes' : 'no') : $value }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section>
            <h2 class="text-lg font-semibold">Makers</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($this->makers() as $maker)
                    <li class="rounded-lg border border-gray-200 p-3">
                        <span class="font-medium">{{ $maker->key }}</span>
                        <span class="text-gray-600">
                            {{ $maker->description }}
                        </span>
                        <div class="mt-3">
                            {{ $this->getAction('maker_' . str_replace('.', '_', $maker->key)) }}
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section>
            <h2 class="text-lg font-semibold">Registry</h2>
            <ul class="mt-3 space-y-2">
                @foreach ($this->configurators()->merge($this->components())->merge($this->blocks()) as $source)
                    <li class="rounded-lg border border-gray-200 p-3">
                        <span class="font-medium">
                            {{ $source->kind }}: {{ $source->key }}
                        </span>
                        <span class="block text-sm text-gray-600">
                            {{ $source->path ?? $source->class ?? $source->view }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
