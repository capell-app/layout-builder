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
        $widgetClass = $widget::class;
        $message = sprintf(
            "Missing required '%s' asset for widget %s (key: '%s').",
            $assetType,
            $widgetClass,
            $widget->key,
        );

        if ($assetIdentifier !== null) {
            $message .= sprintf(
                ' Asset identifier: %s.',
                is_scalar($assetIdentifier)
                    ? "'" . $assetIdentifier . "'"
                    : json_encode($assetIdentifier, JSON_UNESCAPED_SLASHES),
            );
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
