<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookMessage;
use App\Entity\LogBookTest;
use App\Entity\LogBookUpload;
use App\Entity\LogBookVerdict;
use App\Entity\LogBookSetup;
use App\Entity\SuiteExecution;
use App\Repository\LogBookBuildRepository;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookMessageRepository;
use App\Repository\LogBookMessageTypeRepository;
use App\Repository\LogBookSetupRepository;
use App\Repository\LogBookTargetRepository;
use App\Repository\LogBookTestRepository;
use App\Repository\LogBookUserRepository;
use App\Repository\LogBookVerdictRepository;
use App\Repository\SuiteExecutionRepository;
use ArrayIterator;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use App\Utils\RandomName;
use App\Form\LogBookUploadType;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Uploader controller.
 *
 * @Route("upload")
 */
class LogBookUploaderController extends AbstractController
{
    /** @var \Doctrine\Common\Persistence\ObjectManager  */
    protected $em;
    /** @var LogBookTestRepository $testsRepo */
    protected $testsRepo;
    /** @var LogBookCycleRepository $cycleRepo */
    protected $cycleRepo;
    /** @var LogBookVerdictRepository $verdictRepo */
    protected $verdictRepo;
    /** @var LogBookMessageTypeRepository $msgTypeRepo */
    protected $msgTypeRepo;
    /** @var LogBookMessageRepository $logsRepo */
    protected $logsRepo;
    /** @var LogBookSetupRepository $setupRepo */
    protected $setupRepo;
    /** @var LogBookBuildRepository $buildRepo */
    protected $buildRepo;
    /** @var LogBookTargetRepository $targetRepo */
    protected $targetRepo;
    /** @var LogBookUserRepository $targetRepo */
    protected $userRepo;
    /** @var SuiteExecutionRepository $suiteExecutionRepo */
    protected $suiteExecutionRepo;

    private $blackListLevels = array();

    protected $_MIN_LOG_STR_LEN = 10;
    protected $_MIN_CLEAN_LOG_STR_LEN = 1;
    protected $MAX_SINGLE_LOG_SIZE = 2600;
    protected $_SHORT_TIME_LEN = 8;             // 12:48:45
    protected $_SHORT_MILISEC_TIME_LEN = 12;    // 02:44:38.820
    protected $_MEDIUM_TIME_LEN = 14;           // 02/22 11:36:56
    protected $_MEDIUM_MILISEC_TIME_LEN = 18;   // 02/19 02:44:39.177
    protected $RANDOM_FILE_NAME_LEN = 4;
    protected $log_first_lines = array();
    protected $RECOVER_FIRST_LINES = false;
    public static $MAX_EXEC_ORDER_SEARCH_COUNTER = 50;
    public static $UPLOAD_PATH = __DIR__ . '/../../uploads/';

    /**
     * LogBookUploaderController constructor.
     * @param Container $container
     * @throws \LogicException
     */
    public function __construct(Container $container)
    {
        self::$UPLOAD_PATH = self::getUploadPath();
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
        $this->userRepo = $this->em->getRepository('App:LogBookUser');
        $this->suiteExecutionRepo = $this->em->getRepository('App:SuiteExecution');
    }

    /**
     * @return string
     */
    public static function getUploadPath(): string
    {
        return realpath(self::$UPLOAD_PATH);
    }

    /**
     * @Route("/", name="upload_index", methods={"GET"})
     */
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('lbook/upload/index.html.twig', array());
    }

    /**
     * @param LogBookCycle $cycle
     * @return int
     */
    protected function getTestNewExecutionOrder(LogBookCycle $cycle): int
    {
        // Find proper execution order | The correct Way
        $latestTestInCycle = $this->testsRepo->findOneBy(
            array('cycle' => $cycle->getId()),
            array('executionOrder' => 'DESC'));
        if ($latestTestInCycle !== null) {
            $executionOrder = $latestTestInCycle->getExecutionOrder() + 1;
        } else {
            $executionOrder = 0;
        }
        return $executionOrder;
    }

    /**
     * @return string
     */
    protected function generateCycleName(): string
    {
        return RandomName::asClassName(RandomName::getRandomTerm());
    }

    /**
     * @return string
     */
    protected function generateSetupName(): string
    {
        $setup_name = '';
        $setupNameFound = false;
        $counter = 0;
        $post_fix = '';
        while (!$setupNameFound) {
            $setup_name = RandomName::asClassName(RandomName::getRandomTerm()) . $post_fix;
            $setup = $this->setupRepo->findByName($setup_name);
            if ($setup === null) {
                break;
            }
            $counter++;
            if ($counter%100 === 0) {
                try {
                    $post_fix = random_int(1, 9999);
                } catch (\Exception $e) {}
            }
            if ($counter%1000 === 0) {
                try {
                    $post_fix = $counter . random_int(1, 9999);
                } catch (\Exception $e) {}
            }
        }
        return $setup_name;
    }

    /**
     * @param LogBookUpload $obj
     * @param string $setupName
     * @param int $setupId
     * @return LogBookSetup
     * @throws ORMException
     */
    final protected function bringSetup(LogBookUpload $obj, string $setupName = '', int $setupId = -1): LogBookSetup
    {
        /** @var LogBookSetup $setup */
        $setup = null;
        if ($setupId > 0) {
            $obj->addMessage('Searching setup by ID :' . $setupId);
            $setup = $this->setupRepo->findById($setupId);
            if ($setup !== null) {
                $obj->addMessage(sprintf('Found setup [ID:%d], [NAME:%s].', $setup->getId(), $setup->getName()));
            }
        }

        if ($setup === null && $setupName !== '') {
            $obj->addMessage('Searching setup by NAME :' . $setupName);
            $setup = $this->setupRepo->findByName($setupName);
            if ($setup !== null) {
                $obj->addMessage(sprintf('Found setup [ID:%d], [NAME:%s] ', $setup->getId(), $setup->getName()));
            }
        }

        if ($setup === null) {
            if (\strlen($setupName) < LogBookSetup::$MIN_NAME_LEN) {
                $setupName = $this->generateSetupName();
                $obj->addMessage('Generating new setup NAME :' . $setupName);
            }
            $obj->addMessage('Creating setup  :' . $setupName);
            $setup = $this->setupRepo->findOneOrCreate(
                array(
                    'name' => $setupName,
                ));
        }
        return $setup;
    }

    /**
     * @Route("/suite_execution", name="add_new_suite_execution", methods={"GET|POST"})
     * @param Request $request
     * @param LoggerInterface $logger
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createSuiteExecution(Request $request, LoggerInterface $logger): \Symfony\Component\HttpFoundation\Response
    {
        $created = false;
        $suiteExecution = null;
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            $data = array();
        }
        if (!array_key_exists('components', $data)) {
            $data['components'] = array();
        }
        if (!array_key_exists('summary', $data)) {
            $data['summary'] = '';
        }
        if (!array_key_exists('name', $data)) {
            $data['name'] = '';
        }
        if (!array_key_exists('test_environments', $data)) {
            $data['test_environments'] = array();
        }
        $data['components'] = array_filter($data['components']);
        $data['test_environments'] = array_filter($data['test_environments']);
        try {
            $suiteExecution = $this->suiteExecutionRepo->findOneOrCreate($data);
            $created = true;
        } catch (\Throwable $e) {
            $method = $request->getMethod();
            $ip = $request->getClientIp();
            $data['ip'] = $ip;
            $data['method'] = $method;
            $data['request'] = $request->request->all();
            $data['query'] = $request->query->all();
            $data['trace'] = $e->getTraceAsString();
            $logger->critical($method . '::' . $ip . '::ERROR :' . $e->getMessage(), $data);
            return new \Symfony\Component\HttpFoundation\Response($e->getMessage());
        }

        return $this->render('lbook/suite/add_execution.html.twig', array(
            'suiteExecution' => $suiteExecution,
            'created' => $created
        ));
    }

    /**
     * Creates a new Upload entity.
     *
     * @Route("/new_cli", name="upload_new_cli", methods={"GET|POST"})
     * @param Request $request
     * @param LoggerInterface $logger
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws Exception
     */
    public function newCli(Request $request, LoggerInterface $logger)
    {
        //curl --noproxy "127.0.0.1" --max-time 120 --form setup=DELL-KUBUNTU --form 'UPTIME_START=720028.73 2685347.68' --form 'UPTIME_END=720028.73 2685347.68' --form NIC=TEST --form DUTIP=172.17.0.1 --form PlatformName=Platf --form k_ver= --form Kernel=4.4.0-112-generic --form testCaseName=sa --form testSetName=sa --form build=A:_S:_I: --form testCount=2  --form file=@autoserv.DEBUG --form setup='SUPER SETUP' --form cycle='1' --form token=2602161043  http://127.0.0.1:8080/upload/new_cli
        //curl --noproxy "127.0.0.1" --max-time 120 --form file=@autoserv.DEBUG --form token=$(date '+%d%m%H%M%S')$(((RANDOM % 99999)+1)) --form setup=TestSetupRandomToken  http://127.0.0.1:8080/upload/new_cli
        $obj = new LogBookUpload();
        /** @var SuiteExecution $suite_execution */
        $suite_execution = null;
        $p_data = $request->request;
        /** @var LogBookTest $test */
        $test = null;
        /** @var LogBookCycle $cycle */
        $cycle = null;
        /** @var LogBookSetup $setup */
        $setup = null;
        $return_urls_only = $request->request->get('return_urls_only', 'false');
        $ruo_low = mb_strtolower($return_urls_only);
        if ($return_urls_only === '1' || $ruo_low === 'true' || $ruo_low === 'yes') {
            $return_urls_only = 'true';
        }
        if ($p_data->count() >= 1) {
            /** @var UploadedFile $file */
            $file = $request->files->get('file');
            if ($request->request->get('debug', false) === 'true') {
                $obj->setDebug(true);
            }
            $cycle_name = $request->request->get('cycle', '');
            $setup_name = $request->request->get('setup', '');
            $cycle_token = $request->request->get('token', '');
            $build_name = $request->request->get('build', '');
            $test_dut = $request->request->get('dut', '');
            $test_metadata = $request->request->get('test_metadata', '');
            $cycle_metadata = $request->request->get('cycle_metadata', '');
            $suite_execution_id = $request->request->get('suite_execution_id', 0);
            if ($cycle_token !== '') {
                $obj->addMessage('INFO: -1- Token provided [' . $cycle_token . ']');
            }
            if (mb_strlen($setup_name) > LogBookSetup::$MIN_NAME_LEN) {
                $setup = $this->bringSetup($obj, $setup_name);
            }
            $cycle = $this->cycleRepo->findByToken($cycle_token, $setup);
            if ($cycle === null) {
                $obj->addMessage('INFO: -1- Cycle not found by token. Parsing Setup.');
                /* create new cycle -> Need Parse Setup  */
                $setup = $this->bringSetup($obj, $setup_name);
                if ($cycle_name === '') {
                    $obj->addMessage('INFO: -1- Cycle name not provided. Generating it for you.');
                    $cycle_name = $this->generateCycleName();
                    $obj->addMessage('INFO: -1- Generated cycle name [' . $cycle_name . '].');
                } else {
                    $obj->addMessage('INFO: -1- Cycle name provided [' . $cycle_name . '].');
                }
                $obj->addMessage('INFO: -1- Creating cycle.');
                $cycle = $this->cycleRepo->findOneOrCreate(array(
                    'name' => $cycle_name,
                    'setup' => $setup,
                    'uploadToken' => $cycle_token,
                ), false);
                $obj->addMessage('INFO: -1- Cycle created ID:' . $cycle->getId() . '.');
                if ($cycle === null) {
                    $obj->addMessage('CRITICAL -1- Failed to generate cycle');
                    // exit();
                }
                $obj->addMessage('PASS: -1- The LogBOOK system performed all thinks for you. Continue to log parsing.');
            }

            $continue = true;
            if ($cycle !== null) {
                $obj->addMessage('INFO: Cycle found, take SETUP from cycle');
                $setup = $cycle->getSetup();
                /** Update cycle name if changed */
                if ($cycle_name !== '' && $cycle_name !== $cycle->getName()) {
                    $obj->addMessage('WARNING: cycle name changed, updating to new one ['. $cycle_name .']');
                    $cycle->setName($cycle_name);
                }
            } else if ($cycle === null) {
                $obj->addMessage('Cycle not created/found.');
                $continue = false;
            }
//            else {
//                $obj->addMessage('ERROR: Cycle not found, take setup from POST');
//                $setup = $this->bringSetup($obj, $setup_name);
//
//                if ($setup !== null) {
//                    $obj->addMessage('ERROR: TODO Require to create new cycle ');
////                    /** TODO Require to create new cycle */
////                    $cycle = $this->cycleRepo->findOneOrCreate(array(
////                        'name' => $cycle_name,
////                        'setup' => $setup,
////                        'uploadToken' => $cycle_token));
//                } else {
//                    $obj->addMessage('ERROR: TODO - cycle not found, setup not found, Create Setup and then Cycle');
//                    /** TODO - cycle not found, setup not found, Create Setup and then Cycle */
//                }
//            }
            if ($continue) {
                $this->cycleMetaDataHandler($cycle_metadata, $cycle, $obj);
                $remote_ip = $request->getClientIp();
                $new_file = $this->fileHandler($file, $setup, $cycle, $obj, $logger, $remote_ip);

                $testName = $file->getClientOriginalName();
                $testVerdictDefault = $this->parseVerdict('ERROR');

                // the argument is the path of the directory where the locks are created
                $store = new FlockStore(sys_get_temp_dir());
                $factory = new Factory($store);
                $lockName = 'cycle_' . $cycle->getId(). '_order';
                $lock = $factory->createLock($lockName, 15);
                $lock->setLogger($logger);
                if ($lock->acquire(true)) {
                    try {
                        $test_criteria = $this->createTestCriteria($testName, $cycle, $new_file, $testVerdictDefault);
                        $test = $this->insertTest($test_criteria, $cycle, $obj, $logger);
                    } catch (Exception $ex) {
                        $logger->alert('[lock] Found Exception:' . $ex->getMessage(), $ex->getTrace());
                    } finally {
                        $lock->release();
                    }
                }
                if ($suite_execution_id > 0) {
                    $suite_execution = $this->suiteExecutionRepo->find($suite_execution_id);
                    if ($suite_execution !== null) {
                        $test->setSuiteExecution($suite_execution);
                        $cycle->addSuiteExecution($suite_execution);
                    }
                }
                $this->em->flush();
                $this->parseFile($new_file, $test, $obj, $logger);
                $this->em->refresh($cycle);
                $this->calculateAndSetBuild($build_name, $cycle);

                $uploader = $this->targetRepo->findOneOrCreate(array('name' => $remote_ip));
                $dut = $this->targetRepo->findOneOrCreate(array('name' => $test_dut));

                $this->testMetaDataHandler($test_metadata, $test, $obj);

                $cycle->setTargetUploader($uploader);
                $cycle->setController($uploader);
                $cycle->setDut($dut);
                // Set token exparation after each test +1 hour
                $cycle->setTokenExpiration(new \DateTime('+3 hours'));
                if ($suite_execution !== null && $suite_execution->getPublish()) {
                    $daysToAdd = $setup->getRetentionPolicy();
                    if ($suite_execution->getTestingLevel() === 'weekly') {
                        $cycle->setRetentionPolicy(max($daysToAdd, 370));
                        $cycle->setTokenExpiration(new \DateTime('+30 hours'));
                    } else if ($suite_execution->getTestingLevel() === 'nightly') {
                        $cycle->setRetentionPolicy(max($daysToAdd, 200));
                        $cycle->setTokenExpiration(new \DateTime('+10 hours'));
                    } else {
                        if (strpos($suite_execution->getJobName(), 'build_and_promote') !== false) {
                            $cycle->setRetentionPolicy(max($daysToAdd, 7));
                        } else {
                            $cycle->setRetentionPolicy(max($daysToAdd, 93));
                        }
                    }

                } else {
                    $cycle->setDefaultRetentionPolicy();
                }
                $setup->setUpdatedAt();
                $this->em->flush();
                $obj->addMessage('TestID is ' . $test->getId() . '.');
            }
        }
        $test_link = $this->generateUrl('test_show_first',
            ['id' => $test->getId()], UrlGeneratorInterface::ABSOLUTE_URL
        );
        $cycle_link = $this->generateUrl('cycle_show_first',
            ['id' => $cycle->getId()], UrlGeneratorInterface::ABSOLUTE_URL
        );
        return $this->render('lbook/upload/curl.html.twig', array(
            'cycle' => $cycle,
            'setup' => $setup,
            'upload' => $obj,
            'test' => $test,
            'test_link' => $test_link,
            'cycle_link' => $cycle_link,
            'return_urls_only' => $return_urls_only,
        ));
    }

    /**
     * @param UploadedFile $file
     * @param LogBookSetup $setup
     * @param LogBookCycle $cycle
     * @param LogBookUpload $obj
     * @param LoggerInterface $logger
     * @param string $remote_ip
     * @return File
     */
    protected function fileHandler(UploadedFile $file, LogBookSetup $setup, LogBookCycle $cycle, LogBookUpload $obj, LoggerInterface $logger, string $remote_ip): File
    {
        /** @var UploadedFile $new_file */
        $new_file = null;
        try {
            $orig_name = $file->getClientOriginalName();
            if ($orig_name === 'autoserv.DEBUG') {
                $fileName = $this->generateUniqueFileName($this->RANDOM_FILE_NAME_LEN);
            } else {
                $fileName = $this->generateUniqueFileName($this->RANDOM_FILE_NAME_LEN *2). '_' . $orig_name;
            }
            $fileName .= '_' . $cycle->getTestsCount() . '.txt';

            $obj->addMessage('File name is :' . $fileName . '. File ext :'  .$file->guessExtension());
            try {
                $dir = self::$UPLOAD_PATH . '/' . $setup->getId() . '/' . $cycle->getId();
                $new_file = $file->move($dir, $fileName);
                $logger->notice('F_HANDLER:[' . $new_file->getFilename() . ':' . $new_file->getSize() . ']',
                    array(
                        'sid' => $setup->getId(),
                        'sname' => $setup->getName(),
                        'cid' => $cycle->getId(),
                        'cname' => $cycle->getName(),
                        'ip' => $remote_ip
                    ));
            } catch (\Throwable $ex) {
                $msg = 'Fail in fileHandler[move]:' . $ex->getMessage();
                $obj->addMessage($msg);
                $logger->critical('[' . $fileName . ']::ERROR :' . $msg);
            }
            $fileSize = $new_file->getSize();
            if ($fileSize > 20*1024*1024) {
                $msg = array();
                try {
                    $msg = array(
                        'sname' => $setup->getName(),
                        'sid' => $setup->getId(),
                        'cid' => $cycle->getId(),
                        'cname' => $cycle->getName(),
                        'tests_count' => $cycle->getTestsCount(),
                        'build' => $cycle->getBuild()->getName(),
                        'remote_ip' => $remote_ip,
                    );
                } catch (\Throwable $ex) {
                    $logger->critical($ex->getMessage(), $ex);
                }
                $logger->critical('BIG_SIZE: File name is :' . $new_file->getFilename() . '. File size : ' . $fileSize, $msg);
            }
            $obj->addMessage('File copy info :' . $new_file . ' File size is :' . $fileSize);

            if ($fileSize > 0.2*1024*1024) {
                $this->addBlackListLevel('DEBUG');
            }
            if ($fileSize > 0.3*1024*1024) {
                $this->addBlackListLevel('INFO');
            }
            $obj->setLogFile($fileName);
        } catch (\Throwable $ex) {
            $msg = '[fileHandler]:FAIL:' . $ex->getMessage();
            $logger->critical($msg, $ex->getTrace());
            $obj->addMessage($msg);
        }
        return $new_file;
    }

    /**
     * @param string $cycle_metadata
     * @param LogBookCycle $cycle
     * @param LogBookUpload $obj
     */
    protected function cycleMetaDataHandler(string $cycle_metadata, LogBookCycle $cycle, LogBookUpload $obj): void
    {
        try {
            /** Extract CYCLE metadata from request if exist */
            if ($cycle_metadata !== '' && $this->isVariableString($cycle_metadata)) {
                $arr = $this->extractTestVariables($cycle_metadata);
                if (array_key_exists('USER', $arr) && array_key_exists('EMAIL', $arr)) {
                    $user = $this->userRepo->createByEmail($arr['EMAIL'], $arr['USER']);
                    if ($user !== null) {
                        $cycle->setUser($user);
                        $obj->addMessage('User from MT cycle added :' . $user);
                    }
                }
                $cycle->setMetaData($arr);
            }
        } catch (\Throwable $ex) {
            $obj->addMessage('Failed in cycle_metadata :' . $cycle_metadata . ' ' . $ex->getMessage());
        }
    }

    /**
     * @param string $test_metadata
     * @param LogBookTest $test
     * @param LogBookUpload $obj
     */
    protected function testMetaDataHandler(string $test_metadata, LogBookTest $test, LogBookUpload $obj): void
    {
        try {
            /** Extract TEST metadata from request if exist */
            if ($test_metadata !== '' && $this->isVariableString($test_metadata)) {
                $arr = $this->extractTestVariables($test_metadata);
                $test->setMetaData($arr);
            }
        } catch (\Throwable $ex) {
            $obj->addMessage('Failed in test_metadata :' . $test_metadata . ' ' . $ex->getMessage());
        }
    }

    /**
     * @param string $levelName
     */
    private function addBlackListLevel(string $levelName): void
    {
        $preparedLevelName = $this->prepareDebugLevel($levelName);
        if (!isset($this->blackListLevels[$preparedLevelName])) {
            $level = $this->msgTypeRepo->findOneOrCreate(array(
                'name' => $preparedLevelName
            ));
            $this->blackListLevels[$preparedLevelName] = $level;
        }
    }

    /**
     * @param string $name
     * @param LogBookCycle $cycle
     * @param File $file
     * @param LogBookVerdict $verdict
     * @return array
     */
    protected function createTestCriteria(string $name, LogBookCycle $cycle, File $file, LogBookVerdict $verdict): array
    {
        return array(
            'id' => 1,
            'name' => $name,
            'cycle' => $cycle,
            'logFile' => $file->getFilename(),
            'logFileSize' => $file->getSize(),
            'verdict' => $verdict,
            'executionOrder' => $this->getTestNewExecutionOrder($cycle),
        );
    }

    /**
     * Trying to insert test into DB in order to calculated execution order.
     * If there is UniqueConstraintViolationException new execution order will be generated
     * @param array $test_criteria
     * @param LogBookCycle $cycle
     * @param LogBookUpload $obj
     * @param LoggerInterface $logger
     * @return LogBookTest
     * @throws Exception
     */
    protected function insertTest(array $test_criteria, LogBookCycle $cycle, LogBookUpload $obj, LoggerInterface $logger): LogBookTest
    {
        $orderFound = false;
        $counter = 0;
        $test = null;
        while($orderFound !== true && $counter< self::$MAX_EXEC_ORDER_SEARCH_COUNTER) {
            try {
                if ($counter > 0) {
                    $logger->alert('[insertTest] Found counter increment',
                        array(
                        'counter' => $counter,
                        'orderFound' => $orderFound,
                        'test_criteria' => $test_criteria,
                        'cycle' => $cycle,
                    ));
                }
                $test = $this->testsRepo->create($test_criteria);
                if ($test !== null) {
                    $orderFound = true;
                } else {
                    try {
                        /** sleep for 0.2-0.5 second */
                        usleep(\random_int(200000, 500000));
                    } catch (Exception $e) {}
                }
            } catch (UniqueConstraintViolationException $ex) {
                $logger->alert('[insertTest] Found UniqueConstraintViolationException', array('ex' => $ex));
                $obj->addMessage($ex->getMessage(). ' Counter=' . $counter);
                try {
                    /** sleep for 0.2-0.5 second */
                    usleep(\random_int(200000, 500000));
                } catch (Exception $e) {}
                $test_criteria['executionOrder'] = $this->getTestNewExecutionOrder($cycle);
            } catch (ORMException $ex) {
                $logger->alert('[insertTest] Found ORMException', array('ex' => $ex));
                $obj->addMessage($ex->getMessage(). ' Counter=' . $counter);
                try {
                    /** sleep for 0.2-0.5 second */
                    usleep(\random_int(200000, 500000));
                } catch (Exception $e) {}
                $test_criteria['executionOrder'] = $this->getTestNewExecutionOrder($cycle);
                $this->testsRepo  = $this->em->getRepository('App:LogBookTest');
            }

            $counter++;
        }

        if ($test === null) {
            $msg = '[THROW][Exception][insertTest] counter=' . $counter . ' orderFound='. (int)$orderFound;
            $logger->critical($msg, array(
                'counter' => $counter,
                'orderFound' => $orderFound,
                'test_criteria' => $test_criteria,
                'cycle' => $cycle,
            ));
            throw new \RuntimeException($msg);
        }
        return $test;
    }
    /**
     * @param string $build_name
     * @param LogBookCycle $cycle
     */
    private function calculateAndSetBuild($build_name, LogBookCycle $cycle): void
    {
        if ($build_name === '' && ($cycle->getBuild() === null || $cycle->getBuild()->getName() === '')) {
            $build_name = 'Unknown';
        }
        if ($build_name !== '') {
            $cycle->setBuild($this->buildRepo->findOneOrCreate(array('name' => $build_name)));
        }
    }

    /**
     * @param array $temp_arr
     * @param LoggerInterface $logger
     * @return array
     */
    final protected function prepareLogArray(array &$temp_arr, LoggerInterface $logger): array
    {
        $newTempArr = array();
        $last_good_key = -1;
        $firstLines = true;
        foreach ($temp_arr as $key => $value) {
            $msg_len = mb_strlen($value);
            if ($msg_len < $this->_MIN_LOG_STR_LEN
                || $msg_len > $this->MAX_SINGLE_LOG_SIZE
                || strpos($value, '##############################') !== false
                || strpos($value, '*************************') !== false
                || strpos($value, ' LOGBOOK ')  !== false
                || strpos($value, 'EQACC:')  !== false
                || strpos($value, 'Post login')  !== false
                || strpos($value, '==> Pre Test')  !== false
                || (strpos($value, '==========') !== false) && strpos($value, '==================== ') === false) {
                $value = null;
                unset($temp_arr[$key]);
                continue;
            }

            preg_match_all('/(\d{2,}.*\d{1,1})\s*([A-Z]+)\s*\|\s*(.*)/', $value,$oneLine);
            if (\count($oneLine[2]) > 0) {
                $last_good_key = $key;
                $newTempArr[$key] = $this->cleanString($value);
            } else if ($last_good_key > 0) {
                $new_value = $newTempArr[$last_good_key] . "\n" . $this->cleanString($value);
                $tmp_size = mb_strlen($new_value);
                if ($tmp_size >= $this->MAX_SINGLE_LOG_SIZE) {
                    $last_good_key = 0;
                    $firstLines = false;
                    $logger->warning('Reset $last_good_key due big size: ' . $tmp_size);
                } else {
                    $newTempArr[$last_good_key] = $new_value;
                }
            } else if ($firstLines && $this->RECOVER_FIRST_LINES) {
                // add first lines without time to array
                $this->log_first_lines[] = $this->cleanString($value);
                // or Skip first lines
            }
            unset($temp_arr[$key]);
        }

        return $newTempArr;
    }

    /**
     * @param $newTempArr
     * @return int
     */
    protected function recoverFirstLines(&$newTempArr): int
    {

        if (\count($newTempArr) && \count($this->log_first_lines)) {
            // TODO Put first lines into start of $newTempArr
            $reverted = new ArrayIterator(array_reverse($this->log_first_lines));
            foreach ($reverted as $tmp) {
                array_unshift($newTempArr, $tmp);
            }
        }
        return \count($this->log_first_lines);
    }

    /**
     * @param String $file
     * @param LogBookTest $test
     * @param LogBookUpload $uploadObj
     * @param LoggerInterface $logger
     * @return array
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function parseFile($file, LogBookTest $test, LogBookUpload $uploadObj, LoggerInterface $logger): array
    {
        $ret_data = array();
        $file_data = file_get_contents($file , FILE_USE_INCLUDE_PATH);
        $tmp_log_arr = preg_split('/\\r\\n|\\r|\\n/', $file_data);
        $newTempArr = $this->prepareLogArray($tmp_log_arr, $logger);

        unset($file_data);

        $counter=0;
        $objectsToClear = array();

        $testName = null;
        $testNameFound = false;
        $tmpTestNameFlag_TestPrint = false;
        $tmpTestNameFlag_AutotestTestPrint = false;
        $tmpTestNameFlag_ControlTestPrint = false;

        $testVerdict = null;

        $controlFile = $controlFullFile = $controlVersion = $testVersion = '';

//        if (count($this->log_first_lines)) {
//            /**
//             * TODO : Need First time in test
//             */
//            $counter = $this->recoverFirstLines($newTempArr);
//        }

        /* Test Time section*/
        $testStartTime = $testEndTime = null;
        try {
            $testStartTime = new \DateTime('+100 years');
            $testEndTime = new \DateTime('-100 years');
        } catch (Exception $e) {
        }

        $skip_counter = 0;  // used for Not to skip first logs
        /**
         * If in previous FOR used "&" and use same array
         * Dont remove & from  &$value -> will cause to additional duplicated line
         */
        foreach ($newTempArr as $key => $value) {
            preg_match_all('/(\d{2,2}[.|:| |\/|\d]*\d{2,3})\s*([A-Z]+)\s*\|\s*(.*)/s', $value,$oneLine);
            //preg_match_all('/(\d{2,}.*\d{2,3})\s*([A-Z]+)\s*\|\s*(.*)/s', $value,$oneLine);
            //preg_match_all('/(?'TIME'^\d{2,2}.[^\|]*\d{2,3})\s+(?'LEVEL'[A-Z]+)\s*\|\s*(?'MSG'.*)/s', $value,$oneLine); https://regex101.com/r/9T3jd8/2

            if (\count($oneLine[2]) > 0) {
                /** Clean double DEBUG OUTPUT **/
                //Removing : base_job:0395 utils:0262 ssh_host:0116
                preg_match('/([\w|\_]*\:\d+\s*)\|\s*(.*)/s', $oneLine[3][0], $messageWithDebug);
                if (\count($messageWithDebug) === 3) {
                    $oneLine[3][0] = $messageWithDebug[2];
                }
                /** **/
                $msg_str = trim($oneLine[3][0]);
                $msg_len = mb_strlen($msg_str);
                if ($msg_len < $this->_MIN_CLEAN_LOG_STR_LEN || $msg_len > $this->MAX_SINGLE_LOG_SIZE) {
                    continue;
                }
                $msgType_str = $oneLine[2][0];
                $logTime_str = $oneLine[1][0];

                /** Test verdict section **/
                if ($msgType_str === 'INFO') {
                    preg_match('/END\s*([A-Za-z\_]*)/', $msg_str, $possibleVerdict);
                    if (\count($possibleVerdict) === 2) {
                        $testVerdict = $this->parseVerdict($possibleVerdict[1]);
                        if ($testVerdict !== null) {
                            $verName = $testVerdict->getName();
                            if ($verName === 'ABORT' || $verName === 'PASS' || $verName === 'ERROR' || $verName === 'FAIL' || $verName === 'TEST_NA') {
                                $msgType_str = $testVerdict->getName();
                            }
                        }
                    } else {
                        /** Will replace log line verdict if found something from next regex */
                        preg_match('/\s*(FAIL|GOOD|ERROR|TEST_NA|ABORT|WARN)\s*.*(.*timestamp\=.*localtime\=)/', $msg_str, $possibleMessageType);
                        if (\count($possibleMessageType) === 3) {
                            if ($possibleMessageType[1] === 'GOOD') {
                                $possibleMessageType[1] = 'PASS';
                            } elseif ($possibleMessageType[1] === 'WARN') {
                                $possibleMessageType[1] = 'WARNING';
                            } elseif  ($possibleMessageType[1] === 'NOTIC') {
                                $possibleMessageType[1] = 'NOTICE';
                            }
                            $msgType_str = $possibleMessageType[1];
                        }
                    }
                }

                /** **/
                try {
                    /** @var string $preparedLevelName */
                    $preparedLevelName = $this->prepareDebugLevel($msgType_str);
                    if (isset($this->blackListLevels[$preparedLevelName])) {
                        // In case this log LEVEL ignored for DB insert
                        if ($skip_counter > 15) {
                            continue;
                        }
                        $skip_counter++;
                    }

                    $ret_data[$counter] = array(
                        'logTime' => $this->getLogTime($logTime_str),
                        'message' => $msg_str,
                        'chain' => $counter,
                        'test' => $test,
                        'msgType' => $this->msgTypeRepo->findOneOrCreate(array(
                            'name' => $preparedLevelName
                        )),
                    );
                } catch (\Exception $ex) {
                    $logger->alert('[parseFile] Fail in create log_criteria', array('ex' => $ex));
                }

                /** @var LogBookMessage $log */
                $log = $this->logsRepo->create($ret_data[$counter], false);
                $objectsToClear[] = $log;

                /** Test Name section */
                if (!$testNameFound && $log->getMsgType()->getName() === 'INFO') {
                    $tmpName = null;

                    if (!$tmpTestNameFlag_AutotestTestPrint && !$tmpTestNameFlag_ControlTestPrint) {
                        $tmpName = $this->searchTestNameInSingleLogAutoTestPrint($log);
                        if ($tmpName !== null) {
                            $controlFullFile = $tmpName;
                            $tmpTestNameFlag_AutotestTestPrint = true;
                        }
                    } else if (!$tmpTestNameFlag_TestPrint && !$tmpTestNameFlag_ControlTestPrint) {
                        $tmpName = $this->searchTestNameInSingleLogTestPrint($log, true);
                        if ($tmpName !== null) {
                            $tmpTestNameFlag_TestPrint = true;
                            if ($tmpName[1] !== null) {
                                $controlVersion = $tmpName[1];
                            }
                            $tmpName = $tmpName[0]; // set $tmpName to be first element in array(control file name)
                            $controlFile = $tmpName;
                        }
                    } else if (!$tmpTestNameFlag_ControlTestPrint) {
                        $tmpName = $this->searchTestNameInSingleLogControlPrint($log, true);
                        if ($tmpName !== null) {
                            $tmpTestNameFlag_ControlTestPrint = true;
                            $testNameFound = true;
                            if ($tmpName[1] !== null) {
                                $testVersion = $tmpName[1];
                            }
                            $tmpName = $tmpName[0]; // set $tmpName to be first element in array(test name)
                        }
                    }

                    if ($tmpName !== null) {
                        $testName = $tmpName;
                    }
                }

                /** SYSTEM section */
                if ($log->getMsgType()->getName() === 'SYSTEM') {
                    /** Parse KEY::STR_VALUE */
                    if ($this->isVariableString($log->getMessage())) {
                        //$uploadObj->addMessage('START WITH UPPER FOUND '. $log->getMessage());
                        $arr = $this->extractTestVariables($log->getMessage());
                        //$uploadObj->addMessage('HERE IS THE VALUES '. print_r($arr, true));
                        $test->setMetaData($arr);
                    }
//
//                    /** Parse KEY::JSON_VALUE */
//                    if ($this->isJson($log->getMessage())) {
//                        $uploadObj->addMessage('JSON FOUND '. $log->getMessage());
//                    }
                }
                /** Test Time section **/
                $testStartTime = min($testStartTime, $log->getLogTime());
                $testEndTime = max($testEndTime, $log->getLogTime());
                /** **/
                $counter++;
            }
//            } else {
//                if ($counter > 0) {
//                    echo \count($oneLine) . ' '.   $value . ':<pre>';
//                    print_r($oneLine);
//                    echo '</pre><br/>';
//                }
//            }
        }
        $mt_data = array(
            'TEST_FILENAME' => $controlFile,
            'TEST_VERSION_SHOW_OPT' => $testVersion,
            'CONTROL_VERSION_SHOW_OPT' => $controlVersion,
        );
        if ($controlFile === '') {
            unset($mt_data['TEST_FILENAME']);
        }
        if ($testVersion === '') {
            unset($mt_data['TEST_VERSION_SHOW_OPT']);
        }
        if ($controlVersion === '') {
            unset($mt_data['CONTROL_VERSION_SHOW_OPT']);
        }
        if (count($mt_data)) {
            $test->addMetaData($mt_data);
        }

        //$test->setTestFileName($controlFile);
        //$test->setTestFileVersion($controlVersion);
        //$test->setTestVersion($testVersion);

        /**
         * Test Verdict section
         */
        if ($testVerdict !== null) {
            $test->setVerdict($testVerdict);
        } else {
            $test->setVerdict($this->parseVerdict('ERROR'));
        }
        $test->setTimeStart($testStartTime);
        $test->setTimeEnd($testEndTime);
        if ($testName !== null && $testName !== '') {
            $test->setName($testName);
        }
        $this->em->flush();
        foreach ($objectsToClear as $tmp_obj) {
            // In order to free used memory; Decrease running time of 400 cycles, from ~15-20 to 2 minutes
            $this->em->detach($tmp_obj);
        }

        return $ret_data;
    }

    /**
     * @param $string
     * @return array
     */
    private function extractTestVariables($string): array
    {
        $ret = array();
        $tmp_arr = explode(';;', $string);
        foreach ($tmp_arr as $single) {
            $tmp_pair = explode('::', $single);
            $key = trim($tmp_pair[0]);
            $key_len = mb_strlen($key);
            if ($key_len >= 2 && $key_len <= 100 && $this->startsWithUpper($key) ) {
                $ret[$key] = trim($tmp_pair[1]);
            }
        }

        return $ret;
    }

    /**
     * @param $string
     * @return bool
     */
    private function isMultipleVariable($string): bool
    {
        if (substr_count($string, ';;') > 0) {
            $tmp_arr = explode(';;', $string);
            return $this->isVariableString($tmp_arr[0]) && $this->isVariableString($tmp_arr[1]);
        }
        return false;
    }

    /**
     * @param $string
     * @return bool
     */
    private function isVariableString($string): bool
    {
        return $this->startsWithUpper($string) && strpos($string, '::') !== false;
    }

    /**
     * @param $string
     * @return bool
     */
    private function isJson($string): bool
    {
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * @param $str
     * @return bool
     */
    private function startsWithUpper($str): bool
    {
        $chr = mb_substr ($str, 0, 1, 'UTF-8');
        return mb_strtolower($chr, 'UTF-8') !== $chr;
    }

    /**
     * Parse from string Test Verdict
     * @param string $input
     * @return LogBookVerdict
     */
    protected function parseVerdict(string $input): LogBookVerdict
    {
        $input = strtolower(trim($input));
        $criteria['name'] = strtoupper($input);
        switch ($input) {
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
                //$ret = $this->verdictRepo->findOneOrCreate($criteria);
                $ret = $this->verdictRepo->findOneOrCreate(array('name' => 'UNKNOWN'));
                break;
        }
        return $ret;
    }

    /**
     * Used to parse Control print of test name with version, grup test name and test version
     * @param LogBookMessage $log
     * @param bool $includeVersion
     * @return null|array
     */
    protected function searchTestNameInSingleLogControlPrint(LogBookMessage $log, $includeVersion = true): ?array
    {
        $ret = null;
        [$dirty, $testName, $testVersion] = array('', '', '');
        preg_match('/\=*Running Sub-test\: \[([\w\s\_\-\.\;\#\!\$\@\%\^\&\*\(\)\/]*)\]\s*(?>\(ver\.\s*(\d+\.?\d*))?/', $log->getMessage(), $matches);
        if (\count($matches) >= 2) {
            if (\count($matches) === 3) {
                [$dirty, $testName, $testVersion] = $matches;
            } else if (\count($matches) === 2) {
                $testVersion = '';
                [$dirty, $testName] = $matches;
            }

            if ($dirty !== '' && $testName !== '') {
                if ($includeVersion && $testVersion !== '') {
                    $ret = [$testName, $testVersion];
                } else {
                    $ret = [$testName, null];
                }
            }
        }

        return $ret;
    }

    /**
     * Used to parse test print of test name with version, grup test name and test version
     * @param LogBookMessage $log
     * @param bool $includeVersion
     * @return null|array
     */
    protected function searchTestNameInSingleLogTestPrint(LogBookMessage $log, $includeVersion = true): ?array
    {
        $ret = null;
        [$dirty, $testName, $testVersion] = array('', '', '');
        preg_match('/\=+\s*Initialize\s*(.*)\s*test\s*(?>\(ver\.\s*(\d+\.?\d*)\))?/', $log->getMessage(), $matches);
        if (\count($matches) >= 2) {
            if (\count($matches) === 3) {
                [$dirty, $testName, $testVersion] = $matches;
            } else if (\count($matches) === 2) {
                $testVersion = '';
                [$dirty, $testName] = $matches;
            }

            if ($testName !== '' && $dirty !== '') {
                if ($includeVersion && $testVersion !== '') {
                    $ret = [$testName, $testVersion];
                } else {
                    $ret = [$testName, null];
                }
            }
        }

        return $ret;
    }

    /**
     * Used to parse Autotest print of test start, grup test name
     * @param LogBookMessage $log
     * @return null|string
     */
    protected function searchTestNameInSingleLogAutoTestPrint(LogBookMessage $log): ?string
    {
        $ret = null;
        preg_match('/START\s+([\w\.\/\-]+)\s+/', $log->getMessage(), $matches);
        if (\count($matches) === 2) {
            [$dirty, $testName] = $matches;
            if (\mb_strlen($dirty) > 0 && \mb_strlen($testName) > 0 ) {
                $ret = $testName;
            }
        }

        return $ret;
    }

    /**
     * Convert string time to object DateTime
     * @param string $input
     * @return \DateTime
     */
    protected function getLogTime(string $input): \DateTime
    {
        $tmp_time = $this->cleanString($input);
        $len = \strlen($tmp_time);
        $timeFormat = 'U.u';
        $ret = \DateTime::createFromFormat('U', time());

        try {
            if ($len === $this->_MEDIUM_TIME_LEN) {
                $timeFormat = 'm/d H:i:s';
            } else if ($len === $this->_SHORT_TIME_LEN) {
                $timeFormat = 'H:i:s';
            } else if ($len === $this->_SHORT_MILISEC_TIME_LEN) {
                $timeFormat = 'H:i:s.u';
            } else if ($len === $this->_MEDIUM_MILISEC_TIME_LEN) {
                $timeFormat = 'm/d H:i:s.u';
            } else {
                $try_format = new DateTime($tmp_time);
                $tmp_time = $try_format->format('U.u');
            }

            try{
                $ret = \DateTime::createFromFormat($timeFormat, $tmp_time);
            } catch (\Exception $ex) {
//            print_r($ex);
//            exit();
            }
        } catch (\Exception $ex) {

        }

        return $ret;
    }

    /**
     * Clean and replace some debug Level
     * @param string $debugLevel
     * @return string
     */
    protected function prepareDebugLevel(string $debugLevel): string
    {
        //Get debug level message, convert to upper case
        $ret = strtoupper($this->cleanString($debugLevel));
        if ($ret === 'WARNI') {
            $ret = 'WARNING';
        } elseif ($ret === 'CRITI') {
            $ret = 'CRITICAL';
        } elseif ($ret === 'SYSTE') {
            $ret = 'SYSTEM';
        }

        return $ret;
    }

    /**
     * Clean string from bash characters
     * @param $string
     * @return string
     */
    protected function cleanString(string $string): string
    {
        $s = trim($string);
        //$s = iconv('UTF-8', 'UTF-8//IGNORE', $s); // drop all non utf-8 characters
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
     * @param int $max_length
     * @return string
     */
    private function generateUniqueFileName(int $max_length=40): string
    {
        // md5() reduces the similarity of the file names generated by uniqid(), which is based on timestamps
        $ret = md5(uniqid('', true));
        return mb_substr($ret, 0, $max_length);
    }

    /**
     * Creates a new Upload entity.
     *
     * @Route("/new", name="upload_new", methods={"GET|POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    public function newWeb(Request $request)
    {
        //curl --max-time 120 --form file=@autoserv.DEBUG --form token=2602161043 --form setup=DELL-KUBUNTU --form 'UPTIME_START=720028.73 2685347.68' --form 'UPTIME_END=720028.73 2685347.68' --form NIC=TEST --form DUTIP=172.17.0.1 --form PlatformName=Platf --form k_ver= --form Kernel=4.4.0-112-generic --form testCaseName=sa --form testSetName=sa --form build=A:_S:_I: --form testCount=2 http://127.0.0.1:8080/upload/new
        $obj = new LogBookUpload();
        $form = $this->createForm(LogBookUploadType::class, $obj);
        $form->handleRequest($request);
        $setup = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $request_str = 'log_book_upload';
            // $file stores the uploaded PDF file
            /** @var UploadedFile $file */
            $file = $obj->getLogFile();

            $fileName = $this->generateUniqueFileName(). '_' . $file->getClientOriginalName(). '.txt'; //.$file->guessExtension();

            $post_cycle = $request->request->get($request_str)['cycle'];
            $setup_id = $request->request->get($request_str)['setup'];

            $cycle = $this->cycleRepo->findOneBy(array('id' => $post_cycle));
            if ($cycle !== null) {
                $obj->addMessage('Cycle found, take SETUP from cycle');
                $setup = $cycle->getSetup();
            } else {
                $obj->addMessage('Cycle not found, take setup from POST');
                $setup_name = '';   //$request->query->get('build');//$this->cleanString('Test Setup');
                /** @var LogBookSetup $setup */
                $setup = $this->bringSetup($obj,$setup_name , $setup_id);
            }

            $obj->addMessage('New file name is :' . $fileName);
            $obj->addMessage('File ext :' .$file->guessExtension());

            $new_file = $file->move('../uploads/' . $setup->getId() . '/' . $cycle->getId(), $fileName);

            $obj->addMessage('File copy info :' . $new_file);
            $obj->setLogFile($fileName);

            $testName = $file->getClientOriginalName();
            /** @var LogBookTest $test */
            $test = $this->testsRepo->findOneOrCreate(array(
                'id' => 1,
                'name' => $testName,
                'cycle' => $cycle,
                'logFile' => $new_file->getFilename(),
                'logFileSize' => $new_file->getSize(),
                'executionOrder' => $this->getTestNewExecutionOrder($cycle),
            ));
            /** Parse log in test **/
            $this->parseFile($new_file, $test, $obj, null);

            $this->em->refresh($cycle);
            $cycle->setBuild($this->buildRepo->findOneOrCreate(array('name' => 'Some Build')));
            $remote_ip = $request->getClientIp();
            $uploader = $this->targetRepo->findOneOrCreate(array('name' => $remote_ip));
            $dut = $this->targetRepo->findOneOrCreate(array('name' => 'testDut'));

            $cycle->setTargetUploader($uploader);
            $cycle->setController($uploader);
            $cycle->setDut($dut);
            $this->em->flush();
            $obj->addMessage('TestID is ' . $test->getId());
            return $this->showAction($obj);
        }

        return $this->render('lbook/upload/new.html.twig', array(
            'setup' => $setup,
            'verdict' => $obj,
            'form' => $form->createView(),
        ));
    }
}
