<?php

namespace App\Security\Voter;

use App\Entity\LogBookSetup;
use App\Entity\LogBookUser;
use App\Entity\TestFilter;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TestFilterVoter extends Voter
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
        $user = $token->getUser();

        if (!$user instanceof LogBookUser) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Post object, thanks to supports
        /** @var TestFilter $testFilter */
        $testFilter = $subject;

        // ROLE_SUPER_ADMIN can do anything! The power!
        if ($this->decisionManager->decide($token, array('ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_FILTER_CREATOR'))) {
            return true;
        }
        if (in_array('ROLE_FILTER_CREATOR', $user->getRoles())){
            return true;
        }

        switch ($attribute) {
            case self::VIEW:
                return true;
            case self::EDIT:
                return $this->canEdit($testFilter, $user);
            case self::DELETE:
                return $this->canDelete($testFilter, $user);
        }

        return false;
    }

    /**
     * @param TestFilter $testFilter
     * @param LogBookUser $user
     * @return bool
     */
    private function canEdit(TestFilter $testFilter, LogBookUser $user): bool
    {
        return $user === $testFilter->getUser();
    }

    /**
     * @param TestFilter $testFilter
     * @param LogBookUser $user
     * @return bool
     */
    private function canDelete(TestFilter $testFilter, LogBookUser $user): bool
    {
        return $user === $testFilter->getUser();
    }
}
