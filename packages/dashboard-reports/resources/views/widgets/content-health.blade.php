<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('capell-dashboard-reports::dashboard.widget_content_health')"
    >
        <div class="space-y-2">
            @foreach ($this->data->issues as $issue)
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        @if ($issue->count === 0)
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-green-500"
                            ></span>
                        @else
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-amber-500"
                            ></span>
                        @endif
                        <span class="text-sm">{{ $issue->label }}</span>
                    </div>
                    @if ($issue->count > 0 && $issue->filterUrl)
                        <a
                            href="{{ $issue->filterUrl }}"
                            class="text-primary-600 text-xs font-semibold hover:underline"
                        >
                            {{ $issue->count }}
                        </a>
                    @else
                        <span class="text-xs text-gray-400">
                            {{ $issue->count }}
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
