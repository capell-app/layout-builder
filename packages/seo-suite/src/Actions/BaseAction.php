<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use BadMethodCallException;
use Capell\SeoSuite\Contracts\ActionContract;
use Capell\SeoSuite\Contracts\AiActionContextInterface;
use Capell\SeoSuite\Events\AiGenerationCompleted;
use Capell\SeoSuite\Events\AiGenerationFailed;
use Capell\SeoSuite\Events\AiGenerationStarted;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static mixed run(...$args)
 */
abstract class BaseAction implements ActionContract
{
    use AsObject;

    protected array $metadata = [];

    protected float $startTime = 0.0;

    public static function __callStatic(string $method, array $arguments)
    {
        if ($method === 'run') {
            $instance = App::make(static::class);

            return $instance->run(...$arguments);
        }

        throw new BadMethodCallException(sprintf('Method %s does not exist on ', $method) . static::class);
    }

    /** Implement in child actions */
    abstract protected function perform(AiActionContextInterface $context, array $options = []): mixed;

    public function handle(...$args): mixed
    {
        /** @var AiActionContextInterface|null $context */
        $context = $args[0] ?? null;
        $options = is_array($args[1] ?? null) ? $args[1] : [];

        throw_unless($this->validate(['context' => $context, 'options' => $options]), InvalidArgumentException::class, 'Invalid AI action input: missing context or malformed options.');

        $this->before($context, $options);

        try {
            $result = $this->perform($context, $options);
            $this->after($result);

            return $result;
        } catch (Throwable $throwable) {
            $this->onFailure($throwable);
            throw $throwable;
        }
    }

    public function validate(array $input): bool
    {
        // TODO: Replace with a dedicated validator/service; keep fast guards here for now.
        $context = $input['context'] ?? null;
        $options = $input['options'] ?? null;

        if (! $context instanceof AiActionContextInterface) {
            return false;
        }

        if ($options !== null && ! is_array($options)) {
            return false;
        }

        return true;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    protected function before(...$args): void
    {
        $this->startTime = microtime(true);
        event(new AiGenerationStarted(static::class, $args));
    }

    protected function after(mixed $result): void
    {
        $duration = microtime(true) - $this->startTime;
        Log::info('AI Action completed', [
            'action' => static::class,
            'duration_ms' => round($duration * 1000, 2),
            'metadata' => $this->metadata,
        ]);
        event(new AiGenerationCompleted(static::class, $result, $this->metadata));
    }

    protected function onFailure(Throwable $e): void
    {
        Log::error('AI Action failed', [
            'action' => static::class,
            'error' => $e->getMessage(),
        ]);
        event(new AiGenerationFailed(static::class, $e));
    }

    protected function setMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }
}
