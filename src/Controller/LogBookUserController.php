<?php

namespace App\Controller;

use App\Entity\LogBookUser;
use App\Form\LogBookUserType;
use App\Repository\LogBookUserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * LogType controller.
 *
 * @Route("user")
 */
class LogBookUserController extends Controller
{
    /**
     * @Route("/", name="user_index")
     * @param LogBookUserRepository $logBookUserRepository
     * @return Response
     */
    public function index(LogBookUserRepository $logBookUserRepository): Response
    {
        return $this->render('lbook/user/index.html.twig', ['users' => $logBookUserRepository->findAll()]);
    }

    /**
     * Finds and displays a Users  entity.
     *
     * @Route("/{id}", name="user_show")
     * @Method("GET")
     * @param LogBookUser $obj
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function showAction(LogBookUser $obj): Response
    {
        // check for "edit" access: calls all voters
        $this->denyAccessUnlessGranted('view', $obj);
        return $this->render('lbook/user/show.html.twig', array(
            'user' => $obj,
        ));
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="user_edit")
     * @Method({"GET", "POST"})
     * @param Request $request
     * @param LogBookUser $obj
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\Form\Exception\LogicException|\Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function editAction(Request $request, LogBookUser $obj, UserPasswordEncoderInterface $passwordEncoder)
    {
        // check for "edit" access: calls all voters
        $this->denyAccessUnlessGranted('edit', $obj);
        $current_user= $this->get('security.token_storage')->getToken()->getUser();
        $can_change_permissions = $this->isGranted('ROLE_ADMIN');
        $editForm = $this->get('form.factory')->create(LogBookUserType::class, $obj, array(
            'edit_enabled' => true,
            'current_user' => $current_user,
            'can_change_permissions' => $can_change_permissions,
        ));
        //$editForm = $this->createForm('App\Form\LogBookUserType', $obj);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            /** @var LogBookUser $edited_user */
            $edited_user = $editForm->getData();
            if ($edited_user->isLdapUser() === false && $edited_user->getPlainPassword() !== null && \strlen($edited_user->getPlainPassword()) > 2) {
                $password = $passwordEncoder->encodePassword($edited_user, $edited_user->getPlainPassword());
                $edited_user->setPassword($password);
            }
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_edit', array('id' => $obj->getId()));
        }

        return $this->render('lbook/user/edit.html.twig', array(
            'user' => $obj,
            'edit_form' => $editForm->createView(),
        ));
    }

//    /**
//     * @Route("/register", name="user_registration")
//     * @param Request $request
//     * @param UserPasswordEncoderInterface $passwordEncoder
//     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
//     */
//    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
//    {
//        // 1) build the form
//        $user = new LogBookUser();
//        $form = $this->createForm(LogBookUserType::class, $user);
//
//        // 2) handle the submit (will only happen on POST)
//        $form->handleRequest($request);
//        if ($form->isSubmitted() && $form->isValid()) {
//
//            // 3) Encode the password (you could also do this via Doctrine listener)
//            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
//            $user->setPassword($password);
//
//            // 4) save the User!
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->persist($user);
//            $entityManager->flush();
//
//            // ... do any other work - like sending them an email, etc
//            // maybe set a "flash" success message for the user
//
//            return $this->redirectToRoute('home_index');
//        }
//
//        return $this->render(
//            'lbook/login/register.html.twig',
//            array('form' => $form->createView())
//        );
//    }
}
