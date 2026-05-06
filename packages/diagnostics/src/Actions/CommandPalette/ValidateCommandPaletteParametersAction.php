<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\CommandPalette;

use Capell\Diagnostics\Data\CommandPaletteCommandData;
use Capell\Diagnostics\Data\CommandPaletteParameterData;
use Capell\Diagnostics\Enums\CommandPaletteParameterType;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;

final class ValidateCommandPaletteParametersAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    public function handle(CommandPaletteCommandData $command, array $parameters): array
    {
        if ($command->parameters === []) {
            return [];
        }

        return Validator::make($parameters, $this->rules($command))->validate();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(CommandPaletteCommandData $command): array
    {
        $rules = [];

        foreach ($command->parameters as $parameter) {
            $rules[$parameter->name] = $this->rulesForParameter($parameter);
        }

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    private function rulesForParameter(CommandPaletteParameterData $parameter): array
    {
        $rules = [
            $parameter->required ? 'required' : 'nullable',
            ...$parameter->rules,
        ];

        return match ($parameter->type) {
            CommandPaletteParameterType::String => [...$rules, 'string'],
            CommandPaletteParameterType::Boolean => [...$rules, 'boolean'],
        };
    }
}
