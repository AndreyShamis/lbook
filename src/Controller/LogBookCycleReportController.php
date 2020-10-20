<?php

namespace App\Controller;

use App\Entity\LogBookCycleReport;
use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use App\Entity\SuiteExecution;
use App\Form\LogBookCycleReportType;
use App\Repository\LogBookCycleReportRepository;
use App\Repository\LogBookDefectRepository;
use App\Repository\LogBookTestRepository;
use App\Repository\LogBookVerdictRepository;
use App\Repository\SuiteExecutionRepository;
use JiraRestApi\Issue\Issue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;

/**
 * @Route("/reports")
 */
class LogBookCycleReportController extends AbstractController
{
    /**
     * @Route("/", name="log_book_cycle_report_index", methods={"GET"})
     */
    public function index(LogBookCycleReportRepository $logBookCycleReportRepository): Response
    {
        return $this->render('log_book_cycle_report/index.html.twig', [
            'log_book_cycle_reports' => $logBookCycleReportRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="log_book_cycle_report_new", methods={"GET","POST"})
     * @Route("/new/cycle/{cycle}", name="log_book_cycle_report_new_with_cycle", methods={"GET","POST"})
     * @param Request $request
     * @param LogBookCycle|null $cycle
     * @return Response
     */
    public function new(Request $request, LogBookCycle $cycle=null): Response
    {
        /** @var LogBookCycleReport $logBookCycleReport */
        $logBookCycleReport = new LogBookCycleReport();
        try {
            if ($cycle !== null) {
                $logBookCycleReport->addCycle($cycle);
                $logBookCycleReport->setDescription('Cycle executed on ' . $cycle->getBuild());
                $logBookCycleReport->setName($cycle->getName());
                /** @var SuiteExecution $some_suite */
                $some_suite = $cycle->getSuiteExecution()->first();
                $logBookCycleReport->setMode($some_suite->getMode());

                //$logBookCycleReport->setReportNotes($reportNotes);
                $logBookCycleReport->setCyclesNotes('Cycle started at  **' . $cycle->getTimeStart()->format('d/m/Y H:i:s') . '**  finished at **' . $cycle->getTimeEnd()->format('d/m/Y H:i:s') . '**');
                $logBookCycleReport->setSuitesNotes('Suites count  **' . $cycle->getSuiteExecution()->count() . '**');
                $logBookCycleReport->setTestsNotes('Tests count  **' . $cycle->getTestsCount() . '**');
                //$logBookCycleReport->setBugsNotes($request->request->get('bugsNotes'));
            }
        } catch (\Throwable $ex) {

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

            $entityManager = $this->getDoctrine()->getManager();
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
     * @return Response
     */
    public function edit(Request $request, LogBookCycleReport $logBookCycleReport): Response
    {
        $form = $this->createForm(LogBookCycleReportType::class, $logBookCycleReport);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reportNotes = $request->request->get('reportNotes');
            $logBookCycleReport->setReportNotes($reportNotes);
            $logBookCycleReport->setCyclesNotes($request->request->get('cyclesNotes'));
            $logBookCycleReport->setSuitesNotes($request->request->get('suitesNotes'));
            $logBookCycleReport->setTestsNotes($request->request->get('testsNotes'));
            $logBookCycleReport->setBugsNotes($request->request->get('bugsNotes'));
            $this->getDoctrine()->getManager()->flush();


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
    public function lock(Request $request, LogBookCycleReport $report): Response
    {
        $report->setIsLocked(true);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('log_book_cycle_report_show', ['id' => $report->getId()]);
    }

    /**
     * @Route("/{id}/inlock", name="log_book_cycle_report_unlock", methods={"GET","POST"})
     * @param Request $request
     * @param LogBookCycleReport $report
     * @return Response
     */
    public function unlock(Request $request, LogBookCycleReport $report): Response
    {
        $report->setIsLocked(false);
        $this->getDoctrine()->getManager()->flush();
        return $this->redirectToRoute('log_book_cycle_report_show', ['id' => $report->getId()]);
    }

    /**
     * @Route("/{id}", name="log_book_cycle_report_delete", methods={"DELETE"})
     */
    public function delete(Request $request, LogBookCycleReport $logBookCycleReport): Response
    {
        if ($this->isCsrfTokenValid('delete'.$logBookCycleReport->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($logBookCycleReport);
            $entityManager->flush();
        }

        return $this->redirectToRoute('log_book_cycle_report_index');
    }


    /**
     * @Route("/{id}/calculate", name="log_book_cycle_report_calculate", methods={"GET","POST"})
     * @param LogBookCycleReport $report
     * @param LogBookTestRepository $testRepo
     * @param LogBookVerdictRepository $verdicts
     * @param SuiteExecutionRepository $suitesRepo
     * @param LogBookDefectRepository $defectsRepo
     * @return Response
     */
    public function calculate(LogBookCycleReport $report, LogBookTestRepository $testRepo, LogBookVerdictRepository $verdicts, SuiteExecutionRepository $suitesRepo, LogBookDefectRepository $defectsRepo): Response
    {
        if (!$report->isLocked()) {
            //        $verdictPass = $verdicts->findOneOrCreate(['name' => 'PASS']);
//        $verdictFail = $verdicts->findOneOrCreate(['name' => 'FAIL']);
            $suites = $report->getSuites($suitesRepo);
//        $qb = $testRepo->createQueryBuilder('t')
//            ->where('t.cycle IN (:cycles)')
//            ->andWhere('t.disabled = :disabled')
//            ->andWhere('t.verdict != :verdictPass')
//            ->andWhere('t.suite_execution IN (:suite_executions)')
//            ->orderBy('t.executionOrder', 'ASC')
//            //->setParameter('cycle', $cycle->getId());
//            ->setParameters(['cycles'=> $report->getCycles(), 'disabled' => 0, 'verdictPass' => $verdictPass, 'suite_executions' => $suites]);
//        $tests_not_pass = $qb->getQuery()->execute();

            $tests_total = $tests_pass = $tests_fail = $tests_error = $tests_other = 0;
            /** @var SuiteExecution $suite */
            foreach ($suites as $suite) {
                $tests_pass += $suite->getPassCount();
                $tests_fail += $suite->getFailCount();
                $tests_error += $suite->getErrorCount();
                $tests_other += $suite->getOtherCount();
                $tests_total += $suite->getTotalExecutedTests();
            }
            $report->setTestsPass($tests_pass);
            $report->setTestsFail($tests_fail);
            $report->setTestsError($tests_error);
            $report->setTestsOther($tests_other);
            $report->setTestsTotal($tests_total);


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

            } catch (\Throwable $ex) {}


            $this->getDoctrine()->getManager()->flush();
        }

        return $this->redirectToRoute('log_book_cycle_report_show', ['id' => $report->getId()]);
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
