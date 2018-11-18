<?php

namespace App\Controller;

use App\Entity\LogBookCycle;
use App\Entity\LogBookSetup;
use App\Entity\LogBookUser;
use App\Repository\LogBookCycleRepository;
use App\Repository\LogBookSetupRepository;
use App\Service\PagePaginator;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Form\LogBookSetupType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Doctrine\Common\Collections\Collection;

/**
 * Setup controller.
 *
 * @Route("setup")
 */
class LogBookSetupController extends Controller
{
    protected $index_size = 250;
    protected $show_cycle_size = 500;

    /**
     * Lists all setup entities.
     *
     * @Route("/json/page/{page}", name="setups", methods={"GET"})
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookSetupRepository $setupRepo
     * @return JsonResponse
     */
    public function indexJson(int $page = 1, PagePaginator $pagePaginator, LogBookSetupRepository $setupRepo): JsonResponse
    {
        $query = $setupRepo->createQueryBuilder('setups')
           // ->select(array('setups.id', 'setups.disabled', 'setups.updatedAt'))
              // ->addSelect(array('setups.updatedAt as updatedAtDiff'))
            ->where('setups.disabled = 0')
            ->orderBy('setups.updatedAt', 'DESC')
            ->addOrderBy('setups.id', 'DESC');

        $encoder = new JsonEncoder();
        $normalizer = new ObjectNormalizer();

        $dateTimeToStr = function ($dateTime) {
            return $dateTime instanceof \DateTime ? $dateTime->format(\DateTime::ATOM) : ''; //'d/m/Y H:i:s'
        };

        $owner_callback = function ($owner) {
            return $owner instanceof LogBookUser ? $owner->getUsername() : '';
        };
        $counter_callback = function ($obj) {
            return $obj instanceof Collection ? \count($obj) : 0;
        };
        $normalizer->setCallbacks([
            'cycles' => $counter_callback,
            'owner' => $owner_callback,
            'moderators' => $counter_callback,
            'createdAt' => $dateTimeToStr,
            'updatedAt' => $dateTimeToStr
        ]);
        $serializer = new Serializer(array($normalizer), array($encoder));

        $paginator = $pagePaginator->paginate($query, $page, $this->index_size);
        $paginator->setUseOutputWalkers(false);
        $res = $paginator->getQuery()->execute();
        $json = $serializer->serialize($res, 'json');

        $response = $this->json([]);
        $response->setJson($json);
        $response->setEncodingOptions(JSON_PRETTY_PRINT);
        return $response;
    }

    /**
     * Lists all setup entities.
     *
     * @Route("/page/{page}", name="setup_index", methods={"GET"})
     * @Template(template="lbook/setup/index.html.twig")
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookSetupRepository $setupRepo
     * @return array
     */
    public function index(int $page = 1, PagePaginator $pagePaginator, LogBookSetupRepository $setupRepo): array
    {
        $query = $setupRepo->createQueryBuilder('setups')
            ->where('setups.disabled = 0')
            ->orderBy('setups.updatedAt', 'DESC')
            ->addOrderBy('setups.id', 'DESC');

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
     * Lists all setup entities.
     *
     * @Route("/", name="setup_index_first", methods={"GET"})
     * @Template(template="lbook/setup/index.html.twig")
     * @param PagePaginator $pagePaginator
     * @param LogBookSetupRepository $setupRepo
     * @return array
     */
    public function indexFirst(PagePaginator $pagePaginator, LogBookSetupRepository $setupRepo): array
    {
        return $this->index(1, $pagePaginator, $setupRepo);
    }

    /**
     * Creates a new setup entity.
     *
     * @Route("/new", name="setup_new", methods={"GET|POST"})
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
     * @Route("/{id}/page/{page}", name="setup_show", methods={"GET"})
     * @param LogBookSetup $setup
     * @param int $page
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFull(LogBookSetup $setup = null, $page = 1, PagePaginator $pagePaginator = null, LogBookCycleRepository $cycleRepo = null): ?Response
    {
        try {
            if ($setup === null || $cycleRepo === null || $pagePaginator === null) {
                throw new \RuntimeException('');
            }
            $qb = $cycleRepo->createQueryBuilder('t')
                ->where('t.setup = :setup')
                ->orderBy('t.timeEnd', 'DESC') //updatedAt
                ->addOrderBy('t.updatedAt', 'DESC')
                ->setParameter('setup', $setup->getId());
            $paginator = $pagePaginator->paginate($qb, $page, $this->show_cycle_size);
            $totalPosts = $paginator->count(); // Count of ALL posts (ie: `20` posts)
            $iterator = $paginator->getIterator(); # ArrayIterator

            $maxPages = ceil($totalPosts / $this->show_cycle_size);
            $thisPage = $page;
            $deleteForm = $this->createDeleteForm($setup);

            $iterator->rewind();
            $show_build = false;
            $prev_build_id = 0;
            if ($totalPosts > 0) {
                for ($x = 0; $x < $totalPosts; $x++) {
                    /** @var LogBookCycle $cycle */
                    $cycle = $iterator->current();
                    if ($cycle !== null && $cycle->getBuild() !== null) {
                        $build_id = $cycle->getBuild()->getId();
                        if ($prev_build_id > 0 && $prev_build_id !== $build_id) {
                            $show_build = true;
                            break;
                        }
                    }
                    $iterator->next();
                }
            }

            return $this->render('lbook/setup/show.full.html.twig', array(
                'setup'          => $setup,
                'size'          => $totalPosts,
                'maxPages'      => $maxPages,
                'thisPage'      => $thisPage,
                'iterator'      => $iterator,
                'paginator'     => $paginator,
                'delete_form'   => $deleteForm->createView(),
                'show_build'    => $show_build,
            ));
        } catch (\Throwable $ex) {
            return $this->setupNotFound($setup, $ex);
        }
    }

    /**
     * Finds and displays a setup entity.
     *
     * @Route("/{id}", name="setup_show_first", methods={"GET"})
     * @param LogBookSetup $setup
     * @param PagePaginator $pagePaginator
     * @param LogBookCycleRepository $cycleRepo
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showFullFirst(LogBookSetup $setup = null, PagePaginator $pagePaginator, LogBookCycleRepository $cycleRepo): ?Response
    {
        return $this->showFull($setup, 1, $pagePaginator, $cycleRepo);
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
     * Displays a form to edit an existing setup entity.
     *
     * @Route("/{id}/edit", name="setup_edit", methods={"GET|POST"})
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
     * @Route("/{id}", name="setup_delete", methods={"DELETE"})
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

            /** Dont check access for test env */
            $env = getenv('APP_ENV');
            if ($env !== 'test') {
                $this->denyAccessUnlessGranted('delete', $obj);
            }
            $form = $this->createDeleteForm($obj);
            $form->handleRequest($request);

            if (($form->isSubmitted() && $form->isValid()) || $env === 'test') {
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
