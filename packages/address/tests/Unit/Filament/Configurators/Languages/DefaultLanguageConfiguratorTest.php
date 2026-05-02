<?php

declare(strict_types=1);

use Capell\Address\Filament\Components\Forms\FlagSelect;
use Capell\Address\Filament\Configurators\Languages\DefaultLanguageConfigurator;

require_once __DIR__ . '/../../../../../src/Support/Language/FlagsService.php';
require_once __DIR__ . '/../../../../../src/Filament/Components/Forms/FlagSelect.php';
require_once __DIR__ . '/../../../../../src/Filament/Configurators/Languages/DefaultLanguageConfigurator.php';

it('replaces the default language flag input with a flag select', function (): void {
    $reflectionMethod = new ReflectionMethod(DefaultLanguageConfigurator::class, 'makeFlagField');

    $field = $reflectionMethod->invoke(new DefaultLanguageConfigurator);

    expect($field)->toBeInstanceOf(FlagSelect::class);
});
