<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 24/03/18
 * Time: 11:37
 */

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class SetupControllerTest extends LogBookApplicationTestCase
{
    /**
     *
     * @throws \Exception
     */
    public function testSetupIndexPageDefault(): void
    {
        $this->setUp();

        $crawler = $this->getClient()->request('GET', '/setup/page');
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->filter('h1:contains("Setup list")')->count());
    }

    /**
     *
     * @throws \Exception
     */
    public function testSetupIndexPageOne(): void
    {
        $this->setUp();

        $crawler = $this->getClient()->request('GET', '/setup/page/1');
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->filter('h1:contains("Setup list")')->count());
    }


    /**
     *
     * @throws \Exception
     */
    public function testSetupIndexPageTwo(): void
    {
        $this->setUp();

        $crawler = $this->getClient()->request('GET', '/setup/page/2');
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->filter('h1:contains("Setup list")')->count());
    }
}