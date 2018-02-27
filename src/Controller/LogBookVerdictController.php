<?php

namespace App\Controller;

use App\Entity\LogBookVerdict;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Verdict controller.
 *
 * @Route("verdict")
 */
class LogBookVerdictController extends Controller
{

    /**
     * Lists all verdict entities.
     *
     * @Route("/", name="verdict_index")
     * @Method("GET")
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $verdicts = $em->getRepository('App:LogBookVerdict')->findAll();

        return $this->render('lbook/verdict/index.html.twig', array(
            'verdicts' => $verdicts,
        ));
    }

    /**
     * Creates a new verdict entity.
     *
     * @Route("/new", name="verdict_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookVerdict();
        $form = $this->createForm('App\Form\LogBookVerdictType', $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('verdict_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/verdict/new.html.twig', array(
            'verdict' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a verdict entity.
     *
     * @Route("/{id}", name="verdict_show")
     * @Method("GET")
     * @param LogBookVerdict $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookVerdict $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);

        return $this->render('lbook/verdict/show.html.twig', array(
            'verdict' => $obj,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing verdict entity.
     *
     * @Route("/{id}/edit", name="verdict_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookVerdict $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, LogBookVerdict $obj)
    {
        $deleteForm = $this->createDeleteForm($obj);
        $editForm = $this->createForm('App\Form\LogBookVerdictType', $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('verdict_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/verdict/edit.html.twig', array(
            'verdict' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a verdict entity.
     *
     * @Route("/{id}", name="verdict_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookVerdict $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, LogBookVerdict $obj)
    {
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($obj);
            $em->flush();
        }

        return $this->redirectToRoute('verdict_index');
    }

    /**
     * Creates a form to delete a verdict entity.
     *
     * @param LogBookVerdict $obj The verdict entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookVerdict $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('verdict_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
