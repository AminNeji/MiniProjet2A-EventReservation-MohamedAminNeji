<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthApiControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRegisterEndpointRequiresFields(): void
    {
        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginWithBadCredentials(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['username' => 'nonexistent', 'password' => 'wrong']));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeEndpointRequiresAuth(): void
    {
        $this->client->request('GET', '/api/auth/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testPasskeyLoginOptionsReturnsChallenge(): void
    {
        $this->client->request('POST', '/api/auth/passkey/login/options', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('challenge', $data);
        $this->assertArrayHasKey('rpId', $data);
    }
}
