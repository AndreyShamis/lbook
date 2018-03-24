<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 24/03/18
 * Time: 12:02
 */

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;

class CycleControllerTest extends LogBookApplicationTestCase
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
        $this->assertSame(1, $crawler->filter('h1:contains("Cycle list")')->count());
    }

    /**
     *
     * @throws \Exception
     */
    public function testCycleIndexPageDefault(): void
    {
        $crawler = $this->getClient()->request('GET', '/cycle/page');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testCycleIndexPageOne(): void
    {
        $crawler = $this->getClient()->request('GET', '/cycle/page/1');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testCycleIndexPageTwo(): void
    {
        $crawler = $this->getClient()->request('GET', '/cycle/page/2');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testCycleShowNotExist(): void
    {
        $crawler = $this->getClient()->request('GET', '/cycle/9999999999999/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertSame(1, $crawler->filter('h1:contains("Cycle with provided ID:[9999999999999] not found")')->count());
    }
}