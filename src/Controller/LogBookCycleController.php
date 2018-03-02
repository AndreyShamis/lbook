<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cycle controller.
 *
 * @Route("cycle")
 */
class LogBookCycleController extends Controller
{

    /**
     * Lists all setup entities.
     *
     * @Route("/", name="cycle_index")
     * @Method("GET")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $cycles = $em->getRepository('App:LogBookCycle')->findAll();

        return $this->render('lbook/cycle/index.html.twig', array(
            'cycles' => $cycles,
        ));
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
     * Finds and displays a cycle entity.
     *
     * @Route("/{id}", name="cycle_show")
     * @Method("GET")
     * @param LogBookCycle $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookCycle $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);

        return $this->render('lbook/cycle/show.html.twig', array(
            'cycle' => $obj,
//            'delete_form' => $deleteForm->createView(),
        ));
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
            $em->remove($obj);
            $em->flush();
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
