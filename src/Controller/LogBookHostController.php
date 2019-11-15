<?php

namespace App\Controller;

use App\Repository\HostRepository;
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
    protected $index_size = 500;

    /**
     * @Route("/", name="hosts_index")
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
}
