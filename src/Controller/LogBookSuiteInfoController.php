<?php

namespace App\Controller;

use App\Entity\LogBookSuiteInfo;
use App\Form\LogBookSuiteInfoType;
use App\Repository\LogBookSuiteInfoRepository;
use App\Repository\SuiteExecutionRepository;
use App\Service\PagePaginator;
use Doctrine\Persistence\ManagerRegistry;
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
     * @Route("/show/{id}", name="log_book_suite_info_show", methods={"GET"})
     * @param LogBookSuiteInfo $suite
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @param int $size
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     */
    public function show(ManagerRegistry $doctrine, LogBookSuiteInfo $suite, PagePaginator $pagePaginator,  SuiteExecutionRepository $suites, int $size = 2000): Response
    {

        //        $this->denyAccessUnlessGranted('view', $suite);
        $query = $suites->createQueryBuilder('suite_execution')
//            ->where('suite_execution.disabled = 0')
            ->orderBy('suite_execution.updatedAt', 'DESC')
            ->where('suite_execution.name = :name')
            ->andWhere('suite_execution.uuid = :uuid')
            ->setParameter('name', $suite->getName())
            ->setParameter('uuid', $suite->getUuid())
//            ->addOrderBy('suite_execution.cycle', 'DESC')
        ;

        $paginator = $pagePaginator->paginate($query, 1, $size);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();

        $maxPages = ceil($totalPosts / $size);
        $thisPage = 1;
        $this->em = $doctrine->getManager();
        /** @var LogBookSuiteInfoRepository $suiteInfoRepo */
        $suiteInfoRepo = $this->em->getRepository('App:LogBookSuiteInfo');
        //$suite->calculateStatistic();


        return $this->render('log_book_suite_info/show.html.twig', [
            'suite' => $suite,
            'suiteInfo' => $suiteInfoRepo->findOneOrCreate(['name' => $suite->getName(), 'uuid' => $suite->getUuid()]),
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        ]);
    }

    /**
     * @Route("/", name="log_book_suite_info_index", methods={"GET"})
     */
    public function index(LogBookSuiteInfoRepository $logBookSuiteInfoRepository): Response
    {
        return $this->index_filter($logBookSuiteInfoRepository, '');
    }

    /**
     * @Route("/filter/{filter<.*>}", name="log_book_suite_info_index_filter", methods={"GET"})
     */
    public function index_filter(LogBookSuiteInfoRepository $logBookSuiteInfoRepository, string $filter = ''): Response
    {
        return $this->render('log_book_suite_info/index.html.twig', [
            'log_book_suite_infos' => $logBookSuiteInfoRepository->findAll(),
            'filter' => $filter
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
