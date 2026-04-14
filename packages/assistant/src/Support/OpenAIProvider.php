<?php

declare(strict_types=1);

namespace Capell\Assistant\Support;

use Capell\Assistant\Exceptions\OpenAICircuitBreakerOpenException;
use Capell\Core\Contracts\ServiceContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;
use RuntimeException;
use Throwable;

class OpenAIProvider implements ServiceContract
{
    protected readonly string $circuitBreakerKey;

    protected readonly int $failureThreshold;

    protected readonly int $circuitTimeout;

    protected int $maxRetries;

    protected int $retryDelay;

    public function __construct(protected array $config = [])
    {
        $this->circuitBreakerKey = 'openai_circuit_breaker_state';
        $this->failureThreshold = 5;
        $this->circuitTimeout = 300;
        $this->maxRetries = (int) ($this->config['max_retries'] ?? 3);
        $this->retryDelay = (int) ($this->config['retry_delay_ms'] ?? 1000);
    }

    /**
     * @throws Throwable
     */
    public function execute(array $input): mixed
    {
        return $this->chat($input);
    }

    public function chat(array $params): AiResponse
    {
        throw_if($this->isCircuitOpen(), OpenAICircuitBreakerOpenException::class);

        $attempt = 0;
        $lastException = null;
        $startTime = microtime(true);

        while ($attempt < $this->maxRetries) {
            try {
                $this->logRequest($params, $attempt);

                $response = OpenAIFacade::chat()->create($params);

                $duration = microtime(true) - $startTime;
                $this->resetCircuitBreaker();
                $this->recordMetrics($params, $response, $duration);

                return new AiResponse(
                    content: $response->choices[0]->message->content ?? '',
                    tokensUsed: (int) ($response->usage->total_tokens ?? 0),
                    model: (string) ($params['model'] ?? 'unknown'),
                    duration: $duration,
                    metadata: [
                        'finish_reason' => $response->choices[0]->finish_reason ?? null,
                        'prompt_tokens' => (int) ($response->usage->prompt_tokens ?? 0),
                        'completion_tokens' => (int) ($response->usage->completion_tokens ?? 0),
                    ],
                );
            } catch (Throwable $e) {
                $attempt++;
                $lastException = $e;
                $this->recordFailure();
                $this->logAttemptFailure($params, $attempt, $e);

                throw_if($attempt >= $this->maxRetries, $lastException);

                $delay = $this->retryDelay * (2 ** ($attempt - 1));
                $jitter = random_int(0, (int) ($delay * 0.1));
                Sleep::usleep(($delay + $jitter) * 1000);
            }
        }

        throw $lastException ?? new RuntimeException('Unknown OpenAI error');
    }

    public function isAvailable(): bool
    {
        return Cache::remember('openai_api_health_check', 300, function (): bool {
            try {
                OpenAIFacade::models()->list();

                return true;
            } catch (Throwable) {
                return false;
            }
        });
    }

    public function handles(): string
    {
        return 'openai_provider';
    }

    public function resetCircuitBreaker(): void
    {
        Cache::forget($this->circuitBreakerKey);
    }

    protected function isCircuitOpen(): bool
    {
        $state = Cache::get($this->circuitBreakerKey, ['failures' => 0]);

        return (int) ($state['failures'] ?? 0) >= $this->failureThreshold;
    }

    protected function recordFailure(): void
    {
        $state = Cache::get($this->circuitBreakerKey, ['failures' => 0]);
        $state['failures'] = (int) ($state['failures'] ?? 0) + 1;
        Cache::put($this->circuitBreakerKey, $state, $this->circuitTimeout);
    }

    protected function recordMetrics(array $params, object $response, float $duration): void
    {
        Log::debug('AI API Call Metrics', [
            'model' => $params['model'] ?? 'unknown',
            'total_tokens' => $response->usage->total_tokens ?? 0,
            'duration_ms' => round($duration * 1000, 2),
        ]);
    }

    protected function logRequest(array $params, int $attempt): void
    {
        Log::debug('OpenAI Chat Request', [
            'model' => $params['model'] ?? 'unknown',
            'messages_count' => count($params['messages'] ?? []),
            'attempt' => $attempt + 1,
            'max_tokens' => $params['max_tokens'] ?? 'default',
        ]);
    }

    protected function logAttemptFailure(array $params, int $attempt, Throwable $e): void
    {
        Log::warning('OpenAI API attempt failed', [
            'attempt' => $attempt,
            'max_retries' => $this->maxRetries,
            'error' => $e->getMessage(),
            'model' => $params['model'] ?? 'unknown',
        ]);
    }
}
