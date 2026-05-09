<?php

declare(strict_types=1);

namespace Capell\PublicActions\Actions;

use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Models\SiteDomain;
use Capell\PublicActions\Contracts\PublicActionHandler;
use Capell\PublicActions\Data\PublicActionMetadataData;
use Capell\PublicActions\Data\PublicActionPayloadData;
use Capell\PublicActions\Data\PublicActionResultData;
use Capell\PublicActions\Data\PublicActionSubmissionData;
use Capell\PublicActions\Enums\PublicActionDestinationStatus;
use Capell\PublicActions\Enums\PublicActionStatus;
use Capell\PublicActions\Enums\PublicActionSubmissionStatus;
use Capell\PublicActions\Jobs\DispatchPublicActionDestinationJob;
use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionSubmission;
use Capell\PublicActions\Support\PublicActionHandlerRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class SubmitPublicActionAction
{
    use AsAction;

    public function __construct(
        private readonly PublicActionHandlerRegistry $handlers,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function handle(PublicAction|string $action, array $input, ?Request $request = null): PublicActionResultData
    {
        $publicAction = $this->resolve($action, $request);

        $this->assertActionIsAvailable($publicAction);

        $payload = $this->validatedPayload($input);
        $submission = PublicActionSubmission::query()->create([
            'public_action_id' => $publicAction->getKey(),
            'site_id' => $publicAction->site_id,
            'source_type' => $payload['source_type'] ?? null,
            'source_id' => $payload['source_id'] ?? null,
            'payload' => Arr::except($payload, ['source_type', 'source_id']),
            'metadata' => $this->metadata($publicAction, $request)->toArray(),
            'status' => PublicActionSubmissionStatus::Received,
            'submitted_at' => now(),
        ]);

        try {
            $result = $this->resolveHandler($publicAction)->handle(new PublicActionSubmissionData(
                actionKey: $publicAction->key,
                payload: new PublicActionPayloadData($submission->payload ?? []),
                metadata: PublicActionMetadataData::from($submission->metadata ?? []),
                sourceType: $submission->source_type,
                sourceId: $submission->source_id,
            ));
        } catch (ValidationException $exception) {
            $submission->forceFill(['status' => PublicActionSubmissionStatus::Failed])->save();

            throw $exception;
        } catch (Throwable $exception) {
            $submission->forceFill([
                'status' => PublicActionSubmissionStatus::Failed,
                'metadata' => [
                    ...($submission->metadata ?? []),
                    'handler_failed' => true,
                    'handler_error_type' => class_basename($exception),
                ],
            ])->save();

            throw ValidationException::withMessages([
                'action' => __('capell-public-actions::generic.unavailable'),
            ]);
        }

        $submission->forceFill([
            'status' => $result->success ? PublicActionSubmissionStatus::Handled : PublicActionSubmissionStatus::Failed,
        ])->save();

        if ($result->success) {
            $this->dispatchDestinations($publicAction, $submission);
        }

        return $this->resultWithActionDefaults($publicAction, $submission, $result, $request);
    }

    public function resolve(PublicAction|string $action, ?Request $request = null): PublicAction
    {
        if ($action instanceof PublicAction) {
            return $action;
        }

        $query = PublicAction::query()->where('key', $action);
        $siteId = $request instanceof Request ? $this->siteId($request) : $this->siteId(request());

        if ($siteId !== null) {
            $query
                ->where(fn (Builder $builder): Builder => $builder
                    ->where('site_scope_key', 'global')
                    ->orWhere('site_scope_key', 'site:' . $siteId))
                ->orderByRaw('CASE WHEN site_scope_key = ? THEN 0 ELSE 1 END', ['site:' . $siteId]);
        } else {
            $query->where('site_scope_key', 'global');
        }

        return $query->firstOrFail();
    }

    /**
     * @throws ValidationException
     */
    private function assertActionIsAvailable(PublicAction $action): void
    {
        if ($action->status === PublicActionStatus::Active) {
            return;
        }

        throw ValidationException::withMessages([
            'action' => __('capell-public-actions::generic.unavailable'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validatedPayload(array $input): array
    {
        $validated = Validator::make($input, [
            'source_type' => ['nullable', 'string', 'max:255'],
            'source_id' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $payload = Arr::except($input, [
            '_token',
            '_method',
            'g-recaptcha-response',
        ]);

        return [
            ...$payload,
            ...Arr::only($validated, ['source_type', 'source_id']),
        ];
    }

    private function resolveHandler(PublicAction $action): PublicActionHandler
    {
        $handler = $this->handlers->resolve($action->handler_key);

        if ($handler instanceof PublicActionHandler) {
            return $handler;
        }

        throw new InvalidArgumentException(sprintf('Public action handler [%s] is not registered.', $action->handler_key));
    }

    private function metadata(PublicAction $action, ?Request $request): PublicActionMetadataData
    {
        if (! $request instanceof Request) {
            return new PublicActionMetadataData(siteId: $action->site_id);
        }

        return new PublicActionMetadataData(
            ipHash: hash('sha256', (string) $request->ip()),
            userAgent: $request->userAgent(),
            url: $request->fullUrl(),
            referer: $request->headers->get('referer'),
            route: $request->route()?->getName(),
            siteId: $action->site_id ?? $this->siteId($request),
        );
    }

    private function resultWithActionDefaults(
        PublicAction $action,
        PublicActionSubmission $submission,
        PublicActionResultData $result,
        ?Request $request,
    ): PublicActionResultData {
        return new PublicActionResultData(
            success: $result->success,
            message: $result->message ?? ($result->success ? $action->success_message : $action->failure_message),
            redirectUrl: $result->redirectUrl
                ?? ($result->success ? $action->success_redirect_url : $action->failure_redirect_url)
                ?? ($result->success ? $this->payloadRedirectUrl($submission, $request) : null),
            createdModelType: $result->createdModelType,
            createdModelId: $result->createdModelId,
        );
    }

    private function dispatchDestinations(PublicAction $action, PublicActionSubmission $submission): void
    {
        $destinations = $action->destinations()
            ->where('status', PublicActionDestinationStatus::Active)
            ->get();

        foreach ($destinations as $destination) {
            if ((bool) data_get($destination->settings, 'sync', false)) {
                DispatchPublicActionDestinationAction::run($destination, $submission);

                continue;
            }

            dispatch(new DispatchPublicActionDestinationJob($destination, $submission));
        }
    }

    private function payloadRedirectUrl(PublicActionSubmission $submission, ?Request $request): ?string
    {
        $redirectUrl = data_get($submission->payload, 'redirect');

        if (! is_string($redirectUrl) || $redirectUrl === '') {
            return null;
        }

        if (str_starts_with($redirectUrl, '/') && ! str_starts_with($redirectUrl, '//')) {
            return $redirectUrl;
        }

        if (! $request instanceof Request || filter_var($redirectUrl, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $redirectHost = parse_url($redirectUrl, PHP_URL_HOST);

        return is_string($redirectHost) && strcasecmp($redirectHost, $request->getHost()) === 0
            ? $redirectUrl
            : null;
    }

    private function siteId(Request $request): ?int
    {
        if (! Schema::hasTable('sites') || ! Schema::hasTable('site_domains')) {
            return null;
        }

        if ($request->fullUrl() === '') {
            return null;
        }

        $resolved = LoadSiteDomainFromUrlAction::run($request->fullUrl());
        $siteDomain = is_array($resolved) ? ($resolved[0] ?? null) : null;

        return $siteDomain instanceof SiteDomain ? $siteDomain->site_id : null;
    }
}
