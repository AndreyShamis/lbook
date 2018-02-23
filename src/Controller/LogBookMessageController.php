<?php

namespace App\Controller;

use App\Entity\LogBookMessageType;
use App\Entity\LogBookMessage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Uploader controller.
 *
 * @Route("log")
 */
class LogBookMessageController extends Controller
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
     * @param Doctrine\ORM\Query $dql   DQL Query Object
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
     * @Route("/page/{page}", name="log_index")
     * @Method("GET")
     * @Template(template="lbook/log/index.html.twig")
     */
    public function index($page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $limit = 100;
        $logs = $em->getRepository('App:LogBookMessage');
        $paginator = $this->paginate($logs->createQueryBuilder('t'), $page, $limit);
        //$posts = $this->getAllPosts($page); // Returns 5 posts out of 20

// You can also call the count methods (check PHPDoc for `paginate()`)
        $totalPostsReturned = $paginator->getIterator()->count(); # Total fetched (ie: `5` posts)
        $totalPosts = $paginator->count(); # Count of ALL posts (ie: `20` posts)
        $iterator = $paginator->getIterator(); # ArrayIterator


        $maxPages = ceil($totalPosts / $limit);
        $thisPage = $page;
//        return $this->render('lbook/log/index.html.twig', array(
//            //'tests'     => $tests,
//            'maxPages'  => $maxPages,
//            'thisPage'  => $thisPage,
//            'iterator'  => $iterator,
//            'paginator' => $paginator,
//        ));
        return array(
            //'tests'     => $tests,
            'maxPages'  => $maxPages,
            'thisPage'  => $thisPage,
            'iterator'  => $iterator,
            'paginator' => $paginator,
        );

//        $em = $this->getDoctrine()->getManager();
//
//        $messages = $em->getRepository('App:LogBookMessage')->findAll();
//
//        return $this->render('lbook/log/index.html.twig', array(
//            'logs' => $messages,
//        ));
        // replace this line with your own code!
        //return $this->render('@Maker/demoPage.html.twig', [ 'path' => str_replace($this->getParameter('kernel.project_dir').'/', '', __FILE__) ]);
    }

    /**
     * Creates a new Log/Message entity.
     *
     * @Route("/new", name="log_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookMessage();
        $form = $this->createForm('App\Form\LogBookMessageType', $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('log_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/log/new.html.twig', array(
            'log' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Log/Message entity.
     *
     * @Route("/{id}", name="log_show")
     * @Method("GET")
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookMessage $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);

        return $this->render('lbook/log/show.html.twig', array(
            'log' => $obj,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Log/Message entity.
     *
     * @Route("/{id}/edit", name="log_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LogBookMessage $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);
        $editForm = $this->createForm('App\Form\LogBookMessageType', $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('log_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/log/edit.html.twig', array(
            'log' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Log / Message entity.
     *
     * @Route("/{id}", name="log_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LogBookMessage $obj)
    {
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($obj);
            $em->flush();
        }

        return $this->redirectToRoute('log_index');
    }

    /**
     * Creates a form to delete a Log/ Message entity.
     *
     * @param LogBookMessage $obj The verdict entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(LogBookMessage $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('log_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
