@php
    use Capell\Admin\Support\AdminSurfaceLookup;
    use Capell\LayoutBuilder\Enums\ResourceEnum;
@endphp

<ul
    class="capell-entries-layout-blocks divide-y divide-gray-100 dark:divide-gray-800"
>
    @foreach ($getState() as $blockKey)
        @php
            $block = $blocks->firstWhere('key', $blockKey);
        @endphp

        <li class="py-5">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ $blockKey }}
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    {{ $block->getMetaComponent() }}
                </p>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">
                    Type:
                    <span class="text-green-600">
                        {{ $block->getMetaComponentType() }}
                    </span>
                </p>
                <x-filament::link
                    href="{{ AdminSurfaceLookup::resource(ResourceEnum::Block)::getUrl('edit', ['record' => $block]) }}"
                    color="info"
                >
                    {{ __('capell-admin::button.edit') }}
                </x-filament::link>
            </div>
        </li>
    @endforeach
</ul>
