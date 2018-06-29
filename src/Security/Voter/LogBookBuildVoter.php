<?php

namespace App\Security\Voter;

use App\Entity\LogBookBuild;
use App\Entity\LogBookUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class LogBookBuildVoter extends Voter
{
    protected const VIEW = 'view';
    protected const EDIT = 'edit';
    protected const DELETE = 'delete';

    private $decisionManager;

    /**
     * LogBookBuildVoter constructor.
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
        if (!$subject instanceof LogBookBuild) {
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
        $user = $token->getUser();

        if (!$user instanceof LogBookUser) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Post object, thanks to supports
        /** @var LogBookBuild $build */
        $build = $subject;

        // ROLE_SUPER_ADMIN can do anything! The power!
        if ($this->decisionManager->decide($token, array('ROLE_SUPER_ADMIN', 'ROLE_ADMIN'))) {
            return true;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($build, $user);
            case self::EDIT:
                return $this->canEdit($build, $user);
            case self::DELETE:
                return $this->canDelete($build, $user);
        }

        return false;
    }

    /**
     * @param LogBookBuild $build
     * @param LogBookUser $user
     * @return bool
     */
    private function canView(LogBookBuild $build, LogBookUser $user): bool
    {
        if (!$setup->isPrivate()) {
            return true;
        }

        // if they can edit, they can view
        if ($this->canEdit($setup, $user)) {
            return true;
        }
        return false;
    }

    /**
     * @param LogBookBuild $build
     * @param LogBookUser $user
     * @return bool
     */
    private function canEdit(LogBookBuild $build, LogBookUser $user): bool
    {
        // this assumes that the data object has a getOwner() method
        // to get the entity of the user who owns this data object
        return $user === $setup->getOwner() || $setup->getModerators()->contains($user);
    }

    /**
     * @param LogBookBuild $build
     * @param LogBookUser $user
     * @return bool
     */
    private function canDelete(LogBookBuild $build, LogBookUser $user): bool
    {
        // this assumes that the data object has a getOwner() method
        // to get the entity of the user who owns this data object
        return $user === $setup->getOwner();
    }
}
