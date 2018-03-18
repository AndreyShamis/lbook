<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Cycle controller.
 *
 * @Route("cycle")
 */
class LogBookCycleController extends Controller
{

    protected $show_tests_size = 2000;
    protected $index_size = 100;
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
     * Lists all cycle entities.
     *
     * @Route("/page/{page}", name="cycle_index")
     * @Method("GET")
     * @Template(template="lbook/cycle/index.html.twig")
     * @param int $page
     * @return array
     */
    public function index($page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $cycleRepo = $em->getRepository('App:LogBookCycle');
        $query = $cycleRepo->createQueryBuilder('t')
            ->orderBy('t.id', "DESC");
        $paginator = $this->paginate($query, $page, $this->index_size);
        //$posts = $this->getAllPosts($page); // Returns 5 posts out of 20
        // You can also call the count methods (check PHPDoc for `paginate()`)
        //$totalPostsReturned = $paginator->getIterator()->count(); # Total fetched (ie: `5` posts)
        $totalPosts = $paginator->count(); # Count of ALL posts (ie: `20` posts)
        $iterator = $paginator->getIterator(); # ArrayIterator

        $maxPages = ceil($totalPosts / $this->index_size);
        $thisPage = $page;
        return array(
            //'cycles' => $cycles,
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        );
//        $em = $this->getDoctrine()->getManager();
//
//        $cycles = $em->getRepository('App:LogBookCycle')->findAll();
//
//        return $this->render('lbook/cycle/index.html.twig', array(
//            'cycles' => $cycles,
//        ));
    }

    /**
     * Creates a new cycle entity.
     *
     * @Route("/new", name="cycle_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookCycle();
        $form = $this->createForm('App\Form\LogBookCycleType', $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('cycle_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/cycle/new.html.twig', array(
            'cycle' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a cycle entity with paginator.
     *
     * @Route("/{id}/page/{page}", name="cycle_show")
     * @Method("GET")
     * @param LogBookCycle $cycle
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFullAction(LogBookCycle $cycle=null, $page=1)
    {
        try{
            $em = $this->getDoctrine()->getManager();
            $tests = $em->getRepository('App:LogBookTest');
            $qb = $tests->createQueryBuilder('t')
                ->where('t.cycle = :cycle')
                ->orderBy("t.executionOrder", "ASC")
                ->setParameter('cycle', $cycle->getId());
            $paginator = $this->paginate($qb, $page, $this->show_tests_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $maxPages = ceil($totalPosts / $this->show_tests_size);
            $thisPage = $page;

            $deleteForm = $this->createDeleteForm($cycle);

            return $this->render('lbook/cycle/show.full.html.twig', array(
                'cycle'          => $cycle,
                'size'          => $totalPosts,
                'maxPages'      => $maxPages,
                'thisPage'      => $thisPage,
                'iterator'      => $iterator,
                'paginator'     => $paginator,
                'delete_form'   => $deleteForm->createView(),
            ));
        }
        catch (\Throwable $ex){
            return $this->cycleNotFound($cycle, $ex);
        }
    }

    /**
     * Finds and displays a cycle entity.
     *
     * @Route("/{id}", name="cycle_show_full")
     * @Method("GET")
     * @param LogBookCycle $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookCycle $obj)
    {
        //$deleteForm = $this->createDeleteForm($obj);

        return $this->render('lbook/cycle/show.html.twig', array(
            'cycle' => $obj,
//            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * @param LogBookCycle|null $cycle
     * @param \Throwable $ex
     * @return Response
     */
    protected function cycleNotFound(LogBookCycle $cycle=null, \Throwable $ex){
        global $request;
        $possibleId = 0;
        try{
            $possibleId = $request->attributes->get('id');
        }
        catch (\Exception $ex){

        }
        if($cycle === null){
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Cycle with provided ID:[%s] not found', $possibleId),
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
     * Displays a form to edit an existing cycle entity.
     *
     * @Route("/{id}/edit", name="cycle_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookCycle $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LogBookCycle $obj)
    {
        $this->denyAccessUnlessGranted('edit', $obj->getSetup());
        $deleteForm = $this->createDeleteForm($obj);
        $editForm = $this->createForm('App\Form\LogBookCycleType', $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('cycle_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/cycle/edit.html.twig', array(
            'cycle' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a setup entity.
     *
     * @Route("/{id}", name="cycle_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookCycle $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LogBookCycle $obj)
    {
        $this->denyAccessUnlessGranted('delete', $obj->getSetup());
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $cycleRepo = $em->getRepository('App:LogBookCycle');
            $cycleRepo->delete($obj);
        }

        return $this->redirectToRoute('cycle_index');
    }

    /**
     * Creates a form to delete a setup entity.
     *
     * @param LogBookCycle $obj The cycle entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookCycle $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('cycle_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
