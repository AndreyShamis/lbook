<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use App\Repository\LogBookMessageRepository;
use App\Repository\LogBookTestRepository;
use App\Service\PagePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
     * Finds and displays a test entity.
     *
     * @Route("/{id}", name="test_show_full")
     * @Method("GET")
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookTest $test = null): ?Response
    {
        try {
            $deleteForm = $this->createDeleteForm($test);
            return $this->render('lbook/test/show.html.twig', array(
                'test' => $test,
                'delete_form' => $deleteForm->createView(),
            ));
        }
        catch (\Throwable $ex) {
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFullAction(LogBookTest $test = null, $page = 1, PagePaginator $pagePaginator, LogBookMessageRepository $logRepo): ?Response
    {
        try {
            $limit = 500;
            $qb = $logRepo->createQueryBuilder('t')
                ->where('t.test = :test')
                ->setParameter('test', $test->getId());
            $paginator = $pagePaginator->paginate($qb, $page, $limit);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $maxPages = ceil($totalPosts / $limit);
            $thisPage = $page;

            $deleteForm = $this->createDeleteForm($test);

            return $this->render('lbook/test/show.full.html.twig', array(
                'test'          => $test,
                'size'          => $totalPosts,
                'maxPages'      => $maxPages,
                'thisPage'      => $thisPage,
                'iterator'      => $iterator,
                'paginator'     => $paginator,
                'delete_form'   => $deleteForm->createView(),
            ));
        }
        catch (\Throwable $ex) {
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
        global $request;
        $possibleId = 0;
        try {
            $possibleId = $request->attributes->get('id');
        } catch (\Exception $ex) {
        }
        if ($test === null) {
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Test with provided ID:[%s] not found', $possibleId),
                'message' =>  $ex->getMessage(),
                'ex' => $ex,
            ));
        }

        return $this->render('lbook/404.html.twig', array(
            'short_message' => 'Unknown error',
            'message' => $ex->getMessage(),
            'ex' => $ex,
        ));
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
    public function editAction(Request $request, LogBookTest $test)
    {
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
    public function deleteAction(Request $request, LogBookTest $test)
    {
        try {
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
