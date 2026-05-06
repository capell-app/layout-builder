<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BeaconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
        ];
    }
}
