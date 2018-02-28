<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookMessage;
use App\Entity\LogBookTest;
use App\Entity\LogBookUpload;
use App\Entity\LogBookVerdict;
use App\Entity\LogBookSetup;
use ArrayIterator;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use App\Utils\RandomName;

/**
 * Uploader controller.
 *
 * @Route("upload")
 */
class LogBookUploaderController extends Controller
{
    protected $em = null;
    protected $testsRepo = null;
    protected $cycleRepo = null;
    protected $verdictRepo = null;
    protected $msgTypeRepo = null;
    protected $logsRepo = null;
    protected $setupRepo = null;
    protected $buildRepo = null;
    protected $targetRepo = null;
    protected $container;
    protected $_MIN_LOG_STR_LEN = 10;
    protected $_MIN_CLEAN_LOG_STR_LEN = 1;
    protected $_SHORT_TIME_LEN = 8;             // 12:48:45
    protected $_SHORT_MILISEC_TIME_LEN = 12;    // 02:44:38.820
    protected $_MEDIUM_TIME_LEN = 14;           // 02/22 11:36:56
    protected $_MEDIUM_MILISEC_TIME_LEN = 18;   // 02/19 02:44:39.177
    protected $log_first_lines = array();

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em = $this->getDoctrine()->getManager();
        $this->testsRepo = $this->em->getRepository('App:LogBookTest');
        $this->cycleRepo = $this->em->getRepository('App:LogBookCycle');
        $this->verdictRepo = $this->em->getRepository('App:LogBookVerdict');
        $this->msgTypeRepo = $this->em->getRepository('App:LogBookMessageType');
        $this->logsRepo = $this->em->getRepository('App:LogBookMessage');
        $this->setupRepo = $this->em->getRepository('App:LogBookSetup');
        $this->buildRepo = $this->em->getRepository('App:LogBookBuild');
        $this->targetRepo = $this->em->getRepository('App:LogBookTarget');
    }

    /**
     * @Route("/", name="upload_index")
     * @Method("GET")
     */
    public function index()
    {
        return $this->render('lbook/upload/index.html.twig', array());
    }

    protected function getTestNewExecutionOrder(LogBookCycle $cycle){
        /**
         * Find proper execution order | The correct Way
         */
        $latestTestInCycle = $this->testsRepo->findOneBy(array("cycle" => $cycle->getId()), array('executionOrder' => 'DESC'));
        if($latestTestInCycle !== null){
            $executionOrder = $latestTestInCycle->getExecutionOrder() + 1;
        }
        else{
            $executionOrder = 0;
        }
        /**
         * Find proper execution order | Incorrect Way
         */
//        $cycleTestsCount = $this->testsRepo->count(array("cycle" => $cycle->getId()));
//        $executionOrderFound = false;
//        $executionOrder = $cycleTestsCount;
//        while (!$executionOrderFound){
//            $count = $this->testsRepo->count(array(
//                "cycle" => $cycle->getId(),
//                "executionOrder" => $executionOrder,
//            ));
//            if($count > 0){
//                $executionOrder++;
//            }
//            else{
//                $executionOrderFound = true;
//            }
//        }
        return $executionOrder;
    }

    protected function generateCycleName() : string {
        return RandomName::asClassName(RandomName::getRandomTerm());
    }

    protected function generateSetupName() : string {
        $setup_name = "";
        $setupNameFound = false;
        $counter = 0;
        $post_fix = "";
        while(!$setupNameFound){
            $setup_name = RandomName::asClassName(RandomName::getRandomTerm()) . $post_fix;
            $setup = $this->setupRepo->findByName($setup_name);
            if($setup === null){
                $setupNameFound = true;
                break;
            }
            $counter++;
            if($counter%100==0){
                $post_fix = rand(1,9999);
            }
            if($counter%1000==0){
                $post_fix = $counter . rand(1,9999);
            }
        }
        return $setup_name;
    }

    final protected function bringSetup(LogBookUpload &$obj, string $setupName = "", int $setupId = -1) : LogBookSetup{

        /** @var LogBookSetup $setup */
        $setup = null;
        if($setupId > 0){
            $obj->addMessage("Searching setup by ID :" . $setupId);
            $setup = $this->setupRepo->findById($setupId);
            if($setup !== null){
                $obj->addMessage(sprintf("Found setup [ID:%d], [NAME:%s] ", $setup->getId(), $setup->getName()));
            }
        }

        if($setup === null && strlen($setupName) > 0){
            $obj->addMessage("Searching setup by NAME :" . $setupName);
            $setup = $this->setupRepo->findByName($setupName);
            if($setup !== null){
                $obj->addMessage(sprintf("Found setup [ID:%d], [NAME:%s] ", $setup->getId(), $setup->getName()));
            }
        }

        if($setup === null){
            if(strlen($setupName) < 3){
                $setupName = $this->generateSetupName();
                $obj->addMessage("Generating new setup NAME :" . $setupName);
            }
            $obj->addMessage("Creating setup  :" . $setupName);
            $setup = $this->setupRepo->findOneOrCreate(array(
                    "name" => $setupName,
                )
            );
        }

        return $setup;
    }


    /**
     * Creates a new Upload entity.
     *
     * @Route("/new_cli", name="upload_new_cli")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newCliAction(Request $request)
    {
        //curl --noproxy "127.0.0.1" --max-time 120 --form SETUP_NAME=DELL-KUBUNTU --form 'UPTIME_START=720028.73 2685347.68' --form 'UPTIME_END=720028.73 2685347.68' --form NIC=TEST --form DUTIP=172.17.0.1 --form PlatformName=Platf --form k_ver= --form Kernel=4.4.0-112-generic --form testCaseName=sa --form testSetName=sa --form build=A:_S:_I: --form testCount=2  --form file=@autoserv.DEBUG --form setup='SUPER SETUP' --form cycle='1' --form token=2602161043  http://127.0.0.1:8080/upload/new_cli
        $obj = new LogBookUpload();
        $p_data = $request->request;
        /** @var LogBookCycle $cycle */
        $cycle = null;
        /** @var LogBookSetup $setup */
        $setup = null;
        if ($p_data->count() > 1) {
            /** @var UploadedFile $file */
            $file = $request->files->get('file');
            $cycle_name = $request->request->get('cycle');
            $setup_name = $request->request->get('setup');
            $cycle_token = $request->request->get('token');
            $fileName = $this->generateUniqueFileName(). '_' . $file->getClientOriginalName(). '.'.$file->guessExtension();


            if($cycle_token !== null && $cycle_token != ""){
                $obj->addMessage("INFO: Token provided use him : " . $cycle_token);
                $cycle = $this->cycleRepo->findOneBy(array("uploadToken" => $cycle_token));
                if($cycle === null){
                    $obj->addMessage("ERROR: Cycle not found by token ! TODO create new cycle? -> Need Parse Setup");
                    /**
                     * TODO create new cycle? -> Need Parse Setup
                     */
                    $setup = $this->bringSetup($obj, $setup_name);
                    if(strlen($cycle_name) < 1){
                        $cycle_name = $this->generateCycleName();
                    }
                    $cycle = $this->cycleRepo->findOneOrCreate(array(
                        'name' => $cycle_name,   // TODO provide cycle name
                        'setup' => $setup,
                        'uploadToken' => $cycle_token,
                        //'tokenExpiration' => new \DateTime('+7 days');,   // Done in constructor
                    ));
                    if($cycle === null){
                        $obj->addMessage("CRITICAL: Failed to generate cycle");
                        // exit();
                    }
                    $obj->addMessage("ERROR: Cycle not found by token ! TODO create new cycle? -> Need Parse Setup");
                }
                else{
                    /**
                     * TODO Check token exp date
                     */
                }
            }
            else{
                /**
                 * TODO Token not provided -> EXIT?
                 */
            }
//
//            if($cycle === null){
//                $cycle = $this->cycleRepo->findOneBy(array("id" => $cycle_id));
//            }

            if($cycle !== null){
                $obj->addMessage("INFO: Cycle found, take SETUP from cycle");
                $setup = $cycle->getSetup();
            }
            else{
                $obj->addMessage("ERROR: Cycle not found, take setup from POST");
                $setup = $this->bringSetup($obj, $setup_name);

                if($setup !== null){
                    $obj->addMessage("ERROR: TODO Require to create new cycle ");
                    /** TODO Require to create new cycle */
                }
                else{
                    $obj->addMessage("ERROR: TODO - cycle not found, setup not found, Create Setup and then Cycle");
                    /** TODO - cycle not found, setup not found, Create Setup and then Cycle */
                }
            }
            $obj->addMessage("File name is :" . $fileName . ". \tFile ext :"  .$file->guessExtension());
            /** @var UploadedFile $new_file */
            $new_file = $file->move("../uploads/", $fileName);
            $obj->addMessage("File copy info :"  . $new_file . " File size is " . $new_file->getSize());
            $obj->setLogFile($fileName);

            $testName = $file->getClientOriginalName();
            $test = $this->testsRepo->findOneOrCreate(array(
                "id" => 1,
                "name" => $testName,
                "cycle" => $cycle,
                "logFile" => $new_file->getFilename(),
                "logFileSize" => $new_file->getSize(),
                "executionOrder" => $this->getTestNewExecutionOrder($cycle),
            ));
            $obj->data = $this->parseFile($new_file, $test);

            $cycle->setBuild($this->buildRepo->findOneOrCreate(array("name" => 'Some Build')));
            $remote_ip = $request->getClientIp();
            $uploader = $this->targetRepo->findOneOrCreate(array('name' => $remote_ip));
            $dut = $this->targetRepo->findOneOrCreate(array('name' => 'testDut'));

            $cycle->setTargetUploader($uploader);
            $cycle->setController($uploader);
            $cycle->setDut($dut);

            $this->em->flush();
            $obj->addMessage("TestID is " . $test->getId());
        }

        return $this->render('lbook/upload/curl.html.twig', array(
            'cycle' => $cycle,
            'setup' => $setup,
            'upload' => $obj,
        ));
    }


    /**
     * Creates a new Upload entity.
     *
     * @Route("/new", name="upload_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        //curl --max-time 120 --form file=@autoserv.DEBUG --form executionID=2602161043 --form SETUP_NAME=DELL-KUBUNTU --form 'UPTIME_START=720028.73 2685347.68' --form 'UPTIME_END=720028.73 2685347.68' --form NIC=TEST --form DUTIP=172.17.0.1 --form PlatformName=Platf --form k_ver= --form Kernel=4.4.0-112-generic --form testCaseName=sa --form testSetName=sa --form build=A:_S:_I: --form testCount=2 http://127.0.0.1:8080/upload/new
        $obj = new LogBookUpload();
        $form = $this->createForm('App\Form\LogBookUploadType', $obj);
        $form->handleRequest($request);
        $setup = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $request_str = 'log_book_upload';
            // $file stores the uploaded PDF file
            /** @var UploadedFile $file */
            $file = $obj->getLogFile();

            $fileName = $this->generateUniqueFileName(). '_' . $file->getClientOriginalName(). '.'.$file->guessExtension();

            $post_cycle = $request->request->get($request_str)['cycle'];
            $setup_id = $request->request->get($request_str)['setup'];

            $cycle = $this->cycleRepo->findOneBy(array("id" => $post_cycle));
            if($cycle !== null){
                $obj->addMessage("Cycle found, take SETUP from cycle");
                $setup = $cycle->getSetup();
            }
            else{
                $obj->addMessage("Cycle not found, take setup from POST");
                $setup_name = "";//$request->query->get('build');//$this->clean_string("Test Setup");
                /** @var LogBookSetup $setup */
                $setup = $this->bringSetup($obj,$setup_name , $setup_id);
            }

            $obj->addMessage("New file name is :" . $fileName);
            $obj->addMessage("File ext :"  .$file->guessExtension());
            $new_file = $file->move("../uploads/", $fileName);
            $obj->new_file_info = $new_file;
            $obj->addMessage("File copy info :"  . $new_file);
            $obj->setLogFile($fileName);

            $testName = $file->getClientOriginalName();
            $test = $this->testsRepo->findOneOrCreate(array(
                "id" => 1,
                "name" => $testName,
                "cycle" => $cycle,
                "logFile" => $new_file->getFilename(),
                "logFileSize" => $new_file->getSize(),
                "executionOrder" => $this->getTestNewExecutionOrder($cycle),
                ));
            $obj->data = $this->parseFile($new_file, $test);

            $cycle->setBuild($this->buildRepo->findOneOrCreate(array("name" => 'Some Build')));
            $remote_ip = $request->getClientIp();
            $uploader = $this->targetRepo->findOneOrCreate(array('name' => $remote_ip));
            $dut = $this->targetRepo->findOneOrCreate(array('name' => 'testDut'));

            $cycle->setTargetUploader($uploader);
            $cycle->setController($uploader);
            $cycle->setDut($dut);
            $this->em->flush();
            $obj->addMessage("TestID is " . $test->getId());
            return $this->showAction($obj);
        }

        return $this->render('lbook/upload/new.html.twig', array(
            'setup' => $setup,
            'verdict' => $obj,
            'form' => $form->createView(),
        ));
    }

    final protected function prepareLogArray(array &$temp_arr){
        $newTempArr = array();
        $last_good_key = -1;
        foreach ($temp_arr as $key => $value){

            if(strlen($value) < $this->_MIN_LOG_STR_LEN){
                $value = null;
                continue;
            }
            preg_match_all('/(\d{2,}.*\d{1,1})\s*([A-Z]+)\s*\|\s*(.*)/', $value,$oneLine);
            if (count($oneLine[2]) > 0){
                $last_good_key = $key;
                $newTempArr[$key] = $this->clean_string($value);
            }
            else{
                if($last_good_key > 0){
                    $newTempArr[$last_good_key] = $newTempArr[$last_good_key] . "\n" . $this->clean_string($value);
                }
                else{
                    // add first lines without time to array
                    $this->log_first_lines[] = $this->clean_string($value);
                    // or
                    // Skip first lines
                }
            }
            unset($temp_arr[$key]);
        }
        unset($temp_arr);

        return $newTempArr;
    }

    /**
     * @param $newTempArr
     * @return int
     */
    protected function recoverFirstLines(&$newTempArr) : int {

        if(count($newTempArr) && count($this->log_first_lines)){
            // TODO Put first lines into start of $newTempArr
            $reverted = new ArrayIterator(array_reverse($this->log_first_lines));
            foreach($reverted as $tmp){
                array_unshift($newTempArr, $tmp);
            }
        }
        return count($this->log_first_lines);
    }

    /**
     * @param String $file
     * @param LogBookTest $test
     * @return array
     */
    protected function parseFile($file, LogBookTest $test)
    {
        $ret_data = array();
        $file_data = file_get_contents($file , FILE_USE_INCLUDE_PATH);
        $tmp_log_arr = preg_split("/\\r\\n|\\r|\\n/", $file_data);
        $newTempArr = $this->prepareLogArray($tmp_log_arr);

        unset($file_data);

        $counter=0;
        $objectsToClear = array();

        $testName = null;
        $testNameFound = false;
        $tmpTestNameFlag_TestPrint = false;
        $tmpTestNameFlag_AutotestTestPrint = false;
        $tmpTestNameFlag_ControlTestPrint = false;

        $testVerdict = null;

//        if(count($this->log_first_lines)){
//            /**
//             * TODO : Need First time in test
//             */
//            $counter = $this->recoverFirstLines($newTempArr);
//        }
        /*
         * Test Time section
         */
        $testStartTime = new \DateTime('+100 years');
        $testEndTime = new \DateTime('-100 years');


        /**
         * If in previous FOR used "&" and use same array
         * Dont remove & from  &$value -> will cause to additional duplicated line
         */
        foreach ($newTempArr as $key => $value){
            preg_match_all('/(\d{2,}.*\d{1,1})\s*([A-Z]+)\s*\|\s*(.*)/s', $value,$oneLine);

            if (count($oneLine[2]) > 0){
                $dLevel = null;
                /** Clean double DEBUG OUTPUT **/
                //Removing : base_job:0395 utils:0262 ssh_host:0116
                preg_match('/([\w|\_]*\:\d+\s*)\|\s*(.*)/s', $oneLine[3][0], $messageWithDebug);
                if(count($messageWithDebug) == 3){
                    $oneLine[3][0] = $messageWithDebug[2];
                }
                /** **/
                $msg_str = trim($oneLine[3][0]);
                if(strlen($msg_str) < $this->_MIN_CLEAN_LOG_STR_LEN){
                    continue;
                }
                $msgType_str = $oneLine[2][0];
                $logTime_str = $oneLine[1][0];

                /** Test verdict section **/
                if($msgType_str === "INFO"){
                    preg_match('/END\s*([A-Za-z\_]*)/', $msg_str, $possibleVerdict);
                    if(count($possibleVerdict) == 2){
                        $testVerdict = $this->parseVerdict($possibleVerdict[1]);
                        if($testVerdict !== null){
                            if($testVerdict->getName() == 'ABORT' || $testVerdict->getName() == 'PASS' || $testVerdict->getName() == 'ERROR' || $testVerdict->getName() == 'FAIL' || $testVerdict->getName() == 'TEST_NA'){
                                $msgType_str = $testVerdict->getName();
                            }
                        }
                    }
                    else{
                        preg_match('/\s*(FAIL|GOOD|ERROR|TEST_NA|ABORT|WARN)\s*.*(.*timestamp\=.*localtime\=)/D', $msg_str, $possibleMessageType);
                        if(count($possibleMessageType) == 3){
                            if($possibleMessageType[1] == "GOOD"){
                                $possibleMessageType[1] = "PASS";
                            }
                            if($possibleMessageType[1] == "WARN"){
                                $possibleMessageType[1] = "WARNING";
                            }
                            $msgType_str = $possibleMessageType[1];
                        }
                    }
                }

                /** **/
                $ret_data[$counter] = array(
                    'logTime'   => $this->getLogTime($logTime_str),
                    'message'   => $msg_str,
                    'chain'     => $counter,
                    'test'      => $test,
                    'msgType'   => $this->msgTypeRepo->findOneOrCreate(array(
                        'name'      => $this->prepareDebugLevel($msgType_str)
                    )),
                );
                $log = $this->logsRepo->Create($ret_data[$counter], false);
                $objectsToClear[] = $log;

                /*** Test Name section **/
                if(!$testNameFound && $log->getMsgType() == "INFO"){
                    $tmpName = null;

                    if(!$tmpTestNameFlag_AutotestTestPrint && !$tmpTestNameFlag_ControlTestPrint){
                        $tmpName = $this->searchTestNameInSingleLogAutoTestPrint($log);
                        if($tmpName !== null){
                            $tmpTestNameFlag_AutotestTestPrint = true;
                        }
                    }
                    else if(!$tmpTestNameFlag_TestPrint && !$tmpTestNameFlag_ControlTestPrint){
                        $tmpName = $this->searchTestNameInSingleLogTestPrint($log, true);
                        if($tmpName !== null){
                            $tmpTestNameFlag_TestPrint = true;
                        }
                    }
                    else if(!$tmpTestNameFlag_ControlTestPrint){
                        $tmpName = $this->searchTestNameInSingleLogControlPrint($log);
                        if($tmpName !== null){
                            $tmpTestNameFlag_ControlTestPrint = true;
                            $testNameFound = true;
                        }
                    }

                    if($tmpName !== null){
                        $testName = $tmpName;
                    }
                }

                /** Test Time section **/
                $testStartTime = min($testStartTime, $log->getLogTime());
                $testEndTime = max($testEndTime, $log->getLogTime());
                /** **/
                $counter++;
            }
            else{
                if($counter > 0){
                    echo count($oneLine) .  " $value:<pre>";
                    print_r($oneLine);
                    echo "</pre><br/>";
                }
            }
        }
        /**
         * Test Verdict section
         */
        if($testVerdict !== null){
            $test->setVerdict($testVerdict);
        }
        else{
            $test->setVerdict($this->parseVerdict("ERROR"));
        }
        $test->setTimeStart($testStartTime);
        $test->setTimeEnd($testEndTime);
        if($testName !== null && strlen($testName) > 0) {
            $test->setName($testName);
        }
        $this->em->flush();
        foreach ($objectsToClear as $obj){
            $this->em->detach($obj);   // In order to free used memory; Decrease running time of 400 cycles, from ~15-20 to 2 minutes
        }

        return $ret_data;
    }

    /**
     * Parse from string Test Verdict
     * @param string $input
     * @return LogBookVerdict
     */
    protected function parseVerdict(string $input) : LogBookVerdict{
        $input = strtolower(trim($input));
        $criteria['name'] = strtoupper($input);
        switch ($input){
            case 'pass':
                $ret = $this->verdictRepo->findOneOrCreate($criteria);
                break;
            case 'good':
                $ret = $this->verdictRepo->findOneOrCreate(array('name' => 'PASS'));
                break;
            case 'test_na':
                $ret = $this->verdictRepo->findOneOrCreate($criteria);
                break;
            case 'fail':
                $ret = $this->verdictRepo->findOneOrCreate($criteria);
                break;
            case 'error':
                $ret = $this->verdictRepo->findOneOrCreate($criteria);
                break;
            case 'warn':
                $ret = $this->verdictRepo->findOneOrCreate(array('name' => 'WARNING'));
                break;
            case 'warning':
                $ret = $this->verdictRepo->findOneOrCreate($criteria);
                break;
            default:
                $ret = $this->verdictRepo->findOneOrCreate($criteria);
                $ret = $this->verdictRepo->findOneOrCreate(array('name' => 'UNKNOWN'));
                break;
        }
        return $ret;
    }

    /**
     * Used to parse Control print of test name with version, grup test name and test version
     * @param LogBookMessage $log
     * @param bool $includeVersion
     * @return null|string
     */
    protected function searchTestNameInSingleLogControlPrint(LogBookMessage $log, $includeVersion = true){
        $ret = null;
        preg_match('/\=*Running Sub-test\: \[([\w\s\_\-\.\;\#\!\$\@\%\^\&\*\(\)]*)\]\s*\(ver\.\s*(\d+\.?\d*)\,/', $log->getMessage(), $possibleName);
        if(count($possibleName) == 3){
            $dirty = $possibleName[0];
            $testName = $possibleName[1];
            $testVersion = $possibleName[2];
            if (strlen($dirty) > 0 && strlen($testName) > 0 && strlen($testVersion) > 0) {
                if($includeVersion){
                    $ret = $testName . " " . $testVersion;
                }
                else{
                    $ret = $testName;
                }
            }
        }

        return $ret;
    }

    /**
     * Used to parse test print of test name with version, grup test name and test version
     * @param LogBookMessage $log
     * @param bool $includeVersion
     * @return null|string
     */
    protected function searchTestNameInSingleLogTestPrint(LogBookMessage $log, $includeVersion = true){
        $ret = null;
        preg_match('/\=+\s*Initialize\s*(.*)\s*test\s*\(ver\.\s*(\d+\.?\d*)\)/', $log->getMessage(), $possibleName);
        if(count($possibleName) == 3){
            $dirty = $possibleName[0];
            $testName = $possibleName[1];
            $testVersion = $possibleName[2];
            if (strlen($dirty) > 0 && strlen($testName) > 0 && strlen($testVersion) > 0) {
                if($includeVersion){
                    $ret = $testName . " " . $testVersion;
                }
                else{
                    $ret = $testName;
                }
            }
        }

        return $ret;
    }

    /**
     * Used to parse Autotest print of test start, grup test name
     * @param LogBookMessage $log
     * @return null
     */
    protected function searchTestNameInSingleLogAutoTestPrint(LogBookMessage $log){
        $ret = null;
        preg_match('/START\s*([\w\.\/]*)\s*/', $log->getMessage(), $possibleName);
        if(count($possibleName) == 2){
            $dirty = $possibleName[0];
            $testName = $possibleName[1];
            if (strlen($dirty) > 0 && strlen($testName) > 0 ) {
                $ret = $testName;
            }
        }

        return $ret;
    }
    /**
     * Convert string time to object DateTime
     * @param string $input
     * @param int $SHORT_TIME_LEN The length of string without Day and month
     * @return \DateTime
     */
    protected function getLogTime(string $input) : \DateTime{
        $tmp_time = $this->clean_string($input);
        $len = strlen($tmp_time);
        $timeFormat = 'U.u';
        $ret = \DateTime::createFromFormat('U', time());

        if($len == $this->_MEDIUM_TIME_LEN){
            $timeFormat = 'm/d H:i:s';
        }
        else if($len == $this->_SHORT_TIME_LEN) {
            $timeFormat = 'H:i:s';
        }
        else if($len == $this->_SHORT_MILISEC_TIME_LEN){
            $timeFormat = 'H:i:s.u';
        }
        else if($len == $this->_MEDIUM_MILISEC_TIME_LEN){
            $timeFormat = 'm/d H:i:s.u';
        }
        else{
            $try_format = new DateTime($tmp_time);
            $tmp_time = $try_format->format('U.u');
        }

        try{
            $ret = \DateTime::createFromFormat($timeFormat, $tmp_time);
        }
        catch (\Exception $ex){
            print_r($ex);
            exit();
        }
        return $ret;
    }

    /**
     * Clean and replace some debug Level
     * @param string $debugLevel
     * @return string
     */
    protected function prepareDebugLevel(string $debugLevel) : string {
        //Get debug level message, convert to upper case
        $ret = strtoupper($this->clean_string($debugLevel));
        if($ret == 'WARNI'){
            $ret = "WARNING";
        }
        elseif($ret == 'CRITI'){
            $ret = "CRITICAL";
        }
        return $ret;
    }

    /**
     * Clean string from bash characters
     * @param $string
     * @return string
     */
    protected function clean_string(string $string) : string {
        $s = trim($string);
        //$s = iconv("UTF-8", "UTF-8//IGNORE", $s); // drop all non utf-8 characters
        // this is some bad utf-8 byte sequence that makes mysql complain - control and formatting i think
        $s = preg_replace('/(?>[\x00-\x1F]|\xC2[\x80-\x9F]|\xE2[\x80-\x8F]{2}|\xE2\x80[\xA4-\xA8]|\xE2\x81[\x9F-\xAF])/', ' ', $s);
        //$s = preg_replace('/\s+/', ' ', $s); // reduce all multiple whitespace to a single space
        return $s;
    }

//    /**
//     * Show upload file info.
//     *
//     * @Route("/{id}", name="upload_show")
//     * @Method("GET")
//     * @param LogBookUpload $obj
//     * @return \Symfony\Component\HttpFoundation\Response
//     */
    public function showAction(LogBookUpload $obj)
    {
        return $this->render('lbook/upload/show.html.twig', array(
            'upload' => $obj,
        ));
    }

    /**
     * @return string
     */
    private function generateUniqueFileName() : string
    {
        // md5() reduces the similarity of the file names generated by uniqid(), which is based on timestamps
        return md5(uniqid());
    }
}
