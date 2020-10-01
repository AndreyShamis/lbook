<?php

namespace App\Controller;

use App\Entity\LogBookDefect;
use App\Form\LogBookDefectType;
use App\Repository\LogBookDefectRepository;
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
    public function new(Request $request): Response
    {
        $logBookDefect = new LogBookDefect();
        $form = $this->createForm(LogBookDefectType::class, $logBookDefect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
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
     */
    public function show(LogBookDefect $logBookDefect): Response
    {
        return $this->render('log_book_defect/show.html.twig', [
            'log_book_defect' => $logBookDefect,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="log_book_defect_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, LogBookDefect $logBookDefect): Response
    {
        $form = $this->createForm(LogBookDefectType::class, $logBookDefect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('log_book_defect_index');
        }

        return $this->render('log_book_defect/edit.html.twig', [
            'log_book_defect' => $logBookDefect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="log_book_defect_delete", methods={"DELETE"})
     */
    public function delete(Request $request, LogBookDefect $logBookDefect): Response
    {
        if ($this->isCsrfTokenValid('delete'.$logBookDefect->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($logBookDefect);
            $entityManager->flush();
        }

        return $this->redirectToRoute('log_book_defect_index');
    }
}
