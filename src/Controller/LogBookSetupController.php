<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Entity\LogBookTest;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Setup controller.
 *
 * @Route("setup")
 */
class LogBookSetupController extends Controller
{
    protected $index_size = 50;
    protected $show_cycle_size = 15;
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
     * Lists all setup entities.
     *
     * @Route("/page/{page}", name="setup_index")
     * @Method("GET")
     * @Template(template="lbook/setup/index.html.twig")
     * @param int $page
     * @return array
     */
    public function index(int $page=1)
    {
        $em = $this->getDoctrine()->getManager();
        $cycleRepo = $em->getRepository('App:LogBookSetup');
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
            //'setups' => $setups,
            'size'      => $totalPosts,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        );
//        $em = $this->getDoctrine()->getManager();
//
//        $setups = $em->getRepository('App:LogBookSetup')->findAll();
//
//        return $this->render('lbook/setup/index.html.twig', array(
//            'setups' => $setups,
//        ));
    }

    /**
     * Creates a new setup entity.
     *
     * @Route("/new", name="setup_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookSetup();
        $form = $this->get('form.factory')->create('App\Form\LogBookSetupType', $obj, array(
            'user' => $this->get('security.token_storage')->getToken()->getUser(),
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('setup_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/setup/new.html.twig', array(
            'test' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a setup entity.
     *
     * @Route("/{id}/page/{page}", name="setup_show")
     * @Method("GET")
     * @param LogBookSetup $setup
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFullAction(LogBookSetup $setup=null, $page=1)
    {
        try{
            $em = $this->getDoctrine()->getManager();
            $logs = $em->getRepository('App:LogBookCycle');
            $qb = $logs->createQueryBuilder('t')
                ->where('t.setup = :setup')
                ->orderBy("t.updatedAt", "DESC")
                ->setParameter('setup', $setup->getId());
            $paginator = $this->paginate($qb, $page, $this->show_cycle_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $maxPages = ceil($totalPosts / $this->show_cycle_size);
            $thisPage = $page;

            $deleteForm = $this->createDeleteForm($setup);

            return $this->render('lbook/setup/show.full.html.twig', array(
                'setup'          => $setup,
                'size'          => $totalPosts,
                'maxPages'      => $maxPages,
                'thisPage'      => $thisPage,
                'iterator'      => $iterator,
                'paginator'     => $paginator,
                'delete_form'   => $deleteForm->createView(),
            ));
        }
        catch (\Throwable $ex){
            return $this->setupNotFound($setup, $ex);
        }
    }

    /**
     * @param LogBookSetup|null $setup
     * @param \Throwable $ex
     * @return Response
     */
    protected function setupNotFound(LogBookSetup $setup=null, \Throwable $ex){
        global $request;
        $possibleId = 0;
        try{
            $possibleId = $request->attributes->get('id');
        }
        catch (\Exception $ex){

        }
        if($setup === null){
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Setup with provided ID:[%s] not found', $possibleId),
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
     * Finds and displays a setup entity.
     *
     * @Route("/{id}", name="setup_show_full")
     * @Method("GET")
     * @param LogBookSetup $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookSetup $obj)
    {
        $user= $this->get('security.token_storage')->getToken()->getUser();
        /** @var PersistentCollection $moderators */
        $moderators = $obj->getModerators();
        //if(in_array($user, $moderators)){
        if($moderators->contains($user)){
            $deleteForm = $this->createDeleteForm($obj)->createView();
        }
        else{
            $deleteForm = null;
        }

        return $this->render('lbook/setup/show.html.twig', array(
            'setup' => $obj,
            'delete_form' => $deleteForm,
        ));
    }

    /**
     * Displays a form to edit an existing setup entity.
     *
     * @Route("/{id}/edit", name="setup_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookSetup $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LogBookSetup $obj)
    {
        $user= $this->get('security.token_storage')->getToken()->getUser();

        // check for "edit" access: calls all voters
        $this->denyAccessUnlessGranted('edit', $obj);
        /** @var PersistentCollection $moderators */
        $moderators = $obj->getModerators();
        $deleteForm = $this->createDeleteForm($obj);
        //if(in_array($user, $moderators)){
//        if($moderators->contains($user)){
//            $deleteForm = $this->createDeleteForm($obj)->createView();
//        }
//        else{
//            $deleteForm = null;
//        }


        $editForm = $this->get('form.factory')->create('App\Form\LogBookSetupType', $obj, array(
            'user' => $user,
        ));
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('setup_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/setup/edit.html.twig', array(
            'setup' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a setup entity.
     *
     * @Route("/{id}", name="setup_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookSetup $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LogBookSetup $obj)
    {
        $this->denyAccessUnlessGranted('delete', $obj);
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $setupRepo = $em->getRepository('App:LogBookSetup');
            $setupRepo->delete($obj);
            $em->refresh($obj);
            $em->remove($obj);
            $em->flush();
        }

        return $this->redirectToRoute('setup_index');
    }

    /**
     * Creates a form to delete a setup entity.
     *
     * @param LogBookSetup $obj The test entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookSetup $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('setup_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
