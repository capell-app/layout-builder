@php
    use Capell\DeveloperTools\Enums\CommandPaletteParameterType;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <div class="max-w-3xl">
            <input
                class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                placeholder="Search commands, packages, cache, migrations, queues..."
                type="search"
                wire:model.live="query"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
            <section class="space-y-5">
                @forelse ($this->groupedCommands as $group => $commands)
                    <div class="space-y-2">
                        <h2
                            class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            {{ $group }}
                        </h2>

                        <div
                            class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-800 dark:border-gray-800"
                        >
                            @foreach ($commands as $command)
                                <button
                                    class="flex w-full items-start justify-between gap-4 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-900"
                                    type="button"
                                    wire:click="selectCommand('{{ $command->id }}')"
                                >
                                    <span class="min-w-0">
                                        <span
                                            class="block text-sm font-medium text-gray-950 dark:text-gray-100"
                                        >
                                            {{ $command->label }}
                                        </span>
                                        @if ($command->description !== null)
                                            <span
                                                class="mt-1 block text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{ $command->description }}
                                            </span>
                                        @endif
                                    </span>
                                    <span
                                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                    >
                                        {{ $command->type->value }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div
                        class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                    >
                        No commands found.
                    </div>
                @endforelse
            </section>

            <aside
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950"
            >
                @if ($this->selectedCommand)
                    @php($command = $this->selectedCommand)

                    <form
                        class="space-y-4"
                        wire:submit="executeSelectedCommand"
                    >
                        <div>
                            <h2
                                class="text-sm font-semibold text-gray-950 dark:text-gray-100"
                            >
                                {{ $command->label }}
                            </h2>
                            @if ($command->description !== null)
                                <p
                                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ $command->description }}
                                </p>
                            @endif
                        </div>

                        @if ($this->warningFor($command) !== null)
                            <div
                                class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200"
                            >
                                {{ $this->warningFor($command) }}
                            </div>
                        @endif

                        @if ($command->requiresConfirmation)
                            <label
                                class="flex items-start gap-2 rounded-md border border-gray-200 px-3 py-2 text-xs text-gray-600 dark:border-gray-800 dark:text-gray-300"
                            >
                                <input
                                    class="text-primary-600 focus:ring-primary-500 mt-0.5 rounded border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950"
                                    type="checkbox"
                                    wire:model="confirmed"
                                />
                                <span>
                                    I understand this command will run on this
                                    application.
                                </span>
                            </label>
                        @endif

                        @foreach ($command->parameters as $parameter)
                            <label class="block space-y-1.5 text-sm">
                                <span
                                    class="font-medium text-gray-700 dark:text-gray-300"
                                >
                                    {{ $parameter->label }}
                                </span>

                                @if ($parameter->type === CommandPaletteParameterType::Boolean)
                                    <input
                                        class="text-primary-600 focus:ring-primary-500 rounded border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-950"
                                        type="checkbox"
                                        wire:model="parameters.{{ $parameter->name }}"
                                    />
                                @else
                                    <input
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                                        type="text"
                                        wire:model="parameters.{{ $parameter->name }}"
                                    />
                                @endif

                                @if ($parameter->description !== null)
                                    <span
                                        class="block text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ $parameter->description }}
                                    </span>
                                @endif

                                @error($parameter->name)
                                    <span
                                        class="text-danger-600 dark:text-danger-400 block text-xs"
                                    >
                                        {{ $message }}
                                    </span>
                                @enderror
                            </label>
                        @endforeach

                        <div
                            class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800"
                        >
                            <button
                                class="rounded-md px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                                type="button"
                                wire:click="clearSelection"
                            >
                                Cancel
                            </button>
                            <button
                                class="bg-primary-600 hover:bg-primary-500 rounded-md px-3 py-2 text-sm font-medium text-white disabled:opacity-70"
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="executeSelectedCommand"
                            >
                                Run
                            </button>
                        </div>
                    </form>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Select a command to review parameters, permission scope,
                        and confirmation requirements.
                    </p>
                @endif
            </aside>
        </div>
    </div>
</x-filament-panels::page>
