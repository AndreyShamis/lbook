<?php

namespace App\Entity;

use App\Repository\LogBookSuiteInfoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=LogBookSuiteInfoRepository::class)
 */
class LogBookSuiteInfo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=38, nullable=true)
     */
    private $uuid;

    /**
     * @ORM\Column(type="smallint", options={"unsigned"=true})
     */
    private $testsCount = 0;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $assignee = [];


    public static $MAX_NAME_LEN = 250;

    /**
     * @ORM\ManyToMany(targetEntity=LogBookUser::class)
     */
    private $subscribers;

    /**
     * @ORM\ManyToMany(targetEntity=LogBookUser::class)
     * @ORM\JoinTable(name="log_book_setup_log_book_user_fail_subscribers")
     */
    private $failureSubscribers;

    /**
     * @ORM\OneToMany(targetEntity=SuiteExecution::class, mappedBy="suiteInfo")
     */
    private $suiteExecutions;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $testingLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $setupConfig;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $stop_on_fail;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $stop_on_error;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true}, nullable=true)
     */
    private $suite_timeout;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $hours_to_run;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true}, nullable=true)
     */
    private $test_timeout;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $labels = [];

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $supported_farms = [];

    /**
     * @ORM\Column(type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $lastSeen;

    /**
     * @ORM\Column(type="bigint", options={"unsigned"=true, "default"="0"})
     */
    private $creationCount = 0;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $suiteMode;

    public function __construct()
    {
        $this->subscribers = new ArrayCollection();
        $this->failureSubscribers = new ArrayCollection();
        $this->suiteExecutions = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->getName();
    }

    public static function validateName($newName): string
    {
        if (mb_strlen($newName) > self::$MAX_NAME_LEN) {
            $newName = mb_substr($newName, 0, self::$MAX_NAME_LEN);
        }
        return $newName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = LogBookSuiteInfo::validateName($name);

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getTestsCount(): ?int
    {
        if ($this->testsCount === null) {
            return 0;
        }
        return $this->testsCount;
    }

    public function setTestsCount(int $testsCount): self
    {
        $this->testsCount = $testsCount;

        return $this;
    }

    public function getAssignee(): ?array
    {
        return $this->assignee;
    }

    public function setAssignee(?array $assignee): self
    {
        $this->assignee = $assignee;

        return $this;
    }

    /**
     * @return Collection|LogBookUser[]
     */
    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    public function addSubscriber(UserInterface $subscriber): self
    {
        if (!$this->subscribers->contains($subscriber)) {
            $this->subscribers[] = $subscriber;
        }

        return $this;
    }

    public function removeSubscriber(UserInterface $subscriber): self
    {
        if ($this->subscribers->contains($subscriber)) {
            $this->subscribers->removeElement($subscriber);
        }

        return $this;
    }

    /**
     * @return Collection|LogBookUser[]
     */
    public function getFailureSubscribers(): Collection
    {
        return $this->failureSubscribers;
    }

    public function addFailureSubscriber(UserInterface $subscriber): self
    {
        if (!$this->failureSubscribers->contains($subscriber)) {
            $this->failureSubscribers[] = $subscriber;
        }

        return $this;
    }

    public function removeFailureSubscriber(UserInterface $subscriber): self
    {
        if ($this->failureSubscribers->contains($subscriber)) {
            $this->failureSubscribers->removeElement($subscriber);
        }

        return $this;
    }
    /**
     * @return Collection|SuiteExecution[]
     */
    public function getSuiteExecutions(): Collection
    {
        return $this->suiteExecutions;
    }

    public function addSuiteExecution(SuiteExecution $suiteExecution): self
    {
        if (!$this->suiteExecutions->contains($suiteExecution)) {
            $this->suiteExecutions[] = $suiteExecution;
            $suiteExecution->setSuiteInfo($this);
        }

        return $this;
    }

    public function removeSuiteExecution(SuiteExecution $suiteExecution): self
    {
        if ($this->suiteExecutions->contains($suiteExecution)) {
            $this->suiteExecutions->removeElement($suiteExecution);
            // set the owning side to null (unless already changed)
            if ($suiteExecution->getSuiteInfo() === $this) {
                $suiteExecution->setSuiteInfo(null);
            }
        }

        return $this;
    }

    public function getTestingLevel(): ?string
    {
        return $this->testingLevel;
    }

    public function setTestingLevel(?string $testingLevel): self
    {
        $this->testingLevel = $testingLevel;

        return $this;
    }

    public function getSetupConfig(): ?string
    {
        return $this->setupConfig;
    }

    public function setSetupConfig(?string $setupConfig): self
    {
        $this->setupConfig = $setupConfig;

        return $this;
    }

    public function getStopOnFail(): ?bool
    {
        return $this->stop_on_fail;
    }

    public function setStopOnFail(?bool $stop_on_fail): self
    {
        $this->stop_on_fail = $stop_on_fail;

        return $this;
    }

    public function getStopOnError(): ?bool
    {
        return $this->stop_on_error;
    }

    public function setStopOnError(?bool $stop_on_error): self
    {
        $this->stop_on_error = $stop_on_error;

        return $this;
    }

    public function getSuiteTimeout(): ?int
    {
        return $this->suite_timeout;
    }

    public function setSuiteTimeout(?int $suite_timeout): self
    {
        $this->suite_timeout = $suite_timeout;

        return $this;
    }

    public function getHoursToRun(): ?float
    {
        return $this->hours_to_run;
    }

    public function setHoursToRun(?float $hours_to_run): self
    {
        $this->hours_to_run = $hours_to_run;

        return $this;
    }

    public function getTestTimeout(): ?int
    {
        return $this->test_timeout;
    }

    public function setTestTimeout(?int $test_timeout): self
    {
        $this->test_timeout = $test_timeout;

        return $this;
    }

    public function getLabels(): ?array
    {
        return $this->labels;
    }

    public function setLabels(?array $labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    public function getSupportedFarms(): ?array
    {
        return $this->supported_farms;
    }

    public function setSupportedFarms(?array $supported_farms): self
    {
        $this->supported_farms = $supported_farms;

        return $this;
    }

    public function getLastSeen(): ?\DateTimeInterface
    {
        return $this->lastSeen;
    }

    public function setLastSeen(\DateTimeInterface $lastSeen): self
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    public function getCreationCount(): int
    {
        return $this->creationCount;
    }

    public function setCreationCount(int $creationCount): self
    {
        $this->creationCount = $creationCount;

        return $this;
    }

    public function increaseCreation(): self
    {
        $this->creationCount += 1;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSuiteMode(): ?string
    {
        return $this->suiteMode;
    }

    /**
     * @param mixed $suiteMode
     */
    public function setSuiteMode(?string $suiteMode): void
    {
        if (mb_strlen($suiteMode) > 20) {
            $suiteMode = mb_substr($suiteMode, 0, 20);
        }
        $this->suiteMode = $suiteMode;
    }


}
