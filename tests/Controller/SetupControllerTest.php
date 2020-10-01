<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 24/03/18
 * Time: 11:37
 */

namespace App\Tests\Controller;

use App\Entity\LogBookSetup;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use App\Utils\RandomString;

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
        self::$entityManager->clear();
    }

    protected function checkIndex(Crawler $crawler): void
    {
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
        $this->assertGreaterThan(0, $crawler->filter('div:div:h1:contains("Setup list")')->count(), 'Bad setup list count.');
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
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
        $this->assertGreaterThan(0, $crawler->filter('h1:contains("Setup with provided ID:[9999999999999] not found")')->count());
    }

    public static function createSetup(string $setupName = '', EntityManager $em = null): LogBookSetup
    {
        if ($em === null) {
            $setupRepo = self::$entityManager->getRepository(LogBookSetup::class);
        }
        else{
            $setupRepo = $em->getRepository(LogBookSetup::class);
        }

//        $setup = new LogBookSetup();
//        $setup->setName($setupName);
//        $setup->setCheckUpTime(false);
//        $setup->setDisabled(false);
//        $setup->setOwner(null);
//        self::$entityManager->persist($setup);
//        self::$entityManager->flush();
//        self::$entityManager->refresh($setup);
        if ($setupName === '') {
            $setupName = RandomString::generateRandomString(100);
        }
        return $setupRepo->findOneOrCreate(array('name' => $setupName));
    }

    /**
     *
     * @throws \Exception
     */
    public function testSetupExist(): void
    {
        self::$entityManager->clear();
        $setup = self::createSetup();

        $this->checkSetupExist($setup, true);
    }

    /**
     * @param LogBookSetup $setup
     * @param bool $checkSetupName
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    protected function checkSetupExist(LogBookSetup $setup, bool $checkSetupName = true): void
    {
        self::$entityManager->clear();
        $crawler = $this->getClient()->request('GET', '/setup/'. $setup->getId() . '/page');
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
        if ($checkSetupName === true) {
            $searchString = 'h3:contains("Setup [' . $setup->getId() . '] : ' . $setup->getName() . '")';

        } else {
            $searchString = 'h3:contains("Setup [' . $setup->getId() . '] :")';

        }
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString. ': Count '. $crawler->filter($searchString)->count());
    }

    /**
     *
     * @throws \Exception
     */
    public function testSetupNotExistAfterDelete(): void
    {
        self::$entityManager->clear();
        $setup = self::createSetup("NOT_EXIST_ON_DELETE", self::$entityManager);
        $this->checkSetupExist($setup);

        $setupId = $setup->getId();

        CycleControllerTest::createCycle('Cycle_1__SETUP_DELETE', $setup, self::$entityManager);
        CycleControllerTest::createCycle('Cycle_2__SETUP_DELETE', $setup, self::$entityManager);
        CycleControllerTest::createCycle('Cycle_3__SETUP_DELETE', $setup, self::$entityManager);
        CycleControllerTest::createCycle('Cycle_4__SETUP_DELETE', $setup, self::$entityManager);

        self::$entityManager->refresh($setup);

        $searchString = 'h1:contains("Setup with provided ID:[' . $setupId . '] not found")';
        //$setupRepo->delete($setup);
        $crawler = $this->getClient()->request('DELETE', '/setup/'. $setupId . '');
        $this->assertSame(Response::HTTP_FOUND, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));

        $crawler = $this->getClient()->request('GET', '/setup/'. $setupId . '/page');
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->getClient()->getResponse()->getStatusCode(), $this->getErrorMessage($crawler));
        $this->assertGreaterThan(0, $crawler->filter($searchString)->count(), $searchString);
        self::$entityManager->detach($setup);
    }

    /**
     *
     * @throws \Exception
     */
    public function testTenSetupCreations(): void
    {
        self::$entityManager->clear();
        for ($x = 0; $x < 10; $x++) {
            $setup = self::createSetup('__TEN__' . $x*$x);
            $this->checkSetupExist($setup, true);
        }
    }
}