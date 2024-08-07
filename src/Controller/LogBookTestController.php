<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use App\Entity\LogBookTestFailDesc;
use App\Entity\TestSearch;
use App\Form\TestSearchType;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookMessageRepository;
use App\Repository\LogBookTestFailDescRepository;
use App\Repository\LogBookTestInfoRepository;
use App\Repository\LogBookTestMDRepository;
use App\Repository\LogBookTestRepository;
use App\Repository\LogBookTestTypeRepository;
use App\Repository\LogBookVerdictRepository;
use App\Service\PagePaginator;
use App\Utils\LogBookCommon;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use App\Form\LogBookTestType;

/**
 * Test controller.
 *
 * @Route("test")
 */
class LogBookTestController extends AbstractController
{
    protected $index_size = 500;

    protected $log_size = 3000;
    /** @var EntityManagerInterface */
    protected $em;

    /**
     * @param Container $container
     * @throws \LogicException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->em = $this->getDoctrine()->getManager();

    }

    /**
     * Test exporter to JSON file
     *
     * @Route("/export/{test}", name="test_exporter", methods={"GET", "POST"})
     * @param LogBookTestRepository $testRepo
     * @param LogBookTest $test
     * @return JsonResponse
     */
    public function export(LoggerInterface $logger, LogBookTestRepository $testRepo, LogBookTest $test = null): JsonResponse
    {
        $fin_resp = [];
        try{
            if ($test !== null) {
                $fin_resp[] = $test->toJsonExport();
            }
            $response =  new JsonResponse($fin_resp);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        } catch (\Throwable $ex) {
            $logger->critical('test:export:test_exporter: Issue found :' . $ex->getMessage());
            $fin_resp['ERROR'] = $ex->getMessage();
        }
        $response = new JsonResponse($fin_resp);
        $response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
        
    }

    /**
     * Lists all Tests entities.
     *
     * @Route("/page/{page}", name="test_index", methods={"GET"})
     * @Template(template="lbook/test/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @return array
     */
    public function index(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, $page = 1): array
    {
        set_time_limit(10);
        $query = $testRepo->createQueryBuilder('t')
           // ->orderBy('t.id', 'DESC')
            ->setMaxResults($this->index_size);
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        //$posts = $this->getAllPosts($page); // Returns 5 posts out of 20
        // You can also call the count methods (check PHPDoc for `paginate()`)
        //$totalPostsReturned = $paginator->getIterator()->count(); # Total fetched (ie: `5` posts)
        $totalPosts = $paginator->count(); # Count of ALL posts (ie: `20` posts)
        $iterator = $paginator->getIterator(); # ArrayIterator

        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = $page;
        return array(
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        );
    }

    /**
     * @Route("/update/{test}", name="update", methods={"GET", "POST"})
     * @param LogBookTest $test
     * @param Request $request
     * @param LoggerInterface $logger
     * @return Response
     */
    public function update(LogBookTest $test, Request $request, LoggerInterface $logger): Response
    {
        try {
            $status = 200;
            $fin_res = array();

            $data = json_decode($request->getContent(), true);
            if ($data === null) {
                $data = array();
            }
            if (!array_key_exists('test_execution_key', $data)) {
                $data['test_execution_key'] = '';
                $fin_res['message'] = 'test_execution_key not provided';
                $status = 400;
            } else if (mb_strlen($data['test_execution_key']) < 5) {
                $fin_res['message'] = 'Bad test_execution_key provided';
                $status = 400;
            }

            if (!array_key_exists('test_key', $data)) {
                $data['test_key'] = '';
                $fin_res['message'] = 'test_key not provided';
                $status = 400;
            } else if (mb_strlen($data['test_key']) < 5) {
                $fin_res['message'] = 'Bad test_key provided';
                $status = 400;
            }
            if ($status === 200) {
                if ($test !== null) {
                    $test->addNewMetaData(array(
                        'EXECUTION_SHOW' => $data['test_execution_key'],
//                        'TEST_CASE_SHOW' => $data['test_key']
                        ));
                    $test->setTestKey($data['test_key']);
                    $this->em->flush();
                    $fin_res['message'] = 'success';
                } else {
                    $fin_res['message'] = 'Suites not found';
                    $status = 400;
                }
            }
            $response =  new JsonResponse($fin_res, $status);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;

        } catch (\Throwable $ex) {
            $logger->critical('ERROR :' . $ex->getMessage());
            $response = $this->json([]);
            $js = json_encode('["'. $ex->getMessage() .'"]');
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }

    /**
     * @Route("/migration", name="migration", methods={"GET"})
     * @Template(template="lbook/test/index.html.twig")
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testsRepo
     * @param LogBookTestInfoRepository $testInfoRepo
     * @param LogBookTestTypeRepository $testTypeRepo
     * @param LogBookTestMDRepository $mdRepo
     * @param LogBookTestFailDescRepository $fdRepo
     * @param LoggerInterface $logger
     * @return array
     */
    public function testFailDescriptionParser(PagePaginator $pagePaginator, LogBookVerdictRepository $vRepo, LogBookTestRepository $testsRepo, LogBookTestInfoRepository $testInfoRepo, LogBookTestTypeRepository $testTypeRepo, LogBookTestMDRepository $mdRepo, LogBookTestFailDescRepository $fdRepo, LoggerInterface $logger): array
    {
        $timestamp = new \DateTime();
        $timestamp = $timestamp->getTimestamp();

        $vPass = $vRepo->findOneBy(['name' => 'PASS']);
        $vUnknown = $vRepo->findOneBy(['name' => 'UNKNOWN']);
        $vAbort = $vRepo->findOneBy(['name' => 'ABORT']);
        $query = $testsRepo->createQueryBuilder('t')
            ->where('t.verdict NOT IN (:verdict)')
            ->andWhere('t.failDesc IS NULL')
            ->setMaxResults(5000)
            ->setParameter('verdict', [$vPass, $vUnknown, $vAbort])
            ->orderBy('t.id', 'ASC|DESC');
        $query->addSelect('RAND() as HIDDEN rand')->orderBy('rand()');

        /** @var \ArrayIterator $iterator */
        $iterator = $query->getQuery()->execute();
        $totalPosts = count($iterator);
        $maxPages = 1;
        $thisPage = 1;

        $logger->notice('  - Start MIGRATION for ' . count($iterator) . ' tests - ' . $timestamp);

        $changed_tests = [];
        /** @var LogBookTest $test */
        foreach ($iterator as $test) {
            try {
                $curTimestamp = new \DateTime();
                $curTimestamp = $curTimestamp->getTimestamp();
                $tDelata = ($curTimestamp - $timestamp);
                if ( $tDelata >= 120 ) {
                    $logger->notice('  - Break MIGRATION for ' . count($changed_tests) . ' tests - TimeDelta:' . $tDelata);
                    break;
                }
                $fdOld = $test->getFailDescription();
                $fd = LogBookTestFailDesc::validateDescription($test->getFailDescription(true));
                $similarPercent = 0;

                if ($fd !== null && strlen($fd) > 1) { //&& $fdOld != $fd ){
//                    similar_text($fdOld, $fd, $similarPercent);
//                    if ($similarPercent <= 80) {
                        /** @var LogBookTestFailDesc $fDesc */
                    $fDesc = $fdRepo->findOrCreate(['description' => $fd]);
                    $fDesc->addTest($test);
                    $test->setFailDesc($fDesc);
                    $this->em->persist($fDesc);
                    $fDesc->setTestsCount($fDesc->getTests()->count());
                    $this->em->persist($fDesc);
                    //$this->em->flush();
                    $changed_tests[] = $test;
                } else {
                    if ($fdOld !== null && strlen($fdOld)) {
                        $fDesc = $fdRepo->findOrCreate(['description' => $fdOld]);
                        $fDesc->addTest($test);
                        $test->setFailDesc($fDesc);
                        $this->em->persist($fDesc);
                        $fDesc->setTestsCount($fDesc->getTests()->count());
                        $this->em->persist($fDesc);
                    } else {
                        $fDesc = $fdRepo->findOrCreate(['description' => 'EMPTY']);
                        $fDesc->addTest($test);
                        $test->setFailDesc($fDesc);
                        $this->em->persist($fDesc);
//                        $fDesc->setTestsCount($fDesc->getTests()->count());
                        $this->em->persist($fDesc);
                    }
                }
//                if ($test->getFailDescription() !== null && $test->getFailDescription() !== '') {
//                    /** @var LogBookTestFailDesc $fDesc */
//                    $fDesc = $fdRepo->findOrCreate(['description' => $test->getFailDescription()]);
//                    $fDesc->addTest($test);
//                    $this->em->persist($fDesc);
//                }

            } catch (\Throwable $ex) {
                $logger->critical('MIGRATION :' . $ex->getMessage(), [
                    'ex_file' => $ex->getFile(),
                    'ex_line' => $ex->getLine(),
                    'trace' => $ex->getTraceAsString(),
                    ]);
            }

        }
//        exit();
        $this->em->flush();

        $logger->notice('  - FINISH MIGRATION  for ' . count($changed_tests) . ' tests- '. $timestamp);
        return array(
            'size'      => count($changed_tests),
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $changed_tests,
        );
    }

    /**
     * @Route("/search", name="test_search", methods={"GET|POST"})
     * @Route("/search/{name}", name="test_search_name", methods={"GET|POST"})
     * @Route("/search/{name}/bf/{build_flavor}", name="test_search_name_bf", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycleRepository $cycleRepo
     * @param LoggerInterface $logger
     * @param string|null $name
     * @param string|null $build_flavor
     * @return Response
     */
    public function search(Request $request, LogBookTestRepository $testRepo, LogBookCycleRepository $cycleRepo, LoggerInterface $logger, string $name = null, string $build_flavor = null): Response
    {
        set_time_limit(30);
        $tests = $new_tests = array();
        $verdict = null;
        $setups = null;
        $sql = '';
        $suiteInTable = true;
        $leftDate = $rightDate = false;
        $startDate = $endDate = null;
        $DATE_TIME_TYPE = \Doctrine\DBAL\Types\Type::DATETIME;
        $test = new TestSearch();
        $d = null;
        $post = $request->request->get('test_search');

        if ($name !== null) {
            if ($post !== null && array_key_exists('name', $post)) {
                $name = $post['name'];
            } else {
                $test->setName($name);
            }
            if ($post === null || !array_key_exists('fromDate', $post)) {
                $d = new \DateTime('- 30 days');
                $test->setFromDatet($d->format('m/d/Y'));
            }

        }
        $form = $this->createForm(TestSearchType::class, $test, array());
        try {
            $form->handleRequest($request);
        } catch (\Exception $ex) {}

        $addOrder = true;


        if ($post !== null || $name !== null) {
            if ($post === null) {
                $post = [];
            }
            $enableSearch = false;
            if (array_key_exists('verdict', $post)) {
                $verdict = $post['verdict']['name'];
            }
            if (array_key_exists('setup', $post)) {
                $setups = $post['setup']['name'];
            }
            if (array_key_exists('limit', $post)) {
                $limit = (int)$post['limit'];
                if ($limit > 10000) {
                    $limit = 500;
                }
                $test->setLimit($limit);
            }
            $failDesc = $testMetaData = $fromDate = $toDate = null;
            if ($name !== null) {
                $test_name = $name;
                if ($d !== null) {
                    $fromDate = $d->format('m/d/Y');
                }
            } else {
                $test_name = $post['name'];
            }

            if (array_key_exists('metaData', $post)) {
                $testMetaData = $post['metaData'];
            }
            if (array_key_exists('failDesc', $post)) {
                $failDesc = $post['failDesc'];
            }
            if (array_key_exists('toDate', $post)) {
                $toDate = $post['toDate'];
            }
            if (array_key_exists('fromDate', $post)) {
                $fromDate = $post['fromDate'];
            }

            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.disabled = 0')
                ->setMaxResults($test->getLimit());

            if ($fromDate !== null && mb_strlen($fromDate) > 7) {
                $startDate = \DateTime::createFromFormat('m/d/Y H:i', $fromDate . '00:00');
                if ($startDate !== false) {
                    $leftDate = true;
                }
            }
            if ($toDate !== null && mb_strlen($toDate) > 7) {
                $endDate = \DateTime::createFromFormat('m/d/Y H:i', $toDate . '23:59');
                if ($endDate !== false) {
                    $rightDate = true;
                }
            }
            if ($leftDate === true && $rightDate === true) {
                $qb->andWhere('t.timeStart BETWEEN :fromDate AND :toDate')
                    ->setParameter('fromDate', $startDate, $DATE_TIME_TYPE)
                    ->setParameter( 'toDate', $endDate, $DATE_TIME_TYPE);
                $enableSearch = True;
            } else if ($leftDate === true) {
                $qb->andWhere('t.timeStart >= :fromDate')
                    ->setParameter('fromDate', $startDate, $DATE_TIME_TYPE);
                $enableSearch = True;
            } else if ($rightDate === true) {
                $qb->andWhere('t.timeEnd <= :endDate')
                    ->setParameter('endDate', $endDate, $DATE_TIME_TYPE);
                $enableSearch = True;
            }

            if ($verdict !== null && \count($verdict) > 0) {
                $qb->andWhere('t.verdict IN (:verdict)')
                    ->setParameter('verdict', $verdict);
                $enableSearch = True;
            }

            if ($setups !== null && \count($setups) > 0) {
                $qbCycle = $cycleRepo->createQueryBuilder('c')
                    ->where('c.setup IN (:setups)')
                    ->setParameter('setups', $setups);
                $queryCycle = $qbCycle->getQuery()->getResult();
                $qb->andWhere('t.cycle IN (:cycles)')
                    ->setParameter('cycles', $queryCycle);
                $enableSearch = True;
            }

            if ( ($test_name !== null && \mb_strlen($test_name) >= 1) || ($testMetaData !== null && \mb_strlen($testMetaData) >= 1) || ($failDesc !== null && \mb_strlen($failDesc) >= 1) ) {
//                if (\is_numeric($test_name) && (string)(int)$test_name === $test_name) {
//                    $qb->andWhere('t.name LIKE :test_name OR t.meta_data LIKE :metadata OR t.id = :test_id')
//                        ->setParameter('test_id', (int)$test_name);
//                } else {
//                    $qb->andWhere('t.name LIKE :test_name OR t.meta_data LIKE :metadata');
//                }
//                $qb->setParameter('test_name', '%'.$test_name.'%')
//                    ->setParameter('metadata', $test_name.'%');

//                $test_name = trim($test_name);
//                if (\is_numeric($test_name) && (string)(int)$test_name === $test_name) {
//                    $qb->andWhere('MATCH_AGAINST(t.name, :search_str) != 0 OR t.id = :test_id');
//                    $qb->leftJoin('t.newMetaData', 'newMetaData')->orWhere('MATCH_AGAINST(newMetaData.value, :search_str) > 1')->addSelect('MATCH_AGAINST(newMetaData.value, :search_str) as rate2');
//                    $qb->setParameter('test_id', (int)$test_name);
//
//                } else {

                //$qb->andWhere('MATCH_AGAINST(t.name, :search_str) > 1 OR t.name LIKE :test_name');
                if (strlen($test_name)) {
                    $qb->leftJoin('t.testInfo', 'testInfo')->andWhere('MATCH_AGAINST(testInfo.name, testInfo.path, :search_str) > 1 OR testInfo.name LIKE :test_name')->addSelect('MATCH_AGAINST(testInfo.name, testInfo.path, :search_str) as rate');
                }
                //$qb->leftJoin('t.newMetaData', 'newMetaData')->orWhere($qb->expr()->like('newMetaData.value', $qb->expr()->literal('%'. $test_name. '%') ));
                if (strlen($testMetaData)) {
                    $qb->leftJoin('t.newMetaData', 'newMetaData')->andWhere('MATCH_AGAINST(newMetaData.value, :metaData) > 1')->addSelect('MATCH_AGAINST(newMetaData.value, :metaData) as rate2');
                    $qb->addOrderBy('rate2', 'DESC');

                }
                if (strlen($failDesc)) {
                    $qb->leftJoin('t.failDesc', 'failDesc')->andWhere('MATCH_AGAINST(failDesc.description, :failDesc) > 0.1')->addSelect('MATCH_AGAINST(failDesc.description, :failDesc) as rate3');
                    $qb->addOrderBy('rate3', 'DESC');

                }
                //$qb->addSelect('MATCH_AGAINST(t.name, :search_str) as rate');
                if (strlen($test_name)) {
                    $qb->addOrderBy('rate', 'DESC');
                }

                $qb->addOrderBy('t.id', 'DESC');
                $addOrder = false;
//                }

                $test_name_match = str_replace('%', ' ', $test_name);
                $test_name_match = str_replace('?', ' ', $test_name_match);
                $test_name_match = str_replace('  ', ' ', $test_name_match);
                $test_name_match = str_replace(' ', ' +', $test_name_match);
                $test_name_match = str_replace(' ++', ' +', $test_name_match);
                $test_name_match = str_replace(' +-', ' -', $test_name_match);

                $test_name_search = $test_name;
                if (mb_strlen($test_name_search) > 10) {
                    $test_name_search .= '%';
                }
                if (strlen($test_name)) {
                    $qb->setParameter('search_str', $test_name_match);
                    $qb->setParameter('test_name', $test_name_search);
                }
                if (strlen($testMetaData)) {
                    $qb->setParameter('metaData', $testMetaData);
                }
                if (strlen($failDesc)) {
                    $ta = str_replace('%', ' ', $failDesc);
                    $ta = str_replace('?', ' ', $ta);
                    $ta = str_replace('  ', ' ', $ta);
                    $ta = str_replace(' ', ' +', $ta);
                    $ta = str_replace(' ++', ' +', $ta);
                    $ta = str_replace(' +-', ' -', $ta);
                    $qb->setParameter('failDesc', $ta);
                }
                $enableSearch = True;

                if ($build_flavor !== null) {
                    $qb->innerJoin('t.suite_execution', 's')->andWhere('s.buildType = :build_flavor')
                        ->setParameter('build_flavor', $build_flavor);
                    $suiteInTable = true;
                }
            }
            if ($addOrder) {
                $qb->orderBy('t.id', 'DESC');
            }
            if (\is_numeric($test_name) && (string)(int)$test_name === $test_name) {
            }else {
                //$qb->setParameter('test_name', $test_name_search);

            }
            $em = $this->getDoctrine()->getManager();
            /** @var Query $queryObj */
            $queryObj = $em->createQuery("SELECT t FROM App\LogBookTest t");
            $queryObj->setDQL($qb->getDQL());

            $queryObj->setFetchMode(LogBookTest::class, "testInfo", ClassMetadataInfo::FETCH_EAGER);
            $queryObj->setFetchMode(LogBookTest::class, "verdict", ClassMetadataInfo::FETCH_EAGER);
            $queryObj->setFetchMode(LogBookTest::class, "testType", ClassMetadataInfo::FETCH_EAGER);
            $queryObj->setFetchMode(LogBookTest::class, "suite_execution", ClassMetadataInfo::FETCH_EAGER);
            $queryObj->setFetchMode(LogBookTest::class, "cycle", ClassMetadataInfo::FETCH_EAGER);
            $queryObj->setFetchMode(LogBookTest::class, "failDesc", ClassMetadataInfo::FETCH_EAGER);

            $queryObj->setParameters($qb->getParameters());
            $queryObj->setMaxResults($qb->getMaxResults());
            if ($enableSearch) {
                //$query = $qb->getQuery();
                //$sql = $query->getSQL();
                $sql = $queryObj->getSQL();
                $tests = $queryObj->execute();
                //$tests = $query->execute();
                if (!$addOrder){
                    foreach($tests as $tmp_test) {
                        /** @var LogBookTest $t_t */
                        $t_t = $tmp_test[0];
                        try {
                            $t_t->setRate(
                                LogBookCommon::get($tmp_test, 'rate', 0) +
                                LogBookCommon::get($tmp_test, 'rate2', 0) +
                                LogBookCommon::get($tmp_test, 'rate3', 0));
                        } catch (\Throwable $ex) {
                            $logger->critical('setRate in search ' . $ex->getMessage());

                        }
                        $new_tests[] = $t_t;
                    }
                } else {
                    $new_tests = $tests;
                }
            }
        }

        return $this->render('lbook/test/search.html.twig', array(
            //'tests' => $tests,
            'iterator' => $new_tests,
            'tests_count' => \count($new_tests),
            'suiteInTable' => $suiteInTable,
            'sql' => $sql,
//            'thisPage'      => 1,
//            'maxPages'      => 1,
            'form' => $form->createView(),
        ));
    }

    /**
     * Lists all Tests entities.
     *
     * @Route("/", name="test_index_first", methods={"GET"})
     * @Template(template="lbook/test/index.html.twig")
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @return array
     */
    public function indexFirst(PagePaginator $pagePaginator, LogBookTestRepository $testRepo): array
    {
        return $this->index($pagePaginator, $testRepo, 1);
    }

    /**
     * Creates a new test entity.
     *
     * @Route("/new", name="test_new", methods={"GET|POST"})
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function new(Request $request)
    {
        $test = new LogBookTest();
        $form = $this->createForm(LogBookTestType::class, $test, array('search' => true));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($test);
            $em->flush();

            return $this->redirectToRoute('test_show_first', array('id' => $test->getId()));
        }

        return $this->render('lbook/test/new.html.twig', array(
            'test' => $test,
            'form' => $form->createView(),
        ));
    }

    /**
     * @param LogBookTest $test
     * @return bool
     */
    protected function isTestFileExist(LogBookTest $test): bool
    {
        return file_exists($this->getTestFilePath($test));
    }

    /**
     * @param LogBookTest $test
     * @return string
     */
    protected function getTestFilePath(LogBookTest $test): string
    {
        $retFileName = $test->getLogFile();
        $cycle = $test->getCycle();
        $setup = $cycle->getSetup();
        $tmp = '../uploads/%d/%d/%s';
        return sprintf($tmp, $setup->getId(), $cycle->getId(), $retFileName);
    }

    /**
     * @Route("/{id}/downloadlog", name="download_log", methods={"GET"})
     * @param LogBookTest|null $test
     * @return BinaryFileResponse|Response
     */
    public function downloadLogFile(LogBookTest $test = null): Response
    {
        try {
            if (!$test ) {
                throw new \RuntimeException('');
            }

            if (!$this->isTestFileExist($test)) {
                throw new \RuntimeException(sprintf('Log file for test [%d:%s] not exist', $test->getId(), $test->getName()));
            }

            $cycle = $test->getCycle();
            $setup = $cycle->getSetup();
            $path = $this->getTestFilePath($test);

            $ext = pathinfo($path, PATHINFO_EXTENSION);

            if ($ext !== null && $ext !== '') {
                $tmp = '%d-%d-%d__%s_-_%s_-_%s.%s';
                $retFileName = sprintf($tmp, $setup->getId(), $cycle->getId(), $test->getId(), $setup->getName(), $cycle->getName(), $test->getName(), $ext);
            } else {
                $tmp = '%d-%d-%d__%s_-_%s_-_%s.%s';
                $retFileName = sprintf($tmp, $setup->getId(), $cycle->getId(), $test->getId(), $setup->getName(), $cycle->getName(), $test->getName(), 'txt');
            }
            $sanitizedFileName = preg_replace('/[^a-zA-Z0-9\-\._]/','', $retFileName);
            return $this->file($path, $sanitizedFileName);
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * @Route("/{id}/showlog", name="show_log", methods={"GET"})
     * @param LogBookTest|null $test
     * @return BinaryFileResponse|Response
     */
    public function showLogFile(LogBookTest $test = null): Response
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }

            if (!$this->isTestFileExist($test)) {
                throw new \RuntimeException(sprintf('Log file for test [%d:%s] not exist', $test->getId(), $test->getName()));
            }

            $textResponse = new Response(file_get_contents($this->getTestFilePath($test)) , 200);
            $textResponse->headers->set('Content-Type', 'text/plain');
            return $textResponse;
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Finds and displays a test entity.
     *
     * @Route("/{id}", name="test_show_first", methods={"GET"})
     * @param LogBookTest $test
     * @param PagePaginator $pagePaginator
     * @param LogBookMessageRepository $logRepo
     * @param LogBookTestRepository $testRepo
     * @return Response
     */
    public function show(PagePaginator $pagePaginator, LogBookMessageRepository $logRepo, LogBookTestRepository $testRepo, LogBookTest $test = null): ?Response
    {
        return $this->showFull($pagePaginator, $logRepo, $testRepo, $test, 1);
    }

    /**
     * Finds and displays a test entity.
     *
     * @Route("/{id}/page/{page}", name="test_show", methods={"GET"})
     * @param LogBookTest $test
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookMessageRepository $logRepo
     * @param LogBookTestRepository $testRepo
     * @return Response
     */
    public function showFull(PagePaginator $pagePaginator, LogBookMessageRepository $logRepo, LogBookTestRepository $testRepo, LogBookTest $test = null, $page = 1): ?Response
    {
        set_time_limit(10);
        $dataTable = '';
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $bad_case = '';
            $dataTable = 'log_book_message';
            $first_log = $test->getLogs()->first();
            $em = $this->getDoctrine()->getManager();
            if ($first_log === false || $first_log === null) {

                /** @var ClassMetadataInfo $classMetaData */
                $classMetaData = $em->getClassMetadata('App:LogBookMessage');
                $classMetaData->setPrimaryTable(['name' => $test->getDbNameWithPrefix()]);
                // Used in case there is no table created
                $logRepo->createCustomTable($test->getDbPrefix());
                $dataTable = $test->getDbNameWithPrefix();
                $logRepo = $em->getRepository('App:LogBookMessage');
                $logRepo->setCustomTable($test->getDbNameWithPrefix());
                $em->refresh($test);

                $qb2 = $logRepo->createQueryBuilder('log_book_message')
                    ->where('log_book_message.test = :test')
                    ->setCacheable(false)
//                    ->setLifetime(120)
                    ->orderBy('log_book_message.chain', 'ASC')
                    ->setParameter('test', $test->getId());
                $paginator = $pagePaginator->paginate($qb2, $page, $this->log_size);
                $totalPosts = $paginator->count();
            } else {
                $classMetaData = $em->getClassMetadata('App:LogBookMessage');
                $classMetaData->setPrimaryTable(['name' => $dataTable]);
                $logRepo->setCustomTable($dataTable);
                $qb = $logRepo->createQueryBuilder('log_book_message')
                    ->where('log_book_message.test = :test')
                    ->setCacheable(false)
                    ->orderBy('log_book_message.chain', 'ASC')
                    ->setParameter('test', $test->getId());
                $paginator = $pagePaginator->paginate($qb, $page, $this->log_size);
                $totalPosts = $paginator->count();

            }
            if ($totalPosts === 0 && $first_log !== null) {
                $iterator = $test->getLogs()->getIterator();
                $totalPosts = $test->getLogs()->count();
                $bad_case = 'totalPosts === 0 && first_log !== null';

            } else {
                $iterator = $paginator->getIterator();
            }

            $impossibleSize = $this->log_size * ($page-1) + 1;
            $maxPages = ceil($totalPosts / $this->log_size);

            if ($page > 1 && $totalPosts < $impossibleSize && $iterator->count() === 0) {
                return $this->redirectToRoute('test_show', [
                    'id' => $test->getId(),
                    'page' => max(1, min($maxPages, $page - 1))
                ]);
            }

            $thisPage = $page;
            return $this->render('lbook/test/show.full.html.twig', array(
                'test'          => $test,
                'size'          => $totalPosts,
                'maxPages'      => $maxPages,
                'thisPage'      => $thisPage,
                'iterator'      => $iterator,
                'paginator'     => $paginator,
                'data_table'   => $dataTable,
                'first_log'   => $first_log,
//                'delete_form'   => $deleteForm->createView(),
                'file_exist'    => $this->isTestFileExist($test),
                'bad_case'      =>  $bad_case
            ));
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * @param LogBookTest|null $test
     * @param \Throwable $ex
     * @return Response
     */
    protected function testNotFound(LogBookTest $test = null, \Throwable $ex): Response
    {
        /** @var Request $request */
        $request= $this->get('request_stack')->getCurrentRequest();
        $possibleId = 0;
        $response = $otherResponse = null;
        $short_msg = 'Unknown error';
        try {
            $possibleId = $request->attributes->get('id');
            $response = new Response('', Response::HTTP_NOT_FOUND);
            if ( $ex->getCode() > 0 && Response::$statusTexts[$ex->getCode()] !== '') {
                $otherResponse = new Response('', $ex->getCode());
                $short_msg = Response::$statusTexts[$ex->getCode()];
            } else {
                $otherResponse = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $ex) {}
        if ($test === null) {
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Test with provided ID:[%s] not found', $possibleId),
                'message' =>  $ex->getMessage(),
                'ex' => $ex,
            ), $response);
        }

        return $this->render('lbook/500.html.twig', array(
            'short_message' => $short_msg,
            'message' => $ex->getMessage(),
            'ex' => $ex,
        ), $otherResponse);
    }

    /**
     * Displays a form to edit an existing test entity.
     *
     * @Route("/{id}/edit", name="test_edit", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookTest $test
     * @return RedirectResponse|Response
     * @throws \Symfony\Component\Form\Exception\LogicException|\Symfony\Component\Security\Core\Exception\AccessDeniedException|\LogicException
     */
    public function edit(Request $request, LogBookTest $test = null)
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('edit', $test->getCycle()->getSetup());
            $deleteForm = $this->createDeleteForm($test);
            $editForm = $this->createForm(LogBookTestType::class, $test);
            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                /** @var LogBookCycle $cycle */
                $cycle = $test->getCycle();
                $cycle->setDirty(true);
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('test_edit', array('id' => $test->getId()));
            }

            return $this->render('lbook/test/edit.html.twig', array(
                'test' => $test,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Deletes a test entity.
     *
     * @Route("/{id}", name="test_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookTest $test
     * @return RedirectResponse | Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function delete(Request $request, LogBookTest $test = null)
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('delete', $test->getCycle()->getSetup());
            $form = $this->createDeleteForm($test);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var LogBookCycle $cycle */
                $cycle = $test->getCycle();
                $cycle->setDirty(true);
                $em = $this->getDoctrine()->getManager();
                $em->remove($test);
                $em->flush();
            }
            return $this->redirectToRoute('test_index');
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Creates a form to delete a test entity.
     *
     * @param LogBookTest $test The test entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookTest $test)
    {
        try {
            return $this->createFormBuilder()
                ->setAction($this->generateUrl('test_delete', array('id' => $test->getId())))
                ->setMethod('DELETE')
                ->getForm();
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }
}
