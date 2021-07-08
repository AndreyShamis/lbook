<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Entity\LogBookTest;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookSetupRepository;
use App\Repository\LogBookTestRepository;
use App\Service\PagePaginator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class LogBookApiCycleController
 * @package App\Controller
 * @Route("cycle/api/")
 */
class LogBookApiCycleController extends AbstractController
{
    /**
     * @Route("{cycle}", name="api_cycle_index")
     */
    public function index(LogBookCycle $cycle = null, PagePaginator $pagePaginator): ?JsonResponse
    {
        $res = $cycle->toJson();
        $response =  new JsonResponse($res);
        //$response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }

    /**
     * @Route("list/{cycle}", name="api_cycle_list")
     */
    public function show(LogBookCycle $cycle = null, PagePaginator $pagePaginator, LogBookTestRepository $testRepo = null): ?JsonResponse
    {
        $page = 1;
        $paginator_size = 20000;
        $qb = $testRepo->createQueryBuilder('t')
            ->where('t.cycle = :cycle')
//                ->addSelect('i.name as name, i.path as testPath')
            ->andWhere('t.disabled = :disabled')
            ->orderBy('t.executionOrder', 'ASC');
//            $qb->leftJoin('App:LogBookTestInfo', 'i', 'WITH', 't.testInfo = i.id');

        $qb = $qb->setParameters(['cycle'=> $cycle->getId(), 'disabled' => 0]);

        $em = $this->getDoctrine()->getManager();
        /** @var Query $query */
        $query = $em->createQuery("SELECT t FROM App\LogBookTest t");
        $query->setDQL($qb->getDQL());
        $query->setFetchMode(LogBookTest::class, "testInfo", ClassMetadataInfo::FETCH_EAGER);
        $query->setFetchMode(LogBookTest::class, "verdict", ClassMetadataInfo::FETCH_EAGER);
        $query->setFetchMode(LogBookTest::class, "testType", ClassMetadataInfo::FETCH_EAGER);
        $query->setFetchMode(LogBookTest::class, "suite_execution", ClassMetadataInfo::FETCH_EAGER);
        $query->setFetchMode(LogBookTest::class, "failDesc", ClassMetadataInfo::FETCH_EAGER);
        $query->setParameters($qb->getParameters());
        $query->setMaxResults($qb->getMaxResults());

        $paginator = $pagePaginator->paginate($qb, $page, $paginator_size);
        $iterator = $paginator->getIterator();
        $res = [];
        foreach ($iterator as $test) {
            $res[] = $test->toJson();
        }
        $response =  new JsonResponse($res);
        //$response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }
}
