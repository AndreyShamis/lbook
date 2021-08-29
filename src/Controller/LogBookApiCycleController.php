<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookCycleReport;
use App\Entity\LogBookEmail;
use App\Entity\LogBookSetup;
use App\Entity\LogBookTest;
use App\Entity\SuiteExecution;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookDefectRepository;
use App\Repository\LogBookSetupRepository;
use App\Repository\LogBookTestRepository;
use App\Repository\SuiteExecutionRepository;
use App\Service\PagePaginator;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class LogBookApiCycleController
 * @package App\Controller
 * @Route("api/cycle/")
 */
class LogBookApiCycleController extends AbstractController
{

    /**
     * @Route("auto/cycle_close", name="log_book_cycle_auto_close", methods={"GET","POST"})
     * @param LogBookCycleRepository $cycleRepo
     * @param LogBookTestRepository $testRepo
     * @param SuiteExecutionRepository $suitesRepo
     * @param LogBookDefectRepository $defectsRepo
     * @param LoggerInterface $logger
     * @return Response
     */
    public function auto_cycle_close(LogBookCycleRepository $cycleRepo, LogBookTestRepository $testRepo, SuiteExecutionRepository $suitesRepo, LoggerInterface $logger): Response
    {
        $time_limiter1 = new \DateTime('-90 minutes');
        $time_limiter2 = new \DateTime('-40 hours');
        $qb = $cycleRepo->createQueryBuilder('c')
            ->where('c.timeEnd <= :time_limiter1')
            ->andWhere('c.timeEnd > :time_limiter2')
            ->andWhere('c.isClosed = :closed')
            ->innerJoin('c.setup', 's') //->andWhere('s.autoCycleReport = 1')
            ->setParameter('time_limiter1', $time_limiter1)
            ->setParameter('time_limiter2', $time_limiter2)
            ->setParameter('closed', false)
//            ->setMaxResults(10)
        ;

        $cycles = $qb->getQuery()->execute();
        $cycles_ret = [];
        $i = 0;
        $em = $this->getDoctrine()->getManager();

        /** @var LogBookCycle $cycle */
        foreach ($cycles as $cycle) {
            if ($cycle->isAllSuitesFinished() && count($cycle->getLogBookCycleReports()) <= 0) {
                $l = $cycle->getTestingLevels();
                if (count($l) == 1 && in_array($l[0], [ 'integration', 'nightly', 'weekly'])) {

                    if ($i < 200) {
                        $i += 1;
                        $cycles_ret[] = $cycle;
                        $cycle->close();
                        $setup = $cycle->getSetup();
                        try {
                            $subscribers = $setup->getSubscribers();
                            // $fail_subscribers = $setup->getFailureSubscribers();
                            foreach ($subscribers as $subscriber) {
                                if ( $subscriber->getEmail() === null) {
                                    continue;
                                }
                                $newEmail = new LogBookEmail();
                                $b = $cycle->getBuild();
                                if ($cycle->getPassRate() < 100) {
                                    $newEmail->setSubject('['. $cycle->getName() . ']['. $b . '] failed. PR:' . $cycle->getPassRate() . '%');
                                } else{
                                    $newEmail->setSubject('['. $cycle->getName() . ']['. $b . '] finished');
                                }
//                    try {
//                        if ( $fail_subscribers->contains($subscriber) ) {
//                            # For those who subscribed to failure (only)
//                            continue;
//                        }
//                    } catch (\Throwable $ex) {}

                                try {
                                    $body = $this->get('twig')->render('lbook/email/cycle.finished.html.twig', [
                                        'cycle' => $cycle,
                                        'setup' => $setup
                                    ]);
                                    $newEmail->setBody($body);
                                } catch (\Throwable $ex) {
                                    $logger->critical($ex->getMessage());
                                }

                                $newEmail->setRecipient($subscriber);
                                $em->persist($newEmail);
                            }

//                foreach ($fail_subscribers as $subscriber) {
//                    if ( $subscriber->getEmail() === null) {
//                        continue;
//                    }
//                    $newEmail = new LogBookEmail();
//                    if ($cycle->getPassRate() < 100) {
//                        $newEmail->setSubject('['. $cycle->getName() . '] failed. PR:' . $cycle->getPassRate() . '%');
//                    } else{
//                        continue;
//                    }
//                    try {
//                        $body = $this->get('twig')->render('lbook/email/cycle.finished.html.twig', [
//                            'cycle' => $cycle,
//                            'setup' => $setup
//                        ]);
//                        $newEmail->setBody($body);
//                    } catch (\Throwable $ex) {
//                        $logger->critical($ex->getMessage());
//                    }
//
//                    $newEmail->setRecipient($subscriber);
//                    $em->persist($newEmail);
//                }
                        } catch (\Throwable $ex) {
                            $logger->critical($ex->getMessage());
                        }

                    }


                }
            }
            $cycle->setCalculateStatistic(false);
        }

        $em->flush();

        return $this->render('lbook/cycle/index.html.twig', [
            'size'      => count($cycles_ret),
            'maxPages'  => 1,
            'thisPage'  => 1,
            'iterator'  => $cycles_ret
        ]);
    }
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
