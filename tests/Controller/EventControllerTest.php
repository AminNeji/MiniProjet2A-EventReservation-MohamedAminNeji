<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testEventsIndexIsAccessible(): void
    {
        $this->client->request('GET', '/events/');
        $this->assertResponseIsSuccessful();
    }

    public function testHomeIsAccessible(): void
    {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testNonExistentEventReturns404(): void
    {
        $this->client->request('GET', '/events/99999');
        $this->assertResponseStatusCodeSame(404);
    }
}
