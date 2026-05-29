@php
    use Capell\Admin\Facades\CapellAdmin;
@endphp

<div class="capell-entries-layout-block flex items-center justify-between">
    <h3 class="text-lg leading-6 font-medium text-gray-900">
        {{ $block->key }}
    </h3>
    <p class="mt-1 max-w-2xl text-sm text-gray-500">
        {{ $block->type->name }}
    </p>
</div>
<div class="mt-4 flex items-center justify-between">
    <div>
        Site:
        <br />
        <p class="text-sm font-medium text-gray-500">
            Type:
            <span class="text-green-600">
                {{ $block->getMetaComponentType() }}
            </span>
        </p>
        <p class="text-sm font-medium text-gray-500">
            Component:
            <span class="text-green-600">
                {{ $block->getMetaComponent() }}
            </span>
        </p>
    </div>
</div>
