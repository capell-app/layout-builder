<?php

declare(strict_types=1);

use Capell\Notes\Tests\NotesTestCase;

require_once __DIR__ . '/NotesTestCase.php';

pest()->extend(NotesTestCase::class)->group('notes')->in(__DIR__);
