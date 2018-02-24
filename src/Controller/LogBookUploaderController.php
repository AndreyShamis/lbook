<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookMessage;
use App\Entity\LogBookTest;
use App\Entity\LogBookUpload;
use App\Entity\LogBookVerdict;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;


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
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em = $this->getDoctrine()->getManager();
        $this->testsRepo = $this->em->getRepository('App:LogBookTest');
        $this->cycleRepo = $this->em->getRepository('App:LogBookCycle');
        $this->verdictRepo = $this->em->getRepository('App:LogBookVerdict');
        $this->msgTypeRepo = $this->em->getRepository('App:LogBookMessageType');
        $this->logsRepo = $this->em->getRepository('App:LogBookMessage');
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
        $obj = new LogBookUpload();
        $form = $this->createForm('App\Form\LogBookUploadType', $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $file stores the uploaded PDF file
            /** @var UploadedFile $file */
            $file = $obj->getLogFile();

            $fileName = $this->generateUniqueFileName(). '_' . $file->getClientOriginalName(). '.'.$file->guessExtension();
            // moves the file to the directory where brochures are stored
//            $file->move(
//                $this->getParameter('brochures_directory'),
//                $fileName
//            );
            $obj->addMessage("New file name is :" . $fileName);
            $obj->addMessage("File ext :"  .$file->guessExtension());
            $new_file = $file->move("../uploads/", $fileName);
            $obj->new_file_info = $new_file;
            $obj->new_file_info_path = $new_file->getPath();
            $obj->new_file_info_getBasename = $new_file->getBasename();
            $obj->new_file_info_getFilename = $new_file->getFilename();
            $obj->new_file_info_getFileInfo = $new_file->getFileInfo();
            $obj->new_file_info_getRealPath = $new_file->getRealPath();
            $obj->new_file_info_getPathInfo = $new_file->getPathInfo();
            $obj->new_file_info_getPathname = $new_file->getPathname();
            $obj->new_file_info_getCTime = $new_file->getCTime();
            $obj->new_file_info_getSize = $new_file->getSize();
            $obj->addMessage("File copy info :"  . $new_file);
            $obj->setLogFile($fileName);

            $cycle = $this->cycleRepo->findOneBy(array("id" => 1));

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

            return $this->showAction($obj);
        }

        return $this->render('lbook/verdict/new.html.twig', array(
            'verdict' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * @param String $file
     * @param LogBookTest $test
     * @return array
     */
    protected function parseFile($file, LogBookTest $test)
    {
        $MIN_STR_LEN = 10;
        $SHORT_TIME_LEN = 8;

        $ret_data = array();

        $file_data = file_get_contents($file , FILE_USE_INCLUDE_PATH);
        $temp_arr = preg_split("/\\r\\n|\\r|\\n/", $file_data);

        $last_good_key = -1;
        $newTempArr = array();
        foreach ($temp_arr as $key => $value){

            if(strlen($value) < $MIN_STR_LEN){
                $value = null;
                continue;
            }
            preg_match_all('/(\d{2,}.*\d{1,1})\s*([A-Z]+)\s*\|\s*(.*)/', $value,$oneLine);
            if (count($oneLine[2]) > 0){
                $last_good_key = $key;
                $newTempArr[$key] = $this->clean_string($value);
            }
            else{
                $newTempArr[$last_good_key] = $newTempArr[$last_good_key] . "\n" . $this->clean_string($value);
            }
            unset($temp_arr[$key]);
        }
        unset($temp_arr);
        unset($file_data);
        unset($last_good_key);
        unset($oneLine);

        $counter=0;
        $objectsToClear = array();

        $testName = null;
        $testNameFound = false;
        $tmpTestNameFlag_TestPrint = false;
        $tmpTestNameFlag_AutotestTestPrint = false;
        $tmpTestNameFlag_ControlTestPrint = false;

        $testVerdict = null;
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

            if($value === null || strlen($value) < $MIN_STR_LEN){
                continue;
            }
            preg_match_all('/(\d{2,}.*\d{1,1})\s*([A-Z]+)\s*\|\s*(.*)/s', $value,$oneLine);

            if (count($oneLine[2]) > 0){
                $dLevel = null;

                /**
                 * Clean double DEBUG OUTPUT
                 */
                //Removing : base_job:0395 utils:0262 ssh_host:0116
                preg_match('/([\w|\_]*\:\d+\s*)\|\s*(.*)/s', $oneLine[3][0], $messageWithDebug);
                if(count($messageWithDebug) == 3){
                    $oneLine[3][0] = $messageWithDebug[2];
                }
                /**
                 *
                 */
                $msg_str = trim($oneLine[3][0]);
                $msgType_str = $oneLine[2][0];
                $logTime_str = $oneLine[1][0];

                /*
                 * Test verdict section
                 */
                //
                if($msgType_str === "INFO"){
                    preg_match('/END\s*([A-Za-z\_]*)/', $msg_str, $possibleVerdict);
                    if(count($possibleVerdict) == 2){
                        $testVerdict = $this->parseVerdict($possibleVerdict[1]);
                        if($testVerdict !== null){
                            if($testVerdict->getName() == 'ABORT' || $testVerdict->getName() == 'PASS' || $testVerdict->getName() == 'ERROR' || $testVerdict->getName() == 'FAIL' || $testVerdict->getName() == 'TEST_NA'){
                                $msgType_str = $testVerdict->getName();
                            }
//                            elseif($testVerdict->getName() == 'TEST_NA'){
//                                $msgType_str = "ERROR";
//                            }
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

                /**
                 *
                 */
                $ret_data[$counter] = array(
                    'logTime'   => $this->getLogTime($logTime_str, $SHORT_TIME_LEN),
                    'message'   => $msg_str,
                    'chain'     => $counter,
                    'test'      => $test,
                    'msgType'   => $this->msgTypeRepo->findOneOrCreate(array(
                        'name'      => $this->prepareDebugLevel($msgType_str)
                    )),
                );
                $log = $this->logsRepo->Create($ret_data[$counter], false);
                $objectsToClear[] = $log;

                /**
                 * Test Name section
                 */
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

                /*
                 * Test Time section
                 */
                $testStartTime = min($testStartTime, $log->getLogTime());
                $testEndTime = max($testEndTime, $log->getLogTime());
                /**
                 *
                 */
                $counter++;
            }
            else{
                echo count($oneLine) .  " $value:<pre>";
                print_r($oneLine);
                echo "</pre><br/>";
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
        preg_match('/START\s*([\w\/]*)\s*/', $log->getMessage(), $possibleName);
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
    protected function getLogTime(string $input, $SHORT_TIME_LEN = 8) : \DateTime{
        $tmp_time = $this->clean_string($input);
        if(strlen($tmp_time) > $SHORT_TIME_LEN){
            $timeFormat = 'm/d H:i:s';
        }
        else{
            $timeFormat = 'H:i:s';
        }
        return \DateTime::createFromFormat($timeFormat, $tmp_time);
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
