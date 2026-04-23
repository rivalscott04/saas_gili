<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Rate limits use the cache store; without a flush, attempts leak across tests
        // that reuse the same keys (e.g. booking id + IP) in one PHPUnit process.
        if ($this->app->environment('testing')) {
            Cache::flush();
        }
    }
}
