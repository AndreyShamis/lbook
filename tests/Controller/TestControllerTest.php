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
        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        if ($cycle === null) {
            $cycle = CycleControllerTest::createCycle(RandomString::generateRandomString(100), $setup);
        }

        if ($testName === '') {
            $testName = RandomString::generateRandomString(50);
        }
        $test = $testRepo->findOneOrCreate(array(
            'name' => $testName,
            'cycle' => $cycle,
            'logFile' =>  RandomString::generateRandomString(100),
            'logFileSize' => 100,
            'executionOrder' => self::getExecutionOrder(),
        ), true);
        $cycle->setDirty(true);
        self::$entityManager->flush();
        if ($test === null) {
            echo "Error";
            exit();
        }
        return $test;

    }

    /**
     * @param LogBookTest $test
     */
    protected function checkTestExist(LogBookTest $test): void
    {
        ini_set('max_execution_time', 25);
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
        $cycle = self::createTest();

        $this->checkTestExist($cycle);

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
        $test = self::createTest();
        $this->checkTestExist($test);

        $cycleRepo = self::$entityManager->getRepository(LogBookCycle::class);
        $testId = $test->getId();
        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';

        $cycleRepo->delete($test->getCycle());
        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
    }

//    /**
//     *
//     * @throws \Exception
//     */
//    public function testTestNotExistAfterSetupDelete(): void
//    {
//        ini_set('max_execution_time', 125);
//        $test = self::createTest();
//        $this->checkTestExist($test);
//
//
//        $testId = $test->getId();
//        /** @var LogBookSetup $setup */
//        self::$entityManager->refresh($test);
//        $setup = $test->getCycle()->getSetup();
//        $cycle = $test->getCycle();
//
//        self::$entityManager->refresh($setup);
//        self::$entityManager->refresh($cycle);
//        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';
//        echo "Cycle tests count " . $cycle->getTestsCount() ."\n";
//        echo "Setup id is " . $setup->getId() . " Cycles count " . $setup->getCycles()->count() . "\n";
//
//        $setupRepo = self::$entityManager->getRepository(LogBookSetup::class);
//        $setupRepo->delete($setup);
////        echo "Setup id is " . $test->getCycle()->getSetup()->getId() . "\n";
////        echo "Setup id is " . $test->getCycle()->getId() . "\n";
//        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
//        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode());
//        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
//    }
}