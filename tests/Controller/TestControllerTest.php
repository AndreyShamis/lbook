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
        $test = self::createTest('testTestNotExistAfterDelete');
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
        $this->assertNotEquals(1, $cycle->getTests()->count(), 'Check that cycle include one created test. count: ' . $cycle->getTests()->count());
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
//        $setup = SetupControllerTest::createSetup('SetuptestTestNotExistAfterSetupDelete');
//        $cycle = CycleControllerTest::createCycle('CycletestTestNotExistAfterSetupDelete', $setup);
//        $test = self::createTest('TEST_testTestNotExistAfterSetupDelete', $setup, $cycle);
//        $this->checkTestExist($test);
//
//        $testId = $test->getId();
//        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';
//
//        /** Refresh required or cause to - Failed asserting that 200 is identical to 404 */
//
//        $this->assertEquals(1, $cycle->getTests()->count(), 'Check that cycle include one created test. count: ' . $cycle->getTests()->count());
//        $this->assertEquals(1, $setup->getCycles()->count(),'Check that Setup include one created cycle. count: ' . $setup->getCycles()->count());
//        self::$entityManager->refresh($setup);
//        $setupRepo = self::$entityManager->getRepository(LogBookSetup::class);
//        $setupRepo->delete($setup);
//
//        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
//        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode(), 'Check that test with ID=' . $testId . ' not exist any more.');
//        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
//    }
//
//    /**
//     * @throws \Doctrine\ORM\ORMException
//     */
//    public function testCycleContainsXTests(): void
//    {
//        $size = 31;
//        $setup = SetupControllerTest::createSetup('testCycleContainsXTests_SetuptestTestNotExistAfterSetupDelete');
//        $cycle = CycleControllerTest::createCycle('testCycleContainsXTests_CycletestTestNotExistAfterSetupDelete', $setup);
//        for ( $x = 0; $x < $size; $x++) {
//            self::createTest('testCycleContainsXTests_TEST_testTestNotExistAfterSetupDelete', $setup, $cycle);
//        }
//        /** Refresh required or cause to - Failed asserting that 200 is identical to 404 */
//        //self::$entityManager->refresh($setup);
//        self::setUp();
//        self::$entityManager->refresh($setup);
//        $this->assertEquals($size, $cycle->getTests()->count(), 'Check that cycle include one created test. count: ' . $cycle->getTests()->count());
//        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include one created cycle. count: ' . $setup->getCycles()->count());
//
//        $setupRepo = self::$entityManager->getRepository(LogBookSetup::class);
//        $setupRepo->delete($setup);
//    }
//
//    /**
//     * @throws \Doctrine\ORM\ORMException
//     */
//    public function testSetupContainsXCycles(): void
//    {
//        $x_size = 11;
//        $setup = SetupControllerTest::createSetup('testSetupContainsXCycles_SetuptestTestNotExistAfterSetupDelete');
//
//        for ( $x = 0; $x < $x_size; $x++) {
//            CycleControllerTest::createCycle('testSetupContainsXCycles_CycletestTestNotExistAfterSetupDelete', $setup);
//        }
//
//        /** Refresh required or cause to - Failed asserting that 200 is identical to 404 */
//        self::setUp();
//        self::$entityManager->refresh($setup);
//        $this->assertEquals($x_size, $setup->getCycles()->count(), 'Check that Setup include one created cycle. count: ' . $setup->getCycles()->count());
//
//        $setupRepo = self::$entityManager->getRepository(LogBookSetup::class);
//        $setupRepo->delete($setup);
//    }

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

    /**
     * @param Crawler $crawler
     */
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