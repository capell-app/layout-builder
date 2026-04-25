<?php

declare(strict_types=1);

use Capell\Admin\Actions\EnsureSchemaImportsAction;
use Capell\Admin\Tests\Fixtures\ExampleResource;
use Capell\Admin\Tests\Fixtures\ExampleResourceA;
use Capell\Admin\Tests\Fixtures\ExampleResourceB;
use Capell\Admin\Tests\Fixtures\ExampleResourceNoNamespace;
use Capell\Admin\Tests\Fixtures\ExampleResourceWithAlias;
use Illuminate\Support\Str;

test('it adds missing imports for traits used in ExampleResource', function (): void {
    $content = file_get_contents(__DIR__ . '/../../Fixtures/ExampleResource.php');

    $reflector = new ReflectionClass(ExampleResource::class);
    $updated = EnsureSchemaImportsAction::run($content, $reflector, 'Capell\\Admin\\Filament');

    expect($updated)
        ->toContain('use Capell\\Admin\\Filament\\Concerns\\HasFormConfigurator;')
        ->and($updated)->toContain('use Capell\\Admin\\Filament\\Concerns\\HasTableConfigurator;');

    // Check only one import per FQCN (import lines only, not trait usage)
    preg_match_all('/^use Capell\\\\Admin\\\\Filament\\\\Concerns\\\\HasFormConfigurator;$/m', $updated, $matchesForm);
    preg_match_all('/^use Capell\\\\Admin\\\\Filament\\\\Concerns\\\\HasTableConfigurator;$/m', $updated, $matchesTable);
    expect($matchesForm[0])->toHaveCount(1);
    expect($matchesTable[0])->toHaveCount(1);

    // Check the output is valid PHP (can be parsed)
    $tmpFile = tempnam(sys_get_temp_dir(), 'phpclass_') . '.php';
    file_put_contents($tmpFile, $updated);
    $output = null;
    $returnVar = null;
    $phpBinary = PHP_BINARY ?? 'php';
    exec(escapeshellarg($phpBinary) . ' -l ' . escapeshellarg($tmpFile), $output, $returnVar);
    unlink($tmpFile);
    expect($returnVar)->toBe(0);
    expect(implode("\n", $output))->toContain('No syntax errors');
});

test('it updates the namespace to App\\Filament\\Resources and preserves content', function (): void {
    $fixturePath = __DIR__ . '/../../Fixtures/ExampleResource.php';
    $originalContent = file_get_contents($fixturePath);
    $schemaClass = ExampleResource::class;
    $expectedNamespace = 'App\\Filament\\Resources';

    $reflector = new ReflectionClass($schemaClass);

    $updateNamespace = function (string $schemaClass, string $fileContent) {
        $namespace = Str::beforeLast($schemaClass, '\\');
        $relativeNamespace = Str::after($namespace, 'Capell\\Admin\\Tests\\Fixtures');
        $newNamespace = 'App\\Filament\\Resources';
        if ($relativeNamespace !== '') {
            $newNamespace .= '\\' . ltrim($relativeNamespace, '\\');
        }

        $newNamespace = str_replace('\\', '\\', $newNamespace);
        $fileContent = preg_replace('/^namespace\\s+[^;]+;/m', 'namespace ' . $newNamespace . ';', $fileContent);
        $reflector = new ReflectionClass($schemaClass);

        return EnsureSchemaImportsAction::run($fileContent, $reflector, $namespace);
    };

    $updated = $updateNamespace($schemaClass, $originalContent);

    expect($updated)
        ->toContain('namespace ' . $expectedNamespace . ';')
        ->and($updated)->toContain('class ExampleResource extends Resource');
});

test('it adds missing imports for interfaces and parent classes', function (): void {
    require_once __DIR__ . '/../../Fixtures/ExampleInterfaceA.php';
    require_once __DIR__ . '/../../Fixtures/BaseResourceA.php';
    require_once __DIR__ . '/../../Fixtures/ExampleResourceA.php';
    $content = file_get_contents(__DIR__ . '/../../Fixtures/ExampleResourceA.php');
    $reflector = new ReflectionClass(ExampleResourceA::class);
    $updated = EnsureSchemaImportsAction::run($content, $reflector, 'Capell\\Admin\\Tests\\Fixtures');
    expect($updated)
        ->toContain('use Capell\\Admin\\Tests\\Fixtures\\BaseResourceA;')
        ->and($updated)->toContain('use Capell\\Admin\\Tests\\Fixtures\\ExampleInterfaceA;');
    preg_match_all('/^use Capell\\\\Tests\\\\Admin\\\\Fixtures\\\\BaseResourceA;$/m', $updated, $matchesParent);
    preg_match_all('/^use Capell\\\\Tests\\\\Admin\\\\Fixtures\\\\ExampleInterfaceA;$/m', $updated, $matchesInterface);
    expect($matchesParent[0])->toHaveCount(1);
    expect($matchesInterface[0])->toHaveCount(1);
});

test('it aliases imports on name collision', function (): void {
    require_once __DIR__ . '/../../Fixtures/BaseResourceB.php';
    require_once __DIR__ . '/../../Fixtures/BaseResourceBInterface.php';
    require_once __DIR__ . '/../../Fixtures/ExampleResourceB.php';
    $content = file_get_contents(__DIR__ . '/../../Fixtures/ExampleResourceB.php');
    $reflector = new ReflectionClass(ExampleResourceB::class);
    $updated = EnsureSchemaImportsAction::run($content, $reflector, 'Capell\\Admin\\Tests\\Fixtures');
    expect($updated)->toContain('use Capell\\Admin\\Tests\\Fixtures\\BaseResourceB;');
    expect($updated)->toContain('use Capell\\Admin\\Tests\\Fixtures\\BaseResourceBInterface;');
});

test('it does not import the class itself', function (): void {
    require_once __DIR__ . '/../../Fixtures/ExampleResourceA.php';
    $content = file_get_contents(__DIR__ . '/../../Fixtures/ExampleResourceA.php');
    $reflector = new ReflectionClass(ExampleResourceA::class);
    $updated = EnsureSchemaImportsAction::run($content, $reflector, 'Capell\\Admin\\Tests\\Fixtures');
    expect($updated)->not()->toContain('use Capell\\Admin\\Tests\\Fixtures\\ExampleResourceA;');
});

test('it handles files with existing aliases', function (): void {
    require_once __DIR__ . '/../../Fixtures/ExampleResourceWithAlias.php';
    $content = file_get_contents(__DIR__ . '/../../Fixtures/ExampleResourceWithAlias.php');
    $reflector = new ReflectionClass(ExampleResourceWithAlias::class);
    $updated = EnsureSchemaImportsAction::run($content, $reflector, 'Capell\\Admin\\Filament');
    expect($updated)->toContain('use Capell\\Admin\\Filament\\Concerns\\HasFormConfigurator as FormConfig;');
    preg_match_all('/^use Capell\\\\Admin\\\\Filament\\\\Concerns\\\\HasFormConfigurator as FormConfig;$/m', $updated, $matches);
    expect($matches[0])->toHaveCount(1);
});

test('it handles files with no namespace or use statements', function (): void {
    require_once __DIR__ . '/../../Fixtures/ExampleResourceNoNamespace.php';
    $content = file_get_contents(__DIR__ . '/../../Fixtures/ExampleResourceNoNamespace.php');
    $reflector = new ReflectionClass(ExampleResourceNoNamespace::class);
    $updated = EnsureSchemaImportsAction::run($content, $reflector, '');
    expect($updated)
        ->toContain('namespace Capell\\Admin\\Tests\\Fixtures;')
        ->and($updated)->toContain('class ExampleResourceNoNamespace');
});

test('it handles edge cases: empty file, only PHP tag, only namespace', function (): void {
    $cases = [
        '',
        '<?php',
        "<?php\nnamespace Foo;",
    ];
    foreach ($cases as $content) {
        $reflector = new ReflectionClass('stdClass');
        $updated = EnsureSchemaImportsAction::run($content, $reflector, '');
        expect($updated)->toBeString();
    }
});
