<?php

declare(strict_types=1);

use Capell\Installer\Support\InstallGuide\Patches\UserModelPatch;
use Capell\Installer\Support\InstallGuide\PatchStatus;

function writeUserModelForProbeTest(string $content): string
{
    $path = base_path('app/Models/User.php');

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    file_put_contents($path, $content);

    return $path;
}

function cleanupUserModelForProbeTest(): void
{
    $appPath = base_path('app');
    $backupPath = storage_path('capell/php-file-backups');

    if (is_dir($appPath)) {
        exec('rm -rf ' . escapeshellarg($appPath));
    }

    if (is_dir($backupPath)) {
        exec('rm -rf ' . escapeshellarg($backupPath));
    }
}

test('probe_returns_applicable_for_partially_patched_user_model', function (): void {
    writeUserModelForProbeTest(<<<'PHP'
<?php

declare(strict_types=1);

namespace App\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasPanelShield;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
PHP);

    try {
        $patch = new UserModelPatch;

        expect($patch->probe())->toBe(PatchStatus::Applicable);
    } finally {
        cleanupUserModelForProbeTest();
    }
});
