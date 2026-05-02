<?php

declare(strict_types=1);

namespace Capell\Workspaces\Checks;

use Capell\Workspaces\Models\Workspace;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Config;

/**
 * Resolves and runs the configured list of {@see PublishCheck} classes
 * against a workspace. Driven by `capell.workspaces.publish_checks`
 * (array of class-strings). Returns the collected results in config order.
 *
 * Integration with {@see Publisher} is the caller's
 * responsibility: run the pipeline, inspect for {@see PublishCheckResult::isError()},
 * and abort the publish unless the operator opted to bypass.
 */
class PublishCheckPipeline
{
    public function __construct(private readonly Container $container) {}

    /**
     * @return array<int, PublishCheckResult>
     */
    public function run(Workspace $workspace): array
    {
        /** @var array<int, class-string<PublishCheck>> $checkClasses */
        $checkClasses = Config::array('capell.workspaces.publish_checks', []);

        $results = [];
        foreach ($checkClasses as $checkClass) {
            /** @var PublishCheck $check */
            $check = $this->container->make($checkClass);
            $results[] = $check->run($workspace);
        }

        return $results;
    }

    /**
     * @param  array<int, PublishCheckResult>  $results
     */
    public function hasBlockingErrors(array $results): bool
    {
        foreach ($results as $result) {
            if ($result->isError() && ! $result->isClean()) {
                return true;
            }
        }

        return false;
    }
}
