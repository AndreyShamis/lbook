<?php

namespace App\Controller;

use App\Entity\LogBookUser;
use App\Repository\LogBookUserRepository;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LogBookSecurityController extends AbstractController
{
    /**
     * @Route("/logout", name="logout")
     * @param Request $request
     */
    public function logout(Request $request): void
    {

    }

    /**
     * @param $user
     * @param $password
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param int $serverIndex
     * @return array
     */
    protected function ldapLogin($user, $password, UserPasswordEncoderInterface $passwordEncoder, $serverIndex = 0): array
    {
        $user_src = $user;
        $pass_src = $password;
        $ret_arr = array();
        try {
            if ($serverIndex === 0) {
                $ldap_server = getenv('LDAP_SERVER');
                $dn = getenv('LDAP_DN');
                $LDAP_DEFAULT_DOMAIN = getenv('LDAP_DEFAULT_DOMAIN');
                $LDAP_ID_STR = getenv('LDAP_ID_STR');

            } else {
                $ldap_server = getenv('LDAP_SERVER_2');
                $dn = getenv('LDAP_DN_2');
                $LDAP_DEFAULT_DOMAIN = getenv('LDAP_DEFAULT_DOMAIN_2');
                $LDAP_ID_STR = getenv('LDAP_ID_STR_2');
            }

            $LDAP_USERNAME_STR = getenv('LDAP_USERNAME_STR');
            $LDAP_EMAIL_STR = getenv('LDAP_EMAIL_STR');
            $LDAP_FULLNAME_STR = getenv('LDAP_FULLNAME_STR');
            $LDAP_LASTNAME_STR = getenv('LDAP_LASTNAME_STR');
            $LDAP_FIRSTNAME_STR = getenv('LDAP_FIRSTNAME_STR');
            $LDAP_MOBILE_STR = getenv('LDAP_MOBILE_STR');
            $LDAP_SITECODE_STR = getenv('LDAP_SITECODE_STR');

            $ldap = Ldap::create('ext_ldap', array('connection_string' => 'ldap://' . $ldap_server));
            $domain_sufix = explode("\\", $user);
            if (\count($domain_sufix) === 2) {
                $user = $domain_sufix[1];
                $domain = $domain_sufix[0];
            } else {
                $domain = $LDAP_DEFAULT_DOMAIN;
            }
            $userWithDomain = $domain . '\\' . $user;

            $ldap->bind($userWithDomain, $password);
            $query = $ldap->query($dn, "(samaccountname=$user)", array(
                'maxItems' => 1
            ));
            $results = $query->execute();

            foreach ($results as $entry) {
                $ret_arr['username'] = $entry->getAttribute($LDAP_USERNAME_STR)[0];
                $ret_arr['email'] = $entry->getAttribute($LDAP_EMAIL_STR)[0];
                $ret_arr['lastName'] = $entry->getAttribute($LDAP_LASTNAME_STR)[0];
                $ret_arr['firstName'] = $entry->getAttribute($LDAP_FIRSTNAME_STR)[0];
                $ret_arr['fullName'] = $entry->getAttribute($LDAP_FULLNAME_STR)[0];
                $ret_arr['anotherId'] = $entry->getAttribute($LDAP_ID_STR)[0];
                $ret_arr['sitecode'] = $entry->getAttribute($LDAP_SITECODE_STR)[0];
                $ret_arr['mobile'] = $entry->getAttribute($LDAP_MOBILE_STR)[0];
                $ret_arr['dummyPassword'] = $passwordEncoder->encodePassword(new LogBookUser(), 'dummyPassword');
                $ret_arr['ldapUser'] = true;
            }
        } catch (ConnectionException $ex) {
            if ($serverIndex === 1 || !getenv('LDAP_SERVER_2') || getenv('LDAP_SERVER_2') === '') {
                throw new AuthenticationException('LDAP: ' . $ex->getMessage());
            } else {
                return $this->ldapLogin($user_src, $pass_src, $passwordEncoder, 1);
            }
        } catch (LdapException $ex) {
            if ($serverIndex === 1 || !getenv('LDAP_SERVER_2') || getenv('LDAP_SERVER_2') === '') {
                throw new AuthenticationException('LDAP: LdapException - ' . $ex->getMessage());
            } else {
                return $this->ldapLogin($user_src, $pass_src, $passwordEncoder, 1);
            }
        }
        if (count($ret_arr) === 0) {
            throw new AuthenticationException('LDAP: nothing found - Server index ' . $serverIndex);
        }
        return $ret_arr;
    }

    /**
     * @Route("/login", name="login")
     * @param Request $request
     * @param AuthenticationUtils $authUtils
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param LogBookUserRepository $userRepo
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     */
    public function login(Request $request, AuthenticationUtils $authUtils, UserPasswordEncoderInterface $passwordEncoder, LogBookUserRepository $userRepo): Response
    {
        $error = $authUtils->getLastAuthenticationError();
        /** @var LogBookUser $user */
        $user = null;
        $ldapLogin = false;
        $use_ldap = getenv('USE_LDAP');
        $use_only_ldap = getenv('USE_ONLY_LDAP');
        $key = '_security.main.target_path'; #where "main" is your firewall name
        try {
            if ($request->isMethod('POST')) {
                $user_name = $request->request->get('_username');
                $password = $request->request->get('_password');

                if ($use_ldap === 'true') {
                    try {
                        $user = $userRepo->loadUserByUsername($user_name);
                    } catch (\Throwable $ex){}
                    if ($user === null || ($user !== null && $user->isLdapUser())) {
                        $ldapUserArr = $this->ldapLogin($user_name, $password, $passwordEncoder, 0);
                        if (\is_array($ldapUserArr)) {
                            $ldapLogin = true;
                            $user = $userRepo->loadUserByUsername($ldapUserArr['username']);
                            if ($user === null) {
                                /** Need to create the user in DataBase **/
                                $user = $userRepo->create($ldapUserArr);
                            }
                        }
                    } else {
                        $user = $userRepo->loadUserByUsername($user_name);
                    }
                } else {
                    $user = $userRepo->loadUserByUsername($user_name);
                }


                if ($user !== null) {
                    $active = $user->getIsActive();
                    //$encoded_pass = $passwordEncoder->encodePassword($user, $password);
                    //$salt = $user->getSalt();
                    if (!$active) {
                        throw new AuthenticationException('The user is disabled.');
                    }

                    $ldap_LdapUser_Active = $ldapLogin && $user->isLdapUser() && $active;
                    $notLdap_Active = $passwordEncoder->isPasswordValid($user, $password) && !$ldapLogin && $active;

                    if ($notLdap_Active || $ldap_LdapUser_Active) {
                        // The password matches ! then proceed to set the user in session

                        //Handle getting or creating the user entity likely with a posted form
                        // The third parameter "main" can change according to the name of your firewall in security.yml
                        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                        $this->get('security.token_storage')->setToken($token);

                        // If the firewall name is not main, then the set value would be instead:
                        // $this->get('session')->set('_security_XXXFIREWALLNAMEXXX', serialize($token));
                        $this->get('session')->set('_security_main', serialize($token));

                        // Fire the login event manually
                        //$event = new InteractiveLoginEvent($request, $token);
                        //$this->get('event_dispatcher')->dispatch('security.interactive_login', $event);

                        if ($this->container->get('session')->has($key)) {
                            //set the url based on the link they were trying to access before being authenticated
                            $url = $this->container->get('session')->get($key);
                            return new RedirectResponse($url);
                        }

                        return $this->render('lbook/default/index.html.twig', array(
                        ));
                    }

                    throw new AuthenticationException('Username or Password not valid.');
                }

                throw new AuthenticationException('Username or Password not valid.');
            }

        } catch (AuthenticationException $ex) {
            // get the login error if there is one
            $error = $ex;
        }
        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();
        return $this->render('lbook/login/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
            'company_name'  => getenv('COMPANY_NAME'),
            'use_ldap'      => $use_ldap,
            'use_only_ldap' => $use_only_ldap,
        ));
    }
}
