<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookMessageRepository;
use App\Repository\LogBookTestRepository;
use App\Service\PagePaginator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
    protected $index_size = 100;

    protected $log_size = 3000;

    /**
     * Lists all Tests entities.
     *
     * @Route("/page/{page}", name="test_index")
     * @Method("GET")
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
     * Lists all Tests entities.
     *
     * @Route("/", name="test_index_first")
     * @Method("GET")
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
     * @Route("/new", name="test_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     */
    public function newAction(Request $request)
    {
        $test = new LogBookTest();
        $form = $this->createForm(LogBookTestType::class, $test);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($test);
            $em->flush();

            return $this->redirectToRoute('test_show', array('id' => $test->getId()));
        }

        return $this->render('lbook/test/new.html.twig', array(
            'test' => $test,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{id}/downloadlog", name="download_log")
     * @Method("GET")
     * @param LogBookTest|null $test
     * @return BinaryFileResponse|Response
     */
    public function downloadLogFile(LogBookTest $test = null): Response
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $retFileName = $test->getLogFile();
            $cycle = $test->getCycle();
            $setup = $cycle->getSetup();
            $tmp = '../uploads/%d/%d/%s';
            $path = sprintf($tmp, $setup->getId(), $cycle->getId(), $retFileName);

            $ext = pathinfo($path, PATHINFO_EXTENSION);

            if ($ext !== null && $ext !== '') {
                $tmp = '%d_%s__%d_%s__%s.%s';
                $retFileName = sprintf($tmp, $setup->getId(), $setup->getName(), $cycle->getId(), $cycle->getName(), $test->getName(), $ext);
            }
            return $this->file($path, $retFileName);
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * @Route("/{id}/showlog", name="show_log")
     * @Method("GET")
     * @param LogBookTest|null $test
     * @return BinaryFileResponse|Response
     */
    public function showLogFile(LogBookTest $test = null): Response
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $retFileName = $test->getLogFile();
            $cycle = $test->getCycle();
            $setup = $cycle->getSetup();
            $tmp = '../uploads/%d/%d/%s';
            $path = sprintf($tmp, $setup->getId(), $cycle->getId(), $retFileName);
            $textResponse = new Response(file_get_contents($path) , 200);
            $textResponse->headers->set('Content-Type', 'text/plain');
            return $textResponse;
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Finds and displays a test entity.
     *
     * @Route("/{id}", name="test_show_full")
     * @Method("GET")
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(LogBookTest $test = null): ?Response
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }
            $deleteForm = $this->createDeleteForm($test);
            return $this->render('lbook/test/show.html.twig', array(
                'test' => $test,
                'delete_form' => $deleteForm->createView(),
            ));
        } catch (\Throwable $ex) {
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Finds and displays a test entity.
     *
     * @Route("/{id}/page/{page}", name="test_show")
     * @Method("GET")
     * @param LogBookTest $test
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookMessageRepository $logRepo
     * @param LogBookTestRepository $testRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFull(LogBookTest $test = null, $page = 1, PagePaginator $pagePaginator, LogBookMessageRepository $logRepo, LogBookTestRepository $testRepo): ?Response
    {
        try {
            if (!$test) {
                throw new \RuntimeException('');
            }

            $qb = $logRepo->createQueryBuilder('t')
                ->where('t.test = :test')
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
            $test_left = null;
            $test_right = null;

            $deleteForm = $this->createDeleteForm($test);

            try {
                $cycle = $test->getCycle();
                $qbq = $testRepo->createQueryBuilder('t')
                    ->where('t.cycle = :cycle_id')
                    ->andWhere('t.executionOrder = :order_lower or t.executionOrder = :order_upper')
                    ->orderBy('t.executionOrder', 'ASC')
                    ->setParameters(array(
                        'cycle_id' => $cycle->getId(),
                        'order_lower' => $test->getExecutionOrder() - 1,
                        'order_upper' => $test->getExecutionOrder() + 1
                    ))
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
                'delete_form'   => $deleteForm->createView(),
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
        } catch (\Exception $ex) {
        }
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
     * @Route("/{id}/edit", name="test_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\LogicException|\Symfony\Component\Security\Core\Exception\AccessDeniedException|\LogicException
     */
    public function editAction(Request $request, LogBookTest $test = null)
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
     * @Route("/{id}", name="test_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\RedirectResponse | Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function deleteAction(Request $request, LogBookTest $test = null)
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
