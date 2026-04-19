<?php

declare(strict_types=1);

namespace Filament\Schemas\Components;

class TextInput
{
    public static function make(?string $name = null): self
    {
        return new self;
    }
}

class Textarea
{
    public static function make(?string $name = null): self
    {
        return new self;
    }
}

class Select
{
    public static function make(?string $name = null): self
    {
        return new self;
    }
}

class Toggle
{
    public static function make(?string $name = null): self
    {
        return new self;
    }
}

class Repeater
{
    public static function make(?string $name = null): self
    {
        return new self;
    }
}

namespace Filament\Forms\Components;

class Section
{
    public static function make(?string $heading = null): self
    {
        return new self;
    }
}

class Group
{
    public static function make(): self
    {
        return new self;
    }
}
