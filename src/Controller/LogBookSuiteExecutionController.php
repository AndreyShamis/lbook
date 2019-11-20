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
            ->orderBy('suite_execution.updatedAt', 'DESC');
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
     * @Route("/calculate/{days}", name="suite_calculate_api", methods="GET|POST")
     * @Template(template="lbook/suite/calculate.html.twig")
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @param int $days
     * @return array
     * @throws \Exception
     */
    public function calculate_api(PagePaginator $pagePaginator, SuiteExecutionRepository $suites, int $days): array
    {
        if ($days > 30) {
            $days = 1;
        }
        $orders = ['ASC', 'DESC'];
        $rows = ['id', 'testsCount', 'chip', 'testingLevel', 'publish', 'failCount', 'closed', 'passRate'];
        $needed_row = 'suite_execution.'. $rows[array_rand($rows)];
        $query = $suites->createQueryBuilder('suite_execution')
            ->orderBy($needed_row , $orders[array_rand($orders)])
            ->andWhere('suite_execution.startedAt >= :started')
        ->setParameter('started', new \DateTime('-'. $days. ' days'), \Doctrine\DBAL\Types\Type::DATETIME);

        $paginator = $pagePaginator->paginate($query, 1, 5000);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();

        $iterator->rewind();
        $em = $this->getDoctrine()->getManager();
        $output = [];
        $start = microtime(true);
        $persisted = 0;
        $suitePersisted = [];
        $iteratorSize = $iterator->count();
        try {
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var SuiteExecution $suite */
                    $suite = $iterator->current();
                    if ($suite !== null) {
                        $suite->calculateStatistic();
                        $em->persist($suite);
                        $suitePersisted[] = $suite;
                        $persisted++;
                        //$output[] = 'ID: ' . $suite->getId() . ' calculated';
                    }
                    $iterator->next();
                    if ($persisted > 100) {
                        $em->flush();
                        $persisted = 0;
                        foreach ($suitePersisted as $tmp_suite) {
                            $em->clear($tmp_suite);
                        }
                    }
                }
            }
        } catch (\Throwable $ex) { }
        $time_elapsed_secs = microtime(true) - $start;
        $start = microtime(true);
        $em->flush();
        $flush_time_elapsed_secs = microtime(true) - $start;
        return array(
            'output'                        => $output,
            'size'                          => $totalPosts,
            'iteratorSize'                  => $iteratorSize,
            'time_elapsed_secs'             => $time_elapsed_secs,
            'flush_time_elapsed_secs'       => $flush_time_elapsed_secs,
            'needed_row'                    => $needed_row,
        );
    }

    /**
     * @Route("/close_unclosed/{days}", name="close_unclosed_suites_api", methods="GET|POST")
     * @Template(template="lbook/suite/close_unclosed.html.twig")
     * @param PagePaginator $pagePaginator
     * @param SuiteExecutionRepository $suites
     * @param int $days
     * @return array
     * @throws \Exception
     */
    public function close_unclosed_suites_api(PagePaginator $pagePaginator, SuiteExecutionRepository $suites, int $days): array
    {
        if ($days > 100) {
            $days = 100;
        }

        if ($days < 3) {
            $days = 3;
        }
        $orders = ['ASC', 'DESC'];
        $query = $suites->createQueryBuilder('suite_execution')
            ->orderBy('suite_execution.id' , $orders[array_rand($orders)])
            ->andWhere('suite_execution.finishedAt <= :finishedAt')
            ->andWhere('suite_execution.closed = :state')
            ->setParameter('finishedAt', new \DateTime('-'. $days. ' days'), \Doctrine\DBAL\Types\Type::DATETIME)
            ->setParameter('state', false);

        $paginator = $pagePaginator->paginate($query, 1, 1000);
        $totalPosts = $paginator->count();
        /** @var \ArrayIterator $iterator */
        $iterator = $paginator->getIterator();

        $iterator->rewind();
        $em = $this->getDoctrine()->getManager();
        $output = [];
        $start = microtime(true);
        $closed = 0;
        try {
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var SuiteExecution $suite */
                    $suite = $iterator->current();
                    if ($suite !== null) {
                        $suite->calculateStatistic();
                        $suite->setClosed(true);
                        $closed++;
                        $em->persist($suite);
                    }
                    $iterator->next();
                }
            }
        } catch (\Throwable $ex) { }
        $time_elapsed_secs = microtime(true) - $start;
        $start = microtime(true);
        $em->flush();
        $flush_time_elapsed_secs = microtime(true) - $start;
        return array(
            'output'    => $output,
            'size'      => $totalPosts,
            'iterator'      => $iterator,
            'time_elapsed_secs'      => $time_elapsed_secs,
            'flush_time_elapsed_secs'      => $flush_time_elapsed_secs,
            'closed'      => $closed,
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
        $cycle = $suite->getCycle();
        if ($cycle === null) {
            return $this->redirectToRoute('cycle_not_found');
        }
        return $this->redirectToRoute('cycle_show_first', ['id' => $cycle->getId()]);
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
