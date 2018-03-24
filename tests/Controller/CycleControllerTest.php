<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 24/03/18
 * Time: 12:02
 */

namespace App\Tests\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Utils\RandomString;
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
        $this->assertGreaterThan(0, $crawler->filter('h1:contains("Cycle list")')->count());
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
     * @param string $cycleName
     * @param LogBookSetup|null $setup
     * @return LogBookCycle
     */
    public static function createCycle($cycleName = '', LogBookSetup $setup = null): LogBookCycle
    {
        if ($cycleName === '') {
            $cycleName = RandomString::generateRandomString(20);
        }
        if ($setup === null) {
            $setup = SetupControllerTest::createSetup();
        }

        $cycleRepo = self::$entityManager->getRepository(LogBookCycle::class);
        return $cycleRepo->findOneOrCreate(array(
            'name' => $cycleName,
            'setup' => $setup,
            ));
    }

    /**
     *
     * @throws \Exception
     */
    public function testCycleShowNotExist(): void
    {
        $crawler = $this->getClient()->request('GET', '/cycle/9999999999999/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('h1:contains("Cycle with provided ID:[9999999999999] not found")')->count());
    }

    /**
     *
     * @throws \Exception
     */
    public function testCycleExist(): void
    {
        $cycle = self::createCycle();

        $this->checkCycleExist($cycle);

    }

    /**
     * @param LogBookCycle $cycle
     */
    protected function checkCycleExist(LogBookCycle $cycle): void
    {
        $crawler = $this->getClient()->request('GET', '/cycle/'. $cycle->getId() . '/page');
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $searchString = 'h3:contains("Cycle [' . $cycle->getId() . '] : ' . $cycle->getName() . '")';
        $count = $crawler->filter($searchString)->count();
        $this->assertGreaterThan(0, $count, 'Search string is :[' . $searchString. '] Count : '. $count);
    }

    /**
     *
     * @throws \Exception
     */
    public function testCycleNotExistAfterDelete(): void
    {
        $cycle = self::createCycle();
        $this->checkCycleExist($cycle);

        $cycleRepo = self::$entityManager->getRepository(LogBookCycle::class);
        $cycleId = $cycle->getId();
        $searchString = 'h1:contains("Cycle with provided ID:[' . $cycleId . '] not found")';

        $cycleRepo->delete($cycle);
        $crawler = $this->getClient()->request('GET', '/cycle/'. $cycleId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTenCycleCreationsDifferentSetups(): void
    {
        for ($x = 0; $x < 10; $x++) {
            $cycle = self::createCycle('__TEN__' . $x*$x);
            $this->checkCycleExist($cycle);
        }
    }

    /**
     *
     * @throws \Exception
     */
    public function testTenCycleCreationsSameSetups(): void
    {
        $setup = SetupControllerTest::createSetup();
        for ($x = 0; $x < 10; $x++) {
            $cycle = self::createCycle('__TEN__' . $x*$x, $setup);
            $this->checkCycleExist($cycle);
        }
    }
}