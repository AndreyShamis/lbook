<?php

namespace App\Controller;

use App\Entity\LogBookTestInfo;
use App\Entity\TestFilter;
use App\Entity\TestFilterApply;
use App\Form\TestFilterApplyType;
use App\Repository\LogBookTestInfoRepository;
use App\Repository\SuiteExecutionRepository;
use App\Repository\TestFilterApplyRepository;
use App\Repository\TestFilterRepository;
use App\Service\PagePaginator;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/filters_apply")
 */
class TestFilterApplyController extends AbstractController
{
    /**
     * @Route("/page/{page}", name="test_filter_apply_index_page", methods={"GET"}, requirements={"page"="\d*"})
     * @Route("/", name="test_filter_apply_index", methods={"GET"})
     */
    public function index(PagePaginator $pagePaginator, TestFilterApplyRepository $testFilterApplyRepository, int $page = 1): Response
    {
        $index_size = 5000;

        $query = $testFilterApplyRepository->createQueryBuilder('filters')
                ->orderBy('filters.createdAt', 'DESC');


        $paginator = $pagePaginator->paginate($query, $page, $index_size);
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();

        $maxPages = ceil($totalPosts / $index_size);
        $thisPage = $page;

        return $this->render('test_filter_apply/index.html.twig', [
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator
        ]);


    }

    /**
     * @Route("/new_cli", name="test_filter_apply_new_cli", methods={"GET","POST"})
     * @param Request $request
     * @param TestFilterRepository $filters
     * @param LogBookTestInfoRepository $testInfoRepo
     * @param LoggerInterface $logger
     * @return JsonResponse
     */
    public function create(Request $request, TestFilterRepository $filters, LogBookTestInfoRepository $testInfoRepo, SuiteExecutionRepository $suites, LoggerInterface $logger): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $name = $data['name'];
            $path = $data['path'];
            $suite_execution_id = $data['suite_execution_id'];
            $filterId = $data['filter_id'];
            $testFilterApply = new TestFilterApply();
            $ti = $testInfoRepo->findOneBy([
                "name" => $name,
                "path" => $path
            ]);
            if ( $ti === null ) {
                $ti = $testInfoRepo->findOneBy([
                    "name" => $name,
                    "path" => 'server/'.$path
                ]);
            }
            $suiteExecution = $suites->findOneBy(['id' => $suite_execution_id]);
            $ret = [];
            if ( $ti !== null ) {
                $tf = $filters->findOneBy(['id' => $filterId]);
                if ( $tf !== null ) {
                    $testFilterApply->setTestFilter($tf);
                    $testFilterApply->setTestInfo($ti);
                    $testFilterApply->setSuiteExecution($suiteExecution);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($testFilterApply);
                    $em->flush();
                    $ret[] = 'Filter added to history id:' . $testFilterApply->getId();
                }
            }
            $response =  new JsonResponse($ret);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;

        } catch (\Throwable $ex) {
            $logger->critical('ERROR [test_filter_apply_new_cli]:' . $ex->getMessage());
            $response = $this->json([]);
            $arr['ERROR'] = $ex->getMessage();
            $js = json_encode($arr);
            $response->setJson($js);
            $response->setEncodingOptions(JSON_PRETTY_PRINT);
            return $response;
        }
    }


    /**
     * @Route("/new", name="test_filter_apply_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $testFilterApply = new TestFilterApply();
        $form = $this->createForm(TestFilterApplyType::class, $testFilterApply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($testFilterApply);
            $em->flush();

            return $this->redirectToRoute('test_filter_apply_index');
        }

        return $this->render('test_filter_apply/new.html.twig', [
            'test_filter_apply' => $testFilterApply,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="test_filter_apply_show", methods={"GET"})
     */
    public function show(TestFilterApply $testFilterApply): Response
    {
        return $this->render('test_filter_apply/show.html.twig', [
            'test_filter_apply' => $testFilterApply,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="test_filter_apply_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, TestFilterApply $testFilterApply): Response
    {
        $form = $this->createForm(TestFilterApplyType::class, $testFilterApply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('test_filter_apply_index');
        }

        return $this->render('test_filter_apply/edit.html.twig', [
            'test_filter_apply' => $testFilterApply,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="test_filter_apply_delete", methods={"DELETE"})
     */
    public function delete(Request $request, TestFilterApply $testFilterApply): Response
    {
        if ($this->isCsrfTokenValid('delete'.$testFilterApply->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($testFilterApply);
            $em->flush();
        }

        return $this->redirectToRoute('test_filter_apply_index');
    }
}
