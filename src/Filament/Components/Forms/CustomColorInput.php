<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Support\Arrayable;
use Livewire\Component;

class CustomColorInput
{
    public static function make(string $name, string $label, null|array|Arrayable|Closure $options = null): Group
    {
        if ($options === null) {
            $options = [
                'primary' => __('capell-admin::generic.primary'),
                'secondary' => __('capell-admin::generic.secondary'),
                'gray' => __('capell-admin::generic.gray'),
                'light-gray' => __('capell-admin::generic.light_gray'),
                'success' => __('capell-admin::generic.success'),
                'warning' => __('capell-admin::generic.warning'),
                'danger' => __('capell-admin::generic.danger'),
                'info' => __('capell-admin::generic.info'),
                'dark-gray' => __('capell-admin::generic.dark_gray'),
                'custom' => __('capell-admin::generic.custom'),
            ];
        }

        return Group::make()
            ->schema([
                Select::make($name)
                    ->label($label)
                    ->searchable()
                    ->reactive()
                    ->preload()
                    ->mutateDehydratedStateUsing(
                        fn (?string $state, Get $get): mixed => $state === 'custom' ? $get($name . '_custom') : $state,
                    )
                    ->afterStateUpdated(function (Set $set, ?string $state) use ($name): void {
                        if (blank($state)) {
                            $set($name . '_custom', '');
                        }
                    })
                    ->options(function (Set $set, ?string $state, Component $livewire) use ($name, $options): array {
                        if (is_callable($options)) {
                            $options = $options($livewire);
                        }

                        if ($options instanceof Arrayable) {
                            $options = $options->toArray();
                        }

                        if ($state !== null && $state !== '' && ! isset($options[$state])) {
                            $set($name, 'custom');
                            $set($name . '_custom', $state);
                        }

                        $options['custom'] = __('capell-admin::form.option_custom');

                        return $options;
                    }),

                ColorPicker::make($name . '_custom')
                    ->label(__('capell-layout-builder::form.custom'))
                    ->hiddenLabel()
                    ->placeholder(__('capell-admin::generic.custom'))
                    ->dehydrated(false)
                    ->visible(function (Get $get, Component $livewire) use ($name, $options): bool {
                        if ($get($name) !== 'custom') {
                            return false;
                        }

                        if (is_callable($options)) {
                            $options = $options($livewire);
                        }

                        if ($options instanceof Arrayable) {
                            $options = $options->toArray();
                        }

                        return ! in_array($get($name), $options, true);
                    })
                    ->autoFormat(),
            ]);
    }
}
