<?php

namespace App\Security\Voter;

use App\Entity\LogBookSetup;
use App\Entity\LogBookUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class LogBookSetupVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

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
    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, array(self::VIEW, self::EDIT, self::DELETE))) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof LogBookSetup) {
            return false;
        }

        return true;

    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof LogBookUser) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Post object, thanks to supports
        /** @var LogBookSetup $post */
        $setup = $subject;

        // ROLE_SUPER_ADMIN can do anything! The power!
        if ($this->decisionManager->decide($token, array('ROLE_SUPER_ADMIN', 'ROLE_ADMIN'))) {
            return true;
        }


        switch ($attribute) {
            case self::VIEW:
                return $this->canView($setup, $user);
            case self::EDIT:
                return $this->canEdit($setup, $user);
            case self::DELETE:
                return $this->canDelete($setup, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param LogBookSetup $setup
     * @param LogBookUser $user
     * @return bool
     */
    private function canView(LogBookSetup $setup, LogBookUser $user)
    {
        if(!$setup->isPrivate()){
            return true;
        }
        // if they can edit, they can view
        else if ($this->canEdit($setup, $user)) {
            return true;
        }
        return false;
    }

    /**
     * @param LogBookSetup $setup
     * @param LogBookUser $user
     * @return bool
     */
    private function canEdit(LogBookSetup $setup, LogBookUser $user)
    {
        // this assumes that the data object has a getOwner() method
        // to get the entity of the user who owns this data object
        return $user === $setup->getOwner() || $setup->getModerators()->contains($user);
    }

    /**
     * @param LogBookSetup $setup
     * @param LogBookUser $user
     * @return bool
     */
    private function canDelete(LogBookSetup $setup, LogBookUser $user)
    {
        // this assumes that the data object has a getOwner() method
        // to get the entity of the user who owns this data object
        return $user === $setup->getOwner();
        //return $this->canEdit($setup, $user);
    }
}
