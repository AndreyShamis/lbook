<?php

namespace App\Security\Voter;

use App\Entity\LogBookUser;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class LogBookUserVoter extends Voter
{
    // these strings are just invented: you can use anything
    protected const VIEW = 'view';
    protected const EDIT = 'edit';
    protected const DELETE = 'delete';

    private $decisionManager;

    /**
     * LogBookSetupVoter constructor.
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!\in_array($attribute, array(self::VIEW, self::EDIT, self::DELETE), true)) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof LogBookUser) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     * @throws \LogicException
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $logedin_user = $token->getUser();

        if (!$logedin_user instanceof LogBookUser) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Post object, thanks to supports
        /** @var LogBookUser $edited_user */
        $edited_user = $subject;

        // ROLE_SUPER_ADMIN can do anything! The power!
        if ($this->decisionManager->decide($token, array('ROLE_SUPER_ADMIN'))) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($edited_user, $logedin_user);
            case self::EDIT:
                return $this->canEdit($edited_user, $logedin_user);
            case self::DELETE:
                return $this->canDelete($edited_user, $logedin_user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param LogBookUser $edited_user
     * @param LogBookUser $logedin_user
     * @return bool
     */
    private function canView(LogBookUser $edited_user, LogBookUser $logedin_user): bool
    {
//        if (!$edited_user->isPrivate()) {
//            return true;
//        } // if they can edit, they can view
//        else if ($this->canEdit($edited_user, $logedin_user)) {
//            return true;
//        }
        return true;
    }

    /**
     * @param LogBookUser $edited_user
     * @param LogBookUser $logedin_user
     * @return bool
     */
    private function canEdit(LogBookUser $edited_user, LogBookUser $logedin_user): bool
    {
        // this assumes that the data object has a getOwner() method
        // to get the entity of the user who owns this data object
        /** @var PersistentCollection $logedin_user_roles */
        $logedin_user_roles = $logedin_user->getRoles();
        return $logedin_user->getId() === $edited_user->getId()
            || \in_array('ROLE_ADMIN', (array)$logedin_user_roles, true)
            || \in_array('ROLE_SUPER_ADMIN', (array)$logedin_user_roles, true);
    }

    /**
     * @param LogBookUser $edited_user
     * @param LogBookUser $logedin_user
     * @return bool
     */
    private function canDelete(LogBookUser $edited_user, LogBookUser $logedin_user): bool
    {
        // this assumes that the data object has a getOwner() method
        // to get the entity of the user who owns this data object
        /** @var PersistentCollection $logedin_user_roles */
        $logedin_user_roles = $logedin_user->getRoles();
        return \in_array('ROLE_SUPER_ADMIN', (array)$logedin_user_roles, true) && !$edited_user->isLdapUser();
    }
}