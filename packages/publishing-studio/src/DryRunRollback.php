<?php

declare(strict_types=1);

namespace Capell\PublishingStudio;

use RuntimeException;

/**
 * Internal sentinel thrown from inside {@see Publisher::dryRun()} to force
 * the surrounding transaction to roll back after a successful simulated
 * publish. Never leaks outside the publisher.
 */
final class DryRunRollback extends RuntimeException {}
