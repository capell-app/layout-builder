<?php

declare(strict_types=1);

namespace Capell\PublicActions\Http\Controllers\Zapier;

use Capell\PublicActions\Actions\BuildPublicActionIntegrationQueryAction;
use Capell\PublicActions\Actions\BuildZapierSubmissionPayloadAction;
use Capell\PublicActions\Enums\PublicActionIntegrationTokenAbility;
use Capell\PublicActions\Models\PublicActionIntegrationToken;
use Capell\PublicActions\Models\PublicActionSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListZapierPublicActionSubmissionsController
{
    public function __construct(
        private readonly BuildPublicActionIntegrationQueryAction $buildActionQuery,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $token = $this->token($request);

        abort_unless($token->hasAbility(PublicActionIntegrationTokenAbility::ReadSubmissions), 403);

        $afterId = max(0, (int) $request->query('after_id', 0));

        $submissions = PublicActionSubmission::query()
            ->with(['action', 'site'])
            ->when($token->site_id !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $builder): Builder => $builder->where('site_id', $token->site_id)->orWhereNull('site_id'),
            ))
            ->whereHas('action', fn (Builder $query): Builder => $this->buildActionQuery->apply($query, $token))
            ->when($afterId > 0, fn (Builder $query): Builder => $query->where('id', '>', $afterId))
            ->latest('submitted_at')
            ->limit(50)
            ->get()
            ->map(fn (PublicActionSubmission $submission): array => BuildZapierSubmissionPayloadAction::run($submission)->toArray())
            ->values();

        return response()->json([
            'submissions' => $submissions,
            'next_after_id' => $submissions->max('id'),
        ]);
    }

    private function token(Request $request): PublicActionIntegrationToken
    {
        $token = $request->attributes->get('public_action_integration_token');
        abort_unless($token instanceof PublicActionIntegrationToken, 401);

        return $token;
    }
}
