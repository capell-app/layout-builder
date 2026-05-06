<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\SeoSuite\Contracts\AiActionContextInterface;
use Capell\SeoSuite\Events\AiGenerationCompleted;
use Capell\SeoSuite\Events\AiGenerationFailed;
use Capell\SeoSuite\Events\AiGenerationStarted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

class ApplyAiDraftAction
{
    use AsAction;

    /**
     * Apply the draft content from context to the provided target object.
     *
     * @param  array{target:object}  $options
     */
    public function handle(AiActionContextInterface $context, array $options = []): bool
    {
        $startTime = microtime(true);
        Event::dispatch(new AiGenerationStarted(static::class, [$context, $options]));

        try {
            throw_unless($context instanceof AiActionContextInterface, InvalidArgumentException::class, 'Invalid context');

            $target = $options['target'] ?? null;
            $draft = method_exists($context, 'getContent') ? $context->getContent() : null;

            throw_unless(is_object($target), InvalidArgumentException::class, 'Target must be an object');
            throw_unless(property_exists($target, 'content'), InvalidArgumentException::class, 'Target must have a content property');

            $target->content = is_string($draft) ? $draft : null;

            $saved = true;
            if (method_exists($target, 'save')) {
                $result = $target->save();
                $saved = $result === null ? true : (bool) $result;
            }

            $duration = microtime(true) - $startTime;
            Log::info('AI Action completed', [
                'action' => static::class,
                'duration_ms' => round($duration * 1000, 2),
            ]);
            Event::dispatch(new AiGenerationCompleted(static::class, $saved, []));

            return $saved;
        } catch (Throwable $throwable) {
            Log::error('AI Action failed', [
                'action' => static::class,
                'error' => $throwable->getMessage(),
            ]);
            Event::dispatch(new AiGenerationFailed(static::class, $throwable));
            throw $throwable;
        }
    }
}
