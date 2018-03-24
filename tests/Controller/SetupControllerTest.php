<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 24/03/18
 * Time: 11:37
 */

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;

class SetupControllerTest extends LogBookApplicationTestCase
{
    /**
     *
     * @param null|string $name
     * @param array $data
     * @param string $dataName
     * @throws \Exception
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->setUp();
    }

    protected function checkIndex(Crawler $crawler): void
    {
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->filter('h1:contains("Setup list")')->count());
    }

    public function testSetupIndexPageDefault(): void
    {
        $crawler = $this->getClient()->request('GET', '/setup/page');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testSetupIndexPageOne(): void
    {
        $crawler = $this->getClient()->request('GET', '/setup/page/1');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testSetupIndexPageTwo(): void
    {
        $crawler = $this->getClient()->request('GET', '/setup/page/2');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testSetupNotExist(): void
    {
        $crawler = $this->getClient()->request('GET', '/setup/9999999999999/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->filter('h1:contains("Setup with provided ID:[9999999999999] not found")')->count());
    }
}