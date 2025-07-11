<?php

declare(strict_types=1);

?>

@php
    use Capell\Admin\Facades\CapellAdmin;
@endphp

<div class="flex items-center justify-between">
    <h3 class="text-lg font-medium leading-6 text-gray-900">
        {{ $widget->key }}
    </h3>
    <p class="mt-1 max-w-2xl text-sm text-gray-500">
        {{ $widget->type->name }}
    </p>
</div>
<div class="mt-4 flex items-center justify-between">
    <div>
        Site:
        <br />
        <p class="text-sm font-medium text-gray-500">
            Type:
            <span class="text-green-600">
                {{ $widget->getMetaComponentType() }}
            </span>
        </p>
        <p class="text-sm font-medium text-gray-500">
            Component:
            <span class="text-green-600">
                {{ $widget->getMetaComponent() }}
            </span>
        </p>
    </div>
</div>

<?php
