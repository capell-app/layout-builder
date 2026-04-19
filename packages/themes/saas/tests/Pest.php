<?php

declare(strict_types=1);

/*
 | Pest bootstrap — plain-PHP tests (no Testbench required for widgets,
 | SEO generator, actions). Feature tests that touch Blade templates
 | read source files directly to avoid a full Laravel boot.
 */

uses()
    ->beforeEach(function () {
        // no-op shared setup
    })
    ->in('Unit');
