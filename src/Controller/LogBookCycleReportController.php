<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookCycleReport;
use App\Entity\LogBookUser;
use App\Form\LogBookCycleReportType;
use App\Repository\LogBookCycleReportRepository;
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
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $logBookCycleReport = new LogBookCycleReport();
        $logBookCycleReport->setCreator($user);
        if ($cycle !== null) {
            $logBookCycleReport->addCycle($cycle);
        }
        $form = $this->createForm(LogBookCycleReportType::class, $logBookCycleReport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
     */
    public function show(LogBookCycleReport $logBookCycleReport): Response
    {

        $cycle = $logBookCycleReport->getCycles()->first();
        $suites = $cycle->getSuiteExecution();
        return $this->render('log_book_cycle_report/show.html.twig', [
            'log_book_cycle_report' => $logBookCycleReport,
            'suites' => $suites,
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
