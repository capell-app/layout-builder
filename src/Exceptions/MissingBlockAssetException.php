<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Exceptions;

use Capell\LayoutBuilder\Models\Block;
use Exception;

class MissingBlockAssetException extends Exception
{
    /**
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(Block $block, string $assetType, mixed $assetIdentifier = null, array $context = [])
    {
        $blockClass = $block::class;
        $message = sprintf(
            "Missing required '%s' asset for block %s (key: '%s').",
            $assetType,
            $blockClass,
            $block->key,
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
