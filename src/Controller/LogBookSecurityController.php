<?php

namespace App\Controller;

use App\Entity\LogBookUser;
use PHPUnit\Runner\Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
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

    protected function ldapLogin($user, $password){
        $ret_arr = array();
        try{
            $ldap_server = getenv("LDAP_SERVER");
            $dn = getenv("LDAP_DN");

            $LDAP_DEFAULT_DOMAIN = getenv("LDAP_DEFAULT_DOMAIN");
            $LDAP_USERNAME_STR = getenv("LDAP_USERNAME_STR");
            $LDAP_EMAIL_STR = getenv("LDAP_EMAIL_STR");
            $LDAP_FULLNAME_STR = getenv("LDAP_FULLNAME_STR");
            $LDAP_LASTNAME_STR = getenv("LDAP_LASTNAME_STR");
            $LDAP_FIRSTNAME = getenv("LDAP_FIRSTNAME");
            $LDAP_ID_STR = getenv("LDAP_ID_STR");
            $LDAP_MOBILE_STR = getenv("LDAP_MOBILE_STR");
            $LDAP_SITECODE_STR = getenv("LDAP_SITECODE_STR");

            $ldap = Ldap::create('ext_ldap', array('connection_string' => 'ldap://' . $ldap_server));
            $domain_sufix = explode("\\", $user);
            if(count($domain_sufix) == 2)
            {
                $user = $domain_sufix[1];
                $domain = $domain_sufix[0];
            }
            else{
                $domain = $LDAP_DEFAULT_DOMAIN;
            }
            $userWithDomain = $domain . '\\' . $user;

            $ldap->bind($userWithDomain, $password);
            $query = $ldap->query($dn, "(samaccountname=$user)", array(
                "maxItems" => 1
            ));
            $results = $query->execute();

            foreach ($results as $entry) {
                $ret_arr["username"] = $entry->getAttribute("$LDAP_USERNAME_STR")[0];
                $ret_arr["email"] = $entry->getAttribute("$LDAP_EMAIL_STR")[0];
                $ret_arr["lastName"] = $entry->getAttribute("$LDAP_LASTNAME_STR")[0];
                $ret_arr["firstName"] = $entry->getAttribute("$LDAP_FIRSTNAME")[0];
                $ret_arr["fullName"] = $entry->getAttribute("$LDAP_FULLNAME_STR")[0];
                $ret_arr["anotherId"] = $entry->getAttribute("$LDAP_ID_STR")[0];
                $ret_arr["sitecode"] = $entry->getAttribute($LDAP_SITECODE_STR)[0];
                $ret_arr["mobile"] = $entry->getAttribute("$LDAP_MOBILE_STR")[0];
                $ret_arr["ldapUser"] = true;
            }
        }
        catch (ConnectionException $ex){
            throw new AuthenticationException("LDAP: " . $ex->getMessage());
        }
        return $ret_arr;
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
        /** @var LogBookUser $user */
        $user = null;
        try{
            if($request->isMethod('POST')){
                $user_name = $request->request->get('_username');
                $password = $request->request->get('_password');
                // Retrieve the security encoder of symfony
                $user_manager = $this->getDoctrine()->getManager()->getRepository("App:LogBookUser");

                $use_ldap = getenv("USE_LDAP");
                if($use_ldap === "true"){
                    $ldapUserArr = $this->ldapLogin($user_name, $password);
                    if(is_array($ldapUserArr)){
                        $user = $user_manager->loadUserByUsername($ldapUserArr["username"]);
                        if($user === null){
                            /** Need to create the user in DataBase **/
                            $user = $user_manager->create($ldapUserArr);
                        }
                    }
                }
                else{
                    $user = $user_manager->loadUserByUsername($user_name);
                }

                if($user !== null){
                    //$encoded_pass = $passwordEncoder->encodePassword($user, $password);
                    //$salt = $user->getSalt();
                    if($passwordEncoder->isPasswordValid($user, $password) && $user->getIsActive()){
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
                    elseif (!$user->getIsActive()){
                        throw new AuthenticationException('The user is disabled.');
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
