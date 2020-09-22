<?php

namespace App\Controller;

use App\Entity\TestEventCmu;
use App\Form\TestEventCmuType;
use App\Repository\TestEventCmuRepository;
use App\Service\PagePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

//     * @Route("/page/{page}/block/{block}", name="test_event_cmu_index_page_block", methods={"GET", "POST"})
//     * @Route("/block/{block}/page/{page}", name="test_event_cmu_index_block_page", methods={"GET", "POST"})

/**
 * @Route("/eventcmu")
 */
class TestEventCmuController extends AbstractController
{
    /**

     * @Route("/page/{page}", name="event_cmu_index_page", methods={"GET"}, requirements={"page"="\d*"})
     * @Route("/block/{block}/page/{page}", name="event_cmu_index_block_page", methods={"GET"}, requirements={"block"="\w+\_*\w*\d*", "page"="\d*"})
     * @Route("/block/{block}", name="event_cmu_index_block", methods={"GET"}, requirements={"block"="\w+\_*\w*\d*"})
     * @Route("/", name="event_cmu_index", methods={"GET"})
     * @param PagePaginator $pagePaginator
     * @param TestEventCmuRepository $testEventCmuRepository
     * @param string|null $block
     * @param int $page
     * @return Response
     * @throws \Exception
     */
    public function index(PagePaginator $pagePaginator, TestEventCmuRepository $testEventCmuRepository, string $block = null, int $page = 1): Response
    {
        $index_size = 2000;
        if ($block === null || $block === '') {
            $query = $testEventCmuRepository->createQueryBuilder('events')
                ->orderBy('events.createdAt', 'DESC');
        } else {
            $query = $testEventCmuRepository->createQueryBuilder('events')
                ->where('events.block = :block')
                ->orderBy('events.createdAt', 'DESC')
            ->setParameter('block', $block);
        }

        $paginator = $pagePaginator->paginate($query, $page, $index_size);
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();

        $maxPages = ceil($totalPosts / $index_size);
        $thisPage = $page;

        return $this->render('test_event_cmu/index.html.twig', [
            'block'     => $block,
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
            'blocks'    => $testEventCmuRepository->getUniqBlockNames(),
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
