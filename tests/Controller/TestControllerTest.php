<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 24/03/18
 * Time: 16:52
 */

namespace App\Tests\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Entity\LogBookTest;
use App\Utils\RandomString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;

class TestControllerTest extends LogBookApplicationTestCase
{
    public static $executionOrder = 0;

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

    /**
     *
     * @throws \Exception
     */
    public function testTestNotExistAfterDelete(): void
    {
        $test = self::createTest();
        $this->checkTestExist($test);

        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        $testId = $test->getId();
        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';

        $testRepo->delete($test);
        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestNotExistAfterCycleDelete(): void
    {
        $setup = SetupControllerTest::createSetup('SetupttestTestNotExistAfterCycleDelete');
        $cycle = CycleControllerTest::createCycle('CycletestTestNotExistAfterCycleDelete', $setup);
        $test = self::createTest('testTestNotExistAfterCycleDelete', $setup, $cycle);
        $this->checkTestExist($test);

        $cycleRepo = self::$entityManager->getRepository(LogBookCycle::class);
        $testId = $test->getId();
        $cycle = $cycleRepo->find($cycle->getId());
        self::$entityManager->refresh($cycle);
        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';
        //echo "Cycle tests count " . $cycle->getTests()->count() ."\n";
        $cycleRepo->delete($test->getCycle());
        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestNotExistAfterSetupDelete(): void
    {
        ini_set('max_execution_time', 125);
        $setup = SetupControllerTest::createSetup('SetuptestTestNotExistAfterSetupDelete');
        $cycle = CycleControllerTest::createCycle('CycletestTestNotExistAfterSetupDelete', $setup);
        $test = self::createTest('TEST_testTestNotExistAfterSetupDelete', $setup, $cycle);
        $this->checkTestExist($test);
        $setupRepo = self::$entityManager->getRepository(LogBookSetup::class);

        $testId = $test->getId();
        self::$entityManager->refresh($setup);
        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';
        $setupRepo->delete($setup);

        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
    }

    /**
     * @return int
     */
    public static function getExecutionOrder(): int
    {
        self::$executionOrder++;
        return self::$executionOrder;
    }

    /**
     * @param int $executionOrder
     */
    public static function setExecutionOrder(int $executionOrder): void
    {
        self::$executionOrder = $executionOrder;
    }

    protected function checkIndex(Crawler $crawler): void
    {
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('h1:contains("Tests list")')->count());
    }

    public function testTestIndexPageDefault(): void
    {
        $crawler = $this->getClient()->request('GET', '/test/page');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestIndexPageOne(): void
    {
        $crawler = $this->getClient()->request('GET', '/test/page/1');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestIndexPageTwo(): void
    {
        $crawler = $this->getClient()->request('GET', '/test/page/2');
        $this->checkIndex($crawler);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestNotExist(): void
    {
        $crawler = $this->getClient()->request('GET', '/test/9999999999999/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter('h1:contains("Test with provided ID:[9999999999999] not found")')->count());
    }

    /**
     * @param string $testName
     * @param LogBookSetup|null $setup
     * @param LogBookCycle|null $cycle
     * @return LogBookTest
     */
    public static function createTest(string $testName = '', LogBookSetup $setup = null, LogBookCycle $cycle = null): LogBookTest
    {

        if ($cycle === null) {
            $cycle = CycleControllerTest::createCycle(RandomString::generateRandomString(100), $setup);
        }
        if ($testName === '') {
            $testName = RandomString::generateRandomString(50);
        }

        $testRepo = self::$entityManager->getRepository(LogBookTest::class);

        $test = $testRepo->findOneOrCreate(array(
            'name' => $testName,
            'cycle' => $cycle,
            'logFile' =>  RandomString::generateRandomString(100),
            'logFileSize' => 100 + self::getExecutionOrder(),
            'executionOrder' => self::getExecutionOrder(),
        ), true);
        return $test;

    }

    /**
     * @param LogBookTest $test
     */
    protected function checkTestExist(LogBookTest $test): void
    {
        ini_set('max_execution_time', 125);
        $crawler = $this->getClient()->request('GET', '/test/'. $test->getId() . '/page');
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $searchString = 'h3:contains("Test [' . $test->getId() . '] : ' . $test->getName() . '")';
        $count = $crawler->filter($searchString)->count();
        $this->assertGreaterThan(0, $count, 'Search string is :[' . $searchString. '] Count : '. $count);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestExist(): void
    {
        $setup = SetupControllerTest::createSetup('1_SetuptestTestExist');
        $cycle = CycleControllerTest::createCycle('1_CycletestTestExist', $setup);
        $test = self::createTest('1_testtestTestExist', $setup, $cycle);
        self::$entityManager->refresh($cycle);

        $this->checkTestExist($test);

    }


}