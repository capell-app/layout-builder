<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Database\Factories\TypeFactory;
use Capell\Layout\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Content>
 */
class ContentTypeFactory extends TypeFactory
{
    public function content(): self
    {
        return $this->state([
            'type' => 'content',
        ]);
    }
}
