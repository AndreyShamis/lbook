<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 27/03/18
 * Time: 20:18
 */

namespace App\Tests\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Entity\LogBookTest;
use App\Utils\RandomString;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class UploaderTest extends LogBookApplicationTestCase
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

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function testUploadCli():void
    {
        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        $passTestName = 'network_WiFi_Perf.11g';
        $passFileName = 'PASS__' . $passTestName;
        $errorTestName = 'network_WiFi_BluetoothStreamPerf.11a';
        $errorFileName = 'ERROR__' . $errorTestName;
        $failTestName = 'network_WiFi_Perf.ht40';
        $failFileName = 'FAIL__' . $failTestName;
        $token = RandomString::generateRandomString(20);
        $file1PathSrc = realpath(__DIR__) . '/ForUpload/' . $passFileName;
        $file2PathSrc = realpath(__DIR__) . '/ForUpload/' . $errorFileName;
        $file3PathSrc = realpath(__DIR__) . '/ForUpload/' . $failFileName;
        $filePath1 = realpath(__DIR__) . '/' . $passFileName;
        $filePath2 = realpath(__DIR__) . '/' . $errorFileName;
        $filePath3 = realpath(__DIR__) . '/' . $failFileName;
        copy($file1PathSrc, $filePath1);
        copy($file2PathSrc, $filePath2);
        copy($file3PathSrc, $filePath3);

        $setupName = 'Test Setup';

        $file1 = new UploadedFile($filePath1, $passFileName, 'text/plain', filesize($filePath1));
        $file2 = new UploadedFile($filePath2, $errorFileName, 'text/plain', filesize($filePath2));
        $file3 = new UploadedFile($filePath3, $failFileName, 'text/plain', filesize($filePath3));
        $client = $this->getClient();

        $client->request('POST', '/upload/new_cli',
            array(
                'token' => $token,
                'setup' => $setupName,
                //'cycle' => 'asdasdasdasd',
            ),
            array('file' => $file1),
            array('HTTP_REFERER' => '/upload/new_cli',)
        );
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $firstContent = $this->getClient()->getResponse()->getContent();
        //echo  $firstContent;
        $this->assertRegExp('/Token provided \['.$token.'\]/', $firstContent);
        $this->assertRegExp('/Cycle not found by token\. Parsing Setup/', $firstContent);
        $this->assertRegExp('/Searching setup by NAME :'.$setupName.'/', $firstContent);
        $this->assertRegExp('/Creating setup  :'.$setupName.'/', $firstContent);
        $this->assertRegExp('/Cycle name not provided/', $firstContent);
        $this->assertRegExp('/Generated cycle name/', $firstContent);
        $this->assertRegExp('/Creating cycle/', $firstContent);
        $this->assertRegExp('/Cycle created ID:\d+\./', $firstContent);
        $this->assertRegExp('/Cycle found, take SETUP from cycle/', $firstContent);
        $this->assertRegExp('/File name is :[\w\d]+'.$passFileName.'/', $firstContent);
        $this->assertRegExp('/TestID is \d+\.$/', $firstContent);

        /**
         *
         */
        $client->request('POST', '/upload/new_cli',
            array(
                'token' => $token,
                'setup' => $setupName,
            ),
            array('file' => $file2),
            array('HTTP_REFERER' => '/upload/new_cli',
            )
        );

        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $secondContent = $this->getClient()->getResponse()->getContent();
        //echo  $secondContent;
        $this->assertRegExp('/Token provided \['.$token.'\]/', $secondContent);
        $this->assertNotRegExp('/Cycle not found by token\. Parsing Setup/', $secondContent);
        $this->assertNotRegExp('/Searching setup by NAME :'.$setupName.'/', $secondContent);
        $this->assertNotRegExp('/Creating setup  :'.$setupName.'/', $secondContent);
        $this->assertNotRegExp('/Cycle name not provided/', $secondContent);
        $this->assertNotRegExp('/Generated cycle name/', $secondContent);
        $this->assertNotRegExp('/Creating cycle/', $secondContent);
        $this->assertNotRegExp('/Cycle created ID:\d+./', $secondContent);
        $this->assertRegExp('/Cycle found, take SETUP from cycle/', $secondContent);
        $this->assertRegExp('/File name is :[\w\d]+'.$errorFileName.'/', $secondContent);
        $this->assertRegExp('/TestID is \d+\.$/', $secondContent);

        $testId = $this->findTestIdInTest($secondContent);
        //self::setUp();


        $test = $testRepo->find($testId);
        /** @var LogBookCycle $cycle */
        $cycle = $test->getCycle();
        /** @var LogBookSetup $setup */
        $setup = $cycle->getSetup();
        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($errorTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $errorTestName);
        $this->assertSame(35, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 35);
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include 1 created cycle. count: ' . $setup->getCycles()->count());
        $this->assertEquals(2, $cycle->getTests()->count(), 'Check that cycle include 2 created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(50, $cycle->getPassRate(), 'Check that cycle pass rate is 50%: Actual = ' . $cycle->getPassRate());

        /*
         *
         */
        $client->request('POST', '/upload/new_cli',
            array(
                'token' => $token,
                'setup' => $setupName,
            ),
            array('file' => $file3),
            array('HTTP_REFERER' => '/upload/new_cli',
            )
        );
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $thirdResponse = $this->getClient()->getResponse()->getContent();

        $this->assertRegExp('/Token provided \['.$token.'\]/', $thirdResponse);
        $this->assertNotRegExp('/Cycle not found by token\. Parsing Setup/', $thirdResponse);
        $this->assertNotRegExp('/Searching setup by NAME :'.$setupName.'/', $thirdResponse);
        $this->assertNotRegExp('/Creating setup  :'.$setupName.'/', $thirdResponse);
        $this->assertNotRegExp('/Cycle name not provided/', $thirdResponse);
        $this->assertNotRegExp('/Generated cycle name/', $thirdResponse);
        $this->assertNotRegExp('/Creating cycle/', $thirdResponse);
        $this->assertNotRegExp('/Cycle created ID:\d+./', $thirdResponse);
        $this->assertRegExp('/Cycle found, take SETUP from cycle/', $thirdResponse);
        $this->assertRegExp('/File name is :[\w\d]+'.$failFileName.'/', $thirdResponse);
        $this->assertRegExp('/TestID is \d+\.$/', $thirdResponse);

        $testId = $this->findTestIdInTest($thirdResponse);
        //self::setUp();

        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        self::$entityManager->refresh($cycle);
        self::$entityManager->refresh($setup);
        $test = $testRepo->find($testId);
        /** @var LogBookCycle $cycle3 */
        $cycle3 = $test->getCycle();
        /** @var LogBookSetup $setup */
        $setup = $cycle3->getSetup();
        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($failTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $failTestName);
        $this->assertSame(111, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 111);
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include 1 created cycle. count: ' . $setup->getCycles()->count());
        $this->assertEquals(3, $cycle3->getTests()->count(), 'Check that cycle include 2 created test. count: ' . $cycle3->getTests()->count());
        $this->assertEquals(33.33, $cycle3->getPassRate(), 'Check that cycle pass rate is 33.33%: Actual = ' . $cycle3->getPassRate());
    }

    protected function findTestIdInTest(&$input): int
    {
        preg_match('/TestID is (\d+)\./', $input, $matches);
        $testId = $matches[1];
        return (int)$testId;
    }
}