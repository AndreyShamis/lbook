<?php

namespace App\Controller;

use App\Entity\SuiteExecution;
use App\Service\PagePaginator;
use App\Repository\SuiteExecutionRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogBookSuiteExecutionController
 * @package App\Controller
 * @Route("suites")
 */
class LogBookSuiteExecutionController extends AbstractController
{
    protected $index_size = 500;

    /**
     * @Route("/", name="suite_index")
     * @Route("/{page}", name="suite_index")
     * @Template(template="lbook/suite/index.html.twig")
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @param int $page
     * @return array
     */
    public function index(PagePaginator $pagePaginator, SuiteExecutionRepository $suites, int $page = 1): array
    {
        $query = $suites->createQueryBuilder('suite_execution')
//            ->where('suite_execution.disabled = 0')
            ->orderBy('suite_execution.updatedAt', 'DESC')
            ->where('suite_execution.name = :name')
            ->setParameter('name', 'Nightly_Driver_EPM5_EQ5')
//            ->addOrderBy('suite_execution.cycle', 'DESC')
        ;
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();
        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = $page;
        return array(
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        );
    }

    /**
     * @Route("/show/{id}", name="suite_show", methods="GET")
     * @param SuiteExecution $suite
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @return Response
     */
    public function show(SuiteExecution $suite, PagePaginator $pagePaginator, SuiteExecutionRepository $suites): Response
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

        $paginator = $pagePaginator->paginate($query, 1, $this->index_size);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();

        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = 1;
        $suite->calculateStatistic();
        return $this->render('lbook/suite/show.html.twig',
            [
                'suite' => $suite,
                'size'      => $totalPosts,
                'maxPages'  => $maxPages,
                'thisPage'  => $thisPage,
                'iterator'  => $iterator,
                'paginator' => $paginator,
            ]);
    }

    /**
     * @Route("/cycle/{id}", name="suite_cycle_show", methods="GET")
     * @param SuiteExecution $suite
     * @return Response
     */
    public function show_cycle(SuiteExecution $suite): Response
    {
        return $this->redirectToRoute('cycle_show_first', ['id' => $suite->getCycle()->getId()]);
    }

    /**
     * @Route("/close/{id}", name="suite_close", methods="GET|POST")
     * @param SuiteExecution $suite
     * @param SuiteExecutionRepository $suites
     * @return Response
     */
    public function close(SuiteExecution $suite, SuiteExecutionRepository $suites): Response
    {
//        $this->denyAccessUnlessGranted('view', $suite);
        $suite->calculateStatistic();
        $suite->setClosed(true);
        $em = $this->getDoctrine()->getManager();
        $em->persist($suite);
        $em->flush();
        return $this->render('lbook/suite/close.html.twig',
            [
                'suite' => $suite
            ]);
    }
}
