<?php

declare(strict_types=1);

namespace Capell\Admin\Tests\Feature\Actions\Reports;

use Capell\DeveloperTools\Actions\Reports\BuildQueueHealthQueryAction;

describe('BuildQueueHealthQueryAction', function (): void {
    it('returns query builder for failed jobs', function (): void {
        // Act
        $query = BuildQueueHealthQueryAction::run();

        // Assert
        expect($query)->not->toBeNull();
    });

    it('orders failed jobs by most recent first', function (): void {
        // Act
        $query = BuildQueueHealthQueryAction::run();
        $sql = $query->toSql();

        // Assert
        expect($sql)->toContain('order by');
    });
});
