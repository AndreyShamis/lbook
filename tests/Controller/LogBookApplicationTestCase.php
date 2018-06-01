<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 24/03/18
 * Time: 11:35
 */

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class LogBookApplicationTestCase extends WebTestCase
{
    /** @var  Application $application */
    protected static $application;

    /** @var Client $client */
    protected static $client;

    /** @var  ContainerInterface $container */
    protected static $container;

    /** @var  EntityManager $entityManager */
    protected static $entityManager;

    protected static $setupPass = false;

    public function getClient(): Client
    {
        return self::$client;
    }

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        if (self::$container === null || self::isSetupPass() === false) {
            self::runCommand('doctrine:database:drop --force');
            self::runCommand('doctrine:database:create');
            self::runCommand('doctrine:schema:create --dump-sql');
            self::runCommand('doctrine:schema:create -vvv');
            //self::runCommand('doctrine:fixtures:load --append --no-interaction');
            self::$client = static::createClient();

            self::$container = self::$client->getContainer();
            parent::setUp();

            self::logIn();
            self::setSetupPass(true);
        }
        self::$entityManager = self::$container->get('doctrine.orm.entity_manager');

    }

    protected function getErrorMessage(Crawler $crawler) :string
    {
        $error_message = '';
        $error_file = '';
        $error_line = '';
        try {
            $error_message = $crawler->filter('#errorMessage')->text();
            $error_file = $crawler->filter('#errorFile')->text();
            $error_line = $crawler->filter('#errorLineNumber')->text();
        } catch (\Exception $ex) {}

        $error_message = sprintf('Error Message: %s.%sError File %s.%sError line %s.',
            $error_message, "\n",
            $error_file, "\n",
            $error_line);

        return $error_message;
    }
    /**
     *
     */
    protected static function logIn(): void
    {
        /** @var Session $session */
        $session = self::$client->getContainer()->get('session');

        // the firewall context defaults to the firewall name
        $firewallContext = 'main';

        $token = new UsernamePasswordToken('admin', null, $firewallContext, array('ROLE_ADMIN'));
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        self::$client->getCookieJar()->set($cookie);
    }

    /**
     * @param $command
     * @return int
     * @throws \Exception
     */
    protected static function runCommand($command): int
    {
        $command = sprintf('%s --quiet', $command);

        return self::getApplication()->run(new StringInput($command));
    }

    /**
     * @return Application
     */
    protected static function getApplication(): Application
    {
        if (null === self::$application) {
            $client = static::createClient();

            self::$application = new Application($client->getKernel());
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }

//    /**
//     * {@inheritDoc}
//     */
//    protected function tearDown()
//    {
//        self::runCommand('doctrine:database:drop --force');
//
//        parent::tearDown();
//
//        self::$entityManager->close();
//        self::$entityManager = null; // avoid memory leaks
//    }

//    /**
//     * {@inheritDoc}
//     */
//    public static function tearDownAfterClass()
//    {
//        self::runCommand('doctrine:database:drop --force');
//
//        parent::tearDownAfterClass();
//
//        self::$entityManager->close();
//        self::$entityManager = null; // avoid memory leaks
//        print("\n\ntearDownAfterClass\n");
//    }
    /**
     * @return bool
     */
    public static function isSetupPass(): bool
    {
        return self::$setupPass;
    }

    /**
     * @param bool $setupPass
     */
    public static function setSetupPass(bool $setupPass): void
    {
        self::$setupPass = $setupPass;
    }
}