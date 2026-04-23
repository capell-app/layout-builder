<?php

declare(strict_types=1);

?>

@props([
    'background' => 'bg-white/95',
    'containerClass' => 'relative',
    'containerTag' => 'div',
    'dropdownClass' => 'focus:outline-hidden absolute top-full z-50 mt-2 min-w-[230px] origin-top-right overflow-hidden border border-gray-200/80 backdrop-blur-sm',
    'darkMode' => false,
    'rounded' => 'rounded-lg',
    'shadow' => '',
    'label' => '',
    'name' => '',
    'offset' => 8,
    'panelClickOutside' => '',
    'panelHiddenClass' => '',
    'panelTag' => 'div',
    'panelVisibleClass' => '',
    'placement' => 'bottom-start',
    'shift' => false,
    'stopTriggerClickPropagation' => false,
    'teleport' => false,
    'triggerClass' => 'focus:outline-hidden focus-visible:ring-primary/40 inline-flex items-center justify-center rounded-md p-1 text-sm font-medium transition-colors focus-visible:ring-2',
    'triggerClick' => 'toggle($event)',
    'triggerType' => 'button',
    'useFloat' => true,
])
@php
    $dropdownName = $name !== '' ? $name : 'dropdown';

    $floatDropdownData = "{\n        toggle: function (event) {\n            \$refs['{$dropdownName}_dropdown'].toggle(event)\n        },\n\n        open: function (event) {\n            \$refs['{$dropdownName}_dropdown'].open(event)\n        },\n\n        close: function (event) {\n            \$refs['{$dropdownName}_dropdown'].close(event)\n        },\n    }";

    $inlineDropdownData = "{\n        isOpen: false,\n        toggle: function () {\n            if (this.isOpen) {\n                return this.close()\n            }\n\n            \$refs['{$dropdownName}_toggle'].focus()\n\n            this.isOpen = true\n        },\n\n        open: function () {\n            this.isOpen = true\n        },\n\n        close: function (focusAfter) {\n            if (! this.isOpen) return\n\n            this.isOpen = false\n\n            focusAfter && focusAfter.focus()\n        },\n    }";

    $dropdownData = $useFloat ? $floatDropdownData : $inlineDropdownData;

    $containerAttributes = $attributes->except('class')->merge(['class' => $containerClass]);

    $panelAttributes = $attributes->class([
        $dropdownClass,
        $rounded,
        $shadow,
        'hidden' => $useFloat,
        "divide-gray-100 {$background} text-gray-900" => ! $darkMode,
        'divide-gray-800 border-gray-800/80 bg-gray-950/95 text-gray-100' => $darkMode,
    ]);
@endphp

@if ($containerTag === 'li')
    <li
        x-data="{{ $dropdownData }}"
        {{ $containerAttributes }}
        x-id="['{{ $dropdownName }}-dropdown-button']"
    >
@else
    <div
        x-data="{{ $dropdownData }}"
        {{ $containerAttributes }}
        x-id="['{{ $dropdownName }}-dropdown-button']"
    >
@endif
    <button
        @if ($triggerType === 'button')
            type="button"
        @endif
        x-ref="{{ $dropdownName }}_toggle"
        @if ($stopTriggerClickPropagation)
            x-on:click.stop="{{ $triggerClick }}"
        @else
            x-on:click="{{ $triggerClick }}"
        @endif
        @if (! $useFloat)
            :aria-expanded="isOpen"
            :aria-controls="$id('{{ $dropdownName }}-dropdown-button')"
        @endif
        {{ $trigger->attributes->class([$triggerClass]) }}
    >
        {{ $trigger }}
    </button>

    @if ($panelTag === 'ul')
        <ul
            role="menu"
            tabindex="-1"
            x-ref="{{ $dropdownName }}_dropdown"
            x-cloak
            @if ($panelClickOutside !== '')
                x-on:click.outside="{{ $panelClickOutside }}"
            @endif
            @if ($useFloat)
                x-float{{ $placement ? ".placement.{$placement}" : '' }}.flip{{ $shift ? '.shift' : '' }}{{ $teleport ? '.teleport' : '' }}{{ $offset ? '.offset' : '' }}="{ offset: {{ $offset }} }"
                x-transition:enter-start="opacity-0"
                x-transition:leave-end="opacity-0"
                wire:ignore
            @endif
            @if (! $useFloat && $panelVisibleClass !== '' && $panelHiddenClass !== '')
                x-bind:class="isOpen ? '{{ $panelVisibleClass }}' : '{{ $panelHiddenClass }}'"
            @endif
            {{ $panelAttributes }}
            :id="$id('{{ $dropdownName }}-dropdown-button')"
        >
            {{ $slot }}
        </ul>
    @else
        <div
            role="menu"
            tabindex="-1"
            x-ref="{{ $dropdownName }}_dropdown"
            x-cloak
            @if ($panelClickOutside !== '')
                x-on:click.outside="{{ $panelClickOutside }}"
            @endif
            @if ($useFloat)
                x-float{{ $placement ? ".placement.{$placement}" : '' }}.flip{{ $shift ? '.shift' : '' }}{{ $teleport ? '.teleport' : '' }}{{ $offset ? '.offset' : '' }}="{ offset: {{ $offset }} }"
                x-transition:enter-start="opacity-0"
                x-transition:leave-end="opacity-0"
                wire:ignore
            @endif
            @if (! $useFloat && $panelVisibleClass !== '' && $panelHiddenClass !== '')
                x-bind:class="isOpen ? '{{ $panelVisibleClass }}' : '{{ $panelHiddenClass }}'"
            @endif
            {{ $panelAttributes }}
            :id="$id('{{ $dropdownName }}-dropdown-button')"
        >
            {{ $slot }}
        </div>
    @endif

@if ($containerTag === 'li')
    </li>
@else
    </div>
@endif

<?php
