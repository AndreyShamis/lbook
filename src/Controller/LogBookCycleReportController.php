<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookCycleReport;
use App\Entity\LogBookTest;
use App\Entity\LogBookUser;
use App\Form\LogBookCycleReportType;
use App\Repository\LogBookCycleReportRepository;
use App\Repository\LogBookTestRepository;
use App\Repository\LogBookVerdictRepository;
use App\Repository\SuiteExecutionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     */
    public function new(Request $request, LogBookCycle $cycle=null): Response
    {
        /** @var LogBookUser $user */
        $logBookCycleReport = new LogBookCycleReport();

        $form = $this->createForm(LogBookCycleReportType::class, $logBookCycleReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $logBookCycleReport->setCreator($user);
            if ($cycle !== null) {
                $logBookCycleReport->addCycle($cycle);
                $logBookCycleReport->setBuild($cycle->getBuild());
            }
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($logBookCycleReport);
            $entityManager->flush();

            return $this->redirectToRoute('log_book_cycle_report_index');
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
        $suites = [];
        $failed_tests = [];
        $cycles = new ArrayCollection();
        try {
            $cycles = $logBookCycleReport->getCycles();
            $cycle = $cycles->first();
            if ($cycle !== null) {
                $suites = $cycle->getSuiteExecution();
            }
        } catch (\Throwable $ex) {}
        $verdictPass = $verdicts->findOneOrCreate(['name' => 'PASS']);

        $qb = $testRepo->createQueryBuilder('t')
            ->where('t.cycle IN (:cycles)')
            ->andWhere('t.disabled = :disabled')
            ->andWhere('t.verdict != :verdictPass')
            ->orderBy('t.executionOrder', 'ASC')
            //->setParameter('cycle', $cycle->getId());
            ->setParameters(['cycles'=> $cycles, 'disabled' => 0, 'verdictPass' => $verdictPass]);
        $tests = $qb->getQuery()->execute();
        /** @var LogBookTest $test */
        foreach ($tests as $test) {
            if ($test !== null && $test->getVerdict() !== null && $test->getVerdict()->getName() !== 'PASS') {
                $failed_tests[] = $test;
            }
        }
        return $this->render('log_book_cycle_report/show.html.twig', [
            'log_book_cycle_report' => $logBookCycleReport,
            'suites' => $logBookCycleReport->getSuites($suitesRepo),
            'failed_tests' => $failed_tests,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="log_book_cycle_report_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, LogBookCycleReport $logBookCycleReport): Response
    {
        $form = $this->createForm(LogBookCycleReportType::class, $logBookCycleReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('log_book_cycle_report_index');
        }

        return $this->render('log_book_cycle_report/edit.html.twig', [
            'log_book_cycle_report' => $logBookCycleReport,
            'form' => $form->createView(),
        ]);
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
}
