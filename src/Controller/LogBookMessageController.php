<?php

namespace App\Controller;

use App\Form\LogBookMessageType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\LogBookMessage;
use App\Repository\LogBookMessageRepository;
use App\Service\PagePaginator;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Log controller.
 *
 * @Route("log")
 */
class LogBookMessageController extends AbstractController
{
    protected $index_size = 2000;

    /**
     * @Route("/page/{page}", name="log_index", methods={"GET"})
     * @Template(template="lbook/log/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookMessageRepository $logRepo
     * @return Response
     */
    public function index($page = 1, PagePaginator $pagePaginator, LogBookMessageRepository $logRepo): Response
    {
        set_time_limit(30);

        $paginator = $pagePaginator->paginate($logRepo->createQueryBuilder('t'), $page, $this->index_size);
        $totalPosts = $paginator->count();
        return $this->render('lbook/log/index.html.twig', array(
            'size'      => $totalPosts,
            'maxPages'  => ceil($totalPosts / $this->index_size),
            'thisPage'  => $page,
            'iterator'  => $paginator->getIterator(),
            'paginator' => $paginator,
        ));
    }

    /**
     * Creates a new Log/Message entity.
     *
     * @Route("/new", name="log_new", methods={"GET|POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookMessage();
        $form = $this->createForm(LogBookMessageType::class, $obj);
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
     * @Route("/{id}", name="log_show", methods={"GET"})
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(LogBookMessage $obj = null): Response
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $deleteForm = $this->createDeleteForm($obj);
            return $this->render('lbook/log/show.html.twig', array(
                'log' => $obj,
                'delete_form' => $deleteForm->createView(),
            ));
        } catch (\Throwable $ex) {
            return $this->logNotFound($obj, $ex);
        }

    }

    /**
     * Displays a form to edit an existing Log/Message entity.
     *
     * @Route("/{id}/edit", name="log_edit", methods={"GET|POST"})
     * @param Request $request
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \LogicException
     */
    public function edit(Request $request, LogBookMessage $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $deleteForm = $this->createDeleteForm($obj);
            $editForm = $this->createForm(LogBookMessageType::class, $obj);
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
        } catch (\Throwable $ex) {
            return $this->logNotFound($obj, $ex);
        }
    }

    /**
     * Deletes a Log / Message entity.
     *
     * @Route("/{id}", name="log_delete", methods={"DELETE"})
     * @param Request $request
     * @param LogBookMessage $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \LogicException
     */
    public function delete(Request $request, LogBookMessage $obj = null): RedirectResponse
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $form = $this->createDeleteForm($obj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->remove($obj);
                $em->flush();
            }

            return $this->redirectToRoute('log_index');
        } catch (\Throwable $ex) {
            return $this->logNotFound($obj, $ex);
        }
    }

    /**
     * Creates a form to delete a Log/ Message entity.
     *
     * @param LogBookMessage $obj The verdict entity
     *
     * @return \Symfony\Component\Form\FormInterface | Response
     */
    private function createDeleteForm(LogBookMessage $obj)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('log_delete', array('id' => $obj->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    private function logNotFound(LogBookMessage $log = null, \Throwable $ex)
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

        if ($log === null) {
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Log with provided ID:[%s] not found', $possibleId),
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
}
