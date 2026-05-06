<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Approvals;

use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * Reads `capell.publishing-studio.review_policy` and determines who must sign off
 * for a given workspace's content mix. Decoupled from assignment persistence;
 * callers translate each {@see RequiredReviewer} rule into a
 * {@see WorkspaceReviewAssignment} row.
 *
 * Config shape:
 *
 *     'review_policy' => [
 *         'default' => ['minimum' => 1],
 *         'content_types' => [
 *             App\Models\Page::class => ['required_roles' => ['content-editor', 'legal']],
 *         ],
 *     ],
 */
class ReviewPolicyResolver
{
    /**
     * @return Collection<int, RequiredReviewer>
     */
    public function resolve(Workspace $workspace): Collection
    {
        /** @var array<string, mixed> $policy */
        $policy = Config::array('capell.publishing-studio.review_policy', [
            'default' => ['minimum' => 1],
            'content_types' => [],
        ]);

        /** @var array<class-string, array{required_roles?: array<int, string>}> $contentTypes */
        $contentTypes = $policy['content_types'] ?? [];

        $required = new Collection;

        $presentClasses = $this->presentDraftableClasses($workspace);
        foreach ($contentTypes as $class => $rule) {
            if (! in_array($class, $presentClasses, true)) {
                continue;
            }

            foreach ($rule['required_roles'] ?? [] as $role) {
                $required->push(new RequiredReviewer(
                    requiredFor: $class,
                    role: $role,
                ));
            }
        }

        if ($required->isEmpty()) {
            /** @var array{minimum?: int} $defaultRule */
            $defaultRule = $policy['default'] ?? ['minimum' => 1];
            $minimumReviewers = max(1, $defaultRule['minimum'] ?? 1);
            for ($index = 0; $index < $minimumReviewers; $index++) {
                $required->push(new RequiredReviewer(requiredFor: 'any'));
            }
        }

        return $required;
    }

    /**
     * @return array<int, class-string>
     */
    private function presentDraftableClasses(Workspace $workspace): array
    {
        $classes = [];
        foreach (WorkspaceRegistry::modelClasses() as $class) {
            $hasRows = $class::query()
                ->withoutGlobalScopes()
                ->where('workspace_id', $workspace->id)
                ->exists();

            if ($hasRows) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
