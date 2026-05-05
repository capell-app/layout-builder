<?php

declare(strict_types=1);

use Capell\PasswordSecurity\Tests\PasswordSecurityTestCase;

pest()->extend(PasswordSecurityTestCase::class)->in('Feature', 'Unit');
