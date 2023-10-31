<?php

namespace App\Controller;

use App\Entity\Host;
use App\Repository\HostRepository;
use App\Repository\SuiteExecutionRepository;
use App\Service\PagePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Host controller.
 *
 * @Route("hosts")
 */
class LogBookHostController extends AbstractController
{
    protected $index_size = 1000;
    protected $show_suites_size = 2000;

    /**
 * @Route("/", name="hosts_index")
 * @Route("/page/{page}", name="hosts_index_page", methods={"GET"})
 * @param HostRepository $hosts
 * @param PagePaginator $pagePaginator
 * @param int $page
 * @return Response
 */
    public function index(HostRepository $hosts, PagePaginator $pagePaginator, $page = 1)
    {
        $query = $hosts->createQueryBuilder('host')
            ->orderBy('host.lastSeenAt', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();

        $iterator->rewind();

        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = $page;
        return $this->render('lbook/host/index.html.twig', array(
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'hosts'     => $iterator,
            'paginator' => $paginator,
        ));
    }

    /**
     * @Route("/show/{id}", name="host_show")
     * @param SuiteExecutionRepository $suites
     * @param PagePaginator $pagePaginator
     * @param int $page
     * @return Response
     */
    public function show(Host $host, SuiteExecutionRepository $suites, PagePaginator $pagePaginator, $page = 1)
    {
        $query = $suites->createQueryBuilder('suite_execution')
            ->orderBy('suite_execution.updatedAt', 'DESC')
            ->andWhere('suite_execution.host = :host')
            ->setParameter('host', $host->getId());

        $paginator = $pagePaginator->paginate($query, $page, $this->show_suites_size);
        $totalPosts = $paginator->count();
        $iterator = $paginator->getIterator();

        $iterator->rewind();

        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = $page;
        return $this->render('lbook/host/show.html.twig', array(
            'host'      => $host,
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'     => $iterator,
            'paginator' => $paginator,
        ));
    }
}
