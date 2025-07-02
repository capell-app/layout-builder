<?php

declare(strict_types=1);

?>

@props([
    'component',
    'container',
    'containerColspan' => null,
    'containerKey',
    'containerIndex',
    'loop',
    'occurrence' => $widgetData['occurrence'] ?? 1,
    'pageSlot' => null,
    'type',
    'widget',
    'widgetIndex',
    'widgetData',
])
@if ($type === 'blade')
    <x-dynamic-component
        :component="$component"
        :$container
        :$containerColspan
        :$containerKey
        :$containerIndex
        :$loop
        :$pageSlot
        :$occurrence
        :$widget
        :$widgetData
        :$widgetIndex
    />
@elseif ($type === 'livewire')
    @livewire($component,
        [
            'container' => $container,
            'containerColspan' => $containerColspan,
            'containerKey' => $containerKey,
            'containerIndex' => $containerIndex,
            'loop' => $loop,
            'pageSlot' => $pageSlot,
            'widget' => $widget,
            'widgetData' => $widgetData,
            'widgetIndex' => $widgetIndex,
        ],
        key($containerKey.'-'.$widget->key.'-'.$occurrence))
@endif

<?php
