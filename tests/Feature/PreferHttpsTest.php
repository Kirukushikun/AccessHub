<?php

namespace Tests\Feature;

use App\Http\Middleware\PreferHttps;
use Illuminate\Http\Request;
use Tests\TestCase;

class PreferHttpsTest extends TestCase
{
    public function test_production_requests_are_pinned_to_https(): void
    {
        $this->app['env'] = 'production';
        config(['app.url' => 'https://accesshub.bfcgroup.ph']);

        $request = Request::create('http://accesshub.bfcgroup.ph/login', 'GET');

        $seen = null;
        (new PreferHttps)->handle($request, function (Request $req) use (&$seen) {
            $seen = $req;

            return response('ok');
        });

        $this->assertTrue($seen->isSecure());
        $this->assertSame('https', $seen->headers->get('X-Forwarded-Proto'));
        $this->assertStringStartsWith('https://accesshub.bfcgroup.ph', url('/dashboard'));
    }

    public function test_non_production_requests_are_left_alone(): void
    {
        $this->app['env'] = 'local';

        $request = Request::create('http://localhost:8081/login', 'GET');

        (new PreferHttps)->handle($request, function (Request $req) {
            $this->assertFalse($req->isSecure());

            return response('ok');
        });
    }
}
