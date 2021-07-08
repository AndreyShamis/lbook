<?php

namespace App\Controller;

use App\Entity\LogBookSetup;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookSetupRepository;
use App\Service\PagePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class LogBookApiSetupController
 * @package App\Controller
 * @Route("setup/api/")
 */
class LogBookApiSetupController extends AbstractController
{
    /**
     * @Route("index", name="api_setup_index")
     */
    public function index(LogBookSetupRepository $setupRepo, PagePaginator $pagePaginator): ?JsonResponse
    {
        $page = 1;
        $paginator_size = 1000;
        $query = $setupRepo->createQueryBuilder('setups')
            ->where('setups.disabled = 0')
            ->orderBy('setups.updatedAt', 'DESC')
            ->addOrderBy('setups.id', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $paginator_size);
        $iterator = $paginator->getIterator();
        $res = [];
        foreach ($iterator as $setup) {
            $res[] = $setup->toJson();
        }
        $response =  new JsonResponse($res);
        //$response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }

    /**
     * @Route("{setup}", name="api_setup_list")
     */
    public function show(LogBookSetup $setup = null, PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo = null): ?JsonResponse
    {
        $page = 1;
        $paginator_size = 1000;
        $qb = $cycleRepo->createQueryBuilder('t')
            ->where('t.setup = :setup')
            ->orderBy('t.timeEnd', 'DESC')
            ->addOrderBy('t.updatedAt', 'DESC')
            ->setParameter('setup', $setup->getId());
        $paginator = $pagePaginator->paginate($qb, $page, $paginator_size);
        $iterator = $paginator->getIterator();
        $res = [];
        foreach ($iterator as $cycle) {
            $res[] = $cycle->toJson();
        }
        $response =  new JsonResponse($res);
        //$response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }
}
