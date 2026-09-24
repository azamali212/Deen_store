<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Any HTTP call a test did not explicitly fake now fails loudly with
        // the offending URL, instead of silently going out to the internet.
        // This is what would have caught the Gemini calls leaking out of the
        // suite the moment a real key landed in .env.
        Http::preventStrayRequests();
    }
}
