<?php

namespace App\Controller;

use App\Entity\LogBookTest;
use App\Repository\LogBookMessageRepository;
use App\Repository\LogBookTestRepository;
use App\Service\PagePaginator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogBookApiTestController
 * @package App\Controller
 * @Route("test/api/")
 */
class LogBookApiTestController extends AbstractController
{
    /**
     * @Route("{test}", name="api_test_index")
     */
    public function index(LogBookTest $test = null, PagePaginator $pagePaginator): ?JsonResponse
    {
        $res = $test->toJson();
        $response =  new JsonResponse($res);
        //$response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }

    /**
     * @Route("list/{test}", name="api_test_list")
     */
    public function show(LogBookTest $test = null, PagePaginator $pagePaginator, LogBookMessageRepository $logRepo, LogBookTestRepository $testRepo = null): ?JsonResponse
    {
        $res = [];
        $dataTable = '';
        $paginator_size = 90000;
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $bad_case = '';
            $dataTable = 'log_book_message';
            $first_log = $test->getLogs()->first();
            $em = $this->getDoctrine()->getManager();
            if ($first_log === false || $first_log === null) {

                /** @var ClassMetadataInfo $classMetaData */
                $classMetaData = $em->getClassMetadata('App:LogBookMessage');
                $classMetaData->setPrimaryTable(['name' => $test->getDbNameWithPrefix()]);
                // Used in case there is no table created
                $logRepo->createCustomTable($test->getDbPrefix());
                $dataTable = $test->getDbNameWithPrefix();
                $logRepo = $em->getRepository('App:LogBookMessage');
                $logRepo->setCustomTable($test->getDbNameWithPrefix());
                $em->refresh($test);

                $qb2 = $logRepo->createQueryBuilder('log_book_message')
                    ->where('log_book_message.test = :test')
                    ->setCacheable(false)
//                    ->setLifetime(120)
                    ->orderBy('log_book_message.chain', 'ASC')
                    ->setParameter('test', $test->getId());
                $paginator = $pagePaginator->paginate($qb2, 1, $paginator_size);
                $totalPosts = $paginator->count();
            } else {
                $classMetaData = $em->getClassMetadata('App:LogBookMessage');
                $classMetaData->setPrimaryTable(['name' => $dataTable]);
                $logRepo->setCustomTable($dataTable);
                $qb = $logRepo->createQueryBuilder('log_book_message')
                    ->where('log_book_message.test = :test')
                    ->setCacheable(false)
                    ->orderBy('log_book_message.chain', 'ASC')
                    ->setParameter('test', $test->getId());
                $paginator = $pagePaginator->paginate($qb, 1, $paginator_size);
                $totalPosts = $paginator->count();

            }
            if ($totalPosts === 0 && $first_log !== null) {
                $iterator = $test->getLogs()->getIterator();
                $totalPosts = $test->getLogs()->count();
                $bad_case = 'totalPosts === 0 && first_log !== null';

            } else {
                $iterator = $paginator->getIterator();
            }
            $res = [];
            foreach ($iterator as $log) {
                $res[] = $log->toJson();
            }

        } catch (\Throwable $ex) {
            $res['ERROR']['MSG'] = $ex->getMessage();
            $res['ERROR']['MSG'] = $ex->getTrace();
        }


        $response =  new JsonResponse($res);
        //$response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }
}
