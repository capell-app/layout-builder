<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Actions\Install\PublishPackageMigrationsAction;
use Capell\Core\Actions\Install\RunMigrationsAction;
use Capell\Core\Contracts\PackageLifecycleAction;
use Capell\Core\Contracts\ProgressReporter;
use Capell\Core\Data\PackageData;
use Capell\Core\Support\Install\NullProgressReporter;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

final class InstallLayoutBuilderPackageAction implements PackageLifecycleAction
{
    use AsObject;

    public function handle(PackageData $package, array $arguments = [], ?ProgressReporter $reporter = null): void
    {
        $reporter ??= new NullProgressReporter;

        PublishPackageMigrationsAction::run(new Collection([$package->name => $package]), $reporter, true, false);
        RunMigrationsAction::run($reporter);

        $reporter->report('Capell Layout Builder installed successfully.');
    }
}
