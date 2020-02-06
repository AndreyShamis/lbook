<?php

namespace App\Controller;

use App\Entity\TestFilter;
use App\Form\TestFilterType;
use App\Repository\TestFilterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/filters")
 */
class TestFilterController extends AbstractController
{
    /**
     * @Route("/", name="test_filter_index", methods="GET")
     * @param TestFilterRepository $testFilterRepository
     * @return Response
     */
    public function index(TestFilterRepository $testFilterRepository): Response
    {
        return $this->render('test_filter/index.html.twig', ['test_filters' => $testFilterRepository->findAll()]);
    }

    /**
     * @Route("/new", name="test_filter_new", methods="GET|POST")
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $testFilter = new TestFilter();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $testFilter->setUser($user);
        $form = $this->createForm(TestFilterType::class, $testFilter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($testFilter);
            $em->flush();

            return $this->redirectToRoute('test_filter_index');
        }

        return $this->render('test_filter/new.html.twig', [
            'test_filter' => $testFilter,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="test_filter_show", methods="GET")
     * @param TestFilter $testFilter
     * @return Response
     */
    public function show(TestFilter $testFilter): Response
    {
        return $this->render('test_filter/show.html.twig', ['test_filter' => $testFilter]);
    }

    /**
     * @Route("/{id}/edit", name="test_filter_edit", methods="GET|POST")
     * @param Request $request
     * @param TestFilter $testFilter
     * @return Response
     * @throws \Exception
     */
    public function edit(Request $request, TestFilter $testFilter): Response
    {
        $this->denyAccessUnlessGranted('edit', $testFilter);
        $form = $this->createForm(TestFilterType::class, $testFilter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
//            $user = $this->get('security.token_storage')->getToken()->getUser();
//            $testFilter->setUser($user);
            $testFilter->setUpdatedAt(new \DateTime());
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('test_filter_edit', ['id' => $testFilter->getId()]);
        }

        return $this->render('test_filter/edit.html.twig', [
            'test_filter' => $testFilter,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="test_filter_delete", methods="DELETE")
     * @param Request $request
     * @param TestFilter $testFilter
     * @return Response
     */
    public function delete(Request $request, TestFilter $testFilter): Response
    {
        $this->denyAccessUnlessGranted('delete', $testFilter);
        if ($this->isCsrfTokenValid('delete'.$testFilter->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($testFilter);
            $em->flush();
        }

        return $this->redirectToRoute('test_filter_index');
    }
}
