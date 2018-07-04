<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use App\Entity\TestSearch;
use App\Form\TestSearchType;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookMessageRepository;
use App\Repository\LogBookTestRepository;
use App\Service\PagePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use App\Form\LogBookTestType;

/**
 * Test controller.
 *
 * @Route("test")
 */
class LogBookTestController extends Controller
{
    protected $index_size = 1000;

    protected $log_size = 3000;

    /**
     * Lists all Tests entities.
     *
     * @Route("/page/{page}", name="test_index", methods={"GET"})
     * @Template(template="lbook/test/list.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @return array
     */
    public function index($page = 1, PagePaginator $pagePaginator, LogBookTestRepository $testRepo): array
    {
        set_time_limit(10);
        $query = $testRepo->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC');
        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        //$posts = $this->getAllPosts($page); // Returns 5 posts out of 20
        // You can also call the count methods (check PHPDoc for `paginate()`)
        //$totalPostsReturned = $paginator->getIterator()->count(); # Total fetched (ie: `5` posts)
        $totalPosts = $paginator->count(); # Count of ALL posts (ie: `20` posts)
        $iterator = $paginator->getIterator(); # ArrayIterator

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
     * @Route("/search", name="test_search", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookTestRepository $testRepo
     * @return Response
     */
    public function search(Request $request, LogBookTestRepository $testRepo, LogBookCycleRepository $cycleRepo): Response
    {
        set_time_limit(30);
        $tests = array();
        $verdict = null;
        $setups = null;
        $sql = '';
        $leftDate = $rightDate = false;
        $startDate = $endDate = null;
        $DATE_TIME_TYPE = \Doctrine\DBAL\Types\Type::DATETIME;
        $test = new TestSearch();


        $form = $this->createForm(TestSearchType::class, $test, array());
        try {
            $form->handleRequest($request);
        } catch (\Exception $ex) {}


        $post = $request->request->get('test_search');
        if ($post !== null) {
            $enableSearch = false;
            if (array_key_exists('verdict', $post)) {
                $verdict = $post['verdict']['name'];
            }
            if (array_key_exists('setup', $post)) {
                $setups = $post['setup']['name'];
            }
            $test_name = $post['name'];
            $fromDate = $post['fromDate'];
            $toDate = $post['toDate'];

            $qb = $testRepo->createQueryBuilder('t')
                ->where('t.disabled = 0')
                ->orderBy('t.id', 'DESC')
                ->setMaxResults(2000);
            if ($fromDate !== null && mb_strlen($fromDate) > 7) {
                $startDate = \DateTime::createFromFormat('m/d/Y H:i', $fromDate . '00:00');
                if ($startDate !== false) {
                    $leftDate = true;
                }
            }
            if ($toDate !== null && mb_strlen($toDate) > 7) {
                $endDate = \DateTime::createFromFormat('m/d/Y H:i', $toDate . '23:59');
                if ($endDate !== false) {
                    $rightDate = true;
                }
            }
            if ($leftDate === true && $rightDate === true) {
                $qb->andWhere('t.timeStart BETWEEN :fromDate AND :toDate')
                    ->setParameter('fromDate', $startDate, $DATE_TIME_TYPE)
                    ->setParameter( 'toDate', $endDate, $DATE_TIME_TYPE);
                $enableSearch = True;
            } else if ($leftDate === true) {
                $qb->andWhere('t.timeStart >= :fromDate')
                    ->setParameter('fromDate', $startDate, $DATE_TIME_TYPE);
                $enableSearch = True;
            } else if ($rightDate === true) {
                $qb->andWhere('t.timeEnd <= :endDate')
                    ->setParameter('endDate', $endDate, $DATE_TIME_TYPE);
                $enableSearch = True;
            }

            if ($verdict !== null && \count($verdict) > 0) {
                $qb->andWhere('t.verdict IN (:verdict)')
                    ->setParameter('verdict', $verdict);
                $enableSearch = True;
            }

            if ($setups !== null && \count($setups) > 0) {
                $qbCycle = $cycleRepo->createQueryBuilder('c')
                    ->where('c.setup IN (:setups)')
                    ->setParameter('setups', $setups);
                $queryCycle = $qbCycle->getQuery()->getResult();
                $qb->andWhere('t.cycle IN (:cycles)')
                    ->setParameter('cycles', $queryCycle);
                $enableSearch = True;
            }

            if ($test_name !== null && \mb_strlen($test_name) > 2) {
                $qb->andWhere('t.name LIKE :test_name OR t.meta_data LIKE :metadata')
                    ->setParameter('test_name', '%'.$test_name.'%')
                    ->setParameter('metadata', $test_name.'%');
                $enableSearch = True;
            }
            if ($enableSearch) {
                $query = $qb->getQuery();
                $sql = $query->getSQL();
                $tests = $query->execute();
            }
            $a = 1;
        }

        return $this->render('lbook/test/search.html.twig', array(
            'test' => $test,
            'tests' => $tests,
            'iterator' => $tests,
            'tests_count' => \count($tests),
            'sql' => $sql,
//            'thisPage'      => 1,
//            'maxPages'      => 1,
            'form' => $form->createView(),
        ));
    }

    /**
     * Lists all Tests entities.
     *
     * @Route("/", name="test_index_first", methods={"GET"})
     * @Template(template="lbook/test/list.html.twig")
     * @param PagePaginator $pagePaginator
     * @param LogBookTestRepository $testRepo
     * @return array
     */
    public function indexFirst(PagePaginator $pagePaginator, LogBookTestRepository $testRepo): array
    {
        return $this->index(1, $pagePaginator, $testRepo);
    }

    /**
     * Creates a new test entity.
     *
     * @Route("/new", name="test_new", methods={"GET|POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     */
    public function new(Request $request)
    {
        $test = new LogBookTest();
        $form = $this->createForm(LogBookTestType::class, $test, array('search' => true));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($test);
            $em->flush();

            return $this->redirectToRoute('test_show_first', array('id' => $test->getId()));
        }

        return $this->render('lbook/test/new.html.twig', array(
            'test' => $test,
            'form' => $form->createView(),
        ));
    }

    /**
     * @param LogBookTest $test
     * @return bool
     */
    protected function isTestFileExist(LogBookTest $test): bool
    {
        return file_exists($this->getTestFilePath($test));
    }

    /**
     * @param LogBookTest $test
     * @return string
     */
    protected function getTestFilePath(LogBookTest $test): string
    {
        $retFileName = $test->getLogFile();
        $cycle = $test->getCycle();
        $setup = $cycle->getSetup();
        $tmp = '../uploads/%d/%d/%s';
        return sprintf($tmp, $setup->getId(), $cycle->getId(), $retFileName);
    }

    /**
     * @Route("/{id}/downloadlog", name="download_log", methods={"GET"})
     * @param LogBookTest|null $test
     * @return BinaryFileResponse|Response
     */
    public function downloadLogFile(LogBookTest $test = null): Response
    {
        try {
            if (!$test ) {
                throw new \RuntimeException('');
            }

            if (!$this->isTestFileExist($test)) {
                throw new \RuntimeException(sprintf('Log file for test [%d:%s] not exist', $test->getId(), $test->getName()));
            }

            $cycle = $test->getCycle();
            $setup = $cycle->getSetup();
            $path = $this->getTestFilePath($test);

            $ext = pathinfo($path, PATHINFO_EXTENSION);

            if ($ext !== null && $ext !== '') {
                $tmp = '%d-%d-%d__%s_-_%s_-_%s.%s';
                $retFileName = sprintf($tmp, $setup->getId(), $cycle->getId(), $test->getId(), $setup->getName(), $cycle->getName(), $test->getName(), $ext);
            } else {
                $tmp = '%d-%d-%d__%s_-_%s_-_%s.%s';
                $retFileName = sprintf($tmp, $setup->getId(), $cycle->getId(), $test->getId(), $setup->getName(), $cycle->getName(), $test->getName(), 'txt');
            }
            return $this->file($path, $retFileName);
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * @Route("/{id}/showlog", name="show_log", methods={"GET"})
     * @param LogBookTest|null $test
     * @return BinaryFileResponse|Response
     */
    public function showLogFile(LogBookTest $test = null): Response
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }

            if (!$this->isTestFileExist($test)) {
                throw new \RuntimeException(sprintf('Log file for test [%d:%s] not exist', $test->getId(), $test->getName()));
            }

            $textResponse = new Response(file_get_contents($this->getTestFilePath($test)) , 200);
            $textResponse->headers->set('Content-Type', 'text/plain');
            return $textResponse;
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Finds and displays a test entity.
     *
     * @Route("/{id}", name="test_show_first", methods={"GET"})
     * @param LogBookTest $test
     * @param PagePaginator $pagePaginator
     * @param LogBookMessageRepository $logRepo
     * @param LogBookTestRepository $testRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(LogBookTest $test = null, PagePaginator $pagePaginator, LogBookMessageRepository $logRepo, LogBookTestRepository $testRepo): ?Response
    {
        return $this->showFull($test, 1, $pagePaginator, $logRepo, $testRepo);
    }

    /**
     * Finds and displays a test entity.
     *
     * @Route("/{id}/page/{page}", name="test_show", methods={"GET"})
     * @param LogBookTest $test
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookMessageRepository $logRepo
     * @param LogBookTestRepository $testRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFull(LogBookTest $test = null, $page = 1, PagePaginator $pagePaginator, LogBookMessageRepository $logRepo, LogBookTestRepository $testRepo): ?Response
    {
        set_time_limit(10);
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }

            $qb = $logRepo->createQueryBuilder('log_book_message')
                ->where('log_book_message.test = :test')
                ->setCacheable(true)
                ->setLifetime(120)
                ->orderBy('log_book_message.chain', 'ASC')
                ->setParameter('test', $test->getId());
            $paginator = $pagePaginator->paginate($qb, $page, $this->log_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $impossibleSize = $this->log_size * ($page-1) + 1;
            $maxPages = ceil($totalPosts / $this->log_size);

            if ($page > 1 && $totalPosts < $impossibleSize && $iterator->count() === 0) {
                return $this->redirectToRoute('test_show', [
                    'id' => $test->getId(),
                    'page' => max(1, min($maxPages, $page - 1))
                ]);
            }

            $thisPage = $page;
            $test_left = $test_right = null;
//            $deleteForm = $this->createDeleteForm($test);
            $cycle = $test->getCycle();
            $tests_count = $cycle->getTestsCount();
            $max_res = 2;
            if ($tests_count <= 1){
                /** If one or zero tests in cycle - there is no test on right or on left */
                $max_res = 0;
            } else if ($test->getExecutionOrder() === 0) {
                /** If test_count 2 or more and execution order is 0 - there is no test on left */
                $max_res = 1;
            } elseif ($tests_count -1 === $test->getExecutionOrder()){
                /** If test_count 2 or more and execution order is same as test_count - there is no test on right */
                $max_res = 1;
            }
            try {
                if ($max_res > 0) {
                    $qbq = $testRepo->createQueryBuilder('t')
                        ->where('t.cycle = :cycle_id')
                        ->andWhere('t.disabled = 0')
                        ->andWhere('t.forDelete = 0')
                        ->andWhere('t.executionOrder = :order_lower or t.executionOrder = :order_upper')
                        ->orderBy('t.executionOrder', 'ASC')
                        ->setParameters(array(
                            'cycle_id' => $cycle->getId(),
                            'order_lower' => $test->getExecutionOrder() - 1,
                            'order_upper' => $test->getExecutionOrder() + 1
                        ))
                        ->setCacheable(true)
                        ->setLifetime(30)
                        ->setMaxResults($max_res)
                        ->getQuery();
                    /** @var array $tests */
                    $tests = $qbq->execute();
                    if (\count($tests) === 2) {
                        [$test_left, $test_right] = $tests;
                    } elseif (\count($tests) === 1) {
                        /** @var LogBookTest $someTest */
                        $someTest = $tests[0];
                        if ($someTest->getExecutionOrder() < $test->getExecutionOrder()) {
                            $test_left = $someTest;
                        } else {
                            $test_right = $someTest;
                        }
                    }
                }
            } catch (\Exception $ex) {
                throw new \RuntimeException($ex->getMessage());
            }

            return $this->render('lbook/test/show.full.html.twig', array(
                'test'          => $test,
                'size'          => $totalPosts,
                'maxPages'      => $maxPages,
                'thisPage'      => $thisPage,
                'iterator'      => $iterator,
                'paginator'     => $paginator,
                'test_left'     => $test_left,
                'test_right'    => $test_right,
//                'delete_form'   => $deleteForm->createView(),
                'file_exist'    => $this->isTestFileExist($test),
            ));
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * @param LogBookTest|null $test
     * @param \Throwable $ex
     * @return Response
     */
    protected function testNotFound(LogBookTest $test = null, \Throwable $ex): Response
    {
        /** @var Request $request */
        $request= $this->get('request_stack')->getCurrentRequest();
        $possibleId = 0;
        $response = $otherResponse = null;
        $short_msg = 'Unknown error';
        try {
            $possibleId = $request->attributes->get('id');
            $response = new Response('', Response::HTTP_NOT_FOUND);
            if ( $ex->getCode() > 0 && Response::$statusTexts[$ex->getCode()] !== '') {
                $otherResponse = new Response('', $ex->getCode());
                $short_msg = Response::$statusTexts[$ex->getCode()];
            } else {
                $otherResponse = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $ex) {}
        if ($test === null) {
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Test with provided ID:[%s] not found', $possibleId),
                'message' =>  $ex->getMessage(),
                'ex' => $ex,
            ), $response);
        }

        return $this->render('lbook/500.html.twig', array(
            'short_message' => $short_msg,
            'message' => $ex->getMessage(),
            'ex' => $ex,
        ), $otherResponse);
    }

    /**
     * Displays a form to edit an existing test entity.
     *
     * @Route("/{id}/edit", name="test_edit", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\LogicException|\Symfony\Component\Security\Core\Exception\AccessDeniedException|\LogicException
     */
    public function edit(Request $request, LogBookTest $test = null)
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('edit', $test->getCycle()->getSetup());
            $deleteForm = $this->createDeleteForm($test);
            $editForm = $this->createForm(LogBookTestType::class, $test);
            $editForm->handleRequest($request);

            if ($editForm->isSubmitted() && $editForm->isValid()) {
                /** @var LogBookCycle $cycle */
                $cycle = $test->getCycle();
                $cycle->setDirty(true);
                $this->getDoctrine()->getManager()->flush();
                return $this->redirectToRoute('test_edit', array('id' => $test->getId()));
            }

            return $this->render('lbook/test/edit.html.twig', array(
                'test' => $test,
                'edit_form' => $editForm->createView(),
                'delete_form' => $deleteForm->createView(),
            ));
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Deletes a test entity.
     *
     * @Route("/{id}", name="test_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\RedirectResponse | Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function delete(Request $request, LogBookTest $test = null)
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('delete', $test->getCycle()->getSetup());
            $form = $this->createDeleteForm($test);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var LogBookCycle $cycle */
                $cycle = $test->getCycle();
                $cycle->setDirty(true);
                $em = $this->getDoctrine()->getManager();
                $em->remove($test);
                $em->flush();
            }
            return $this->redirectToRoute('test_index');
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Creates a form to delete a test entity.
     *
     * @param LogBookTest $test The test entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookTest $test)
    {
        try {
            return $this->createFormBuilder()
                ->setAction($this->generateUrl('test_delete', array('id' => $test->getId())))
                ->setMethod('DELETE')
                ->getForm();
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }
}
