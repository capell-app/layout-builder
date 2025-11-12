<?php

declare(strict_types=1);

namespace Capell\Layout\Exceptions;

use Capell\Layout\Models\Widget;
use Exception;

class MissingWidgetAssetException extends Exception
{
    protected array $context;

    public function __construct(Widget $widget, string $assetType, mixed $assetIdentifier = null, array $context = [])
    {
        $message = sprintf("The required asset of type '%s' for the widget '%s' is missing.", $assetType, $widget->key);
        if ($assetIdentifier !== null) {
            $message .= " Asset Identifier: '" . (is_scalar($assetIdentifier) ? (string) $assetIdentifier : json_encode($assetIdentifier)) . "'.";
        }

        if ($context !== []) {
            $this->context = $context;

            $message .= ' Context: ' . json_encode($this->context, JSON_UNESCAPED_SLASHES) . '.';
        } else {
            $this->context = [];
        }

        parent::__construct($message);
    }

    /**
     * @return array<string,mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
