<?php

namespace App\Controller;

use App\Entity\CycleSearch;
use App\Entity\Host;
use App\Entity\LogBookCycle;
use App\Entity\LogBookEmail;
use App\Entity\LogBookTest;
use App\Entity\LogBookTestInfo;
use App\Entity\LogBookTestType;
use App\Entity\LogBookVerdict;
use App\Entity\SuiteExecution;
use App\Form\CycleSearchType;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookTestRepository;
use App\Repository\SuiteExecutionRepository;
use App\Repository\TestFilterApplyRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Form\LogBookCycleType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Service\PagePaginator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Cycle controller.
 *
 * @Route("cycle")
 */
class LogBookCycleController extends AbstractController
{
    protected $index_size = 3000;
    protected $show_tests_size = 5000;
    /** @var LogBookSetupRepository $setupRepo */
    protected $setupRepo;
    /** @var LogBookCycleRepository $cycleRepo */
    protected $cycleRepo;
//    /**
//     *
//     * @Route("/close_test/{cycle}", name="close_cycle_test", methods={"GET"})
//     * @return JsonResponse
//     */
//    public function close_test(LogBookCycle $cycle, LoggerInterface $logger): ?Response
//    {
//        $fin_res = [];
//        try {
//            $setup = $cycle->getSetup();
//
//
//        } catch (\Throwable $ex) {
//            $logger->critical($ex->getMessage());
//        }
//        return $this->render('lbook/email/cycle.finished.html.twig', [
//            'cycle' => $cycle,
//            'setup' => $setup
//        ]);
//    }
    /**
     * LogBookCycleController constructor.
     * @param Container $container
     * @throws \LogicException
     */
    public function __construct(Container $container)
    {

        $this->container = $container;
        $this->em = $this->getDoctrine()->getManager();
        $this->cycleRepo = $this->em->getRepository('App:LogBookCycle');
        $this->setupRepo = $this->em->getRepository('App:LogBookSetup');
    }

        /**
     * @Route("/tests/{id}", name="cycle_show_first_tests_only", methods={"GET"})
     * @Route("/tests/{id}/{maxSize}", name="cycle_show_size_tests_only", methods={"GET"}, defaults={"maxSize"=""})
     * @Route("/tests/{id}/{maxSize}/{page}", name="cycle_show_page_tests_only", methods={"GET"}, defaults={"page"=1, "maxSize"=5000})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle $cycle
     * @param int $maxSize
     * @return Response
     */
    public function showFirstPageTestsOnly(TestFilterApplyRepository $app_filters, PagePaginator $pagePaginator,
                                  LogBookTestRepository $testRepo, LogBookCycle $cycle = null, $page = null,
                                  $maxSize = null): ?Response
    {
        if ($page === null) {
            $page = 1;
        }
        if ($maxSize === null || $maxSize == "" || $maxSize == "1") {
            $maxSize = $this->show_tests_size;
        }
        $page = (int)$page;
        $maxSize = (int)$maxSize;
        return $this->show($app_filters, $pagePaginator, $testRepo, $cycle, null, $page, false, $maxSize, true);
    }
    
    /**
     * @Route("/api/token/keep_alive", name="cycle_token_keep_alive", methods={"GET", "POST"})
     * @param Request $request
     * @param LoggerInterface $logger
     * @return JsonResponse
     */
    public function cycleTokenKeepAlive(Request $request, LoggerInterface $logger): JsonResponse
    {
        $token = null;
        $setupName = $cycleName = null;
        $ip = $request->getClientIp();
        $fin_res['DEBUG'] = [];
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            $data = array();
        }
        if (array_key_exists('token', $data)) {
            $token = $data['token'];
        }
        if (array_key_exists('setup_name', $data)) {
            $setupName = $data['setup_name'];
        }
        $fin_res['DEBUG']['STARTED'] = 'yes';
        $fin_res['DEBUG']['token'] = $token;
        $fin_res['DEBUG']['setup'] = $setupName;
        try {
            try {
                if ( $token !== null && $setupName !== null && $setupName !== '' ) {
                    $setup = $this->setupRepo->findByName($setupName);
                    $cycle = $this->cycleRepo->findByToken($token, $setup);
                    if ($cycle !== null) {
                        $cycle->setTokenExpiration(new \DateTime('+1 hours'));
                        $fin_res['DEBUG']['CYCLE_ID'] = $cycle->getId();
                        $fin_res['DEBUG']['SETUP_ID'] = $setup->getId();
                        $fin_res['DEBUG']['SUCCESS'] = 'yes';
                        $fin_res['DEBUG']['NEW_VALUE'] = $cycle->getTokenExpiration()->format('d/m/Y H:i:s');
                    }
                }
            } catch (\Throwable $ex) {
                $logger->critical('Failed to set cycleTokenKeepAlive:[' . $token . ']', $ex->getTrace());
                $fin_res['DEBUG']['token'] = $token;
                $fin_res['DEBUG']['ERROR'] = $ex->getMessage();
            }

        } catch (\Throwable $e) {
            $method = $request->getMethod();
            $data['ip'] = $ip;
            $data['method'] = $method;
            $data['request'] = $request->request->all();
            $data['query'] = $request->query->all();
            $data['trace'] = $e->getTraceAsString();
            $data['DEBUG'][] = $e->getMessage();
            $logger->critical($method . '::' . $ip . '::ERROR :' . $e->getMessage(), $data);
            $response =  new JsonResponse($data);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }

        try {

            $this->em->flush();
        } catch (\Throwable $ex) {
            $fin_res['DEBUG'][] = $ex->getMessage();
            $logger->critical($ex->getMessage());

        }
        $response =  new JsonResponse($fin_res);
        $response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }

    /**
     *
     * @Route("/close/{cycle}", name="close_cycle", methods={"GET"})
     * @return JsonResponse
     */
    public function close_cycle(LogBookCycle $cycle, LoggerInterface $logger): ?JsonResponse
    {
        $fin_res = [];
        try {
            $em = $this->getDoctrine()->getManager();
            try{
                if (!$cycle->getIsClosed() && $cycle->isAllSuitesFinished()) {
                    $cycle->close();
                    $setup = $cycle->getSetup();
                    try {
                        $subscribers = $setup->getSubscribers();
                        // $fail_subscribers = $setup->getFailureSubscribers();
                        foreach ($subscribers as $subscriber) {
                            if ( $subscriber->getEmail() === null) {
                                continue;
                            }
                            $newEmail = new LogBookEmail();
                            $b = $cycle->getBuild();
                            if ($cycle->getPassRate() < 100) {
                                $newEmail->setSubject('['. $cycle->getName() . ']['. $b . '] failed. PR:' . $cycle->getPassRate() . '%');
                            } else{
                                $newEmail->setSubject('['. $cycle->getName() . ']['. $b . '] finished');
                            }
//                    try {
//                        if ( $fail_subscribers->contains($subscriber) ) {
//                            # For those who subscribed to failure (only)
//                            continue;
//                        }
//                    } catch (\Throwable $ex) {}

                            try {
                                $body = $this->get('twig')->render('lbook/email/cycle.finished.html.twig', [
                                    'cycle' => $cycle,
                                    'setup' => $setup
                                ]);
                                $newEmail->setBody($body);
                            } catch (\Throwable $ex) {
                                $logger->critical($ex->getMessage());
                            }

                            $newEmail->setRecipient($subscriber);
                            $em->persist($newEmail);
                        }

//                foreach ($fail_subscribers as $subscriber) {
//                    if ( $subscriber->getEmail() === null) {
//                        continue;
//                    }
//                    $newEmail = new LogBookEmail();
//                    if ($cycle->getPassRate() < 100) {
//                        $newEmail->setSubject('['. $cycle->getName() . '] failed. PR:' . $cycle->getPassRate() . '%');
//                    } else{
//                        continue;
//                    }
//                    try {
//                        $body = $this->get('twig')->render('lbook/email/cycle.finished.html.twig', [
//                            'cycle' => $cycle,
//                            'setup' => $setup
//                        ]);
//                        $newEmail->setBody($body);
//                    } catch (\Throwable $ex) {
//                        $logger->critical($ex->getMessage());
//                    }
//
//                    $newEmail->setRecipient($subscriber);
//                    $em->persist($newEmail);
//                }
                    } catch (\Throwable $ex) {
                        $logger->critical($ex->getMessage());
                    }

                }
                $em->flush();
            } catch (\Throwable $ex) {
                $fin_res['ERROR_LINE'] = $ex->getLine();
                $fin_res['ERROR_FILE'] = $ex->getFile();
                $fin_res['ERROR_MESSAGE'] = $ex->getMessage();
            }


            $logger->notice('CLOSE_CYCLE:',
                array(
                    'name' => $cycle->getName()
                ));
            $response = new JsonResponse($fin_res);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;

        } catch (\Throwable $ex) {
            $response = $this->json([]);
            $js = json_encode('["'. $ex->getMessage() .'"]');
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }

    /**
     * @Route("/search", name="cycle_search", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookCycleRepository $cycleRepo
     * @param SuiteExecutionRepository $suitesRepo
     * @return Response
     */
    public function search(Request $request, LogBookCycleRepository $cycleRepo, SuiteExecutionRepository $suitesRepo): Response
    {
        set_time_limit(30);
        $tests = $new_tests = array();
        $setups = null;
        $sql = '';
        $leftDate = $rightDate = false;
        $startDate = $endDate = null;
        $DATE_TIME_TYPE = \Doctrine\DBAL\Types\Type::DATETIME;
        $cycles = new CycleSearch();

        $form = $this->createForm(CycleSearchType::class, $cycles, array());
        try {
            $form->handleRequest($request);
        } catch (\Exception $ex) {}

        $addOrder = true;
        $post = $request->request->get('cycle_search');
        if ($post !== null) {
            $enableSearch = false;
            if (array_key_exists('setup', $post)) {
                $setups = $post['setup']['name'];
            }
            if (array_key_exists('limit', $post)) {
                $limit = (int)$post['limit'];
                if ($limit > 10000) {
                    $limit = 500;
                }
                $cycles->setLimit($limit);
            }
            $cycle_name = $post['name'];
            $fromDate = $post['fromDate'];
            $toDate = $post['toDate'];

            $qb = $cycleRepo->createQueryBuilder('t')
                ->where('t.disabled = 0')
                ->setMaxResults($cycles->getLimit());
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

            if ($cycle_name !== null && \mb_strlen($cycle_name) >= 2) {

                $qb->andWhere('MATCH_AGAINST(t.name, t.meta_data, :search_str) > 1 OR t.name LIKE :cycle_name OR t.id = :cycle_id OR t.uploadToken LIKE :cycle_name');
                $qb->addSelect('MATCH_AGAINST(t.name, t.meta_data, :search_str) as rate');
                $qb->orderBy('rate', 'DESC');
                $qb->addOrderBy('t.id', 'DESC');
                $addOrder = false;
//                }
                $cycle_name = trim($cycle_name);
                $cycle_name_match = str_replace('%', ' ', $cycle_name);
                $cycle_name_match = str_replace('?', ' ', $cycle_name_match);
                $cycle_name_match = str_replace('  ', ' ', $cycle_name_match);
                $cycle_name_match = str_replace(' ', ' +', $cycle_name_match);
                $cycle_name_match = str_replace(' ++', ' +', $cycle_name_match);
                $cycle_name_match = str_replace(' +-', ' -', $cycle_name_match);

                $qb->leftJoin('t.targetUploader', 'targetUploader')->orWhere($qb->expr()->like('targetUploader.name', $qb->expr()->literal($cycle_name)));
                $qb->leftJoin('t.controller', 'controller')->orWhere($qb->expr()->like('controller.name', $qb->expr()->literal($cycle_name)));
                $qb->leftJoin('t.dut', 'dut')->orWhere($qb->expr()->like('dut.name', $qb->expr()->literal($cycle_name)));
                $qb->leftJoin('t.user', 'userExecutor')
                    ->orWhere($qb->expr()->like('userExecutor.username', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('userExecutor.email', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('userExecutor.fullName', $qb->expr()->literal('%' . $cycle_name . '%')));

                $qb->leftJoin('t.suiteExecution', 'suite')
                    ->orWhere($qb->expr()->like('suite.summary', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.description', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.productVersion', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.jobName', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.buildTag', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.testingLevel', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.platform', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.chip', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.name', $qb->expr()->literal('%' . $cycle_name . '%')))
                    ->orWhere($qb->expr()->like('suite.uuid', $qb->expr()->literal('%' . $cycle_name . '%')));

                $qb->setParameter('search_str', $cycle_name_match);
                $cycle_name_search = $cycle_name;
                $str_len = mb_strlen($cycle_name_search);
                if ($str_len >= 3) {
                    $cycle_name_search = '%' . $cycle_name_search . '%';
                } elseif ($str_len < 3) {
                    $cycle_name_search .= '%';
                }

                $qb->setParameter('cycle_name', $cycle_name_search);
                $qb->setParameter('cycle_id', (int)$cycle_name);
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
            if ($setups !== null && \count($setups) > 0) {
                $qb->andWhere('t.setup IN (:setups)')
                    ->setParameter('setups', $setups);
                $enableSearch = True;
            }
            $enableSearch = True;
            if ($addOrder) {
                $qb->orderBy('t.id', 'DESC');
            }

            if ($enableSearch) {
                $query = $qb->getQuery();
                $sql = $query->getSQL();
                $tests = $query->execute();
                if (!$addOrder){
                    foreach($tests as $tmp_test) {
                        /** @var LogBookCycle $t_t */
                        $t_t = $tmp_test[0];
                        $t_t->setRate($tmp_test['rate']);
                        $new_tests[] = $t_t;
                    }
                } else {
                    $new_tests = $tests;
                }
            }
        }

        return $this->render('lbook/cycle/search.html.twig', array(
            //'tests' => $tests,
            'iterator' => $new_tests,
            'tests_count' => \count($new_tests),
            'sql' => $sql,
            'form' => $form->createView(),
        ));
    }

        /**
     * @Route("/searchjson", name="cycle_search_json", methods={"GET", "POST"})
     * @param Request $request
     * @param LogBookCycleRepository $cycleRepo
     * @param SuiteExecutionRepository $suitesRepo
     * @return Response
     */
    public function search_json(Request $request, LogBookCycleRepository $cycleRepo, SuiteExecutionRepository $suitesRepo): JsonResponse
    {
        $method = $request->getRealMethod();
        if ($method === 'GET') {
            $data = $request->query->all();
        } else {
            $data = json_decode($request->getContent(), true);
        }
        $cycle_name = $data['cycle_name'];
        if (array_key_exists('fromDate', $data)) {
            $fromDate = $data['fromDate'];
        } else {
            $fromDate = '';
        }
        if (array_key_exists('toDate', $data)) {
            $toDate = $data['toDate'];
        } else {
            $toDate = '';
        }
        if (array_key_exists('setups', $data)) {
            $setups_in = $data['setups'];
        } else {
            $setups_in = array();
        }

        $limit = 200;
        set_time_limit(30);
        $tests = $new_tests = $cycles_res = array();
        $setups = null;
        $sql = '';
        $leftDate = $rightDate = false;
        $startDate = $endDate = null;
        $DATE_TIME_TYPE = \Doctrine\DBAL\Types\Type::DATETIME;
        $cycles = new CycleSearch();

        $addOrder = true;
        $enableSearch = false;
        $setups = $setups_in;
        if (array_key_exists('limit', $data)) {
            $limit = (int)$data['limit'];
            if ($limit > 10000) {
                $limit = 500;
            }
        }
        $cycles->setLimit($limit);
        $qb = $cycleRepo->createQueryBuilder('t')
            ->where('t.disabled = 0')
            ->setMaxResults($cycles->getLimit());

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

        if ($cycle_name !== null && \mb_strlen($cycle_name) >= 2) {

            $qb->andWhere('MATCH_AGAINST(t.name, t.meta_data, :search_str) > 1 OR t.name LIKE :cycle_name OR t.id = :cycle_id OR t.uploadToken LIKE :cycle_name');
            $qb->addSelect('MATCH_AGAINST(t.name, t.meta_data, :search_str) as rate');
            $qb->orderBy('rate', 'DESC');
            $qb->addOrderBy('t.id', 'DESC');
            $addOrder = false;
//                }
            $cycle_name = trim($cycle_name);
            $cycle_name_match = str_replace('%', ' ', $cycle_name);
            $cycle_name_match = str_replace('?', ' ', $cycle_name_match);
            $cycle_name_match = str_replace('  ', ' ', $cycle_name_match);
            $cycle_name_match = str_replace(' ', ' +', $cycle_name_match);
            $cycle_name_match = str_replace(' ++', ' +', $cycle_name_match);
            $cycle_name_match = str_replace(' +-', ' -', $cycle_name_match);

            $qb->leftJoin('t.targetUploader', 'targetUploader')->orWhere($qb->expr()->like('targetUploader.name', $qb->expr()->literal($cycle_name)));
            $qb->leftJoin('t.controller', 'controller')->orWhere($qb->expr()->like('controller.name', $qb->expr()->literal($cycle_name)));
            $qb->leftJoin('t.dut', 'dut')->orWhere($qb->expr()->like('dut.name', $qb->expr()->literal($cycle_name)));
            $qb->leftJoin('t.user', 'userExecutor')
                ->orWhere($qb->expr()->like('userExecutor.username', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('userExecutor.email', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('userExecutor.fullName', $qb->expr()->literal('%' . $cycle_name . '%')));

            $qb->leftJoin('t.suiteExecution', 'suite')
                ->orWhere($qb->expr()->like('suite.summary', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.description', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.productVersion', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.jobName', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.buildTag', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.testingLevel', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.platform', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.chip', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.name', $qb->expr()->literal('%' . $cycle_name . '%')))
                ->orWhere($qb->expr()->like('suite.uuid', $qb->expr()->literal('%' . $cycle_name . '%')));

            $qb->setParameter('search_str', $cycle_name_match);
            $cycle_name_search = $cycle_name;
            $str_len = mb_strlen($cycle_name_search);
            if ($str_len >= 3) {
                $cycle_name_search = '%' . $cycle_name_search . '%';
            } elseif ($str_len < 3) {
                $cycle_name_search .= '%';
            }

            $qb->setParameter('cycle_name', $cycle_name_search);
            $qb->setParameter('cycle_id', (int)$cycle_name);
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
        if ($setups !== null && \count($setups) > 0) {
            $qb->andWhere('t.setup IN (:setups)')
                ->setParameter('setups', $setups);
            $enableSearch = True;
        }
        $enableSearch = True;
        if ($addOrder) {
            $qb->orderBy('t.id', 'DESC');
        }

        if ($enableSearch) {
            $query = $qb->getQuery();
            $sql = $query->getSQL();
            $tests = $query->execute();
            if (!$addOrder){
                foreach($tests as $tmp_test) {
                    /** @var LogBookCycle $t_t */
                    $t_t = $tmp_test[0];
                    $t_t->setRate($tmp_test['rate']);
                    $new_tests[] = $t_t;
                }
            } else {
                $new_tests = $tests;
            }
        }
        
        foreach($tests as $tmp_test) {
            /** @var LogBookCycle $t_t */
            $t_t = $tmp_test[0];
            $cycles_res[$t_t->getId()] = $t_t->toJson();
        }
        $fin_resp['count'] =  \count($new_tests);
        $fin_resp['cycles'] =  $cycles_res;
        $fin_resp['query'] = $data;
        $fin_resp['limit'] = $limit;
        $response =  new JsonResponse($fin_resp);
        return $response;
    }

    /**
     * Tests exporter to JSON file
     *
     * @Route("/export/{cycle}", name="cycle_test_exporter", methods={"GET"})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle $cycle
     * @return JsonResponse
     */
    public function export(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, LogBookCycle $cycle = null): JsonResponse
    {
        try {
            if ($cycle === null) {
                throw new \RuntimeException('');
            }

            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.cycle = :cycle')
                ->andWhere('t.disabled = :disabled')
                ->orderBy('t.executionOrder', 'ASC')
                //->setParameter('cycle', $cycle->getId());
                ->setParameters(['cycle'=> $cycle->getId(), 'disabled' => 0]);
            $paginator = $pagePaginator->paginate($qb, 1, 200000); //$this->show_tests_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator
            $fin_res = array();
            $iterator->rewind();
            $cycle_info = [];
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookTest $test */
                    $test = $iterator->current();
                    if ($test !== null) {
                        $ret_test = $test->toJsonExport();
                        $fin_res[] = $ret_test;
                    }
                    $iterator->next();
                }
            }
            if ($cycle !== null) {
                $cycle_info['id'] = $cycle->getId();
                $cycle_info['name'] = $cycle->getName();
                $cycle_info['build_project'] = $cycle->getBuild()->getName();
                $cycle_info['setup'] = $cycle->getSetup()->getName();
                $cycle_info['time_start'] = $cycle->getTimeStart()->getTimestamp();
                $cycle_info['time_end'] = $cycle->getTimeEnd()->getTimestamp();
                $cycle_info['period'] = $cycle->getPeriod();
                $cycle_info['run_time'] = $cycle->getTestsTimeSum();
                $cycle_info['tests_fail'] = $cycle->getTestsFail();
                $cycle_info['tests_error'] = $cycle->getTestsError();
                $cycle_info['tests_pass'] = $cycle->getTestsPass();
                $cycle_info['tests_na'] = $cycle->getTestsNa();
                $cycle_info['tests_unknown'] = $cycle->getTestsUnknown();
                $cycle_info['tests_warning'] = $cycle->getTestsWarning();
                $cycle_info['tests_total'] = $cycle->getTestsCount();
                $cycle_info['metadata'] = $cycle->getMetaData();
            }
            $fin_resp = [
                "tests" => $fin_res,
                "cycle" => $cycle_info
            ];
            $response =  new JsonResponse($fin_resp);
            // $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $cycle);
        }
    }

    /**
     * @param LogBookCycle $cycle
     * @return array
     */
    private function buildExportCycleArray(LogBookCycle $cycle, LoggerInterface $logger): array
    {
        $arr = [];
        try {
            $arr['id'] = $cycle->getId();
            $arr['name'] = $cycle->getName();
            $arr['build_project'] = $cycle->getBuild()->getName();
            $arr['setup'] = $cycle->getSetup()->getName();
            $arr['setup_id'] = $cycle->getSetup()->getId();
            $arr['time_start'] = $cycle->getTimeStart()->getTimestamp();
            $arr['time_end'] = $cycle->getTimeEnd()->getTimestamp();
            $arr['period'] = $cycle->getPeriod();
            $arr['run_time'] = $cycle->getTestsTimeSum();
            $arr['tests_fail'] = $cycle->getTestsFail();
            $arr['tests_error'] = $cycle->getTestsError();
            $arr['tests_pass'] = $cycle->getTestsPass();
            $arr['tests_na'] = $cycle->getTestsNa();
            $arr['tests_unknown'] = $cycle->getTestsUnknown();
            $arr['tests_warning'] = $cycle->getTestsWarning();
            $arr['tests_total'] = $cycle->getTestsCount();
            $arr['metadata'] = $cycle->getMetaData();
        } catch (\Throwable $ex) {
            $this->keep_critical_log('[buildExportCycleArray]', $logger, $ex);
        }
        return $arr;
    }

    private function keep_critical_log($msg, LoggerInterface $logger, \Throwable $ex): void
    {
        $logger->critical($msg . ':' . $ex->getMessage(), [
            $ex->getLine(),
            $ex->getTraceAsString()
        ]);
    }

    /**
     * Tests exporter to JSON file
     *
     * @Route("/multiexport", name="test_multi_exporter", methods={"GET", "POST"})
     * @param Request $request
     * @param LogBookCycleRepository $cycleRepo
     * @param LogBookTestRepository $testRepo
     * @param LoggerInterface $logger
     * @return Response
     */
    public function multiExport(Request $request, LogBookCycleRepository $cycleRepo, LogBookTestRepository $testRepo, LoggerInterface $logger): Response
    {
        $time_start = microtime(true);
        $cycles = [];
        $ret_cycle_arr = [];
        $cycle_ids = [];
        $cycles_requested = [];
        $uri = $request->getUri();
        $data['request'] = $request->request->all();
        $data['query'] = $request->query->all();
        $fin_res = array();
        $query_time = 0;
        try {

            $em = $this->getDoctrine()->getManager();
            if (count($data['query']) > 0) {
                // WORK with GET method
                $work_arr = $data['query'];
                $work_str_of_lists = $work_arr['cycles'];
                $work_list = explode(';', $work_str_of_lists);
                $cycles_requested = $work_list;
                if (count($work_list) > 0) {
                    foreach ($work_list as $cycle_id_str) {
                        try {
                            $cycle_id = intval($cycle_id_str);
                            if ($cycle_id > 0) {
                                /** @var LogBookCycle $cycle */
                                $cycle = $cycleRepo->findOneBy(['id' => $cycle_id]);
                                if ($cycle !== null) {
                                    $cycles[] = $cycle;
                                    $cycle_ids[] = $cycle_id;

                                    if (!array_key_exists($cycle_id, $ret_cycle_arr)) {
                                        $ret_cycle_arr[$cycle->getId()] = $cycle->toJson();
                                        // $ret_cycle_arr[$cycle->getId()] = $this->buildExportCycleArray($cycle, $logger);
                                        $tests = $cycle->getTests();
                                        try {
                                            foreach ($tests as $test) {
                                                try {
                                                    /** @var LogBookTest $test */
                                                    $fin_res[] = $test->toJsonExport();
                                                } catch (\Throwable $ex) {
                                                    $this->keep_critical_log('MULTI_EXPORT[IN_LOOP]', $logger, $ex);
                                                }
                                            }
                                        } catch (\Throwable $ex) {
                                            $this->keep_critical_log('MULTI_EXPORT[ON_LOOP]', $logger, $ex);
                                        }
                                    }
                                    $em->clear();
                                }
                            }
                        } catch (\Throwable $ex) {
                            $this->keep_critical_log('MULTI_EXPORT[CYCLE_LOOP]', $logger, $ex);
                        }
                    }
                }
            }
        } catch (\Throwable $ex) {
            $this->keep_critical_log('MULTI_EXPORT', $logger, $ex);
        }
        $time_end = microtime(true);
        $total_time = ($time_end - $time_start);
//        $time_end = microtime(true);
//        $loop_time = ($time_end - $time_start);
        $fin_resp = [
//            "QUERY_TIME" => round($query_time, 4),
//            "LOOP_TIME" => round($loop_time, 4),
            "TOTAL_TIME" => round($total_time, 4),
            "cycle_count" => count($cycle_ids),
            "cycle_requested" => count($cycles_requested),
            "cycle_ids" => $cycle_ids,
            "cycles" => $ret_cycle_arr,
            "tests" => $fin_res,
            "URL" => $uri
        ];

        $response = $this->json([]);
        $resp = json_encode($fin_resp);
        $response->setJson($resp);
        $response->headers->set('Content-Type', 'application/json');

        $file_name = 'DUMP';
        try {
            $datetime = new \DateTime();
            $file_name = 'DUMP-' . count($cycle_ids) . '.Tests-' . count($fin_res) . '_' .  $datetime->getTimestamp();
        } catch (\Throwable $ex) {

        }
        try {
            $file_name = $file_name . '.json';
        } catch (\Throwable $ex) {

        }
        $response->headers->set('Accept-Encoding', 'gzip,compress');
        $response->headers->set('Content-Disposition', 'attachment; filename="'. $file_name . '"');

        try {
            $fs = new \Symfony\Component\Filesystem\Filesystem();
            $fs->dumpFile('/var/www/lbook/downloads/' . $file_name, $resp);
        }
        catch(IOException $e) {}
        return $response;
    }

    /**
     * Displays a form to edit an existing cycle entity.
     *
     * @Route("/{id}/edit", name="cycle_edit", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookCycle $obj
     * @return RedirectResponse|Response
     * @throws LogicException
     * @throws AccessDeniedException
     * @throws \LogicException
     */
    public function edit(Request $request, LogBookCycle $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('edit', $obj->getSetup());
            $deleteForm = $this->createDeleteForm($obj);
            $editForm = $this->createForm(LogBookCycleType::class, $obj);
            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('cycle_edit', array('id' => $obj->getId()));
            }

            return $this->render('lbook/cycle/edit.html.twig', array(
                'cycle' => $obj,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $obj);
        }
    }


    /**
     *
     * @Route("/te/{testExecutionKey}", name="get_tests_by_jira_te", methods={"GET"})
     * @param string $testExecutionKey
     * @param LogBookTestRepository $testRepo
     * @return JsonResponse
     */
    public function jira(string $testExecutionKey, LogBookTestRepository $testRepo): ?JsonResponse
    {
        // PUBLISHER
        try {
            $key_len = mb_strlen($testExecutionKey);
            //$metadata_1 = '%s:14:"EXECUTION_SHOW";s:'. $key_len . ':"' .$testExecutionKey. '";%';
            $metadata_1 = '%s:'. $key_len . ':"' .$testExecutionKey. '"%';

            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.timeEnd > :period');

            $qb = $qb->leftJoin('t.newMetaData', 'newMetaData')->andWhere($qb->expr()->like('newMetaData.value', $qb->expr()->literal($metadata_1)))
                ->orderBy('t.executionOrder', 'ASC')
                ->setMaxResults(5000)
                ->setParameter('period', new \DateTime('-7 days'));
            $q = $qb->getQuery();
            $tests = $q->execute();

//            if (count($tests) < 1) {
//                $qb = $testRepo->createQueryBuilder('t')
//                    ->where('t.timeEnd > :period')
//                    ->andWhere('t.meta_data LIKE :metadata_1')
//                    ->setMaxResults(3000)
//                    ->orderBy('t.executionOrder', 'ASC')
//                    ->setParameter('metadata_1', $metadata_1)
//                    ->setParameter('period', new \DateTime('-7 days'));
//                $q = $qb->getQuery();
//                $tests = $q->execute();
//            }

            $final = array();
//            print (count($tests));
//            exit();
            /** @var LogBookTest $test */
            foreach ($tests as $test) {
                if ($test->getTestKey() !== null && $test->getTestKey() !== '') {
                    $test_dict['testKey'] = $test->getTestKey();
                } else {
                    if (array_key_exists('TEST_CASE_SHOW', $test->getMetaData())){
                        $test_dict['testKey'] = $test->getMetaData()['TEST_CASE_SHOW'];
                    } else {
                        // TODO HERE issue
                    }
                }

                $test_dict['start'] = $test->getTimeStart()->format(\DateTime::ATOM);
                $test_dict['finish'] = $test->getTimeEnd()->format(\DateTime::ATOM);
                $test_dict['comment'] = '';
                $test_dict['status'] = $test->getVerdict()->getName();
                $final[] = $test_dict;
            }
            $fin_res['testExecutionKey'] = $testExecutionKey;
            $fin_res['tests'] = $final;
            try{
                $fin_res['query_dql'] = $q->getDQL();
                $fin_res['query_sql'] = $q->getSQL();
                $fin_res['tests_total'] = count($tests);
                $tmp_arr = $q->getParameters()->toArray();
                $fin_res['query_params'] = [];
                /**
                 * @var  $key
                 * @var \Doctrine\ORM\Query\Parameter $val
                 */
                foreach ($tmp_arr as $key => $val) {
                    $fin_res['query_params'][$val->getName()] = $val->getValue();
                }
            } catch (\Throwable $ex) {}
            return new JsonResponse($fin_res);
        } catch (\Throwable $ex) {
            $response = $this->json([]);
            $js = json_encode('["'. $ex->getMessage() .'"]');
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }

    /**
     * Lists all cycle entities.
     *
     * @Route("/page/{page}", name="cycle_index", methods={"GET"})
     * @Template(template="lbook/cycle/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return array
     * @throws \Exception
     */
    public function index(PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo, $page = 1): array
    {
        $query = $cycleRepo->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();
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

    protected function getLogsFolder(LogBookCycle $cycle = null): string
    {
        if ($cycle === null) {
            return '';
        }
        $setup = $cycle->getSetup();
        $tmp = '%s/%d/%d/';
        return sprintf($tmp,  LogBookUploaderController::getUploadPath(), $setup->getId(), $cycle->getId());
    }

    /**
     * Download full cycle as archive
     *
     * @Route("/{id}/suite/{suite}/download", name="cycle_suite_download", methods={"GET"})
     * @param LogBookCycle|null $cycle
     * @param SuiteExecution|null $suite
     * @return Response
     */
    public function downloadArchiveForSuite(LogBookCycle $cycle = null, SuiteExecution $suite = null): Response
    {
        return $this->downloadArchive($cycle, $suite);
    }

    /**
     * Download full cycle as archive
     *
     * @Route("/{id}/download", name="cycle_download", methods={"GET"})
     * @param LogBookCycle|null $cycle
     * @param SuiteExecution|null $suite
     * @return Response
     */
    public function downloadArchive(LogBookCycle $cycle = null, SuiteExecution $suite = null): Response
    {
        try {
            if (!$cycle) {
                throw new \RuntimeException('');
            }
            $fileSystem = new Filesystem();
            $path = $this->getLogsFolder($cycle);

            $zip = new \ZipArchive();
            if ($suite !== null) {
                $zipName = sprintf('%d__%d__%d__%s_%s_%s.zip', $cycle->getSetup()->getId(), $cycle->getId(), $suite->getId(), $suite->getName(), $suite->getUuid(), $suite->getBuildType());
            } else {
                $zipName = sprintf('%d__%d__%s.zip', $cycle->getSetup()->getId(), $cycle->getId(), $cycle->getName());
            }
            $zipName = preg_replace('/[^a-zA-Z0-9\-\_\.\(\)\s]/', '', $zipName);

            $zip->open($zipName,  \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            /** @var LogBookTest $test */
            foreach ($cycle->getTests() as $test) {
                $log_path = $path . $test->getLogFile();
                if ($fileSystem->exists($log_path)) {
                    if ($suite === null || ($suite !== null && $suite->getTests()->contains($test))) {
                        $fixedFileName = str_replace(array('/', '\\'), '_', $test->getName());
                        $newFileName = $test->getExecutionOrder() . '__' . $fixedFileName . '.txt';
                        $zip->addFromString(basename($newFileName), file_get_contents($log_path));
                    }
                }
            }

            $zip->close();
            $response = new Response(file_get_contents($zipName));
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            $response->headers->set('Content-length', filesize($zipName));
            try {
                $em = $this->getDoctrine()->getManager();
                $cycle->increaseDownloads();
                $em->persist($cycle);
                $em->flush();
            } catch (\Exception $ex) {

            }
            return $response;
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $cycle);
        }
    }

    /**
     * Lists all cycle entities.
     *
     * @Route("/", name="cycle_index_first", methods={"GET"})
     * @Template(template="lbook/cycle/index.html.twig")
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return array
     * @throws \Exception
     */
    public function indexFirst(PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo): array
    {
        return $this->index($pagePaginator, $cycleRepo, 1);
    }

    /**
     * Creates a new cycle entity.
     *
     * @Route("/new", name="cycle_new", methods={"GET|POST"})
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws \LogicException
     * @throws \Exception
     */
    public function new(Request $request)
    {
        $obj = new LogBookCycle();
        $form = $this->createForm(LogBookCycleType::class, $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('cycle_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/cycle/new.html.twig', array(
            'cycle' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a cycle entity with paginator.
     *
     * @Route("/json/{id}", name="cycle_tests_json", methods={"GET"})
     * @param LogBookCycle $cycle
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @return JsonResponse
     */
    public function cycleTestsJson(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, LogBookCycle $cycle): ?JsonResponse
    {
        try {
            if (!$cycle) {
                throw new \RuntimeException('');
            }

            $qb = $testRepo->createQueryBuilder('t')
                // ->select('t')
                ->addSelect('v.name as verdict')
                ->addSelect('tt.name as testType')
                //->addSelect('v.name ')
                //  ->from('App:LogBookVerdict', 'ver')
                ->innerJoin('App:LogBookVerdict', 'v', 'WITH', 'v.id = t.verdict')
                ->innerJoin('App:LogBookTestType', 'tt', 'WITH', 't.testType = tt.id')
                //  ->leftJoin('t.verdict', 'v')
                ->where('t.cycle = :cycle')
                ->andWhere('t.disabled = :disabled');
//            $qb->leftJoin('App:LogBookTestMD', 'p', 'WITH', 't.newMetaData = p.id');

            $qb->orderBy('t.executionOrder', 'ASC')
                ->setParameters(['cycle'=> $cycle->getId(), 'disabled' => 0]);

            $q = $qb->getQuery();
            $sql = $q->getSQL();
            $encoder = new JsonEncoder();
            $normalizer = new ObjectNormalizer();

//            //$normalizer->setCircularReferenceLimit(0);
//            $dateTimeToStr = function ($dateTime) {
//                return $dateTime instanceof \DateTime ? $dateTime->format(\DateTime::ATOM) : ''; //'d/m/Y H:i:s'
//            };
//            $tests = function ($test) {
//                return $test instanceof LogBookTest ? $test->getName() : ''; //'d/m/Y H:i:s'
//            };
//            $verdicts = function ($verdict) {
//                return $verdict instanceof LogBookVerdict ? $verdict->getName() : ''; //'d/m/Y H:i:s'
//            };
//            $logs = function ($log) {
//                return  ''; //'d/m/Y H:i:s'
//            };
////          $owner_callback = function ($owner) {
////              return $owner instanceof LogBookUser ? $owner->getUsername() : '';
////          };
//            $counter_callback = function ($obj) {
//                return $obj instanceof Collection ? \count($obj) : 0;
//            };
//            $normalizer->setCallbacks([
////                'id' => $counter_callback,
////                'name' => $owner_callback,
//                'log' => $logs,
//                'verdict' => $verdicts,
//                'test' => $tests,
//                'timeStart' => $dateTimeToStr,
//                'timeEnd' => $dateTimeToStr
//            ]);
            $serializer = new Serializer(array($normalizer), array($encoder));

            $paginator = $pagePaginator->paginate($qb, 1, $this->show_tests_size*10);
            //$paginator->setUseOutputWalkers(false);
            //$res = $paginator->getQuery()->execute(null,Query::HYDRATE_ARRAY);
            $res = $paginator->getQuery()->getResult(Query::HYDRATE_ARRAY);
//            $additional_cols = array();
//            $additional_opt_cols = array();

            foreach ($res as $key => $val) {
                $test = $val[0];
                $verdict = $val['verdict'];
                $test['verdict'] = $verdict;
                $test['TEST_TYPE'] = $val['testType'];
                $val = $test;
                //$val['timeStart'] = $val['timeStart']->format('H:i:s');
                $val['timeStart'] = $val['timeStart']->format(\DateTime::ATOM);
                //$val['timeEnd'] = $val['timeEnd']->format('H:i:s');
                $val['timeEnd'] = $val['timeEnd']->format(\DateTime::ATOM);
                unset($val['disabled'], $val['logFile'], $val['dutUpTimeStart'], $val['dutUpTimeEnd'], $val['forDelete']);
                unset($val['meta_data']);
                $res[$key] = $val;
            }
            $fin_res['total'] = $paginator->count();
            $fin_res['rows'] = $res;
            return new JsonResponse($fin_res);
        } catch (\Throwable $ex) {
            $response = $this->json([]);
            $js = json_encode('["'. $ex->getMessage() .'"]');
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }

    /**
     * @Route("/suite/keep/{cycle}/{weeks}", name="cycle_keep", methods={"GET"})
     * @param LogBookCycle $cycle
     * @param int $weeks
     * @return RedirectResponse|Response
     */
    public function keepCycle(LogBookCycle $cycle = null, int $weeks=12)
    {
        try {
            if (!$cycle) {
                throw new \RuntimeException('');
            }
            if ($weeks > 100) {
                $weeks = 20;
            }
            if ($weeks < 1) {
                $weeks = 3;
            }
            if ($cycle->getDeleteAt() > new \DateTime('+' . $weeks . ' weeks')) {

            } else {
                $em = $this->getDoctrine()->getManager();
                $cycle->setDeleteAt(new \DateTime('+' . $weeks . ' weeks'));
                $em->persist($cycle);
                $em->flush();
            }

            return $this->redirectToRoute('cycle_show_first', ['id' => $cycle->getId()]);
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $cycle);
        }

    }

    /**
     * @Route("/suiteid/{suite}", name="cycle_no_suiteid_show_first", methods={"GET", "POST"})
     * @Route("/suite/{cycle}/{suite}", name="cycle_suite_show_first", methods={"GET"})
     * @Route("/suite/{cycle}/{suite}/{maxSize}", name="cycle_suite_show_size", methods={"GET"}, defaults={"maxSize"=""})
     * @Route("/suite/{cycle}/{suite}/{maxSize}/{page}", name="cycle_suite_show_page", methods={"GET"}, defaults={"page"=1, "maxSize"=1000})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle $cycle
     * @param SuiteExecution|null $suite
     * @param null $page
     * @param int $maxSize
     * @return Response
     */
    public function showSuiteFirstPage(TestFilterApplyRepository $app_filters, PagePaginator $pagePaginator,
                                       LogBookTestRepository $testRepo, LogBookCycle $cycle = null,
                                       SuiteExecution $suite = null, $page = null, $maxSize = null): ?Response
    {
        if ($cycle === null && $suite !== null) {
            $cycle = $suite->getCycle();
        }
        if ($page === null) {
            $page = 1;
        }
        if ($maxSize === null || $maxSize == "" || $maxSize == "1") {
            $maxSize = $this->show_tests_size;
        }
        $page = (int)$page;
        $maxSize = (int)$maxSize;
        return $this->show($app_filters, $pagePaginator, $testRepo, $cycle, $suite, $page, false, $maxSize);
    }

    /**
     * @Route("/{id}", name="cycle_show_first", methods={"GET"})
     * @Route("/{id}/{maxSize}", name="cycle_show_size", methods={"GET"}, defaults={"maxSize"=""})
     * @Route("/{id}/{maxSize}/{page}", name="cycle_show_page", methods={"GET"}, defaults={"page"=1, "maxSize"=5000})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle $cycle
     * @param int $maxSize
     * @return Response
     */
    public function showFirstPage(TestFilterApplyRepository $app_filters, PagePaginator $pagePaginator,
                                  LogBookTestRepository $testRepo, LogBookCycle $cycle = null, $page = null,
                                  $maxSize = null): ?Response
    {
        if ($page === null) {
            $page = 1;
        }
        if ($maxSize === null || $maxSize == "" || $maxSize == "1") {
            $maxSize = $this->show_tests_size;
        }
        $page = (int)$page;
        $maxSize = (int)$maxSize;
        return $this->show($app_filters, $pagePaginator, $testRepo, $cycle, null, $page, false, $maxSize);
    }

    /**
     * Finds and displays a cycle entity with paginator.
     *
     * @Route("/{id}/{maxSize<\d+>?1}/{page<\d+>?1}/use_json/{forJson}", name="cycle_show", methods={"GET"})
     * @param TestFilterApplyRepository $app_filters
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle|null $cycle
     * @param SuiteExecution|null $suite
     * @param null $page
     * @param bool $forJson if True the JSON table for tests will be used
     * @param null $maxSize
     * @return Response
     */
    public function show(TestFilterApplyRepository $app_filters, PagePaginator $pagePaginator,
                         LogBookTestRepository $testRepo, LogBookCycle $cycle = null, SuiteExecution $suite = null,
                         $page = null, $forJson=false, $maxSize=null, $showOnlyTests=false): ?Response
    {
        $suiteMode = false;
        if ($page === null) {
            $page = 1;
        }
        if ($maxSize === null) {
            $maxSize = $this->show_tests_size;
        }
        try {

            if ($cycle === null && $suite !== null) {
                $cycle = $suite->getCycle();
            }
            if ($cycle === null) {
                throw new \RuntimeException('');
            }

            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.cycle = :cycle')
//                ->addSelect('i.name as name, i.path as testPath')
                ->andWhere('t.disabled = :disabled')
                ->orderBy('t.executionOrder', 'ASC');
//            $qb->leftJoin('App:LogBookTestInfo', 'i', 'WITH', 't.testInfo = i.id');

            $qb = $qb->setParameters(['cycle'=> $cycle->getId(), 'disabled' => 0]);
            if ($suite !== null) {
                $qb->andWhere('t.suite_execution = :suite')
                    ->setParameter('suite', $suite->getId());
                $suiteMode = true;
            }

            $em = $this->getDoctrine()->getManager();
            $types = ['PRE_CYCLE', 'PRE_TEST_FLOW', 'POST_CYCLE', 'POST_TEST_FLOW', 'HEALTH_CHECK', 'RECOVERY'];
            /** @var Query $query */
            $query = $em->createQuery("SELECT t FROM App\LogBookTest t");// WHERE t.testType NOT IN ('PRE_CYCLE', 'PRE_TEST_FLOW', 'POST_CYCLE', 'POST_TEST_FLOW', 'HEALTH_CHECK', 'RECOVERY')");
            // ->where('t.testType IN (:types)')
            // ->setParameter('types', $types);
            $query->setDQL($qb->getDQL());
            $query->setFetchMode(LogBookTest::class, "testInfo", ClassMetadataInfo::FETCH_EAGER);
            $query->setFetchMode(LogBookTest::class, "verdict", ClassMetadataInfo::FETCH_EAGER);
            $query->setFetchMode(LogBookTest::class, "testType", ClassMetadataInfo::FETCH_EAGER);
            $query->setFetchMode(LogBookTest::class, "suite_execution", ClassMetadataInfo::FETCH_EAGER);
            $query->setFetchMode(LogBookTest::class, "failDesc", ClassMetadataInfo::FETCH_EAGER);
            $query->setParameters($qb->getParameters());
            $query->setMaxResults($qb->getMaxResults());
//            $res = $query->execute();
            $paginator = $pagePaginator->paginate($query, $page, $maxSize); //$this->show_tests_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator
            $maxPages = ceil($totalPosts / $maxSize); //$this->show_tests_size);
            $thisPage = $page;
            $disable_uptime = false;
            $deleteForm = $this->createDeleteForm($cycle);
            $nul_found = 0;
            $additional_cols = $additional_opt_cols = $suites = $failed_tests = $errors = array();
            $iterator->rewind();
            //$res = $iterator->getArrayCopy();
            $suites = $cycle->getSuiteExecution();
            $suites_pass_rate = $suites_tests_pass = $suites_tests_aborted = $suites_tests_total = $suites_tests_wip = 0;
            if (!$suiteMode) {
                $pr_sum = 0;
                /** @var SuiteExecution $suite */
                foreach ($suites as $suite) {
                    $pr_sum += $suite->getPassRate();
                    $suites_tests_pass += $suite->getPassCount();
                    $suites_tests_total += $suite->getTestsCountEnabled();

                    if ($suite->getClosed() ) {
                        $suites_tests_aborted += ($suite->getTestsCountEnabled() - $suite->getTotalExecutedTests());
                    } else {
                        $suites_tests_wip += ($suite->getTestsCountEnabled() - $suite->getTotalExecutedTests());
                    }

                }
                $suite_count = count($suites);

                if ($suite_count > 0) {
                    $suites_pass_rate = round($pr_sum/$suite_count, 1);
                }
            }
            //print_r('<br><br><br>');
            $ret_tests = [];
            $errors_found = false;
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookTest $test */
                    $obj = $iterator->current();
                    $test = $obj;

//                    $test = $obj[0];
//                    $testName = $obj['name'];
//                    $testPath = $obj['testPath'];
                    //$testFailDescription = $obj['testFailDesc'];
                    if ($test instanceof \App\Entity\LogBookTest) {
                        if ($showOnlyTests === true && in_array($test->getTestType()->getName(), $types)) {
                            //print_r('Remove ' . $test->getTestType()->getName() . "-<br>");
                            $iterator->next();
                            continue;
                             
                        } else {
                            
                        }
//                        if ($testName !== null) {
//                            $test->setName($testName);
//                        }
//                        if ($testPath !== null) {
//                            $test->setTestPath($testPath);
//                        }
                        //$test->setTestFailDescription($testFailDescription);
                        $ret_tests[] = $test;

                        if ($test !== null && $test->getVerdict() !== null && $test->getVerdict()->getName() !== 'PASS') {
                            $errors_found = true;
                            $failed_tests[] = $test;
                        }

                        if ($test !== null) {
                            /**
                             * Search for metadata with _SHOW postfix, if exist that column will be shown
                             * @var array $md
                             */
//                        $suite = $test->getSuiteExecution();
//                        if ($suite !== null && !in_array($suite, $suites, true)) {
//                            $suites[] = $suite;
//                        }

                            $md = $test->getMetaData();
                            if (\count($md) > 0) {
                                foreach ($md as $key => $value) {
                                    if ($forJson) {
                                        $tmp_key = $key;
                                        if (!\in_array($tmp_key, $additional_cols, true)) {
                                            $additional_cols[] = $tmp_key;
                                        }
                                    } else {
                                        if ($this->endsWith($key, '_SHOW') && !\in_array($key, $additional_cols, true)) {
                                            $additional_cols[] = $key;
                                        } else if ($this->endsWith($key, '_SHOW_OPT') && !\in_array($key, $additional_opt_cols, true)) {
                                            $additional_opt_cols[] = $key;
                                        }
                                    }
                                }
                            }
                            /** Search for uptime if show or not */
                            if ($test->getDutUpTimeStart() === 0 && $test->getDutUpTimeEnd() === 0) {
                                $nul_found++;
                            }
                        }
                        $iterator->next();
                    }

                }
            }

            if ($nul_found === $totalPosts) {
                $disable_uptime = true;
            }
            if ($forJson) {
                $iterator = null;
            }
            $qb_f = $app_filters->createQueryBuilder('f')
                ->where('f.suiteExecution IN (:suites)')
                ->setParameter('suites', $suites);
            $q = $qb_f->getQuery();
            $filters = $q->execute();
            $ret_arr = array(
                'cycle'                 => $cycle,
                'size'                  => $totalPosts,
                'maxPages'              => $maxPages,
                'thisPage'              => $thisPage,
                'iterator'              => $ret_tests,
                'disabled_uptime'       => $disable_uptime,
                'delete_form'           => $deleteForm->createView(),
                'additional_cols'       => $additional_cols,
                'additional_opt_cols'   => $additional_opt_cols,
                'tests_in_json'         => $forJson,
                'suites'                => $suites,
                'suiteMode'             => $suiteMode,
                'suite'                 => $suite,
                'errors_found'          => $errors_found,
                'failed_tests'          => $failed_tests,
                'suites_pass_rate' => $suites_pass_rate,
                'suites_tests_pass' => $suites_tests_pass,
                'suites_tests_aborted' => $suites_tests_aborted,
                'suites_tests_total' => $suites_tests_total,
                'suites_tests_wip' => $suites_tests_wip,
                'applied_filters' => $filters

//                'errors'                => $errors,
            );

            return $this->render('lbook/cycle/show.full.html.twig', $ret_arr);

        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $cycle);
        }
    }

    /**
     * Finds and displays a cycle entity with paginator.
     *
     * @Route("/ajax/{id}", name="cycle_show_ajax", methods={"GET", "POST"})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @param LogBookCycle $cycle
     * @return Response
     */
    public function showAjax(PagePaginator $pagePaginator, LogBookTestRepository $testRepo, LogBookCycle $cycle = null): ?Response
    {
        try {
            if (!$cycle) {
                throw new \RuntimeException('');
            }

            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.cycle = :cycle')
                ->andWhere('t.disabled = :disabled')
                ->orderBy('t.executionOrder', 'ASC')
                //->setParameter('cycle', $cycle->getId());
                ->setParameters(['cycle'=> $cycle->getId(), 'disabled' => 0]);
            $paginator = $pagePaginator->paginate($qb, 1, $this->show_tests_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $maxPages = ceil($totalPosts / $this->show_tests_size);
            $thisPage = 1;
            $disable_uptime = false;
            $deleteForm = $this->createDeleteForm($cycle);
            $nul_found = 0;

            $additional_cols = array();
            $additional_opt_cols = array();
            $iterator->rewind();
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookTest $test */
                    $test = $iterator->current();
                    if ($test !== null) {
                        /**
                         * Search for metadata with _SHOW postfix, if exist that column will be shown
                         * @var array $md
                         */
                        $md = $test->getMetaData();
                        if (\count($md) > 0) {
                            foreach ($md as $key => $value) {
                                if ($this->endsWith($key, '_SHOW') && !\in_array($key, $additional_cols, true)) {
                                    $additional_cols[] = $key;
                                } else if ($this->endsWith($key, '_SHOW_OPT') && !\in_array($key, $additional_opt_cols, true)) {
                                    $additional_opt_cols[] = $key;
                                }

                            }
                        }
                        /** Search for uptime if show or not */
                        if ($test->getDutUpTimeStart() === 0 && $test->getDutUpTimeEnd() === 0) {
                            $nul_found++;
                        }
                    }

                    $iterator->next();
                }
            }

            if ($nul_found === $totalPosts) {
                $disable_uptime = true;
            }

            $ret_arr = array(
                'cycle'                 => $cycle,
                'size'                  => $totalPosts,
                'maxPages'              => $maxPages,
                'thisPage'              => $thisPage,
                'iterator'              => $iterator,
                'disabled_uptime'       => $disable_uptime,
                'delete_form'           => $deleteForm->createView(),
                'additional_cols'       => $additional_cols,
                'additional_opt_cols'   => $additional_opt_cols,
            );

            return $this->render('lbook/cycle/show.ajax.html.twig', $ret_arr);

        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $cycle);
        }
    }

    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    private function endsWith($haystack, $needle): bool
    {
        $length = mb_strlen($needle);

        return $length === 0 || (substr($haystack, -$length) === $needle);
    }

    /**
     * @Route("/cycle_not_found/{cycle}", name="cycle_not_found", methods={"GET", "POST"})
     * @param \Throwable $ex
     * @param LogBookCycle|null $cycle
     * @return Response
     */
    protected function cycleNotFound(\Throwable $ex, LogBookCycle $cycle = null): ?Response
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
        } catch (\Exception $ex) {
        }

        if ($cycle === null) {
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Cycle with provided ID:[%s] not found', $possibleId),
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
     * Deletes a setup entity.
     *
     * @Route("/{id}", name="cycle_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookCycle $obj
     * @return RedirectResponse|Response
     * @throws LogicException
     * @throws \LogicException
     * @throws AccessDeniedException
     */
    public function delete(Request $request, LogBookCycle $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('delete', $obj->getSetup());
            $form = $this->createDeleteForm($obj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                /** @var LogBookCycleRepository $cycleRepo */
                $cycleRepo = $em->getRepository('App:LogBookCycle');
                $cycleRepo->delete($obj);
            }

            return $this->redirectToRoute('cycle_index_first');
        } catch (\Throwable $ex) {
            return $this->cycleNotFound($ex, $obj);
        }
    }

    /**
     * Creates a form to delete a setup entity.
     *
     * @param LogBookCycle $obj The cycle entity
     *
     * @return FormInterface | Response
     */
    private function createDeleteForm(LogBookCycle $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('cycle_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }

}
