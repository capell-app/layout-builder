<?php

declare(strict_types=1);

/*
 | Pest bootstrap — uses Orchestra Testbench where needed. Tests that only
 | exercise plain PHP classes (widgets, SEO, actions) don't need Testbench.
 */

uses()
    ->beforeEach(function () {
        // no-op shared setup
    })
    ->in('Unit');
