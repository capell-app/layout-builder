<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('capell-layout-builder::widgets.admin.layout_health.heading')"
    >
        <div class="space-y-2">
            @forelse ($data->workQueue as $item)
                <div
                    class="rounded-lg border border-gray-200 px-3 py-2 dark:border-white/10"
                >
                    @if ($item->url !== null)
                        <a
                            href="{{ $item->url }}"
                            class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400"
                        >
                            {{ $item->title }}
                        </a>
                    @else
                        <span
                            class="text-sm font-medium text-gray-900 dark:text-white"
                        >
                            {{ $item->title }}
                        </span>
                    @endif

                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">
                        {{ $item->description }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    {{ __('capell-layout-builder::widgets.admin.layout_health.work_queue_empty') }}
                </p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
