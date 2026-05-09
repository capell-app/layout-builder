<?php

declare(strict_types=1);

namespace Capell\PublicActions\Support\Providers;

use Capell\PublicActions\Contracts\PublicActionDestinationAdapter;
use Capell\PublicActions\Data\PublicActionDispatchResultData;
use Capell\PublicActions\Enums\PublicActionDispatchStatus;
use Capell\PublicActions\Models\PublicActionDestination;
use Capell\PublicActions\Models\PublicActionDispatchAttempt;
use Capell\PublicActions\Models\PublicActionSubmission;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class HttpWebhookPublicActionAdapter implements PublicActionDestinationAdapter
{
    public function dispatch(
        PublicActionDestination $destination,
        PublicActionSubmission $submission,
    ): PublicActionDispatchResultData {
        $body = $this->body($destination, $submission);
        $requestHash = hash('sha256', json_encode($body, JSON_THROW_ON_ERROR));
        $attemptNumber = $this->nextAttemptNumber($destination, $submission);

        $attempt = PublicActionDispatchAttempt::query()->create([
            'public_action_submission_id' => $submission->getKey(),
            'public_action_destination_id' => $destination->getKey(),
            'adapter' => $destination->adapter,
            'status' => PublicActionDispatchStatus::Pending,
            'attempt' => $attemptNumber,
            'request_hash' => $requestHash,
            'response_status' => null,
            'response_summary' => null,
            'error_message' => null,
            'dispatched_at' => now(),
        ]);

        try {
            $endpointUrl = $this->endpointUrl($destination);

            $response = Http::timeout($this->timeoutSeconds($destination))
                ->withHeaders($this->headers($destination, $body))
                ->send($this->method($destination), $endpointUrl, $this->sendOptions($destination, $body));

            $status = $response->successful()
                ? PublicActionDispatchStatus::Succeeded
                : PublicActionDispatchStatus::Retryable;
            $summary = $this->redact($destination, Str::limit($response->body(), 1000, ''));

            $attempt->forceFill([
                'status' => $status,
                'response_status' => $response->status(),
                'response_summary' => $summary,
            ])->save();

            return new PublicActionDispatchResultData(
                success: $response->successful(),
                responseStatus: $response->status(),
                responseSummary: $summary,
                externalId: $response->header('X-Request-Id'),
                errorMessage: $response->successful() ? null : $summary,
            );
        } catch (ConnectionException $exception) {
            return $this->recordException($attempt, $destination, $exception, PublicActionDispatchStatus::Retryable);
        } catch (Throwable $exception) {
            return $this->recordException($attempt, $destination, $exception, PublicActionDispatchStatus::Failed);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function body(PublicActionDestination $destination, PublicActionSubmission $submission): array
    {
        $action = $submission->action;

        return [
            'action' => [
                'key' => $action?->key,
                'name' => $action?->name,
            ],
            'submission' => [
                'id' => (string) $submission->getKey(),
                'status' => $submission->status->value,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
                'source_type' => $submission->source_type,
                'source_id' => $submission->source_id,
            ],
            'payload' => $submission->payload ?? [],
            'metadata' => $submission->metadata ?? [],
            'destination' => [
                'name' => $destination->name,
                'adapter' => $destination->adapter,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, string>
     */
    private function headers(PublicActionDestination $destination, array $body): array
    {
        $headers = is_array($destination->headers) ? $destination->headers : [];

        $normalizedHeaders = collect($headers)
            ->filter(fn (mixed $value, mixed $key): bool => is_string($key) && is_scalar($value))
            ->mapWithKeys(fn (mixed $value, string $key): array => [$key => (string) $value])
            ->all();

        if (is_string($destination->secret) && $destination->secret !== '') {
            $normalizedHeaders['X-Capell-Signature'] = hash_hmac('sha256', json_encode($body, JSON_THROW_ON_ERROR), $destination->secret);
        }

        return $normalizedHeaders;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function sendOptions(PublicActionDestination $destination, array $body): array
    {
        if ($this->method($destination) === 'GET') {
            return ['query' => $body];
        }

        return ['json' => $body];
    }

    private function method(PublicActionDestination $destination): string
    {
        $method = data_get($destination->settings, 'method', 'POST');

        return in_array($method, ['GET', 'POST', 'PUT', 'PATCH'], true) ? $method : 'POST';
    }

    private function timeoutSeconds(PublicActionDestination $destination): int
    {
        $timeout = data_get($destination->settings, 'timeout_seconds');

        if (is_numeric($timeout) && (int) $timeout > 0) {
            return (int) $timeout;
        }

        $configuredTimeout = config('capell-public-actions.webhook_timeout_seconds', 10);

        return is_int($configuredTimeout) && $configuredTimeout > 0 ? $configuredTimeout : 10;
    }

    private function endpointUrl(PublicActionDestination $destination): string
    {
        $endpointUrl = is_string($destination->endpoint_url) ? $destination->endpoint_url : '';
        $parts = parse_url($endpointUrl);
        $scheme = is_string($parts['scheme'] ?? null) ? strtolower($parts['scheme']) : null;
        $host = is_string($parts['host'] ?? null) ? strtolower($parts['host']) : null;

        throw_if(! in_array($scheme, ['https', 'http'], true) || $host === null || $host === '', InvalidArgumentException::class, 'Webhook destination endpoint must be an absolute HTTP URL.');

        throw_if($scheme !== 'https' && ! config('capell-public-actions.allow_insecure_webhook_urls', false), InvalidArgumentException::class, 'Webhook destination endpoint must use HTTPS.');

        throw_if(! config('capell-public-actions.allow_private_webhook_urls', false) && $this->isPrivateHost($host), InvalidArgumentException::class, 'Webhook destination endpoint host is not allowed.');

        return $endpointUrl;
    }

    private function isPrivateHost(string $host): bool
    {
        if (in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.localhost')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isPrivateAddress($host);
        }

        foreach ($this->resolvedHostAddresses($host) as $address) {
            if ($this->isPrivateAddress($address)) {
                return true;
            }
        }

        return false;
    }

    private function isPrivateAddress(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) === false;
    }

    /**
     * @return list<string>
     */
    private function resolvedHostAddresses(string $host): array
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false) {
            return [];
        }

        return collect($records)
            ->flatMap(static fn (array $record): array => array_values(array_filter([
                is_string($record['ip'] ?? null) ? $record['ip'] : null,
                is_string($record['ipv6'] ?? null) ? $record['ipv6'] : null,
            ], static fn (?string $address): bool => $address !== null && $address !== '')))
            ->unique()
            ->values()
            ->all();
    }

    private function nextAttemptNumber(PublicActionDestination $destination, PublicActionSubmission $submission): int
    {
        return ((int) PublicActionDispatchAttempt::query()
            ->where('public_action_submission_id', $submission->getKey())
            ->where('public_action_destination_id', $destination->getKey())
            ->max('attempt')) + 1;
    }

    private function recordException(
        PublicActionDispatchAttempt $attempt,
        PublicActionDestination $destination,
        Throwable $exception,
        PublicActionDispatchStatus $status,
    ): PublicActionDispatchResultData {
        $message = $this->redact($destination, $exception->getMessage());

        $attempt->forceFill([
            'status' => $status,
            'error_message' => $message,
        ])->save();

        return new PublicActionDispatchResultData(
            success: false,
            errorMessage: $message,
        );
    }

    private function redact(PublicActionDestination $destination, string $value): string
    {
        $redacted = $value;

        foreach ([$destination->endpoint_url, $destination->secret] as $secretValue) {
            if (is_string($secretValue) && $secretValue !== '') {
                $redacted = str_replace($secretValue, '[redacted]', $redacted);
            }
        }

        foreach (($destination->headers ?? []) as $headerValue) {
            if (is_scalar($headerValue) && (string) $headerValue !== '') {
                $redacted = str_replace((string) $headerValue, '[redacted]', $redacted);
            }
        }

        return $redacted;
    }
}
