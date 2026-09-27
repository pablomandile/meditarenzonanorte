<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ningún test le pega a Mailchimp de verdad. Sin esto, un test mal escrito
        // podría llegar a crear una campaña —o peor, a enviarla— contra la cuenta real.
        // El que necesite red la finge con Http::fake().
        Http::preventStrayRequests();
    }
}
