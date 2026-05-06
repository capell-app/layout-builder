<?php

declare(strict_types=1);

use Capell\PasswordPolicy\Tests\PasswordPolicyTestCase;

pest()->extend(PasswordPolicyTestCase::class)->in('Feature', 'Unit');
