<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SuiteExecutionRepository")
 * @ORM\Table(name="suite_execution",
 *     uniqueConstraints={@ORM\UniqueConstraint(
 *     name="uniq_suite_execution",
 *     columns={
 *      "summary",
 *      "testing_level",
 *      "product_version"}
 *     )})
 */
class SuiteExecution
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookCycle", inversedBy="suiteExecution")
     */
    private $cycle;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $summary = '';

    /**
     * @ORM\Column(type="text")
     */
    private $description = '';

    /**
     * @ORM\Column(name="product_version", type="string", length=150)
     */
    private $productVersion = '';

    /**
     * @ORM\Column(name="job_name", type="string", length=255)
     */
    private $jobName = '';

    /**
     * @ORM\Column(name="build_tag", type="string", length=255)
     */
    private $buildTag = '';

    /**
     * @ORM\Column(name="target_arch", type="string", length=50)
     */
    private $targetArch = '';

    /**
     * @ORM\Column(name="arch", type="string", length=50)
     */
    private $arch = '';

    /**
     * @ORM\Column(name="testing_level", type="string", length=20)
     */
    private $testingLevel = 'integration';

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $platform;

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $chip;

    /**
     * @ORM\Column(type="simple_array")
     */
    private $components = [];

    /**
     * @ORM\Column(name="test_environments", type="simple_array")
     */
    private $testEnvironments = [];

    /**
     * @ORM\Column(name="test_plan_url", type="string", length=255, nullable=true)
     */
    private $testPlanUrl;

    /**
     * @ORM\Column(name="ci_url", type="string", length=255, nullable=true)
     */
    private $ciUrl;

    /**
     * @ORM\Column(name="test_set_url", type="string", length=255, nullable=true)
     */
    private $testSetUrl;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookTest", mappedBy="suite_execution")
     */
    private $tests;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $jira_key;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSummary(): string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductVersion(): string
    {
        return $this->productVersion;
    }

    public function setProductVersion(string $productVersion): self
    {
        $this->productVersion = $productVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function setJobName(string $jobName): self
    {
        $this->jobName = $jobName;

        return $this;
    }

    /**
     * @return string
     */
    public function getBuildTag(): string
    {
        return $this->buildTag;
    }

    public function setBuildTag(string $buildTag): self
    {
        $this->buildTag = $buildTag;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargetArch(): string
    {
        return $this->targetArch;
    }

    public function setTargetArch(string $targetArch): self
    {
        $this->targetArch = $targetArch;

        return $this;
    }

    /**
     * @return string
     */
    public function getArch(): string
    {
        return $this->arch;
    }

    public function setArch(string $arch): self
    {
        $this->arch = $arch;

        return $this;
    }

    /**
     * @return string
     */
    public function getTestingLevel(): string
    {
        return $this->testingLevel;
    }

    public function setTestingLevel(string $testingLevel): self
    {
        $this->testingLevel = $testingLevel;

        return $this;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    public function getChip(): ?string
    {
        return $this->chip;
    }

    public function setChip(string $chip): self
    {
        $this->chip = $chip;

        return $this;
    }

    public function getComponents(): ?array
    {
        return $this->components;
    }

    public function setComponents(array $components): self
    {
        $this->components = $components;

        return $this;
    }

    public function getTestEnvironments(): ?array
    {
        return $this->testEnvironments;
    }

    public function setTestEnvironments(array $testEnvironments): self
    {
        $this->testEnvironments = $testEnvironments;

        return $this;
    }

    public function getTestPlanUrl(): ?string
    {
        return $this->testPlanUrl;
    }

    public function setTestPlanUrl(?string $testPlanUrl): self
    {
        $this->testPlanUrl = $testPlanUrl;

        return $this;
    }

    public function getCiUrl(): ?string
    {
        return $this->ciUrl;
    }

    public function setCiUrl(?string $ciUrl): self
    {
        $this->ciUrl = $ciUrl;

        return $this;
    }

    public function getTestSetUrl(): ?string
    {
        return $this->testSetUrl;
    }

    public function setTestSetUrl(?string $testSetUrl): self
    {
        $this->testSetUrl = $testSetUrl;

        return $this;
    }

    /**
     * @return LogBookCycle|null
     */
    public function getCycle(): ?LogBookCycle
    {
        return $this->cycle;
    }

    /**
     * @param LogBookCycle|null $cycle
     * @return SuiteExecution
     */
    public function setCycle(?LogBookCycle $cycle): self
    {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSummary();
    }

    /**
     * @return Collection|LogBookTest[]
     */
    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function addTest(LogBookTest $test): self
    {
        if (!$this->tests->contains($test)) {
            $this->tests[] = $test;
            $test->setSuiteExecution($this);
        }

        return $this;
    }

    public function removeTest(LogBookTest $test): self
    {
        if ($this->tests->contains($test)) {
            $this->tests->removeElement($test);
            // set the owning side to null (unless already changed)
            if ($test->getSuiteExecution() === $this) {
                $test->setSuiteExecution(null);
            }
        }

        return $this;
    }

    public function getJiraKey(): ?string
    {
        return $this->jira_key;
    }

    public function setJiraKey(?string $jira_key): self
    {
        $this->jira_key = $jira_key;

        return $this;
    }
}
