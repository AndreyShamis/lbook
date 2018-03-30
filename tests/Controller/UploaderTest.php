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
        $token = RandomString::generateRandomString(20);
        $setupName = 'Test Setup';
        $postParams = array(
            'token' => $token,
            'setup' => $setupName,
            //'cycle' => 'asdasdasdasd',
        );
        $postHeader = array('HTTP_REFERER' => '/upload/new_cli',);
        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        $passTestName = 'network_WiFi_Perf.11g';
        $passFileName = 'PASS__' . $passTestName;
        $errorTestName = 'network_WiFi_BluetoothStreamPerf.11a';
        $errorFileName = 'ERROR__' . $errorTestName;
        $failTestName = 'network_WiFi_Perf.ht40';
        $failFileName = 'FAIL__' . $failTestName;
        $currentPath = realpath(__DIR__) . '/';
        $filePath1 = $currentPath . $passFileName;
        $filePath2 = $currentPath . $errorFileName;
        $filePath3 = $currentPath . $failFileName;

        $this->resource_copy($currentPath . 'ForUpload/', $currentPath);

        $file1 = new UploadedFile($filePath1, $passFileName, 'text/plain', filesize($filePath1));
        $file2 = new UploadedFile($filePath2, $errorFileName, 'text/plain', filesize($filePath2));
        $file3 = new UploadedFile($filePath3, $failFileName, 'text/plain', filesize($filePath3));
        $client = $this->getClient();

        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file1), $postHeader);
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $firstContent = $this->getClient()->getResponse()->getContent();
        //echo  $firstContent;
        $this->validateNotExistingTestResponse($firstContent, $setupName, $token, $passFileName);

        /** Second file upload */
        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file2), $postHeader);

        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $secondContent = $this->getClient()->getResponse()->getContent();
        //echo  $secondContent;
        $this->validateExistingTestResponse($secondContent, $setupName, $token, $errorFileName);
        $testId = $this->findTestIdInTest($secondContent);

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

        /** Third file upload */
        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file3), $postHeader);

        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $thirdResponse = $this->getClient()->getResponse()->getContent();

        $this->validateExistingTestResponse($thirdResponse, $setupName, $token, $failFileName);

        $testId = $this->findTestIdInTest($thirdResponse);

        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        self::$entityManager->refresh($cycle);
        self::$entityManager->refresh($setup);
        $test = $testRepo->find($testId);

        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($failTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $failTestName);
        $this->assertSame(111, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 111);
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include 1 created cycle. count: ' . $setup->getCycles()->count());
        $this->assertEquals(3, $cycle->getTests()->count(), 'Check that cycle include 2 created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(33.33, $cycle->getPassRate(), 'Check that cycle pass rate is 33.33%: Actual = ' . $cycle->getPassRate());

        /** Check Test Pass/Fail/Error/Warning counters */
        $this->assertEquals(1, $cycle->getTestsPass(), 'Check that cycle Pass Count is 1: Actual = ' . $cycle->getTestsPass());
        $this->assertEquals(1, $cycle->getTestsFail(), 'Check that cycle Fail Count is 1: Actual = ' . $cycle->getTestsFail());
        $this->assertEquals(1, $cycle->getTestsError(), 'Check that cycle Error Count is 1: Actual = ' . $cycle->getTestsError());
        $this->assertEquals(0, $cycle->getTestsWarning(), 'Check that cycle Warning Count is 0: Actual = ' . $cycle->getTestsWarning());

        /** Check Test Run times */
        $period = 1437;
        $testTimeSum = 572;
        $this->assertEquals($period, $cycle->getPeriod(), 'Check that cycle Tests Period is '.$period.': Actual = ' . $cycle->getPeriod());
        $this->assertEquals($testTimeSum, $cycle->getTestsTimeSum(), 'Check that cycle Tests [Run Time(tests_time_sum)] is '.$testTimeSum.': Actual = ' . $cycle->getTestsTimeSum());

        /** Validate that  Pass/Fail/Error/Warning Rates similar to  Pass/Fail/Error/Warning counters */
        $this->assertEquals(round($cycle->getTestsPass(), 2), $cycle->getPassRate());
        $this->assertEquals(round($cycle->getTestsFail(), 2), $cycle->getFailRate());
        $this->assertEquals(round($cycle->getTestsError(), 2), $cycle->getErrorRate());
        $this->assertEquals(round($cycle->getTestsWarning(), 2), $cycle->getWarningRate());

        /**   */
    }

    /**
     * @param string $stringResponse
     * @param string $setupName
     * @param string $token
     * @param string $fileName
     */
    protected function validateExistingTestResponse(string &$stringResponse, string &$setupName, string &$token, string &$fileName): void
    {
        $this->assertNotRegExp('/Failed to generate cycle/', $stringResponse);
        $this->assertRegExp('/Token provided \['.$token.'\]/', $stringResponse);
        $this->assertNotRegExp('/Cycle not found by token\. Parsing Setup/', $stringResponse);
        $this->assertNotRegExp('/Searching setup by NAME :'.$setupName.'/', $stringResponse);
        $this->assertNotRegExp('/Creating setup  :'.$setupName.'/', $stringResponse);
        $this->assertNotRegExp('/Cycle name not provided/', $stringResponse);
        $this->assertNotRegExp('/Generated cycle name/', $stringResponse);
        $this->assertNotRegExp('/Creating cycle/', $stringResponse);
        $this->assertNotRegExp('/Cycle created ID:\d+./', $stringResponse);
        $this->assertRegExp('/Cycle found, take SETUP from cycle/', $stringResponse);
        $this->assertRegExp('/File name is :[\w\d]+'.$fileName.'/', $stringResponse);
        $this->assertRegExp('/TestID is \d+\.$/', $stringResponse);
    }

    /**
     * @param string $stringResponse
     * @param string $setupName
     * @param string $token
     * @param string $fileName
     */
    protected function validateNotExistingTestResponse(string &$stringResponse, string &$setupName, string &$token, string &$fileName): void
    {
        $this->assertNotRegExp('/Failed to generate cycle/', $stringResponse);
        $this->assertRegExp('/Token provided \['.$token.'\]/', $stringResponse);
        $this->assertRegExp('/Cycle not found by token\. Parsing Setup/', $stringResponse);
        $this->assertRegExp('/Searching setup by NAME :'.$setupName.'/', $stringResponse);
        $this->assertRegExp('/Creating setup  :'.$setupName.'/', $stringResponse);
        $this->assertRegExp('/Cycle name not provided/', $stringResponse);
        $this->assertRegExp('/Generated cycle name/', $stringResponse);
        $this->assertRegExp('/Creating cycle/', $stringResponse);
        $this->assertRegExp('/Cycle created ID:\d+\./', $stringResponse);
        $this->assertRegExp('/Cycle found, take SETUP from cycle/', $stringResponse);
        $this->assertRegExp('/File name is :[\w\d]+'.$fileName.'/', $stringResponse);
        $this->assertRegExp('/TestID is \d+\.$/', $stringResponse);
    }

    /**
     * @param $input
     * @return int
     */
    protected function findTestIdInTest(&$input): int
    {
        preg_match('/TestID is (\d+)\./', $input, $matches);
        $testId = $matches[1];
        return (int)$testId;
    }

    /**
     * @param $src
     * @param $dst
     */
    protected function resource_copy(string $src, string $dst): void
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ( $file = readdir($dir)) ) {
            if (( $file !== '.' ) && ( $file !== '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->resource_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}