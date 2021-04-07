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
     * @Route("/{page}", name="log_book_test_info_index", methods={"GET"})
     * @param LogBookTestInfoRepository $logBookTestInfoRepository
     * @return Response
     */
    public function index(PagePaginator $pagePaginator, LogBookTestInfoRepository $logBookTestInfoRepository, int $page = 1): Response
    {
        $size = 100000;
        $paginator = $pagePaginator->paginate(
            $logBookTestInfoRepository->createQueryBuilder('tt')
            ->where('tt.path is NOT NULL')
            ->orderBy('tt.lastMarkedAsSeenAt', 'DESC')
            , $page, $size);
        $totalPosts = $paginator->count();
        return $this->render('log_book_test_info/index.html.twig', [
            'log_book_test_infos' => $paginator,
            'thisPage' => $page,
            'maxPages' => ceil($totalPosts / $size),
        ]);
    }

    /**
     * @Route("/update", name="log_book_test_info_update", methods={"GET"})
     * @param LogBookTestInfoRepository $logBookTestInfoRepository
     * @return Response
     */
    public function update(PagePaginator $pagePaginator, LogBookTestInfoRepository $logBookTestInfoRepository): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $da = $logBookTestInfoRepository->findAll();
        foreach ($da as $d) {
//            if ($d->getTestCount() > 2000) {
            $d->setTestCount($d->getLogBookTests()->count());
//            }

        }
        $entityManager->flush();
        return $this->index($pagePaginator, $logBookTestInfoRepository);
    }

    /**
     * @Route("/{id}", name="log_book_test_info_show", methods={"GET"})
     * @param LogBookTestInfo $logBookTestInfo
     * @return Response
     */
    public function show(LogBookTestInfo $logBookTestInfo): Response
    {

        return $this->render('log_book_test_info/show.html.twig', [
            'log_book_test_info' => $logBookTestInfo,
        ]);
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
