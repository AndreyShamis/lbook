<?php

namespace App\Security\Voter;

use App\Entity\LogBookUser;
use App\Entity\LogBookCycleReport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ReportVoter extends Voter
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
        if (!$subject instanceof LogBookCycleReport) {
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
        /** @var LogBookCycleReport $report */
        $report = $subject;

        // ROLE_SUPER_ADMIN can do anything! The power!
        if ($this->decisionManager->decide($token, array('ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_REPORT_CREATOR'))) {
            return true;
        }
        switch ($attribute) {
            case self::VIEW:
                return true;
            case self::EDIT:
                return $this->canEdit($report, $user);
            case self::DELETE:
                return $this->canDelete($report, $user);
        }

        return false;
    }

    /**
     * @param LogBookCycleReport $report
     * @param LogBookUser $user
     * @return bool
     */
    private function canEdit(LogBookCycleReport $report, LogBookUser $user): bool
    {
        return $user === $report->getUser();
    }

    /**
     * @param LogBookCycleReport $report
     * @param LogBookUser $user
     * @return bool
     */
    private function canDelete(LogBookCycleReport $report, LogBookUser $user): bool
    {
        return $user === $report->getUser();
    }
}
