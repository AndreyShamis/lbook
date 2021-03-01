<?php

namespace App\Controller;

use App\Entity\LogBookSuiteInfo;
use App\Form\LogBookSuiteInfoType;
use App\Repository\LogBookSuiteInfoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/suite")
 */
class LogBookSuiteInfoController extends AbstractController
{
    /**
     * @Route("/", name="log_book_suite_info_index", methods={"GET"})
     */
    public function index(LogBookSuiteInfoRepository $logBookSuiteInfoRepository): Response
    {
        return $this->render('log_book_suite_info/index.html.twig', [
            'log_book_suite_infos' => $logBookSuiteInfoRepository->findAll(),
        ]);
    }

//    /**
//     * @Route("/new", name="log_book_suite_info_new", methods={"GET","POST"})
//     */
//    public function new(Request $request): Response
//    {
//        $logBookSuiteInfo = new LogBookSuiteInfo();
//        $form = $this->createForm(LogBookSuiteInfoType::class, $logBookSuiteInfo);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->persist($logBookSuiteInfo);
//            $entityManager->flush();
//
//            return $this->redirectToRoute('log_book_suite_info_index');
//        }
//
//        return $this->render('log_book_suite_info/new.html.twig', [
//            'log_book_suite_info' => $logBookSuiteInfo,
//            'form' => $form->createView(),
//        ]);
//    }

    /**
     * @Route("/{id}", name="log_book_suite_info_show", methods={"GET"})
     */
    public function show(LogBookSuiteInfo $logBookSuiteInfo): Response
    {
        return $this->render('log_book_suite_info/show.html.twig', [
            'log_book_suite_info' => $logBookSuiteInfo,
        ]);
    }

//    /**
//     * @Route("/{id}/edit", name="log_book_suite_info_edit", methods={"GET","POST"})
//     */
//    public function edit(Request $request, LogBookSuiteInfo $logBookSuiteInfo): Response
//    {
//        $form = $this->createForm(LogBookSuiteInfoType::class, $logBookSuiteInfo);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $this->getDoctrine()->getManager()->flush();
//
//            return $this->redirectToRoute('log_book_suite_info_index');
//        }
//
//        return $this->render('log_book_suite_info/edit.html.twig', [
//            'log_book_suite_info' => $logBookSuiteInfo,
//            'form' => $form->createView(),
//        ]);
//    }
//
//    /**
//     * @Route("/{id}", name="log_book_suite_info_delete", methods={"DELETE"})
//     */
//    public function delete(Request $request, LogBookSuiteInfo $logBookSuiteInfo): Response
//    {
//        if ($this->isCsrfTokenValid('delete'.$logBookSuiteInfo->getId(), $request->request->get('_token'))) {
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->remove($logBookSuiteInfo);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('log_book_suite_info_index');
//    }
}
