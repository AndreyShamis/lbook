<?php

namespace App\Controller;

use App\Entity\LogBookMessageType;
use App\Form\LogBookMessageTypeType;
use App\Repository\LogBookMessageTypeRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * LogType controller.
 *
 * @Route("msg_type")
 */
class LogBookMessageTypeController extends Controller
{
    /**
     * @Route("/", name="msg_type_index")
     * @param LogBookMessageTypeRepository $logBookMessageTypeRepository
     * @return Response
     */
    public function index(LogBookMessageTypeRepository $logBookMessageTypeRepository): Response
    {
        return $this->render('lbook/msg_type/index.html.twig', ['msg_types' => $logBookMessageTypeRepository->findAll()]);
    }

    /**
     * Creates a new Log/Message Type entity.
     *
     * @Route("/new", name="msg_type_new", methods={"GET|POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookMessageType();
        $form = $this->createForm(LogBookMessageTypeType::class, $obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($obj);
            $em->flush();

            return $this->redirectToRoute('msg_type_show', array('id' => $obj->getId()));
        }

        return $this->render('lbook/msg_type/new.html.twig', array(
            'msg_type' => $obj,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Log/Message Type entity.
     *
     * @Route("/{id}", name="msg_type_show", methods={"GET"})
     * @param LogBookMessageType $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookMessageType $obj): Response
    {
        $this->denyAccessUnlessGranted('view', $obj);
        $deleteForm = $this->createDeleteForm($obj);

        return $this->render('lbook/msg_type/show.html.twig', array(
            'msg_type' => $obj,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Log/Message Type entity.
     *
     * @Route("/{id}/edit", name="msg_type_edit", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookMessageType $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     */
    public function editAction(Request $request, LogBookMessageType $obj)
    {
        $this->denyAccessUnlessGranted('edit', $obj);
        $deleteForm = $this->createDeleteForm($obj);
        $editForm = $this->createForm(LogBookMessageTypeType::class, $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('msg_type_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/msg_type/edit.html.twig', array(
            'msg_type' => $obj,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Log/Message Type entity.
     *
     * @Route("/{id}", name="msg_type_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookMessageType $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \LogicException
     */
    public function deleteAction(Request $request, LogBookMessageType $obj): RedirectResponse
    {
        $this->denyAccessUnlessGranted('delete', $obj);
        $form = $this->createDeleteForm($obj);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($obj);
            $em->flush();
        }

        return $this->redirectToRoute('msg_type_index');
    }

    /**
     * Creates a form to delete a Log/Message Type entity.
     *
     * @param LogBookMessageType $obj The verdict entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookMessageType $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('msg_type_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm()
            ;
    }
}
