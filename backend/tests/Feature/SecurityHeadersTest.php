<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** SR-02: security headers on every response, HSTS only where TLS is in front (production). */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_response_carries_the_security_headers(): void
    {
        $this->getJson('/api/experiments')   // a 401: errors carry them too
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_production_adds_hsts(): void
    {
        $this->app['env'] = 'production';

        $this->get('/up')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
