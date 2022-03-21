<?php

namespace App\Controller;

use App\Entity\LogBookEmail;
use App\Entity\LogBookSuiteInfo;
use App\Entity\SuiteExecution;
use App\Entity\SuiteExecutionSearch;
use App\Form\SuiteExecutionSearchType;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookSuiteInfoRepository;
use App\Service\PagePaginator;
use App\Repository\SuiteExecutionRepository;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogBookSuiteExecutionController
 * @package App\Controller
 * @Route("suites")
 */
class LogBookSuiteExecutionController extends AbstractController
{
    protected $index_size = 500;


    /**
     * @Route("/search", name="suite_search", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookCycleRepository $cycleRepo
     * @return Response
     */
    public function search(Request $request, LogBookCycleRepository $cycleRepo, SuiteExecutionRepository $suitesRepo): Response
    {
        $new_tests = array();
        $sql = '';
        $leftDate = $rightDate = false;
        $startDate = $endDate = null;
        $suiteSearch = new SuiteExecutionSearch();
        $form = $this->get('form.factory')->createNamed('frmss', SuiteExecutionSearchType::class, $suiteSearch);

        //$form = $this->createForm(SuiteExecutionSearchType::class, $suiteSearch, array());

        try {
            $form->handleRequest($request);
        } catch (\Exception $ex) {}

        $addOrder = true;
        if ($request->isMethod('POST')) {
            $post = $request->request->get($form->getName());
        }
        if ($request->isMethod('GET')) {
            $post = $request->query->get($form->getName());
            //$form->submit($post);
        }


        if ($post !== null) {
            $enableSearch = false;
            if (array_key_exists('setup', $post)) {
                $suiteSearch->setSetup($post['setup']['name']);
            }
            if (array_key_exists('limit', $post)) {
                $limit = (int)$post['limit'];
                $suiteSearch->setLimit($limit);
            }
            if (array_key_exists('testingLevel', $post)) {
                $suiteSearch->setTestingLevel($post['testingLevel']);
            }
            if (array_key_exists('publish', $post)) {
                $suiteSearch->setPublish($post['publish']);
            }
            if (array_key_exists('platforms', $post)) {
                $suiteSearch->setPlatforms($post['platforms']);
            }
            if (array_key_exists('chips', $post)) {
                $suiteSearch->setChips($post['chips']);
            }
            if (array_key_exists('components', $post)) {
                $suiteSearch->setComponents($post['components']);
            }
            if (array_key_exists('jobNames', $post)) {
                $suiteSearch->setJobNames($post['jobNames']);
            }
            if (array_key_exists('modes', $post)) {
                $suiteSearch->setModes($post['modes']);
            }
            $name = $post['name'];
            $fromDate = $post['fromDate'];
            $toDate = $post['toDate'];

            $qb = $suitesRepo->createQueryBuilder('s')
                ->setMaxResults($suiteSearch->getLimit());

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

            if ($name !== null && \mb_strlen($name) >= 2) {

                $qb->andWhere('MATCH_AGAINST(s.name, s.productVersion, s.platform, s.chip, s.summary, s.jobName, s.buildTag, :search_str) > 1 OR s.name LIKE :cycle_name OR s.id = :cycle_id OR s.uuid LIKE :cycle_name_src OR s.components LIKE :cycle_name_src');
                $qb->addSelect('MATCH_AGAINST(s.name, s.productVersion, s.platform, s.chip, s.summary, s.jobName, s.buildTag, :search_str) as rate');
                $qb->orderBy('rate', 'DESC');
                $qb->addOrderBy('s.id', 'DESC');
                $addOrder = false;
//                }
                $name = trim($name);
                $cycle_name_match = str_replace('%', ' ', $name);
                $cycle_name_match = str_replace('?', ' ', $cycle_name_match);
                $cycle_name_match = str_replace('  ', ' ', $cycle_name_match);
                $cycle_name_match = str_replace(' ', ' +', $cycle_name_match);
                $cycle_name_match = str_replace(' ++', ' +', $cycle_name_match);
                $cycle_name_match = str_replace(' +-', ' -', $cycle_name_match);

                $qb->setParameter('search_str', $cycle_name_match);
                $cycle_name_search = $name;
                $str_len = mb_strlen($cycle_name_search);
                if ($str_len >= 3) {
                    $cycle_name_search = '%' . $cycle_name_search . '%';
                } elseif ($str_len < 3) {
                    $cycle_name_search .= '%';
                }

                $qb->setParameter('cycle_name', $cycle_name_search);
                $qb->setParameter('cycle_id', (int)$name);
                $qb->setParameter('cycle_name_src', $name);

            }
            if (count($suiteSearch->getTestingLevel())) {
                $qb->andWhere('s.testingLevel IN (:testingLevels)')
                    ->setParameter('testingLevels', $suiteSearch->getTestingLevel());
            }
            if (count($suiteSearch->getPublish())) {
                $qb->andWhere('s.publish IN (:publish)')
                    ->setParameter('publish',  $suiteSearch->getPublish());
            }
            if (count($suiteSearch->getPlatforms())) {
                $qb->andWhere('s.platform IN (:platforms)')
                    ->setParameter('platforms',  $suiteSearch->getPlatforms());
            }
            if (count($suiteSearch->getChips())) {
                $qb->andWhere('s.chip IN (:chips)')
                    ->setParameter('chips',  $suiteSearch->getChips());
            }
            if (count($suiteSearch->getComponents())) {
                $qb->andWhere('s.components IN (:components)')
                    ->setParameter('components',  $suiteSearch->getComponents());
            }
            if (count($suiteSearch->getJobNames())) {
                $qb->andWhere('s.jobName IN (:jobNames)')
                    ->setParameter('jobNames',  $suiteSearch->getJobNames());
            }
            if (count($suiteSearch->getModes())) {
                $qb->andWhere('s.packageMode IN (:modes)')
                    ->setParameter('modes',  $suiteSearch->getModes());
            }
            if ($leftDate === true && $rightDate === true) {
                $qb->andWhere('s.startedAt BETWEEN :fromDate AND :toDate')
                    ->setParameter('fromDate', $startDate, Type::DATETIME)
                    ->setParameter( 'toDate', $endDate, Type::DATETIME);
            } else if ($leftDate === true) {
                $qb->andWhere('s.startedAt >= :fromDate')
                    ->setParameter('fromDate', $startDate, Type::DATETIME);
            } else if ($rightDate === true) {
                $qb->andWhere('s.finishedAt <= :endDate')
                    ->setParameter('endDate', $endDate, Type::DATETIME);
            }

            if ($suiteSearch->getSetup() !== null && count($suiteSearch->getSetup())) {
                $qb->leftJoin('s.cycle', 'cycle')
                    ->andWhere('cycle.setup  IN (:setups)')
                    ->setParameter('setups',  $suiteSearch->getSetup());
            }

            $enableSearch = True;
            if ($addOrder) {
                $qb->orderBy('s.id', 'DESC');
            }

            if ($enableSearch) {
                $query = $qb->getQuery();
                $sql = $query->getSQL();
                $tests = $query->execute();
                if (!$addOrder){
                    foreach($tests as $tmp_test) {
                        /** @var SuiteExecution $t_t */
                        $t_t = $tmp_test[0];
                        $t_t->setRate($tmp_test['rate']);
                        $new_tests[] = $t_t;
                    }
                } else {
                    $new_tests = $tests;
                }
            }
        }
        return $this->render('lbook/suite/search.html.twig', array(
            'iterator' => $new_tests,
            'tests_count' => \count($new_tests),
            'sql' => $sql,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/", name="suite_index")
     * @Route("/{page}", name="suite_index")
     * @Template(template="lbook/suite/index.html.twig")
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @param int $page
     * @return array
     * @throws Exception
     */
    public function index(PagePaginator $pagePaginator, SuiteExecutionRepository $suites, int $page = 1): array
    {
        $query = $suites->createQueryBuilder('suite_execution')
//            ->where('suite_execution.disabled = 0')
            ->orderBy('suite_execution.updatedAt', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
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


    /**
     * @Route("/calculate/{days}", name="suite_calculate_api", methods="GET|POST")
     * @Template(template="lbook/suite/calculate.html.twig")
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @param int $days
     * @return array
     * @throws Exception
     */
    public function calculate_api(PagePaginator $pagePaginator, SuiteExecutionRepository $suites, int $days, ManagerRegistry $doctrine): array
    {
        if ($days > 30) {
            $days = 1;
        }
        $orders = ['ASC', 'DESC'];
        $rows = ['id', 'testsCount', 'chip', 'testingLevel', 'publish', 'failCount', 'closed', 'passRate'];
        $needed_row = 'suite_execution.'. $rows[array_rand($rows)];
        $query = $suites->createQueryBuilder('suite_execution')
            ->orderBy($needed_row , $orders[array_rand($orders)])
            ->andWhere('suite_execution.startedAt >= :started')
        ->setParameter('started', new \DateTime('-'. $days. ' days'), Type::DATETIME);

        $paginator = $pagePaginator->paginate($query, 1, 5000);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();

        $iterator->rewind();
        $em = $doctrine->getManager();
        $output = [];
        $start = microtime(true);
        $persisted = 0;
        $suitePersisted = [];
        $iteratorSize = $iterator->count();
        try {
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var SuiteExecution $suite */
                    $suite = $iterator->current();
                    if ($suite !== null) {
                        $suite->calculateStatistic();
                        $em->persist($suite);
                        $suitePersisted[] = $suite;
                        $persisted++;
                        //$output[] = 'ID: ' . $suite->getId() . ' calculated';
                    }
                    $iterator->next();
                    if ($persisted > 100) {
                        $em->flush();
                        $persisted = 0;
                        foreach ($suitePersisted as $tmp_suite) {
                            $em->clear($tmp_suite);
                        }
                    }
                }
            }
        } catch (\Throwable $ex) { }
        $time_elapsed_secs = microtime(true) - $start;
        $start = microtime(true);
        $em->flush();
        $flush_time_elapsed_secs = microtime(true) - $start;
        return array(
            'output'                        => $output,
            'size'                          => $totalPosts,
            'iteratorSize'                  => $iteratorSize,
            'time_elapsed_secs'             => $time_elapsed_secs,
            'flush_time_elapsed_secs'       => $flush_time_elapsed_secs,
            'needed_row'                    => $needed_row,
        );
    }

    /**
     * @Route("/close_unclosed/{days}", name="close_unclosed_suites_api", methods="GET|POST")
     * @Template(template="lbook/suite/close_unclosed.html.twig")
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @param int $days
     * @return array
     * @throws Exception
     */
    public function close_unclosed_suites_api(PagePaginator $pagePaginator, SuiteExecutionRepository $suites, int $days, ManagerRegistry $doctrine): array
    {
        if ($days > 100) {
            $days = 100;
        }

        if ($days < 3) {
            $days = 3;
        }
        $orders = ['ASC', 'DESC'];
        $query = $suites->createQueryBuilder('suite_execution')
            ->orderBy('suite_execution.id' , $orders[array_rand($orders)])
            ->andWhere('suite_execution.finishedAt <= :finishedAt')
            ->andWhere('suite_execution.closed = :state')
            ->setParameter('finishedAt', new \DateTime('-'. $days. ' days'), Type::DATETIME)
            ->setParameter('state', false);

        $paginator = $pagePaginator->paginate($query, 1, 1000);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();

        $iterator->rewind();
        $em = $doctrine->getManager();
        $output = [];
        $start = microtime(true);
        $closed = 0;
        try {
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var SuiteExecution $suite */
                    $suite = $iterator->current();
                    if ($suite !== null) {
                        //$suite->calculateStatistic();
                        $suite->setClosed(true);
                        $closed++;
                        $em->persist($suite);
                    }
                    $iterator->next();
                }
            }
        } catch (\Throwable $ex) { }
        $time_elapsed_secs = microtime(true) - $start;
        $start = microtime(true);
        $em->flush();
        $flush_time_elapsed_secs = microtime(true) - $start;
        return array(
            'output'    => $output,
            'size'      => $totalPosts,
            'iterator'      => $iterator,
            'time_elapsed_secs'      => $time_elapsed_secs,
            'flush_time_elapsed_secs'      => $flush_time_elapsed_secs,
            'closed'      => $closed,
        );
    }

    /**
     * @Route("/show/{id}", name="suite_show", methods="GET")
     * @param SuiteExecution $suite
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @return Response
     * @throws Exception
     */
    public function show(SuiteExecution $suite, PagePaginator $pagePaginator, SuiteExecutionRepository $suites, ManagerRegistry $doctrine): Response
    {
//        $this->denyAccessUnlessGranted('view', $suite);
        $query = $suites->createQueryBuilder('suite_execution')
//            ->where('suite_execution.disabled = 0')
            ->orderBy('suite_execution.updatedAt', 'DESC')
            ->where('suite_execution.name = :name')
            ->andWhere('suite_execution.uuid = :uuid')
            ->setParameter('name', $suite->getName())
            ->setParameter('uuid', $suite->getUuid())
//            ->addOrderBy('suite_execution.cycle', 'DESC')
        ;

        $paginator = $pagePaginator->paginate($query, 1, $this->index_size);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();

        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = 1;
        $this->em = $doctrine->getManager();
        /** @var LogBookSuiteInfoRepository $suiteInfoRepo */
        $suiteInfoRepo = $this->em->getRepository('App:LogBookSuiteInfo');
        //$suite->calculateStatistic();
        return $this->render('lbook/suite/show.html.twig',
            [
                'suite' => $suite,
                'suiteInfo' => $suiteInfoRepo->findOneOrCreate(['name' => $suite->getName(), 'uuid' => $suite->getUuid()]),
                'size'      => $totalPosts,
                'maxPages'  => $maxPages,
                'thisPage'  => $thisPage,
                'iterator'  => $iterator,
                'paginator' => $paginator,
            ]);
    }

    /**
     * @Route("/cycle/{id}", name="suite_cycle_show", methods="GET")
     * @param SuiteExecution $suite
     * @return Response
     */
    public function show_cycle(SuiteExecution $suite): Response
    {
        $cycle = $suite->getCycle();
        if ($cycle === null) {
            return $this->redirectToRoute('cycle_not_found');
        }
        return $this->redirectToRoute('cycle_show_first', ['id' => $cycle->getId()]);
    }


    /**
     *
     * @Route("/subscribe/{suite}", name="suite_subscribe", methods={"GET"})
     * @param Request $request
     * @param LogBookSuiteInfo|null $suite
     * @return Response
     */
    public function subscribe(Request $request, LogBookSuiteInfo $suite = null, ManagerRegistry $doctrine): Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $suite->addSubscriber($user);
        $doctrine->getManager()->flush();

        $referer = $request->headers->get('referer');
        if ($referer === null) {
            return $this->redirectToRoute('index');
        }
        return $this->redirect($referer);
    }

    /**
     *
     * @Route("/fail_subscribe/{suite}", name="fail_suite_subscribe", methods={"GET"})
     * @param Request $request
     * @param LogBookSuiteInfo|null $suite
     * @return Response
     */
    public function failSubscribe(Request $request, LogBookSuiteInfo $suite = null, ManagerRegistry $doctrine): Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $suite->addFailureSubscriber($user);
        $doctrine->getManager()->flush();

        $referer = $request->headers->get('referer');
        if ($referer === null) {
            return $this->redirectToRoute('index');
        }
        return $this->redirect($referer);
    }

    /**
     *
     * @Route("/unsubscribe/{suite}", name="suite_unsubscribe", methods={"GET", "POST"})
     * @param Request $request
     * @param LogBookSuiteInfo|null $suite
     * @return Response
     */
    public function unsubscribe(Request $request, LogBookSuiteInfo $suite = null, ManagerRegistry $doctrine): Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $suite->removeSubscriber($user);
        $doctrine->getManager()->flush();
        $referer = $request->headers->get('referer');
        if ($referer === null) {
            try {
                $executions = $suite->getSuiteExecutions();
                if (count($executions)) {
                    $lastExecution = $executions->first();
                    return $this->redirectToRoute('suite_show',  ['id' => $lastExecution->getId()]);
                }
            } catch (\Throwable $ex) {}
            return $this->redirectToRoute('index');
        }
        return $this->redirect($referer);
    }

    /**
     *
     * @Route("/fail_unsubscribe/{suite}", name="fail_suite_unsubscribe", methods={"GET", "POST"})
     * @param Request $request
     * @param LogBookSuiteInfo|null $suite
     * @return Response
     */
    public function failUnsubscribe(Request $request, LogBookSuiteInfo $suite = null, ManagerRegistry $doctrine): Response
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $suite->removeFailureSubscriber($user);
        $doctrine->getManager()->flush();
        $referer = $request->headers->get('referer');
        if ($referer === null) {
            try {
                $executions = $suite->getSuiteExecutions();
                if (count($executions)) {
                    $lastExecution = $executions->first();
                    return $this->redirectToRoute('suite_show',  ['id' => $lastExecution->getId()]);
                }
            } catch (\Throwable $ex) {}
            return $this->redirectToRoute('index');
        }
        return $this->redirect($referer);
    }

    /**
     * @Route("/close/{id}", name="suite_close", methods="GET|POST")
     * @param SuiteExecution $suite
     * @param LoggerInterface $logger
     * @param LogBookSuiteInfoRepository $suiteInfoRepo
     * @return Response
     */
    public function close(SuiteExecution $suite, LoggerInterface $logger, LogBookSuiteInfoRepository $suiteInfoRepo, ManagerRegistry $doctrine): Response
    {
//        $this->denyAccessUnlessGranted('view', $suite);
        $suite->calculateStatistic();
        $suite->setClosed(true);

        $em = $doctrine->getManager();
        $em->persist($suite);

        try {
            $newSuiteInfo = $suiteInfoRepo->findOneOrCreate([
                'name' => $suite->getName(),
                'uuid' => $suite->getUuid(),
            ]);

            $subscribers = $newSuiteInfo->getSubscribers();
            $fail_subscribers = $newSuiteInfo->getFailureSubscribers();

            foreach ($subscribers as $subscriber) {
                if ( $subscriber->getEmail() === null) {
                    continue;
                }
                $newEmail = new LogBookEmail();
                $b = $suite->getBranch();
                if ($suite->getPassRate() < 100) {
                    $newEmail->setSubject('['. $newSuiteInfo->getName() . ']['. $b . '] failed. PR:' . $suite->getPassRate() . '%');
                } else{
                    $newEmail->setSubject('['. $newSuiteInfo->getName() . ']['. $b . '] finished');

                }
                try {
                    if ( $fail_subscribers->contains($subscriber) ) {
                        # For those who subscribed to failure (only)
                        continue;
                    }
                } catch (\Throwable $ex) {}

                try {
                    $body = $this->get('twig')->render('lbook/email/suite_finished.html.twig', [
                        'suite' => $suite,
                        'suiteInfo' => $newSuiteInfo
                    ]);
                    $newEmail->setBody($body);
                } catch (\Throwable $ex) {
                    $logger->critical($ex->getMessage());
                }

                $newEmail->setRecipient($subscriber);
                $em->persist($newEmail);
            }

            foreach ($fail_subscribers as $subscriber) {
                if ( $subscriber->getEmail() === null) {
                    continue;
                }
                $newEmail = new LogBookEmail();
                if ($suite->getPassRate() < 100) {
                    $newEmail->setSubject('['. $newSuiteInfo->getName() . '] failed. PR:' . $suite->getPassRate() . '%');
                } else{
                    continue;
                }
                try {
                    $body = $this->get('twig')->render('lbook/email/suite_finished.html.twig', [
                        'suite' => $suite,
                        'suiteInfo' => $newSuiteInfo
                    ]);
                    $newEmail->setBody($body);
                } catch (\Throwable $ex) {
                    $logger->critical($ex->getMessage());
                }

                $newEmail->setRecipient($subscriber);
                $em->persist($newEmail);
            }
        } catch (\Throwable $ex) {
            $logger->critical($ex->getMessage());
        }

        $em->flush();
        $logger->notice('CLOSE_SUITE:',
            array(
                'name' => $suite->getName(),
                'job_name' => $suite->getJobName(),
            ));
        return $this->render('lbook/suite/close.html.twig',
            [
                'suite' => $suite
            ]);
    }

    /**
     * @Route("/debugclose/{id}", name="suite_close_aa", methods="GET|POST")
     * @param SuiteExecution $suite
     * @param LoggerInterface $logger
     * @param LogBookSuiteInfoRepository $suiteInfoRepo
     * @return Response
     */
    public function close_aaa(SuiteExecution $suite, LoggerInterface $logger, LogBookSuiteInfoRepository $suiteInfoRepo): Response
    {

        $newSuiteInfo = $suiteInfoRepo->findOneOrCreate([
            'name' => $suite->getName(),
            'uuid' => $suite->getUuid(),
        ]);

        return $this->render('lbook/email/suite_finished.html.twig',
            [
                'suite' => $suite,
                'suiteInfo' => $newSuiteInfo
            ]);

    }
}
