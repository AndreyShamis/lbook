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
use Doctrine\ORM\EntityManager;
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
        self::$entityManager->clear();
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestNotExistAfterDelete(): void
    {
        self::$entityManager->clear();
        $test = self::createTest('testTestNotExistAfterDelete', null, null, self::$entityManager);
        $this->checkTestExist($test);

        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        $testId = $test->getId();
        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';

        $testRepo->delete($test);
        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestNotExistAfterCycleDelete(): void
    {
        self::$entityManager->clear();
        $setup = SetupControllerTest::createSetup('SetupttestTestNotExistAfterCycleDelete', self::$entityManager);
        $cycle = CycleControllerTest::createCycle('CycletestTestNotExistAfterCycleDelete', $setup, self::$entityManager);
        $test = self::createTest('testTestNotExistAfterCycleDelete', $setup, $cycle, self::$entityManager);
        $this->checkTestExist($test);

        $cycleRepo = self::$entityManager->getRepository(LogBookCycle::class);
        $testId = $test->getId();
        $this->assertNotEquals(1, $cycle->getTests()->count(), 'Check that cycle include one created test. count: ' . $cycle->getTests()->count());
        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';
        $cycleRepo->delete($test->getCycle());
        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTestNotExistAfterSetupDelete(): void
    {
        self::$entityManager->clear();
        ini_set('max_execution_time', 125);
        $setup = SetupControllerTest::createSetup('SetuptestTestNotExistAfterSetupDelete', self::$entityManager);
        $cycle = CycleControllerTest::createCycle('CycletestTestNotExistAfterSetupDelete', $setup, self::$entityManager);
        $test = self::createTest('TEST_testTestNotExistAfterSetupDelete', $setup, $cycle, self::$entityManager);
        $this->checkTestExist($test);

        $testId = $test->getId();
        $searchString = 'h1:contains("Test with provided ID:[' . $testId . '] not found")';

        /** Refresh required or cause to - Failed asserting that 200 is identical to 404 */
        self::$entityManager->refresh($setup);
        self::$entityManager->refresh($cycle);
        $this->assertEquals(1, $cycle->getTests()->count(), 'Check that cycle include one created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(1, $setup->getCycles()->count(),'Check that Setup include one created cycle. count: ' . $setup->getCycles()->count());

        $setupRepo = self::$entityManager->getRepository(LogBookSetup::class);
        $setupRepo->delete($setup);

        $crawler = $this->getClient()->request('GET', '/test/'. $testId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode(), 'Check that test with ID=' . $testId . ' not exist any more.' . $this->getErrorMessage($crawler));
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testCycleContainsXTests(): void
    {
        $size = 31;
        $em = self::$entityManager;
        $em->clear();
        SetupControllerTest::createSetup('1', $em);
        SetupControllerTest::createSetup('2', $em);
        $setup = SetupControllerTest::createSetup('SETUP_testCycleContainsXTests', $em);
        $cycle = CycleControllerTest::createCycle('CYCLE_testCycleContainsXTests', $setup, $em);
        for ( $x = 0; $x < $size; $x++) {
            self::createTest('TEST_testCycleContainsXTests_' . $x, $setup, $cycle, $em);
        }
        /** Refresh required or cause to - Failed asserting that 200 is identical to 404 */
        self::$entityManager->refresh($setup);
        self::$entityManager->refresh($cycle);
        $this->assertEquals($size, $cycle->getTests()->count(), 'Check that cycle '.$cycle->getId().':Setup' . $setup->getId() . ' include one created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include one created cycle. count: ' . $setup->getCycles()->count());
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function testSetupContainsXCycles(): void
    {
        self::$entityManager->clear();
        $x_size = 11;
        $setup = SetupControllerTest::createSetup('testSetupContainsXCycles_SetuptestTestNotExistAfterSetupDelete', self::$entityManager);

        for ( $x = 0; $x < $x_size; $x++) {
            CycleControllerTest::createCycle('testSetupContainsXCycles_CycletestTestNotExistAfterSetupDelete' . $x, $setup, self::$entityManager);
        }
        self::$entityManager->refresh($setup);
        $this->assertEquals($x_size, $setup->getCycles()->count(), 'Check that Setup include created cycles. Actual: ' . $setup->getCycles()->count() . ', expected=' . $x_size);
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

    /**
     * @param Crawler $crawler
     */
    protected function checkIndex(Crawler $crawler): void
    {
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
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
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
        $this->assertGreaterThan(0, $crawler->filter('h1:contains("Test with provided ID:[9999999999999] not found")')->count());
    }

    /**
     * @param string $testName
     * @param LogBookSetup|null $setup
     * @param LogBookCycle|null $cycle
     * @param EntityManager $em
     * @return LogBookTest
     */
    public static function createTest(string $testName = '', LogBookSetup $setup = null, LogBookCycle $cycle = null, EntityManager $em = null): LogBookTest
    {
        if ($em === null) {
            $testRepo = self::$entityManager->getRepository(LogBookTest::class);
            $em = self::$entityManager;
        } else {
            $testRepo = $em->getRepository(LogBookTest::class);
        }
        if ($cycle === null) {
            $cycle = CycleControllerTest::createCycle(RandomString::generateRandomString(100), $setup, $em);
        }
        if ($testName === '') {
            $testName = RandomString::generateRandomString(50);
        }


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
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
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