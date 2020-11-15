<?php

namespace App\Controller;

use App\Entity\LogBookTestFailDesc;
use App\Form\LogBookTestFailDescType;
use App\Repository\LogBookTestFailDescRepository;
use App\Service\PagePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/failure")
 */
class LogBookTestFailDescController extends AbstractController
{
    /**
     * @Route("/", name="fail_desc_index", methods={"GET"})
     * @Route("/page/{page}", name="fail_desc_index_page", methods={"GET"})
     * @param LogBookTestFailDescRepository $logBookTestFailDescRepository
     * @param PagePaginator $pagePaginator
     * @param int $page
     * @return Response
     * @throws \Exception
     */
    public function index(LogBookTestFailDescRepository $logBookTestFailDescRepository, PagePaginator $pagePaginator, int $page = 1): Response
    {
        $paginator_size = 2000;
        $query = $logBookTestFailDescRepository->createQueryBuilder('f')
            ->orderBy('f.lastMarkedAsSeenAt', 'DESC')
            ->addOrderBy('f.testsCount', 'DESC')

        ;
        $paginator = $pagePaginator->paginate($query, $page, $paginator_size);
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();

        $maxPages = ceil($totalPosts / $paginator_size);
        $thisPage = $page;


        return $this->render('log_book_test_fail_desc/index.html.twig', [
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        ]);
    }

    /**
     * @Route("/{id}", name="fail_desc_show", methods={"GET"})
     * @Route("/{id}/page/{page}", name="fail_desc_show_page", methods={"GET"})
     */
    public function show(LogBookTestFailDesc $logBookTestFailDesc, int $page = 1): Response
    {

        $totalPosts =$logBookTestFailDesc->getTests()->count();
        $paginator_size = $totalPosts;
        $maxPages = ceil($totalPosts / $paginator_size);
        return $this->render('log_book_test_fail_desc/show.html.twig', [
            'log_book_test_fail_desc' => $logBookTestFailDesc,
            'size' => $totalPosts,
            'thisPage' => $page,
            'maxPages' => $maxPages,
            'iterator' => $logBookTestFailDesc->getTests(),
            'pagePath' => 'fail_desc_show_page',
        ]);
    }

    /**
     * @Route("/{id}/edit", name="fail_desc_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, LogBookTestFailDesc $logBookTestFailDesc): Response
    {
        return $this->redirectToRoute('fail_desc_index');
        $form = $this->createForm(LogBookTestFailDescType::class, $logBookTestFailDesc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('fail_desc_index');
        }

        return $this->render('log_book_test_fail_desc/edit.html.twig', [
            'log_book_test_fail_desc' => $logBookTestFailDesc,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="log_book_test_fail_desc_delete", methods={"DELETE"})
     */
    public function delete(Request $request, LogBookTestFailDesc $logBookTestFailDesc): Response
    {
        return $this->redirectToRoute('fail_desc_index');
        if ($this->isCsrfTokenValid('delete'.$logBookTestFailDesc->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($logBookTestFailDesc);
            $entityManager->flush();
        }

        return $this->redirectToRoute('fail_desc_index');
    }
}
