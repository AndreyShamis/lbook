<?php

namespace App\Controller;

use App\Entity\LogBookTestInfo;
use App\Form\LogBookTestInfoType;
use App\Repository\LogBookTestInfoRepository;
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
     */
    public function index(LogBookTestInfoRepository $logBookTestInfoRepository): Response
    {
        return $this->render('log_book_test_info/index.html.twig', [
            'log_book_test_infos' => $logBookTestInfoRepository->findAll(),
        ]);
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
