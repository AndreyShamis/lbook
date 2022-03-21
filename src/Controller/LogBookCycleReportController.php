<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookEmail;
use App\Entity\LogBookSetup;
use App\Entity\LogBookTest;
use App\Entity\SuiteExecution;
use App\Form\LogBookCycleReportType;
use App\Entity\LogBookCycleReport;
use App\Service\PagePaginator;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use JiraRestApi\Issue\Issue;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use App\Repository\CycleReportEditHistoryRepository;
use App\Repository\LogBookCycleReportRepository;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookDefectRepository;
use App\Repository\LogBookTestRepository;
use App\Repository\LogBookVerdictRepository;
use App\Repository\SuiteExecutionRepository;

/**
 * @Route("/reports")
 */
class LogBookCycleReportController extends AbstractController
{
    /**
     * @Route("/", name="log_book_cycle_report_index", methods={"GET"})
     * @Route("/page/{page}", name="log_book_cycle_report_index_page", methods={"GET"})
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleReportRepository $logBookCycleReportRepository
     * @param int $page
     * @return Response
     * @throws Exception
     */
    public function index(PagePaginator $pagePaginator, LogBookCycleReportRepository $logBookCycleReportRepository, int $page = 1): Response
    {
        $paginator_size = 500;
        $query = $logBookCycleReportRepository->createQueryBuilder('reports')
            ->orderBy('reports.createdAt', 'DESC')
            ->addOrderBy('reports.updatedAt', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $paginator_size);
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();

        $maxPages = ceil($totalPosts / $paginator_size);
        $thisPage = $page;
        return $this->render('log_book_cycle_report/index.html.twig', [
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        ]);
    }

    /**
     * @Route("/new", name="log_book_cycle_report_new", methods={"GET","POST"})
     * @Route("/new/cycle/{cycle}", name="log_book_cycle_report_new_with_cycle", methods={"GET","POST"})
     * @param Request $request
     * @param LogBookCycle|null $cycle
     * @param LoggerInterface $logger
     * @return Response
     */
    public function new(Request $request, LogBookCycle $cycle=null, LoggerInterface $logger, ManagerRegistry $doctrine): Response
    {
        /** @var LogBookCycleReport $logBookCycleReport */
        $logBookCycleReport = new LogBookCycleReport();
        try {
            if ($cycle !== null) {
                $logBookCycleReport->addCycle($cycle);
                $logBookCycleReport->setDescription('Cycle executed on ' . $cycle->getBuild());
                /** @var SuiteExecution $some_suite */
                $some_suite = $cycle->getSuiteExecution()->first();
                $logBookCycleReport->setMode($some_suite->getMode());
                $logBookCycleReport->setName('['. strtoupper($some_suite->getTestingLevel()) .'][' . str_replace('_MODE', '', strtoupper($logBookCycleReport->getMode())) .'] ' . $cycle->getName());
                
                //$logBookCycleReport->setReportNotes($reportNotes);
                $logBookCycleReport->setCyclesNotes('Cycle started at  **' . $cycle->getTimeStart()->format('d/m/Y H:i:s') . '**  finished at **' . $cycle->getTimeEnd()->format('d/m/Y H:i:s') . '**');
                $logBookCycleReport->setSuitesNotes('Suites count  **' . $cycle->getSuiteExecution()->count() . '**');
                $logBookCycleReport->setTestsNotes('Tests count  **' . $cycle->getTestsCount() . '**');
                //$logBookCycleReport->setBugsNotes($request->request->get('bugsNotes'));
            }
        } catch (\Throwable $ex) {
            $logger->critical($ex->getMessage());
        }
        $form = $this->createForm(LogBookCycleReportType::class, $logBookCycleReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $logBookCycleReport->setCreator($user);
            if ($cycle !== null) {
                $logBookCycleReport->addCycle($cycle);
                $logBookCycleReport->setBuild($cycle->getBuild());
            }
            $reportNotes = $request->request->get('reportNotes');
            $logBookCycleReport->setReportNotes($reportNotes);
            $logBookCycleReport->setCyclesNotes($request->request->get('cyclesNotes'));
            $logBookCycleReport->setSuitesNotes($request->request->get('suitesNotes'));
            $logBookCycleReport->setTestsNotes($request->request->get('testsNotes'));
            $logBookCycleReport->setBugsNotes($request->request->get('bugsNotes'));

            $entityManager = $doctrine->getManager();
            $entityManager->persist($logBookCycleReport);
            $entityManager->flush();

            return $this->redirectToRoute('log_book_cycle_report_calculate', ['id' => $logBookCycleReport->getId()]);
        }

        return $this->render('log_book_cycle_report/new.html.twig', [
            'log_book_cycle_report' => $logBookCycleReport,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="log_book_cycle_report_show", methods={"GET"})
     * @param LogBookCycleReport $logBookCycleReport
     * @param LogBookTestRepository $testRepo
     * @param LogBookVerdictRepository $verdicts
     * @param SuiteExecutionRepository $suitesRepo
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function show(LogBookCycleReport $logBookCycleReport, LogBookTestRepository $testRepo, LogBookVerdictRepository $verdicts, SuiteExecutionRepository $suitesRepo): Response
    {
        $failed_tests = [];
        $issued_tests = [];
        $cycles = $logBookCycleReport->getCycles();
        $verdictPass = $verdicts->findOneOrCreate(['name' => 'PASS']);
        $suites = $logBookCycleReport->getSuites($suitesRepo);
        $qb = $testRepo->createQueryBuilder('t')
            ->where('t.cycle IN (:cycles)')
            ->andWhere('t.disabled = :disabled')
            ->andWhere('t.verdict != :verdictPass')
            ->andWhere('t.suite_execution IN (:suite_executions)')
            ->orderBy('t.executionOrder', 'ASC')
            //->setParameter('cycle', $cycle->getId());
            ->setParameters(['cycles'=> $cycles, 'disabled' => 0, 'verdictPass' => $verdictPass, 'suite_executions' => $suites]);
        $tests = $qb->getQuery()->execute();
        /** @var LogBookTest $test */
        foreach ($tests as $test) {
            if ($test !== null && $test->getVerdict() !== null && $test->getVerdict()->getName() !== 'PASS') {
                $issued_tests[] = $test;
            }
            if ($test !== null && $test->getVerdict() !== null && $test->getVerdict()->getName() === 'FAIL') {
                $failed_tests[] = $test;
            }
        }

        return $this->render('log_book_cycle_report/show.html.twig', [
            'log_book_cycle_report' => $logBookCycleReport,
            'suites' => $logBookCycleReport->getSuites($suitesRepo),
            'failed_tests' => $failed_tests,
            'testsNotPass' => $issued_tests,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="log_book_cycle_report_edit", methods={"GET","POST"})
     * @param Request $request
     * @param LogBookCycleReport $logBookCycleReport
     * @param CycleReportEditHistoryRepository $historyRepo
     * @param LoggerInterface $logger
     * @return Response
     */
    public function edit(Request $request, LogBookCycleReport $logBookCycleReport, CycleReportEditHistoryRepository $historyRepo, LoggerInterface $logger, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('edit', $logBookCycleReport);
        $form = $this->createForm(LogBookCycleReportType::class, $logBookCycleReport);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reportNotes = $request->request->get('reportNotes');
            $logBookCycleReport->setReportNotes($reportNotes);
            $logBookCycleReport->setCyclesNotes($request->request->get('cyclesNotes'));
            $logBookCycleReport->setSuitesNotes($request->request->get('suitesNotes'));
            $logBookCycleReport->setTestsNotes($request->request->get('testsNotes'));
            $logBookCycleReport->setBugsNotes($request->request->get('bugsNotes'));
            try{
                /** @var UnitOfWork $uow */
                $uow = $doctrine->getManager()->getUnitOfWork();
                $uow->computeChangeSets(); // do not compute changes if inside a listener
                $diff_arr = $uow->getEntityChangeSet($logBookCycleReport);
                $user = $this->get('security.token_storage')->getToken()->getUser();
                try{
                    unset($diff_arr['updatedAt']);
                } catch (\Throwable $ex) {}
                $diff_str = json_encode($diff_arr, JSON_FORCE_OBJECT|JSON_PRETTY_PRINT);
                $f = [
                    'user' => $user,
                    'report' => $logBookCycleReport,
                    'diff' => $diff_str,
                    'happenedAt' => new \DateTime(),
                ];

                $history = $historyRepo->findOneOrCreate($f);
                $logBookCycleReport->addHistory($history);
            } catch (\Throwable $ex) {
                $logger->critical('REPORT_DIFF:' .$ex->getMessage());
            }
            $doctrine->getManager()->flush();

            return $this->redirectToRoute('log_book_cycle_report_calculate', ['id' => $logBookCycleReport->getId()]);
        }

        return $this->render('log_book_cycle_report/edit.html.twig', [
            'log_book_cycle_report' => $logBookCycleReport,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/lock", name="log_book_cycle_report_lock", methods={"GET","POST"})
     * @param Request $request
     * @param LogBookCycleReport $report
     * @return Response
     */
    public function lock(Request $request, LogBookCycleReport $report, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('edit', $report);
        $report->setIsLocked(true);
        $doctrine->getManager()->flush();
        return $this->redirectToRoute('log_book_cycle_report_show', ['id' => $report->getId()]);
    }

    /**
     * @Route("/{id}/inlock", name="log_book_cycle_report_unlock", methods={"GET","POST"})
     * @param Request $request
     * @param LogBookCycleReport $report
     * @return Response
     */
    public function unlock(Request $request, LogBookCycleReport $report, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('edit', $report);
        $report->setIsLocked(false);
        $doctrine->getManager()->flush();
        return $this->redirectToRoute('log_book_cycle_report_show', ['id' => $report->getId()]);
    }

    /**
     * @Route("/{id}", name="log_book_cycle_report_delete", methods={"DELETE"})
     */
    public function delete(Request $request, LogBookCycleReport $logBookCycleReport, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('delete', $logBookCycleReport);
        if ($this->isCsrfTokenValid('delete'.$logBookCycleReport->getId(), $request->request->get('_token'))) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($logBookCycleReport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('log_book_cycle_report_index');
    }


    /**
     * @Route("/auto/create", name="log_book_cycle_report_auto_create", methods={"GET","POST"})
     * @param LogBookCycleRepository $cycleRepo
     * @param LogBookTestRepository $testRepo
     * @param SuiteExecutionRepository $suitesRepo
     * @param LogBookDefectRepository $defectsRepo
     * @param LoggerInterface $logger
     * @return Response
     */
    public function auto_create(LogBookCycleRepository $cycleRepo, LogBookTestRepository $testRepo, SuiteExecutionRepository $suitesRepo, LogBookDefectRepository $defectsRepo, LoggerInterface $logger, ManagerRegistry $doctrine): Response
    {
        $time_limiter1 = new \DateTime('-90 minutes');
        $time_limiter2 = new \DateTime('-40 hours');
        $qb = $cycleRepo->createQueryBuilder('c')
            ->where('c.timeEnd <= :time_limiter1')
            ->andWhere('c.timeEnd > :time_limiter2')
            ->innerJoin('c.setup', 's')->andWhere('s.autoCycleReport = 1')
            ->setParameter('time_limiter1', $time_limiter1)
            ->setParameter('time_limiter2', $time_limiter2)
            ;

        $cycles = $qb->getQuery()->execute();
        $cycles_ret = [];
        $entityManager = $doctrine->getManager();
        $i = 0;
        /** @var LogBookCycle $cycle */
        foreach ($cycles as $cycle) {
            if ($cycle->isAllSuitesFinished() && count($cycle->getLogBookCycleReports()) <= 0) {
                $l = $cycle->getTestingLevels();
                if (count($l) == 1 && in_array($l[0], [ 'nightly', 'weekly'])) {

                    if ($i < 5) {
                        $i += 1;
                        $report = new LogBookCycleReport();
                        try {
                            $report->setIsAutoCreated(true);
                            $report->addCycle($cycle);
                            $report->setExtDefectsJql($cycle->getSetup()->getExtDefectsJql());
                            $report->setBuild($cycle->getBuild());
                            $report->setDescription('Cycle executed on ' . $cycle->getBuild());
                            /** @var SuiteExecution $some_suite */
                            $some_suite = $cycle->getSuiteExecution()->first();
                            $report->setMode($some_suite->getMode());
                            $report->setName('Job Report ['. $some_suite->getPlatform() . '] ' . ucfirst(strtolower($l[0])) . ' ' . ucfirst(str_replace('_mode', '', strtolower($report->getMode()))) . ' ' . $cycle->getBuild());

                            //$logBookCycleReport->setReportNotes($reportNotes);
                            $report->setCyclesNotes('Cycle started at  **' . $cycle->getTimeStart()->format('d/m/Y H:i:s') . '**  finished at **' . $cycle->getTimeEnd()->format('d/m/Y H:i:s') . '**');
                            $report->setSuitesNotes('Suites count  **' . $cycle->getSuiteExecution()->count() . '**');
                            $report->setTestsNotes('Tests count  **' . $cycle->getTestsCount() . '**');
                            try {
                                $report->setPlatforms($cycle->getSuitesPlatforms());
                                $report->setChips($cycle->getSuitesChips());
                                $report->setComponents($cycle->getSuitesComponents());
                            } catch (\Throwable $ex) {
                                $logger->critical('Failed to set Component/Chip/Platform:' .$ex->getMessage());
                            }

//                            try {
//                                $user = $this->get('security.token_storage')->getToken()->getUser();
//                                $report->setCreator($user);
//                            } catch (\Throwable $ex) {
//                                $logger->critical('Failed to set Report Creator:' .$ex->getMessage());
//                            }

                            $report->setReportNotes('Report generated automatically');
                            $report->setBugsNotes('');


                            $entityManager->persist($report);
                            $this->calculateReport($report, $testRepo, $suitesRepo, $defectsRepo, $logger);

                            $report->setIsAutoCreated(true);
                            $report->setIsLocked(true);
                            $entityManager->persist($report);

                            try {
                                $body = '';
                                $subject = 'Report [' . $report->getName() . '] created';
                                try {
                                    $body = $this->get('twig')->render('lbook/email/report_created.html.twig', [
                                        'report' => $report,
                                    ]);
                                } catch (\Throwable $ex) {
                                    $logger->critical($ex->getMessage());
                                }

                                foreach ($cycle->getSetup()->getModerators() as $tmp_user) {
                                    try {
                                        if($tmp_user !== null) {
                                            $newEmail = new LogBookEmail();
                                            $newEmail->setRecipient($tmp_user);
                                            $newEmail->setBody($body);
                                            $newEmail->setSubject($subject);
                                            $entityManager->persist($newEmail);
                                        }
                                    } catch (\Throwable $ex) {
                                        $logger->critical($ex->getMessage());
                                    }
                                }

                                foreach ($cycle->getSetup()->getSubscribers() as $tmp_user) {
                                    try {
                                        if($tmp_user !== null) {
                                            $newEmail = new LogBookEmail();
                                            $newEmail->setRecipient($tmp_user);
                                            $newEmail->setBody($body);
                                            $newEmail->setSubject($subject);
                                            $entityManager->persist($newEmail);
                                        }
                                    } catch (\Throwable $ex) {
                                        $logger->critical($ex->getMessage());
                                    }
                                }

                                try {
                                    if($cycle->getSetup()->getOwner() !== null) {
                                        $newEmail = new LogBookEmail();
                                        $newEmail->setSubject($subject);
                                        $newEmail->setRecipient($cycle->getSetup()->getOwner());
                                        $newEmail->setBody($body);
                                        $entityManager->persist($newEmail);
                                    }
                                } catch (\Throwable $ex) {
                                    $logger->critical($ex->getMessage());
                                }
                                
                            } catch (\Throwable $ex) {
                                $logger->critical($ex->getMessage());
                            }

                        } catch (\Throwable $ex) {
                            $logger->critical('AUTO REPORT CREATOR:' .$ex->getMessage());
                        }
                    }

                    $cycles_ret[] = $cycle;

                }
            }
            $cycle->setCalculateStatistic(false);
        }

        $entityManager->flush();

        return $this->render('lbook/cycle/index.html.twig', [
            'size'      => count($cycles_ret),
            'maxPages'  => 1,
            'thisPage'  => 1,
            'iterator'  => $cycles_ret
        ]);
    }

    /**
     * @Route("/auto/createdebug/{id}", name="log_book_cycle_report_auto_create_debug", methods={"GET","POST"})

     * @return Response
     */
    public function create_debug(LogBookCycleReport $report, LoggerInterface $logger): Response
    {

        //$em = $this->getDoctrine()->getManager();

        return $this->render('lbook/email/report_created.html.twig',
            [
                'report' => $report,
            ]);

    }

    /**
     * @Route("/{id}/calculate", name="log_book_cycle_report_calculate", methods={"GET","POST"})
     * @param LogBookCycleReport $report
     * @param LogBookTestRepository $testRepo
     * @param LogBookVerdictRepository $verdicts
     * @param SuiteExecutionRepository $suitesRepo
     * @param LogBookDefectRepository $defectsRepo
     * @param LoggerInterface $logger
     * @return Response
     */
    public function calculate(LogBookCycleReport $report, LogBookTestRepository $testRepo, LogBookVerdictRepository $verdicts, SuiteExecutionRepository $suitesRepo, LogBookDefectRepository $defectsRepo, LoggerInterface $logger, ManagerRegistry $doctrine): Response
    {

        $this->calculateReport($report, $testRepo, $suitesRepo, $defectsRepo, $logger, $doctrine);
        return $this->redirectToRoute('log_book_cycle_report_show', ['id' => $report->getId()]);
    }

    private function calculateReport(LogBookCycleReport $report, LogBookTestRepository $testRepo, SuiteExecutionRepository $suitesRepo, LogBookDefectRepository $defectsRepo, LoggerInterface $logger, ManagerRegistry $doctrine)
    {
        if (!$report->isLocked()) {
            $testsTotalEnabledInSuites = 0;
            //        $verdictPass = $verdicts->findOneOrCreate(['name' => 'PASS']);
            //        $verdictFail = $verdicts->findOneOrCreate(['name' => 'FAIL']);
            $suites = $report->getSuites($suitesRepo);

            $tests_total = $tests_pass = $tests_fail = $tests_error = $tests_other = 0;
            /** @var SuiteExecution $suite */
            foreach ($suites as $suite) {
                $tests_pass += $suite->getPassCount();
                $tests_fail += $suite->getFailCount();
                $tests_error += $suite->getErrorCount();
                $tests_other += $suite->getOtherCount();
                $tests_total += $suite->getTotalExecutedTests();
                $testsTotalEnabledInSuites += $suite->getTestsCountEnabled();
            }
            $report->setTestsPass($tests_pass);
            $report->setTestsFail($tests_fail);
            $report->setTestsError($tests_error);
            $report->setTestsOther($tests_other);
            $report->setTestsTotal($tests_total);
            $report->setTestsTotalEnabledInSuites($testsTotalEnabledInSuites);

            $report->setSuitesCount(count($suites));
            $report->setUpdatedAt(new \DateTime());
            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.cycle IN (:cycles)')
                ->andWhere('t.disabled = :disabled')
                ->andWhere('t.suite_execution IN (:suite_executions)')
                ->orderBy('t.executionOrder', 'ASC')
                //->setParameter('cycle', $cycle->getId());
                ->setParameters(['cycles'=> $report->getCycles(), 'disabled' => 0, 'suite_executions' => $suites]);
            $tests_all = $qb->getQuery()->execute();

            $testsTimeSum = 0;
            $min_time = new \DateTime('+100 years');
            $max_time = new \DateTime('-100 years');
            $tests = $tests_all;
            if (count($tests)) {
                foreach ($tests as $test) {
                    /** @var LogBookTest $test */
                    $max_time = max($max_time, $test->getTimeEnd());
                    $min_time = min($min_time, $test->getTimeStart());
                    $testsTimeSum += $test->getTimeRun();
                }
            } else {
                $min_time = new \DateTime();
                $max_time = new \DateTime();
            }
            //$this->setTimeStart($min_time);
            //$this->setTimeEnd($max_time);
            $report->setPeriod($max_time->getTimestamp() - $min_time->getTimestamp());
            $report->setDuration($testsTimeSum);

            try {
                $jql = $report->getExtDefectsJql();
                if(strlen($jql) > 10) {
                    try {
                        $cycles = $report->getCycles();
                        if ($cycles !== null && count($cycles) > 0) {
                            /** @var LogBookSetup $setup */
                            $setup = $cycles[0]->getSetup();
                            $setup->setExtDefectsJql($report->getExtDefectsJql());
                            $setup->setAutoCycleReport(true);
                        }
                    } catch (\Throwable $ex) {
                        $logger->critical($ex->getMessage());
                    }

                    $defects = $this->getIssues($report, $defectsRepo);
                    if (count($defects)){
                        foreach ($defects as $defect){
                            $report->addDefect($defect);
                        }
                        if ($report->getBugsNotes() === null || $report->getBugsNotes() === '') {
                            $report->setBugsNotes('Attached ' . count($defects) . ' defects by ' . $jql);
                        }
                    }
                }
            } catch (\Throwable $ex) {
                $logger->critical($ex->getMessage());
            }
            $doctrine->getManager()->flush();
        }
    }

    /**
     * @param LogBookCycleReport $report
     * @param LogBookDefectRepository $defectsRepo
     */
    protected function getIssues(LogBookCycleReport $report, LogBookDefectRepository $defectsRepo): array
    {
        $ret = [];
        try {
            $jql = $report->getExtDefectsJql();
            //$jql = 'project = TEST AND labels = monitoring  AND resolution = Unresolved AND issuetype = bug ORDER BY priority DESC';
            try {
                $V_FOUND = 'customfield_15482';
                $issueService = new IssueService();

                $jiraRet = $issueService->search($jql, 0 , 100);
                if ($jiraRet->total) {
                    $issues = $jiraRet->getIssues();
                    /** @var Issue $issue */
                    foreach ($issues as $issue) {
                        if ($issue->fields->issuetype->name == 'Bug') {
                            $versionFound = '';
                            $status = $issue->fields->status->name; // NEW
                            $reporter = $issue->fields->reporter->emailAddress;
                            $key = $issue->key;
                            $assignee = $issue->fields->assignee->emailAddress;
                            $updated_fmt = null;
                            $created_fmt = null;
                            try {
                                $updated_fmt = $issue->fields->updated;
                                $created_fmt = $issue->fields->created;
                            } catch (\Throwable $ex) {}
                            $priority = $issue->fields->priority->name; // Medium, Critical
                            $summary = $issue->fields->summary;
                            $labels = $issue->fields->labels; // array
                            $description = $issue->fields->description; //string
                            if (array_key_exists($V_FOUND, $issue->fields->customFields)) {
                                $versionFound = $issue->fields->customFields[$V_FOUND];
                            }

                            $defect = $defectsRepo->findOneOrCreate([
                                    'name' => $summary,
                                    'ext_id' => $key,
                                    'description' => $description,
                                    'labels' => $labels,
                                    'statusString' => $status,
                                    'extReporter' => $reporter,
                                    'extAssignee' => $assignee,
                                    'priority' => $priority,
                                    'extVersionFound' => $versionFound,
                                    'ExtUpdatedAt' => $updated_fmt,
                                    'ExtCreatedAt' => $created_fmt,
                                ]
                                , true);
                            if($updated_fmt !== null && ($defect->getExtUpdatedAt() === null || $defect->getExtUpdatedAt()->getTimestamp() != $updated_fmt->getTimestamp())) {
                                $defect->setExtUpdatedAt($updated_fmt);
                                $defect->setUpdatedAt(new \DateTime());
                            }
                            if($created_fmt !== null && ($defect->getExtCreatedAt() === null || $defect->getExtCreatedAt()->getTimestamp() != $created_fmt->getTimestamp())) {
                                $defect->setExtCreatedAt($created_fmt);
                                $defect->setUpdatedAt(new \DateTime());
                            }
                            $ret[] = $defect;
                        }

                    }

                }

            } catch (JiraException $e) {
                print("Error Occured! " . $e->getMessage());
                //$this->assertTrue(false, 'testSearch Failed : '.$e->getMessage());
            }
        } catch (JiraException $e) {
            print("Error Occured! " . $e->getMessage());
        }
        return $ret;
    }


    /**
     * @Route("/jira/test", name="log_book_cycle_report_jira", methods={"GET","POST"})
     * @param LogBookDefectRepository $defectsRepo
     * @throws \JsonMapper_Exception
     */
    public function jira_test(LogBookDefectRepository $defectsRepo)
    {


        try {
//            $issueService = new IssueService();
//
//            $queryParam = [
////                'fields' => [  // default: '*all'
////                    'summary',
////                    'comment',
////                ],
//                'expand' => [
//                    'renderedFields',
//                    'names',
//                    'schema',
//                    'transitions',
//                    'operations',
//                    'editmeta',
//                    'changelog',
//                ]
//            ];
//
//            $issue = $issueService->get('MESW-164525', $queryParam);
//            $status = $issue->fields->status->name; // NEW
//            $reporter = $issue->fields->reporter->emailAddress;
//            $key = $issue->key;
//            $assignee = $issue->fields->assignee->emailAddress;
//            $updated = $issue->fields->updated->date;  // 2020-06-14 19:46:34.000000
//            $priority = $issue->fields->priority->name; // Medium
//            $summary = $issue->fields->summary;
//
//            var_dump($issue->fields);


            $jql = 'project = MESW AND labels = monitoring  AND resolution = Unresolved AND issuetype = bug ORDER BY priority DESC';

            try {
                $V_FOUND = 'customfield_15482';
                $issueService = new IssueService();

                $ret = $issueService->search($jql, 0 , 100);
                if ($ret->total) {
                    $issues = $ret->getIssues();
                    /** @var Issue $issue */
                    foreach ($issues as $issue) {
                        if ($issue->fields->issuetype->name == 'Bug') {
                            $labels = [];
                            $versionFound = '';
                            $status = $issue->fields->status->name; // NEW
                            $reporter = $issue->fields->reporter->emailAddress;
                            $key = $issue->key;
                            $assignee = $issue->fields->assignee->emailAddress;
                            try {
                                $updated = $issue->fields->updated->date;  // 2020-06-14 19:46:34.000000
                            } catch (\Throwable $ex) {
                                $updated = '';
                            }
                            $priority = $issue->fields->priority->name; // Medium, Critical
                            $summary = $issue->fields->summary;
                            $labels = $issue->fields->labels; // array
                            $description = $issue->fields->description; //string
                            if (array_key_exists($V_FOUND, $issue->fields->customFields)) {
                                $versionFound = $issue->fields->customFields[$V_FOUND];
                            }

                            $defect = $defectsRepo->findOneOrCreate([
                                'name' => $summary,
                                'ext_id' => $key,
                                'description' => $description,
                                'labels' => $labels,
                                'statusString' => $status,
                                'extReporter' => $reporter,
                                'extAssignee' => $assignee,
                                'priority' => $priority,
                                'extVersionFound' => $versionFound,
                                ]
                            , true);
                        }

                    }
                    var_dump($ret);
                }

            } catch (JiraException $e) {
                print("Error Occured! " . $e->getMessage());
                //$this->assertTrue(false, 'testSearch Failed : '.$e->getMessage());
            }
        } catch (JiraException $e) {
            print("Error Occured! " . $e->getMessage());
        }
        exit();
    }
}
