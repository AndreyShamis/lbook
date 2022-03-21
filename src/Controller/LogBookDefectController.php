<?php

namespace App\Controller;

use App\Entity\LogBookDefect;
use App\Entity\LogBookUser;
use App\Form\LogBookDefectType;
use App\Repository\LogBookDefectRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/defects")
 */
class LogBookDefectController extends AbstractController
{
    /**
     * @Route("/", name="log_book_defect_index", methods={"GET"})
     * @param LogBookDefectRepository $logBookDefectRepository
     * @return Response
     */
    public function index(LogBookDefectRepository $logBookDefectRepository): Response
    {
        return $this->render('log_book_defect/index.html.twig', [
            'log_book_defects' => $logBookDefectRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="log_book_defect_new", methods={"GET","POST"})
     */
    public function new(Request $request, ManagerRegistry $doctrine): Response
    {
        $logBookDefect = new LogBookDefect();
        $form = $this->createForm(LogBookDefectType::class, $logBookDefect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var LogBookUser $user */
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $logBookDefect->setReporter($user);

            $entityManager = $doctrine->getManager();
            $entityManager->persist($logBookDefect);
            $entityManager->flush();

            return $this->redirectToRoute('log_book_defect_index');
        }

        return $this->render('log_book_defect/new.html.twig', [
            'log_book_defect' => $logBookDefect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="log_book_defect_show", methods={"GET"})
     * @param LogBookDefect $logBookDefect
     * @return Response
     */
    public function show(LogBookDefect $logBookDefect): Response
    {
        return $this->render('log_book_defect/show.html.twig', [
            'log_book_defect' => $logBookDefect,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="log_book_defect_edit", methods={"GET","POST"})
     * @param Request $request
     * @param LogBookDefect $logBookDefect
     * @return Response
     */
    public function edit(Request $request, LogBookDefect $logBookDefect, ManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(LogBookDefectType::class, $logBookDefect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $doctrine->getManager()->flush();

            return $this->redirectToRoute('log_book_defect_index');
        }

        return $this->render('log_book_defect/edit.html.twig', [
            'log_book_defect' => $logBookDefect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="log_book_defect_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookDefect $logBookDefect
     * @return Response
     */
    public function delete(Request $request, LogBookDefect $logBookDefect, ManagerRegistry $doctrine): Response
    {
        if ($this->isCsrfTokenValid('delete'.$logBookDefect->getId(), $request->request->get('_token'))) {
            $entityManager = $doctrine->getManager();
            $entityManager->remove($logBookDefect);
            $entityManager->flush();
        }

        return $this->redirectToRoute('log_book_defect_index');
    }
}
