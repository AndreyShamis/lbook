<?php

namespace App\Controller;

use App\Entity\TestEventCmu;
use App\Form\TestEventCmuType;
use App\Repository\TestEventCmuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/eventcmu")
 */
class TestEventCmuController extends AbstractController
{
    /**
     * @Route("/", name="test_event_cmu_index", methods={"GET"})
     * @param TestEventCmuRepository $testEventCmuRepository
     * @return Response
     */
    public function index(TestEventCmuRepository $testEventCmuRepository): Response
    {
        return $this->render('test_event_cmu/index.html.twig', [
            'test_event_cmus' => $testEventCmuRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="test_event_cmu_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $testEventCmu = new TestEventCmu();
        $form = $this->createForm(TestEventCmuType::class, $testEventCmu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($testEventCmu);
            $entityManager->flush();

            return $this->redirectToRoute('test_event_cmu_index');
        }

        return $this->render('test_event_cmu/new.html.twig', [
            'test_event_cmu' => $testEventCmu,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="test_event_cmu_show", methods={"GET"})
     */
    public function show(TestEventCmu $testEventCmu): Response
    {
        return $this->render('test_event_cmu/show.html.twig', [
            'test_event_cmu' => $testEventCmu,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="test_event_cmu_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, TestEventCmu $testEventCmu): Response
    {
        $form = $this->createForm(TestEventCmuType::class, $testEventCmu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('test_event_cmu_index');
        }

        return $this->render('test_event_cmu/edit.html.twig', [
            'test_event_cmu' => $testEventCmu,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="test_event_cmu_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TestEventCmu $testEventCmu): Response
    {
        if ($this->isCsrfTokenValid('delete'.$testEventCmu->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($testEventCmu);
            $entityManager->flush();
        }

        return $this->redirectToRoute('test_event_cmu_index');
    }
}
