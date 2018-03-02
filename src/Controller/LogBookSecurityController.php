<?php

namespace App\Controller;

use App\Entity\LogBookUser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LogBookSecurityController extends Controller
{
    /**
     * @Route("/logout", name="logout")
     * @param Request $request
     */
    public function logout(Request $request){

    }

    /**
     * @Route("/login", name="login")
     * @param Request $request
     * @param AuthenticationUtils $authUtils
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return Response
     */
    public function login(Request $request, AuthenticationUtils $authUtils, UserPasswordEncoderInterface $passwordEncoder)
    {

        $error = $authUtils->getLastAuthenticationError();
        try{
            if($request->isMethod('POST')){
                $user_name = $request->request->get('_username');
                $password = $request->request->get('_password');
                // Retrieve the security encoder of symfony

                $user_manager = $this->getDoctrine()->getManager()->getRepository("App:LogBookUser");
                /** @var LogBookUser $user */
                $user = $user_manager->loadUserByUsername($user_name);

                if($user !== null){
                    //$encoded_pass = $passwordEncoder->encodePassword($user, $password);
                    //$salt = $user->getSalt();
                    if($passwordEncoder->isPasswordValid($user, $password)){
                        // The password matches ! then proceed to set the user in session

                        //Handle getting or creating the user entity likely with a posted form
                        // The third parameter "main" can change according to the name of your firewall in security.yml
                        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                        $this->get('security.token_storage')->setToken($token);

                        // If the firewall name is not main, then the set value would be instead:
                        // $this->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
                        $this->get('session')->set('_security_main', serialize($token));

                        // Fire the login event manually
                        $event = new InteractiveLoginEvent($request, $token);
                        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);
                        return $this->render('lbook/default/index.html.twig', array(
                        ));
                    }
                    else
                    {
                        throw new AuthenticationException('Username or Password not valid.');
                    }
                }
                else{
                    throw new AuthenticationException('Username or Password not valid.');
                }
            }

        }
        catch (AuthenticationException $ex){
            // get the login error if there is one
            $error = $ex;
        }
        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();
        return $this->render('lbook/login/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));

    }
}
