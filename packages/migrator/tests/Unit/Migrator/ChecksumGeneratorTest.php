<?php

declare(strict_types=1);

use Capell\Migrator\Support\ChecksumGenerator;

it('prefixes sha256 hashes consistently', function (): void {
    $first = ChecksumGenerator::forString('hello');
    $second = ChecksumGenerator::forString('hello');
    $third = ChecksumGenerator::forString('goodbye');

    expect($first)->toStartWith('sha256-')
        ->and($first)->toBe($second)
        ->and($first)->not->toBe($third);
});

it('hashes files on disk', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'cs-');
    file_put_contents($path, 'payload-body');

    $checksum = ChecksumGenerator::forFile($path);

    expect($checksum)->toStartWith('sha256-')
        ->and($checksum)->toBe(ChecksumGenerator::forString('payload-body'));

    unlink($path);
});
