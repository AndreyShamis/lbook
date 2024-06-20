<?php

namespace App\Controller;

use App\Entity\LogBookTestInfo;
use App\Form\LogBookTestInfoType;
use App\Repository\LogBookTestInfoRepository;
use App\Service\PagePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/testinfo")
 */
class LogBookTestInfoController extends AbstractController
{
    /**
     * @Route("/", name="log_book_test_info_index", methods={"GET"})
     * @Route("/page/{page}", name="log_book_test_info_index_page", methods={"GET"})
     * @param PagePaginator $pagePaginator
     * @param LogBookTestInfoRepository $logBookTestInfoRepository
     * @param Request $request
     * @param int $page
     * @return Response
     */
    public function index(PagePaginator $pagePaginator, LogBookTestInfoRepository $logBookTestInfoRepository, Request $request, int $page = 1): Response
    {
        $filter = $request->query->get('filter', 'with_path');
        $size = 30000;
        $queryBuilder = $logBookTestInfoRepository->createQueryBuilder('tt')
        ->orderBy('tt.lastMarkedAsSeenAt', 'DESC');
        if ($filter === 'path_null') {
            $queryBuilder->where('tt.path IS NULL');
        } else {
            $queryBuilder->where('tt.path IS NOT NULL');
        }
        $queryBuilder->andHaving('tt.testCount > 0');
        $paginator = $pagePaginator->paginate($queryBuilder, $page, $size);
        $totalPosts = $paginator->count();
        return $this->render('log_book_test_info/index.html.twig', [
            'log_book_test_infos' => $paginator,
            'thisPage' => $page,
            'maxPages' => ceil($totalPosts / $size),
            'filter' => $filter,
        ]);
    }

    /**
     * @Route("/update", name="log_book_test_info_update", methods={"GET"})
     * @param LogBookTestInfoRepository $logBookTestInfoRepository
     * @return Response
     */
    public function update(LogBookTestInfoRepository $logBookTestInfoRepository): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $da = $logBookTestInfoRepository->findAll();
        foreach ($da as $d) {
//            if ($d->getTestCount() > 2000) {
            $d->setTestCount($d->getLogBookTests()->count());
//            }

        }
        $entityManager->flush();
        return $this->redirectToRoute('log_book_test_info_index');
    }

    /**
     * @Route("/{id}", name="log_book_test_info_show", methods={"GET"})
     * @param LogBookTestInfo $logBookTestInfo
     * @return Response
     */
    public function show(LogBookTestInfo $logBookTestInfo): Response
    {
        $uniqueKeys = $this->getUniqueKeys($logBookTestInfo->getLogBookTests());
        return $this->render('log_book_test_info/show.html.twig', [
            'log_book_test_info' => $logBookTestInfo,
            'uniqueKeys' => array_keys($uniqueKeys),
        ]);
    }

    private function getUniqueKeys($tests): array
    {
        $uniqueKeys = [];

        foreach ($tests as $test) {
            $metaData = $test->getNewMetaData();
            if ($metaData) {
                foreach ($metaData->getValue() as $key => $value) {
                    $uniqueKeys[$key] = true;
                }
            }
        }

        return $uniqueKeys;
    }

//    /**
//     * @Route("/{id}/edit", name="log_book_test_info_edit", methods={"GET","POST"})
//     */
//    public function edit(Request $request, LogBookTestInfo $logBookTestInfo): Response
//    {
//        $form = $this->createForm(LogBookTestInfoType::class, $logBookTestInfo);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $this->getDoctrine()->getManager()->flush();
//
//            return $this->redirectToRoute('log_book_test_info_index');
//        }
//
//        return $this->render('log_book_test_info/edit.html.twig', [
//            'log_book_test_info' => $logBookTestInfo,
//            'form' => $form->createView(),
//        ]);
//    }
//
//    /**
//     * @Route("/{id}", name="log_book_test_info_delete", methods={"DELETE"})
//     */
//    public function delete(Request $request, LogBookTestInfo $logBookTestInfo): Response
//    {
//        if ($this->isCsrfTokenValid('delete'.$logBookTestInfo->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->remove($logBookTestInfo);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('log_book_test_info_index');
//    }
}
