<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Symfony Request::create defaults Accept-Language to en-us; keep tests on the app default.
        $this->withHeader('Accept-Language', 'pt-BR,pt;q=0.9');
    }
}
