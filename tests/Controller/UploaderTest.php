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
     * Check that Upload CLI works without token
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testUploadCliEmptyRequest():void
    {
        $postParams = array(
            'debug' => 'true',
        );
        $postHeader = array('HTTP_REFERER' => '/upload/new_cli',);
        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        $passTestName = 'network_WiFi_Perf.11g';
        $passFileName = 'PASS__' . $passTestName;
        $errorTestName = 'network_WiFi_BluetoothStreamPerf.11a';
        $errorFileName = 'ERROR__' . $errorTestName;
        $currentPath = realpath(__DIR__) . '/';
        $filePath1 = $currentPath . $passFileName;
        $filePath2 = $currentPath . $errorFileName;

        $this->resource_copy($currentPath . 'ForUpload/', $currentPath);

        $file1 = new UploadedFile($filePath1, $passFileName, 'text/plain');
        $file2 = new UploadedFile($filePath2, $errorFileName, 'text/plain');
        $client = $this->getClient();

        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file1), $postHeader);
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $firstContent = $this->getClient()->getResponse()->getContent();

        $setupName = null;
        $this->validateNotExistingTestResponse($firstContent, $setupName, null, $passFileName);

        self::$entityManager->clear();

        /** Second file upload */
        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file2), $postHeader);
        $secondContent = $this->getClient()->getResponse()->getContent();
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode(), 'Actual code is ' . $this->getClient()->getResponse()->getStatusCode() . ' ' . $secondContent);


        $setupName = null;
        $this->validateNotExistingTestResponse($secondContent, $setupName, null, $errorFileName, false);
        $testId = $this->findTestIdInTest($secondContent);
        self::$entityManager->clear();

        /** @var LogBookTest $test */
        $test = $testRepo->find($testId);
        /** @var LogBookCycle $cycle */
        $cycle = $test->getCycle();
        /** @var LogBookSetup $setup */
        $setup = $cycle->getSetup();

        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($setupName, $setup->getName(), 'Check that AutoGEN Setup name is same: Actual: ' . $setup->getName() . ', expected ' . $setupName . 'Current testID:' . $testId);
        $this->assertSame($errorTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $errorTestName . 'Current testID:' . $testId);
        $this->assertSame(35, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 35);
        $this->assertSame(359, $test->getLogs()->count(), 'Check that test Logs count is same: Actual: ' . $test->getTimeRun() . ', expected ' . 359);
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include 1 created cycle. count: ' . $setup->getCycles()->count());
        $this->assertEquals(1, $cycle->getTests()->count(), 'Check that cycle include 1 created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(0, $cycle->getPassRate(), 'Check that cycle pass rate is 0%: Actual = ' . $cycle->getPassRate());
    }

    /**
     * Check that Upload CLI works without token
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testUploadCliNoToken():void
    {
        $token = RandomString::generateRandomString(20);
        $setupName = 'UPLOAD_TOKEN_NOT_PROVIDED';
        $postParams = array(
            'setup' => $setupName,
            'debug' => 'true',
        );
        $postHeader = array('HTTP_REFERER' => '/upload/new_cli',);
        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        $passTestName = 'network_WiFi_Perf.11g';
        $passFileName = 'PASS__' . $passTestName;
        $errorTestName = 'network_WiFi_BluetoothStreamPerf.11a';
        $errorFileName = 'ERROR__' . $errorTestName;
        $currentPath = realpath(__DIR__) . '/';
        $filePath1 = $currentPath . $passFileName;
        $filePath2 = $currentPath . $errorFileName;

        $this->resource_copy($currentPath . 'ForUpload/', $currentPath);

        $file1 = new UploadedFile($filePath1, $passFileName, 'text/plain');
        $file2 = new UploadedFile($filePath2, $errorFileName, 'text/plain');
        $client = $this->getClient();

        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file1), $postHeader);
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $firstContent = $this->getClient()->getResponse()->getContent();

        $this->validateNotExistingTestResponse($firstContent, $setupName, null, $passFileName);

        self::$entityManager->clear();

        /** Second file upload */
        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file2), $postHeader);

        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $secondContent = $this->getClient()->getResponse()->getContent();
        //echo  $secondContent;

        $this->validateNotExistingTestResponse($secondContent, $setupName, null, $errorFileName, false);
        $testId = $this->findTestIdInTest($secondContent);
        self::$entityManager->clear();

        $test = $testRepo->find($testId);
        /** @var LogBookCycle $cycle */
        $cycle = $test->getCycle();
        /** @var LogBookSetup $setup */
        $setup = $cycle->getSetup();

        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($setupName, $setup->getName(), 'Check that AutoGEN Setup name is same: Actual: ' . $setup->getName() . ', expected ' . $setupName . 'Current testID:' . $testId);
        $this->assertSame($errorTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $errorTestName . 'Current testID:' . $testId);
        $this->assertSame(35, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 35);
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include 1 created cycle. count: ' . $setup->getCycles()->count());
        $this->assertEquals(1, $cycle->getTests()->count(), 'Check that cycle include 1 created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(0, $cycle->getPassRate(), 'Check that cycle pass rate is 0%: Actual = ' . $cycle->getPassRate());
    }

    /**
     * Check that Upload CLI works with Auto setup name generator
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testUploadCliTokenExp():void
    {
        $token = RandomString::generateRandomString(20);
        $setupName = 'UPLOAD_TOKEN_EXPIRED';
        $postParams = array(
            'setup' => $setupName,
            'token' => $token,
            'debug' => 'true',
        );
        $postHeader = array('HTTP_REFERER' => '/upload/new_cli',);
        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        $passTestName = 'network_WiFi_Perf.11g';
        $passFileName = 'PASS__' . $passTestName;
        $errorTestName = 'network_WiFi_BluetoothStreamPerf.11a';
        $errorFileName = 'ERROR__' . $errorTestName;
        $currentPath = realpath(__DIR__) . '/';
        $filePath1 = $currentPath . $passFileName;
        $filePath2 = $currentPath . $errorFileName;

        $this->resource_copy($currentPath . 'ForUpload/', $currentPath);

        $file1 = new UploadedFile($filePath1, $passFileName, 'text/plain');
        $file2 = new UploadedFile($filePath2, $errorFileName, 'text/plain');
        $client = $this->getClient();

        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file1), $postHeader);
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $firstContent = $this->getClient()->getResponse()->getContent();

        $this->validateNotExistingTestResponse($firstContent, $setupName, $token, $passFileName);
        $testId = $this->findTestIdInTest($firstContent);

        $test = $testRepo->find($testId);
        /** @var LogBookCycle $cycle */
        $cycle = $test->getCycle();
        $cycle->setTokenExpiration(new \DateTime('-1 days'));

        self::$entityManager->flush($cycle);
        self::$entityManager->clear();

        /** Second file upload */
        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file2), $postHeader);

        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $secondContent = $this->getClient()->getResponse()->getContent();
        //echo  $secondContent;

        $this->validateNotExistingTestResponse($secondContent, $setupName, $token, $errorFileName, false);
        $testId = $this->findTestIdInTest($secondContent);
        self::$entityManager->clear();

        $test = $testRepo->find($testId);
        /** @var LogBookCycle $cycle */
        $cycle = $test->getCycle();
        /** @var LogBookSetup $setup */
        $setup = $cycle->getSetup();

        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($setupName, $setup->getName(), 'Check that AutoGEN Setup name is same: Actual: ' . $setup->getName() . ', expected ' . $setupName . 'Current testID:' . $testId);
        $this->assertSame($errorTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $errorTestName . 'Current testID:' . $testId);
        $this->assertSame(35, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 35);
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include 1 created cycle. count: ' . $setup->getCycles()->count());
        $this->assertEquals(1, $cycle->getTests()->count(), 'Check that cycle include 1 created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(0, $cycle->getPassRate(), 'Check that cycle pass rate is 0%: Actual = ' . $cycle->getPassRate());
    }

    /**
     * Check that Upload CLI works with Auto setup name generator
     */
    public function testUploadCliNoSetup():void
    {
        $token = RandomString::generateRandomString(20);
        $postParams = array(
            'token' => $token,
            'debug' => 'true',
        );
        $postHeader = array('HTTP_REFERER' => '/upload/new_cli',);
        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        $passTestName = 'network_WiFi_Perf.11g';
        $passFileName = 'PASS__' . $passTestName;
        $errorTestName = 'network_WiFi_BluetoothStreamPerf.11a';
        $errorFileName = 'ERROR__' . $errorTestName;
        $currentPath = realpath(__DIR__) . '/';
        $filePath1 = $currentPath . $passFileName;
        $filePath2 = $currentPath . $errorFileName;

        $this->resource_copy($currentPath . 'ForUpload/', $currentPath);

        $file1 = new UploadedFile($filePath1, $passFileName, 'text/plain');
        $file2 = new UploadedFile($filePath2, $errorFileName, 'text/plain');
        $client = $this->getClient();

        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file1), $postHeader);
        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $firstContent = $this->getClient()->getResponse()->getContent();
        $setupName = null;
        $this->validateNotExistingTestResponse($firstContent, $setupName, $token, $passFileName);

        /** Second file upload */
        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file2), $postHeader);

        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $secondContent = $this->getClient()->getResponse()->getContent();
        //echo  $secondContent;

        $this->validateExistingTestResponse($secondContent, $token, $errorFileName);
        $testId = $this->findTestIdInTest($secondContent);

        $test = $testRepo->find($testId);
        /** @var LogBookCycle $cycle */
        $cycle = $test->getCycle();
        /** @var LogBookSetup $setup */
        $setup = $cycle->getSetup();
        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($setupName, $setup->getName(), 'Check that AutoGEN Setup name is same: Actual: ' . $setup->getName() . ', expected ' . $setupName . 'Current testID:' . $testId);
        $this->assertSame($errorTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $errorTestName . 'Current testID:' . $testId);
        $this->assertSame(35, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 35);
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include 1 created cycle. count: ' . $setup->getCycles()->count());
        $this->assertEquals(2, $cycle->getTests()->count(), 'Check that cycle include 2 created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(50, $cycle->getPassRate(), 'Check that cycle pass rate is 50%: Actual = ' . $cycle->getPassRate());
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    public function testUploadCli():void
    {
        $token = RandomString::generateRandomString(20);
        $setupName = 'SETUP_PROVIDED___' . $token;
        $postParams = array(
            'token' => $token,
            'setup' => $setupName,
            'debug' => 'true',
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

        $file1 = new UploadedFile($filePath1, $passFileName, 'text/plain');
        $file2 = new UploadedFile($filePath2, $errorFileName, 'text/plain');
        $file3 = new UploadedFile($filePath3, $failFileName, 'text/plain');
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
        $this->validateExistingTestResponse($secondContent, $token, $errorFileName);
        $testId = $this->findTestIdInTest($secondContent);

        $test = $testRepo->find($testId);
        /** @var LogBookCycle $cycle */
        $cycle = $test->getCycle();
        /** @var LogBookSetup $setup */
        $setup = $cycle->getSetup();
        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($errorTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $errorTestName . 'Current testID:' . $testId);
        $this->assertSame(35, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 35);
        $this->assertEquals(1, $setup->getCycles()->count(), 'Check that Setup include 1 created cycle. count: ' . $setup->getCycles()->count());
        $this->assertEquals(2, $cycle->getTests()->count(), 'Check that cycle include 2 created test. count: ' . $cycle->getTests()->count());
        $this->assertEquals(50, $cycle->getPassRate(), 'Check that cycle pass rate is 50%: Actual = ' . $cycle->getPassRate());

        /** Third file upload */
        $postParams['cycle'] = '3CycleNameShouldBeLike_This';
        $client->request('POST', '/upload/new_cli', $postParams, array('file' => $file3), $postHeader);

        $this->assertSame(Response::HTTP_OK, $this->getClient()->getResponse()->getStatusCode());
        $thirdResponse = $this->getClient()->getResponse()->getContent();

        $this->validateExistingTestResponse($thirdResponse, $token, $failFileName, $postParams['cycle']);

        $testId = $this->findTestIdInTest($thirdResponse);

        $testRepo = self::$entityManager->getRepository(LogBookTest::class);
        self::$entityManager->refresh($cycle);
        self::$entityManager->refresh($setup);
        $test = $testRepo->find($testId);

        $this->assertEquals($postParams['cycle'], $cycle->getName(), 'Check that cycle Name was changed in DB. Expect '.$postParams['cycle'].': Actual = ' . $cycle->getName());

        //echo "Setup cycles count " . count($setup->getCycles()) . "\n";
        $this->assertSame($failTestName, $test->getName(), 'Check that test name is same: Actual: ' . $test->getName() . ', expected ' . $failTestName);
        $this->assertSame(110, $test->getTimeRun(), 'Check that test RunTime is same: Actual: ' . $test->getTimeRun() . ', expected ' . 111);
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
        $testTimeSum = 571;
        $this->assertEquals($period, $cycle->getPeriod(), 'Check that cycle Tests Period is '.$period.': Actual = ' . $cycle->getPeriod());
        $this->assertEquals($testTimeSum, $cycle->getTestsTimeSum(), 'Check that cycle Tests [Run Time(tests_time_sum)] is '.$testTimeSum.': Actual = ' . $cycle->getTestsTimeSum());

        $coefficient = 100/$cycle->getTestsCount();

        /** Validate that  Pass/Fail/Error/Warning Rates similar to  Pass/Fail/Error/Warning counters */
        $this->assertEquals(round($cycle->getTestsPass()*$coefficient, 2), $cycle->getPassRate());
        $this->assertEquals(round($cycle->getTestsFail()*$coefficient, 2), $cycle->getFailRate());
        $this->assertEquals(round($cycle->getTestsError()*$coefficient, 2), $cycle->getErrorRate());
        $this->assertEquals(round($cycle->getTestsWarning()*$coefficient, 2), $cycle->getWarningRate());
    }

    /**
     * @param string $stringResponse
     * @param string $setupName
     * @param string $token
     * @param string $fileName
     * @param null $cycleName
     */
    protected function validateExistingTestResponse(string $stringResponse, string $token, string $fileName, $cycleName=null): void
    {
        $this->assertNotRegExp('/Failed to generate cycle/', $stringResponse);
        $this->assertRegExp('/Token provided \['.$token.'\]/', $stringResponse);
        $this->assertNotRegExp('/Cycle not found by token\. Parsing Setup/', $stringResponse);
        $this->assertNotRegExp('/Creating setup  :.*/', $stringResponse);
        $this->assertNotRegExp('/Cycle name not provided/', $stringResponse);
        $this->assertNotRegExp('/Generated cycle name/', $stringResponse);
        $this->assertNotRegExp('/Creating cycle/', $stringResponse);
        $this->assertNotRegExp('/Cycle created ID:\d+./', $stringResponse);
        $this->assertRegExp('/Cycle found, take SETUP from cycle/', $stringResponse);
        if ($cycleName !== null) {
            $this->assertRegExp('/WARNING: cycle name changed, updating to new one \['. $cycleName .'\]/', $stringResponse);
        }
        $this->assertRegExp('/File name is :[\w\d]+'.$fileName.'/', $stringResponse);
        $this->assertRegExp('/TestID is \d+\.$/', $stringResponse);
    }

    /**
     * @param string $stringResponse
     * @param string $setupName
     * @param string $token
     * @param string $fileName
     * @param bool $new_setup Mark if setup is new and need to search for setup creation
     */
    protected function validateNotExistingTestResponse(string $stringResponse, string &$setupName=null, string $token=null, string $fileName, $new_setup=true): void
    {
        $this->assertNotRegExp('/Failed to generate cycle/', $stringResponse);
        if ($token !== null) {
            $this->assertRegExp('/Token provided \['.$token.'\]/', $stringResponse);
        }
        $this->assertNotRegExp('/WARNING: cycle name changed, updating to new one \[[\w\d]+\]/', $stringResponse);
        $this->assertRegExp('/Cycle not found by token\. Parsing Setup/', $stringResponse);
        if ($setupName !== null) {
            $this->assertRegExp('/Searching setup by NAME :'.$setupName.'/', $stringResponse);
        } else {
            $this->assertRegExp('/Generating new setup NAME :[\w|\d]+/', $stringResponse);

        }
        if ($new_setup) {
            $this->assertRegExp('/Creating setup  :'.$setupName.'/', $stringResponse);
        }
        $this->assertRegExp('/Cycle name not provided/', $stringResponse);
        $this->assertRegExp('/Generated cycle name/', $stringResponse);
        $this->assertRegExp('/Creating cycle/', $stringResponse);
        $this->assertRegExp('/Cycle created ID:\d+\./', $stringResponse);
        $this->assertRegExp('/Cycle found, take SETUP from cycle/', $stringResponse);
        $this->assertRegExp('/File name is :[\w\d]+'.$fileName.'/', $stringResponse);
        $this->assertRegExp('/TestID is \d+\.$/', $stringResponse);
        if ($setupName === null) {
            $pattern = '/Creating setup  :(.*)/';
            if(preg_match_all($pattern, $stringResponse,$matches)){
                $setupName = $matches[1][0];
            }
        }
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