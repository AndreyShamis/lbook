<?php

namespace App\Controller;

use App\Entity\LogBookSetup;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookSetupRepository;
use App\Service\PagePaginator;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Form\LogBookSetupType;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Setup controller.
 *
 * @Route("setup")
 */
class LogBookSetupController extends Controller
{
    protected $index_size = 50;
    protected $show_cycle_size = 100;

    /**
     * Lists all setup entities.
     *
     * @Route("/page/{page}", name="setup_index")
     * @Method("GET")
     * @Template(template="lbook/setup/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookSetupRepository $setupRepo
     * @return array
     */
    public function index(int $page = 1, PagePaginator $pagePaginator, LogBookSetupRepository $setupRepo): array
    {
        $query = $setupRepo->createQueryBuilder('t')
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
     * Creates a new setup entity.
     *
     * @Route("/new", name="setup_new")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\LogicException|\LogicException|\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function newAction(Request $request)
    {
        $obj = new LogBookSetup();
        $form = $this->get('form.factory')->create(LogBookSetupType::class, $obj, array(
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
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFullAction(LogBookSetup $setup = null, $page = 1, PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo): ?Response
    {
        try {
            if ($setup === null) {
                throw new \RuntimeException('');
            }
            $qb = $cycleRepo->createQueryBuilder('t')
                ->where('t.setup = :setup')
                ->orderBy('t.updatedAt', 'DESC')
                ->setParameter('setup', $setup->getId());
            $paginator = $pagePaginator->paginate($qb, $page, $this->show_cycle_size);
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
        } catch (\Throwable $ex) {
            return $this->setupNotFound($setup, $ex);
        }
    }

    /**
     * @param LogBookSetup|null $setup
     * @param \Throwable $ex
     * @return Response
     */
    protected function setupNotFound(LogBookSetup $setup = null, \Throwable $ex): ?Response
    {
        /** @var Request $request */
        $request= $this->get('request_stack')->getCurrentRequest();
        $possibleId = 0;
        $response = $otherResponse = null;
        $short_msg = 'Unknown error';
        try {;
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
        if ($setup === null) {
            return $this->render('lbook/404.html.twig', array(
                'short_message' => sprintf('Setup with provided ID:[%s] not found', $possibleId),
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
     * Finds and displays a setup entity.
     *
     * @Route("/{id}", name="setup_show_full")
     * @Method("GET")
     * @param LogBookSetup $obj
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction(LogBookSetup $obj = null): Response
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $user= $this->get('security.token_storage')->getToken()->getUser();
            /** @var PersistentCollection $moderators */
            $moderators = $obj->getModerators();
            //if(in_array($user, $moderators)){
            if ($moderators->contains($user)) {
                $deleteForm = $this->createDeleteForm($obj)->createView();
            } else {
                $deleteForm = null;
            }

            return $this->render('lbook/setup/show.html.twig', array(
                'setup' => $obj,
                'delete_form' => $deleteForm,
            ));
        } catch (\Throwable $ex) {
            return $this->setupNotFound($obj, $ex);
        }
    }

    /**
     * Displays a form to edit an existing setup entity.
     *
     * @Route("/{id}/edit", name="setup_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookSetup $obj
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \LogicException|\Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function editAction(Request $request, LogBookSetup $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $user= $this->get('security.token_storage')->getToken()->getUser();

            // check for "edit" access: calls all voters
            $this->denyAccessUnlessGranted('edit', $obj);
            /** @var PersistentCollection $moderators */
            //$moderators = $obj->getModerators();
            $deleteForm = $this->createDeleteForm($obj);
            //if (in_array($user, $moderators)) {
    //        if ($moderators->contains($user)) {
    //            $deleteForm = $this->createDeleteForm($obj)->createView();
    //        } else {
    //            $deleteForm = null;
    //        }

            $editForm = $this->get('form.factory')->create(LogBookSetupType::class, $obj, array(
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

        } catch (\Throwable $ex) {
            return $this->setupNotFound($obj, $ex);
        }
    }

    /**
     * Deletes a setup entity.
     *
     * @Route("/{id}", name="setup_delete")
     * @Method("DELETE")
     * @param Request $request
     * @param LogBookSetup $obj
     * @return RedirectResponse|Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException|\LogicException
     */
    public function deleteAction(Request $request, LogBookSetup $obj = null)
    {
        try {
            if (!$obj) {
                throw new \RuntimeException('');
            }
            $this->denyAccessUnlessGranted('delete', $obj);
            $form = $this->createDeleteForm($obj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();

                $setupRepo = $em->getRepository('App:LogBookSetup');
                $setupRepo->delete($obj);
            }

            return $this->redirectToRoute('setup_index');
        } catch (\Throwable $ex) {
            return $this->setupNotFound($obj, $ex);
        }
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
            ->getForm();
    }
}
