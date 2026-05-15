<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Exceptions;

use Capell\LayoutBuilder\Models\Element;
use Exception;

class MissingElementAssetException extends Exception
{
    /**
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(Element $element, string $assetType, mixed $assetIdentifier = null, array $context = [])
    {
        $elementClass = $element::class;
        $message = sprintf(
            "Missing required '%s' asset for element %s (key: '%s').",
            $assetType,
            $elementClass,
            $element->key,
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
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
