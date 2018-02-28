<?php
//
//namespace App\Controller;
//
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
//use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\HttpFoundation\Response;
//use App\Entity\LogBookTest;
//
//class LogBookTestController extends Controller
//{
//    /**
//     * @Route("/log/book", name="log_book")
//     */
//    public function index()
//    {
//        $em = $this->getDoctrine()->getManager();
//
//        $logbook_tests = $em->getRepository('App:LogBookTest')->findAll();
//
//        return $this->render('lbook/test/index.html.twig', array(
//            'logbook_tests' => $logbook_tests,
//        ));
//
//        // replace this line with your own code!
//        return $this->render('@Maker/demoPage.html.twig', [ 'path' => str_replace($this->getParameter('kernel.project_dir').'/', '', __FILE__) ]);
//    }
//}

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookTest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * Test controller.
 *
 * @Route("test")
 */
class LogBookTestController extends Controller
{

    /**
     * Paginator Helper
     *
     * Pass through a query object, current page & limit
     * the offset is calculated from the page and limit
     * returns an `Paginator` instance, which you can call the following on:
     *
     *     $paginator->getIterator()->count() # Total fetched (ie: `5` posts)
     *     $paginator->count() # Count of ALL posts (ie: `20` posts)
     *     $paginator->getIterator() # ArrayIterator
     *
     * @param Query|QueryBuilder $dql  A Doctrine ORM query or query builder.
     * @param integer            $page  Current page (defaults to 1)
     * @param integer            $limit The total number per page (defaults to 5)
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function paginate($dql, $page = 1, $limit = 20)
    {
        $paginator = new Paginator($dql);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            ->setMaxResults($limit); // Limit
        return $paginator;
    }

    /**
     * Lists all Tests entities.
     *
     * @Route("/page/{page}", name="_test_index")
     * @Method("GET")
     * @Template(template="lbook/test/list.html.twig")
     * @param int $page
     * @return array
     */
    public function listAction($page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $limit = 40;
        $testRepo = $em->getRepository('App:LogBookTest');
        $query = $testRepo->createQueryBuilder('t')
            ->orderBy('t.id', "DESC");
        $paginator = $this->paginate($query, $page, $limit);
        //$posts = $this->getAllPosts($page); // Returns 5 posts out of 20
        // You can also call the count methods (check PHPDoc for `paginate()`)
        //$totalPostsReturned = $paginator->getIterator()->count(); # Total fetched (ie: `5` posts)
        $totalPosts = $paginator->count(); # Count of ALL posts (ie: `20` posts)
        $iterator = $paginator->getIterator(); # ArrayIterator

        $maxPages = ceil($totalPosts / $limit);
        $thisPage = $page;
        return array(
//            'tests'     => $testRepo,
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        );
    }

    /**
     * Lists all test entities.
     *
     * @Route("/", name="test_index")
     * @Method("GET")
     */
    public function index()
    {
        set_time_limit(180);
        $em = $this->getDoctrine()->getManager();
        $testRepo = $em->getRepository('App:LogBookTest');
        //$query  = $logs->createQueryBuilder('a');
        $query = $testRepo->createQueryBuilder('t')
            ->orderBy('t.id', "DESC")
            ->setMaxResults(300);
        $tests = $query->getQuery()->execute();
        return $this->render('lbook/test/index.html.twig', array(
            'tests' => $tests,
        ));

    }

    /**
     * Creates a new test entity.
     *
     * @Route("/new", name="test_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $test = new LogBookTest();
        $form = $this->createForm('App\Form\LogBookTestType', $test);
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
     * @Route("/{id}", name="test_show")
     * @Method("GET")
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookTest $test=null)
    {
        try{

            $deleteForm = $this->createDeleteForm($test);

            return $this->render('lbook/test/show.html.twig', array(
                'test' => $test,
                'delete_form' => $deleteForm->createView(),
            ));
        }
        catch (\Throwable $ex){
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * Finds and displays a test entity.
     *
     * @Route("/{id}/page/{page}", name="test_show_full")
     * @Method("GET")
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFullAction(LogBookTest $test=null, $page=1)
    {
        try{

            $em = $this->getDoctrine()->getManager();
            $limit = 500;
            $logs = $em->getRepository('App:LogBookMessage');
            $qb = $logs->createQueryBuilder('t')
                ->where('t.test = :test')
                ->setParameter('test', $test->getId());
            $paginator = $this->paginate($qb, $page, $limit);
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
        catch (\Throwable $ex){
            return $this->testNotFound($test, $ex);
        }
    }

    /**
     * @param LogBookTest|null $test
     * @param \Throwable $ex
     * @return Response
     */
    protected function testNotFound(LogBookTest $test=null, \Throwable $ex){
        global $request;
        $possibleId = 0;
        try{
            $possibleId = $request->attributes->get('id');
        }
        catch (\Exception $ex){

        }
        if($test === null){
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Test with provided ID:[%s] not found', $possibleId),
                'message' =>  $ex->getMessage(),
                'ex' => $ex,
            ));
        }
        else{
            return $this->render('lbook/404.html.twig', array(
                'short_message' => 'Unknown error',
                'message' => $ex->getMessage(),
                'ex' => $ex,
            ));
        }
    }

    /**
     * Displays a form to edit an existing test entity.
     *
     * @Route("/{id}/edit", name="test_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookTest $test
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LogBookTest $test)
    {
        $deleteForm = $this->createDeleteForm($test);
        $editForm = $this->createForm('App\Form\LogBookTestType', $test);
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
     */
    public function deleteAction(Request $request, LogBookTest $test)
    {
        try{
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
        }
        catch (\Throwable $ex){
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
        try{
            return $this->createFormBuilder()
                ->setAction($this->generateUrl('test_delete', array('id' => $test->getId())))
                ->setMethod('DELETE')
                ->getForm();
        }
        catch (\Throwable $ex){
            return $this->testNotFound($test, $ex);
        }

    }
}
