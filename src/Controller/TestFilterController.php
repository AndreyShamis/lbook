<?php

namespace App\Controller;

use App\Entity\LogBookUser;
use App\Entity\TestFilter;
use App\Form\TestFilterType;
use App\Repository\FilterEditHistoryRepository;
use App\Repository\TestFilterRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
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
    public function new(Request $request, ManagerRegistry $doctrine): Response
    {
        $testFilter = new TestFilter();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if ($user !== null && $user instanceof LogBookUser) {
            $testFilter->setUser($user);
        } else {
            return $this->redirectToRoute('index');
        }
        $form = $this->createForm(TestFilterType::class, $testFilter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
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
     * @Route("/showtest/{id}", name="show_test_test_filter_show", methods="GET")
     * @param TestFilter $testFilter
     * @return Response
     */
    public function showTest(TestFilter $testFilter, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        $testFilter->setName('AAAAAA');
        /** @var UnitOfWork $uow */
        $uow = $em->getUnitOfWork();
        $uow->computeChangeSets(); // do not compute changes if inside a listener
        $changeset = $uow->getEntityChangeSet($testFilter);
        return $this->render('test_filter/show_test_show.html.twig', [
            'uow' => $uow,
            'changeset' => print_r($changeset, false),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="test_filter_edit", methods="GET|POST")
     * @param Request $request
     * @param TestFilter $testFilter
     * @param FilterEditHistoryRepository $historyRepo
     * @return Response
     * @throws \Exception
     */
    public function edit(Request $request, TestFilter $testFilter, FilterEditHistoryRepository $historyRepo, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('edit', $testFilter);
        $form = $this->createForm(TestFilterType::class, $testFilter);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
//            $user = $this->get('security.token_storage')->getToken()->getUser();
//            $testFilter->setUser($user);
            $testFilter->setUpdatedAt(new \DateTime());
            try{
                /** @var UnitOfWork $uow */
                $uow = $doctrine->getManager()->getUnitOfWork();
                $uow->computeChangeSets(); // do not compute changes if inside a listener
                $diff_arr = $uow->getEntityChangeSet($testFilter);
                $user = $this->get('security.token_storage')->getToken()->getUser();
                try{
                    unset($diff_arr['updatedAt']);
                } catch (\Throwable $ex) {}
                $diff_str = json_encode($diff_arr, JSON_FORCE_OBJECT|JSON_PRETTY_PRINT);
                $f = [
                    'user' => $user,
                    'testFilter' => $testFilter,
                    'diff' => $diff_str,
                    'happenedAt' => new \DateTime(),
                ];

                $history = $historyRepo->findOneOrCreate($f);
                $testFilter->addFilterEditHistory($history);
            } catch (\Throwable $ex) {}
            $doctrine->getManager()->flush();
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
    public function delete(Request $request, TestFilter $testFilter, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('delete', $testFilter);
        if ($this->isCsrfTokenValid('delete'.$testFilter->getId(), $request->request->get('_token'))) {
            $em = $doctrine->getManager();
            //$em->remove($testFilter);
            $testFilter->setEnabled(false);
            $em->persist($em);
            $em->flush();
        }

        return $this->redirectToRoute('test_filter_index');
    }
}
