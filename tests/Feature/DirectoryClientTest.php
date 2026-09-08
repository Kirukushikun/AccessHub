<?php

namespace Tests\Feature;

use App\Services\DirectoryClient;
use App\Services\DirectoryUnavailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DirectoryClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.user_api.endpoint', 'https://directory.test/api/v1/users');
        Config::set('services.user_api.key', 'test-key');
    }

    public function test_it_decodes_a_bare_array_with_split_names_and_encrypted_ids(): void
    {
        Http::fake([
            'directory.test/*' => Http::response([
                [
                    'id' => Crypt::encryptString('412'),
                    'first_name' => 'Maria Christina',
                    'last_name' => 'Santos',
                    'email' => 'm.santos@example.org',
                ],
            ]),
        ]);

        $users = app(DirectoryClient::class)->users();

        $this->assertCount(1, $users);
        $this->assertSame(412, $users[0]['user_id']);
        $this->assertSame('Maria Christina Santos', $users[0]['name']);
    }

    public function test_a_record_encrypted_with_a_foreign_key_is_skipped_not_fatal(): void
    {
        Http::fake([
            'directory.test/*' => Http::response([
                ['id' => Crypt::encryptString('1'), 'first_name' => 'Good', 'last_name' => 'One', 'email' => 'g@x.org'],
                ['id' => 'not-a-valid-payload', 'first_name' => 'Bad', 'last_name' => 'One', 'email' => 'b@x.org'],
            ]),
        ]);

        $users = app(DirectoryClient::class)->users();

        $this->assertCount(1, $users);
        $this->assertSame('Good One', $users[0]['name']);
    }

    public function test_it_throws_when_not_configured(): void
    {
        Config::set('services.user_api.endpoint', '');

        $this->expectException(DirectoryUnavailable::class);
        app(DirectoryClient::class)->users();
    }

    public function test_it_throws_when_the_endpoint_errors(): void
    {
        Http::fake(['directory.test/*' => Http::response('nope', 500)]);

        $this->expectException(DirectoryUnavailable::class);
        app(DirectoryClient::class)->users();
    }
}
