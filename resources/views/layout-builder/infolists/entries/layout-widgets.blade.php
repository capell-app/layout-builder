@php
    use Capell\Admin\Support\AdminSurfaceLookup;
    use Capell\LayoutBuilder\Enums\ResourceEnum;
@endphp

<ul class="divide-y divide-gray-100 dark:divide-gray-800">
    @foreach ($getState() as $widgetKey)
        @php
            $widget = $widgets->firstWhere('key', $widgetKey);
        @endphp

        <li class="py-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium leading-6 text-gray-900">
                    {{ $widgetKey }}
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    {{ $widget->getMetaComponent() }}
                </p>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">
                    Type:
                    <span class="text-green-600">
                        {{ $widget->getMetaComponentType() }}
                    </span>
                </p>
                <x-filament::link
                    href="{{ AdminSurfaceLookup::resource(ResourceEnum::Widget)::getUrl('edit', ['record' => $widget]) }}"
                    color="info"
                >
                    {{ __('capell-admin::button.edit') }}
                </x-filament::link>
            </div>
        </li>
    @endforeach
</ul>
