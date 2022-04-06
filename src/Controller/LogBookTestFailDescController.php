<?php

namespace App\Controller;

use App\Entity\LogBookTestFailDesc;
use App\Form\LogBookTestFailDescType;
use App\Repository\LogBookTestFailDescRepository;
use App\Service\PagePaginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

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
        $paginator_size = 20000;

        $query = $logBookTestFailDescRepository->createQueryBuilder('f')
            ->orderBy('f.lastMarkedAsSeenAt', 'DESC')
            ->addOrderBy('f.testsCount', 'DESC')
            ->where('f.testsCount > 0')
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
     * @Route("/maintain", name="fail_desc_maintain", methods={"GET"})
     * @param LogBookTestFailDescRepository $logBookTestFailDescRepository
     * @param PagePaginator $pagePaginator
     * @param int $page
     * @return Response
     * @throws \Exception
     */
    public function maintain(LoggerInterface $logger, ManagerRegistry $doctrine, LogBookTestFailDescRepository $logBookTestFailDescRepository, PagePaginator $pagePaginator): Response
    {
        $query = $logBookTestFailDescRepository->createQueryBuilder('f')
            ->orderBy('f.lastMarkedAsSeenAt', 'DESC')
            ->addOrderBy('f.testsCount', 'DESC');
        $query->addSelect('RAND() as HIDDEN rand')->orderBy('rand()');
        $failDescs = $query->getQuery()->execute();
        $em = $doctrine->getManager();
        try {
            foreach ($failDescs as $failDesc) {
                try {
                    /** @var LogBookTestFailDesc $failDesc */
                    if ($failDesc !== null) {
                        $realTestsNumber = $failDesc->getTests()->count();
                        if ($failDesc->getTestsCount() !== $realTestsNumber) {
                            $failDesc->setTestsCount($realTestsNumber);
                            if ($realTestsNumber === 0 && $failDesc->getTestsCount() === 0) {
                                $em->remove($failDesc);
                            } else {
                                $em->persist($failDesc);
                            }
                        }
                    }
                } catch (\Throwable $ex) {
                    $logger->critical('fail_desc_maintain:maintain: Issue found in loop:' . $ex->getMessage());
                }
            }
            $em->flush();
        } catch (\Throwable $ex) {
            $logger->critical('fail_desc_maintain:maintain: Issue found:' . $ex->getMessage());
        }

        return $this->redirectToRoute('fail_desc_index');
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
