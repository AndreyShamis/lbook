<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PreFlush;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SuiteExecutionRepository")
 * @ORM\Table(name="suite_execution", uniqueConstraints={@ORM\UniqueConstraint(
 *          name="uniq_suite_execution",
 *          columns={"datetime","testing_level","product_version"}
 *      )
 *  },
 *  indexes={
 *     @Index(name="name_index", columns={"name"}),
 *     @Index(name="uuid_index", columns={"uuid"}),
 *     @Index(name="jira_key_index", columns={"jira_key"}),
 *     @Index(name="testing_level_index", columns={"testing_level"}),
 *     @Index(name="tests_count_index", columns={"tests_count"}),
 *     @Index(name="tests_count_enabled_index", columns={"tests_count_enabled"}),
 *     @Index(name="total_executed_tests_index", columns={"total_executed_tests"}),
 *     @Index(name="state_index", columns={"state"}),
 *     @Index(name="created_at_index", columns={"created_at"}),
 *     @Index(name="updated_at_index", columns={"updated_at"}),
 *     @Index(name="started_at_index", columns={"started_at"}),
 *     @Index(name="finished_at_index", columns={"finished_at"}),
 *     @Index(name="platform_index", columns={"platform"}),
 *     @Index(name="chip_index", columns={"chip"}),
 *     @Index(name="job_name_index", columns={"job_name"}),
 *     @Index(name="build_tag_index", columns={"build_tag"}),
 *     @Index(name="product_version_index", columns={"product_version"}),
 *     @Index(name="pass_rate_index", columns={"pass_rate"}),
 *     @Index(name="pass_count_index", columns={"pass_count"}),
 *     @Index(name="publish_index", columns={"publish"}),
 *     @Index(name="find_one_by_index", columns={"publish", "state", "uuid"}),
 *     @Index(name="find_one_by_cycled_index", columns={"cycle_id", "publish", "state", "uuid"}),
 *     @Index(name="fulltext_custom", columns={"name", "product_version", "platform", "chip", "summary", "job_name", "build_tag"}, flags={"fulltext"}),
 *  }
 * )
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
     * @ORM\Column(type="string", length=250)
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
     * @ORM\Column(name="package_mode", type="string", length=30, options={"default"=""})
     */
    private $packageMode = 'regular_mode';

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $platform;

    /**
     * @ORM\Column(type="string", length=40)
     */
    private $chip;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=30, options={"default"="devel"})
     */
    private $buildType = '';

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=10, options={"default"=""})
     */
    private $platformHardwareVersion = '';

    /**
     * @ORM\Column(name="owners", type="simple_array")
     */
    private $owners = [];

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $components;

    /**
     * @ORM\Column(name="test_environments", type="simple_array", nullable=true)
     */
    private $testEnvironments;

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
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookTest", mappedBy="suite_execution", fetch="LAZY", cascade="all")
     */
    private $tests;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $jira_key;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $publish;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $datetime;

    /**
     * @ORM\Column(name="tests_count", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    private $testsCount = 0;

    /**
     * @ORM\Column(name="tests_count_enabled", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    private $testsCountEnabled = 0;

    /**
     * @ORM\Column(name="name", type="string", length=250, options={"default"=""})
     */
    private $name = '';

    /**
     * @ORM\Column(type="string", length=38, options={"default"=""})
     */
    private $uuid = '';

    /**
     * @ORM\Column(type="smallint", options={"default"="0"})
     */
    private $state = 0;

    /**
     * @ORM\Column(name="created_at", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="started_at", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $startedAt;

    /**
     * @ORM\Column(name="finished_at", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    private $finishedAt;
    /**
     * @ORM\Column(type="integer", options={"default"="0"})
     */
    private $totalExecutedTests = 0;

    /**
     * @ORM\Column(type="float", options={"default"="0"})
     */
    private $passRate = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $passCount = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $failCount = 0;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true, "default"="0"})
     */
    private $errorCount = 0;

    /**
     * @ORM\Column(type="boolean", options={"unsigned"=true, "default"="1"})
     */
    private $closed = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Host", inversedBy="suiteExecutions", fetch="EXTRA_LAZY")
     */
    private $host;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $branchName;

    protected $rate = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookSuiteInfo", inversedBy="suiteExecutions")
     */
    private $suiteInfo;

    public function setRate(float $r) {
        $this->rate = round($r, 2);
    }

    public function getRate(): float{
        return $this->rate;
    }
    /**
     * SuiteExecution constructor.
     */
    public function __construct()
    {

        try {
            $this->setUpdatedAt();
        } catch (\Exception $e) {
        }
        try {
            $this->setCreatedAt();
        } catch (\Exception $e) {
        }
        $this->createdAt = new \DateTime();
        $this->startedAt = new \DateTime();
        $this->finishedAt = new \DateTime();
        $this->tests = new ArrayCollection();
        $this->setClosed(false);
        $this->owners = [];
        $this->components = [];
        $this->testEnvironments = [];
    }

    /**
     * @return string
     */
    public function getPackageMode(): string
    {
        return $this->packageMode;
    }

    /**
     * @param string $packageMode
     */
    public function setPackageMode(string $packageMode): void
    {
        $this->packageMode = $packageMode;
    }  // or package_mode

    public function getRunTime(): int
    {
        $ret = 0;
        try {
            $ret = $this->getFinishedAt()->getTimestamp() - $this->getStartedAt()->getTimestamp();
            if ($ret < 0) {
                $ret = 0;
            }
        } catch (\Throwable $ex) {}
        return $ret;
    }

    /**
     *
     */
    public function calculateStatistic()
    {
        $passCount = 0;
        $failCount = 0;
        $errorCount = 0;
        $total_test_time = 0;
        $startTime = $this->getCreatedAt();
        $endTime = new \DateTime('-100 years');
        $totoal_real_tests_found = 0;
        /** @var LogBookTest[] $tests */
        $tests = $this->getTests();
        $testsFound = false;
        /** @var LogBookTest $test */
        foreach ($tests as $test) {
            try {
                $type = $test->getTestType()->getName();
            } catch (\Throwable $ex) {
                $type = 'TEST';
            }

            if ($type === 'TEST') {
                $totoal_real_tests_found += 1;
                if ($test->isPass()) {
                    $passCount += 1;
                }
                if ($test->isFail()) {
                    $failCount += 1;
                }
                if ($test->isError()) {
                    $errorCount += 1;
                }
            }
            $startTime = min($startTime, $test->getTimeStart());
            $endTime = max($endTime, $test->getTimeEnd());

            $total_test_time += $test->getTimeRun();
            $testsFound = true;
        }

        $suite_tests_count = $this->getTestsCountEnabled();
        if ($totoal_real_tests_found >= $suite_tests_count) {
            $cof = $totoal_real_tests_found;

        } else {
            $cof = $suite_tests_count;
        }
        $sof = 0;
        if ($cof > 0) {
            $sof = 100 / $cof;
        }
        $this->setTotalExecutedTests($totoal_real_tests_found);
        $this->setPassRate(round($sof * $passCount, 2));
        $this->setPassCount($passCount);
        $this->setFailCount($failCount);
        $this->setErrorCount($errorCount);
        $this->setStartedAt($startTime);
        /** WA for suite without tests */
        if ($testsFound) {
            $this->setFinishedAt($endTime);
        } else {
            $this->setFinishedAt($this->getCreatedAt());
        }

    }

    /**
     * @return int
     */
    public function getTotalExecutedTests(): int
    {
        return $this->totalExecutedTests;
    }

    /**
     * @param int $totalExecutedTests
     */
    public function setTotalExecutedTests(int $totalExecutedTests): void
    {
        $this->totalExecutedTests = $totalExecutedTests;
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

    public function getBranch(): string
    {
        try {
            $desc = $this->getDescription();
            $patt = '/\"MANIFEST_REVISION\"\:\s*\"(.*)\",/';
            $res = preg_match($patt, $desc, $match);
            if ($res) {
                return $match[1];
            }
        } catch (\Throwable $ex) {}

        return '';
    }

    public function getMode(): string
    {
        try {
            $desc = $this->getDescription();
            $patt = '/\"MODE\"\:\s*\"(.*)\",/';
            $res = preg_match($patt, $desc, $match);
            if ($res) {
                return $match[1];
            }

            $patt = '/\"IS_PACKAGE_MODE\"\:\s*\"(true)\",/';
            $res = preg_match($patt, $desc, $match);
            if ($res) {
                return 'package_mode';
            }
        } catch (\Throwable $ex) {}

        return 'regular_mode';
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

    /**
     * @return array
     */
    public function getOwners(): array
    {
        return $this->owners;
    }

    /**
     * @param array $owners
     * @return $this
     */
    public function setOwners(array $owners): self
    {
        if ($owners === null) {
            $this->owners = [];
        } else {
            $this->owners = array_filter($owners);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getComponents(): array
    {
        if ($this->components === null) {
            return [];
        }
        return $this->components;
    }

    /**
     * @param array $components
     * @return $this
     */
    public function setComponents(?array $components): self
    {
        $this->components = $components;

        return $this;
    }

    public function getTestEnvironments(): ?array
    {
        if ($this->testEnvironments === null) {
            return [];
        }
        return $this->testEnvironments;
    }

    public function setTestEnvironments(?array $testEnvironments): self
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

    /**
     * @param string|null $jira_key
     * @return SuiteExecution
     */
    public function setJiraKey(?string $jira_key): self
    {
        $this->jira_key = $jira_key;

        return $this;
    }

    public function getPublish(): ?bool
    {
        return $this->publish;
    }

    public function setPublish(?bool $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    public function getDatetime(): ?string
    {
        return $this->datetime;
    }

    public function setDatetime(?string $datetime): self
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * @return int
     */
    public function getTestsCount(): int
    {
        return $this->testsCount;
    }

    public function setTestsCount(int $testsCount): self
    {
        $this->testsCount = $testsCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getTestsCountDisabled(): int
    {
        return $this->getTestsCount() - $this->getTestsCountEnabled();
    }

    /**
     * @return int
     */
    public function getTestsCountEnabled(): int
    {
        return $this->testsCountEnabled;
    }

    public function setTestsCountEnabled(int $testsCountEnabled): self
    {
        $this->testsCountEnabled = $testsCountEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @PrePersist
     * @throws \Exception
     */
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @PreFlush
     * @PrePersist
     * @throws \Exception
     */
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @param float $passRate
     * @return $this
     */
    public function setPassRate(float $passRate): self
    {
        $this->passRate = $passRate;

        return $this;
    }

    /**
     * @return float
     */
    public function getPassRate(): float
    {
        return $this->passRate;
    }

    /**
     * @return float
     */
    public function getFailRate(): float
    {
        $executed = $this->getTotalExecutedTests();
        if ($this->failCount === 0 || $executed === 0) {
            return 0;
        }
        return round(100 * $this->failCount / $executed, 2);
    }

    /**
     * @return float
     */
    public function getErrorRate(): float
    {
        $executed = $this->getTotalExecutedTests();
        if ($this->errorCount === 0|| $executed === 0) {
            return 0;
        }
        return round(100 * $this->errorCount / $executed, 2);
    }

    public function getOtherCount(): int
    {
        return $this->getTotalExecutedTests() - ($this->errorCount + $this->passCount + $this->failCount);
    }

    /**
     * @return float
     */
    public function getOtherRate(): float
    {
        $executed = $this->getTotalExecutedTests();
        $other =$this->getOtherCount();
        if ($other === 0) {
            return 0;
        }
        return round(100 * $other / $executed, 2);
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(\DateTimeInterface $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getPassCount(): int
    {
        return $this->passCount;
    }

    /**
     * @param int $passCount
     * @return SuiteExecution
     */
    public function setPassCount(int $passCount): self
    {
        $this->passCount = $passCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getFailCount(): int
    {
        return $this->failCount;
    }

    /**
     * @param int $failCount
     * @return SuiteExecution
     */
    public function setFailCount(int $failCount): self
    {
        $this->failCount = $failCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    public function setErrorCount(int $errorCount): self
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function getClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @param bool $closed
     * @return SuiteExecution
     */
    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function getHost(): ?Host
    {
        return $this->host;
    }

    public function setHost(?Host $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getBuildType(): string
    {
        return $this->buildType;
    }

    /**
     * @param string $buildType
     */
    public function setBuildType(string $buildType): void
    {
        $this->buildType = $buildType;
    }

    /**
     * @return string
     */
    public function getPlatformHardwareVersion(): string
    {
        return $this->platformHardwareVersion;
    }

    /**
     * @param string $platformHardwareVersion
     */
    public function setPlatformHardwareVersion(string $platformHardwareVersion): void
    {
        $this->platformHardwareVersion = $platformHardwareVersion;
    }

    /**
     * @return mixed
     */
    public function getBranchName() : string
    {
        if ($this->branchName === null) {
            return $this->getBranch();
        }
        return $this->branchName;
    }

    /**
     * @param mixed $branchName
     */
    public function setBranchName(?string $branchName): void
    {
        $this->branchName = $branchName;
    }

    public function getSuiteInfo(): ?LogBookSuiteInfo
    {
        return $this->suiteInfo;
    }

    public function setSuiteInfo(?LogBookSuiteInfo $suiteInfo): self
    {
        $this->suiteInfo = $suiteInfo;

        return $this;
    }

    /**
     * @return string
     */
    public function getExecutedOn(): string
    {
        return '';
    }

}
