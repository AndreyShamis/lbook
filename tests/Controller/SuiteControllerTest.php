<?php

/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 01/12/19
 * Time: 11:52
 */

namespace App\Tests\Controller;

use App\Entity\Host;
use App\Entity\LogBookSetup;
use App\Entity\SuiteExecution;
use App\Repository\HostRepository;
use App\Repository\SuiteExecutionRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use App\Utils\RandomString;

class SuiteControllerTest extends LogBookApplicationTestCase
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
        self::$entityManager->clear();
    }

    protected function checkIndex(Crawler $crawler): void
    {
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
        $this->assertGreaterThan(0, $crawler->filter('h3:contains("Suite Executions")')->count());
    }

    public function testSuitesIndex(): void
    {
        $crawler = $this->getClient()->request('GET', '/suites');
        $this->checkIndex($crawler);

        self::createSuite('FirstSuite');
    }

    public function testSuitesCalculateAPI(): void
    {
        self::createSuite('SecondSuite');
        $crawler = $this->getClient()->request('GET', '/suites/calculate/1');
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode(), '' . $this->getErrorMessage($crawler));
    }

    public function testSuitesCloseUnclosed(): void
    {
        self::createSuite('ThirdSuite');
        $crawler = $this->getClient()->request('GET', '/suites/close_unclosed/3');
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
    }

    public static function createSuite(string $suiteName = '', EntityManager $em = null): SuiteExecution
    {
        if ($em === null) {
            /** @var SuiteExecutionRepository $suiteRepo */
            $suiteRepo = self::$entityManager->getRepository(SuiteExecution::class);
            /** @var HostRepository $hosts */
            $hosts = self::$entityManager->getRepository(Host::class);
        }
        else{
            /** @var SuiteExecutionRepository $suiteRepo */
            $suiteRepo = $em->getRepository(SuiteExecution::class);
            /** @var HostRepository $hosts */
            $hosts = $em->getRepository(Host::class);
        }
        if ($suiteName === '') {
            $suiteName = RandomString::generateRandomString(100);
        }
        $host = $hosts->findOneOrCreate(['name' => '127.0.0.1', 'ip' => '127.0.0.1']);
        return $suiteRepo->findOneOrCreate(array(
            'name' => $suiteName,
            'summary' => RandomString::generateRandomString(100),
            'uuid' => RandomString::generateRandomString(15),
            'testing_level' => 'sanity',
            'product_version' => RandomString::generateRandomString(10),
            'platform' => 'Platform',
            'chip' => 'Chip',
            'publish' => '0',
            'job_name' => 'JobName',
            'build_tag' => '',
            'target_arch' => 'x86',
            'arch' => 'x86',
            'datetime' => RandomString::generateRandomString(12),
            'tests_count' => 5,
            'tests_count_enabled' => 5,
            'components' => ['Component1,Component2'],
            'test_environments' => ['ENV1,ENV2'],
            'host' => $host,
            'cycle' => null
        ));
    }
}