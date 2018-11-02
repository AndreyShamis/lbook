<?php

namespace App\Entity;

use App\Utils\RandomString;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\PreFlush;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookCycleRepository")
 * @ORM\Table(name="lbook_cycles")
 * @ORM\HasLifecycleCallbacks()
 */
class LogBookCycle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name = '';

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookTest", mappedBy="cycle", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="tests", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"executionOrder" = "ASC"})
     */
    protected $tests;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookSetup", cascade={"persist"}, inversedBy="cycles")
     * @ORM\JoinColumn(name="setup", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    protected $setup;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookBuild", cascade={"persist"})
     * @ORM\JoinColumn(name="build", fieldName="id", referencedColumnName="id")
     */
    protected $build;

    /**
     * @var float
     *
     * @ORM\Column(name="pass_rate", type="float", precision=3, scale=2, options={"unsigned"=true, "default"="0"})
     */
    protected $passRate = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="fail_rate", type="float", precision=3, scale=2, options={"unsigned"=true, "default"="0"})
     */
    protected $failRate = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="error_rate", type="float", precision=3, scale=2, options={"unsigned"=true, "default"="0"})
     */
    protected $errorRate = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="warning_rate", type="float", precision=3, scale=2, options={"unsigned"=true, "default"="0"})
     */
    protected $warningRate = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="na_rate", type="float", precision=3, scale=2, options={"unsigned"=true, "default"="0"})
     */
    protected $naRate = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="unknown_rate", type="float", precision=3, scale=2, options={"unsigned"=true, "default"="0"})
     */
    protected $unknownRate = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     * //@Assert\DateTime()
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
     * //@Assert\DateTime()
     */
    protected $updatedAt;

    /**
     * @var \DateTime Took MIN time from tests
     *
     * @ORM\Column(name="time_start", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $timeStart;

    /**
     * @var \DateTime Took MAX time from tests
     *
     * @ORM\Column(name="time_end", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $timeEnd;

    /**
     * @var integer Time in seconds between min tests time to max tests time
     *
     * @ORM\Column(name="period", type="integer", options={"unsigned"=true, "default"="0"})
     */
    protected $period = 0;

    /**
     * @var integer Time in seconds calculated from all execution time of tests
     *
     * @ORM\Column(name="tests_time_sum", type="integer", options={"unsigned"=true, "default"="0"})
     */
    protected $testsTimeSum = 0;


    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookTarget", cascade={"persist"})
     * @ORM\JoinColumn(name="target_uploader", fieldName="id", referencedColumnName="id")
     */
    protected $targetUploader;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookTarget", cascade={"persist"})
     * @ORM\JoinColumn(name="controller", fieldName="id", referencedColumnName="id")
     */
    protected $controller;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookTarget", cascade={"persist"})
     * @ORM\JoinColumn(name="dut", fieldName="id", referencedColumnName="id")
     */
    protected $dut;

    /**
     * @var string
     *
     * @ORM\Column(name="upload_token", type="string", length=255, options={"default"=""})
     */
    protected $uploadToken = '';

    /**
     * @var \DateTime Time till token can be used
     *
     * @ORM\Column(name="token_expiration", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $tokenExpiration;

    /**
     * @var boolean Flag to mark that need call all PreFlush function
     *
     * @ORM\Column(name="dirty", type="boolean", options={"default"="0"})
     */
    protected $dirty = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tests_count", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $testsCount = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tests_disabled", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $testsDisabled = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tests_pass", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $testsPass = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tests_fail", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $testsFail = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tests_error", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $testsError = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tests_warning", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $testsWarning = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tests_na", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $testsNa = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="tests_unknown", type="smallint", options={"unsigned"=true, "default"="0"})
     */
    protected $testsUnknown = 0;

    /**
     * @var boolean
     * @ORM\Column(name="disabled", type="boolean")
     */
    protected $disabled = false;

    /**
     * @var boolean
     * @ORM\Column(name="for_delete", type="boolean")
     */
    protected $forDelete = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="downloads_count", type="integer", options={"unsigned"=true, "default"="0"})
     */
    protected $downloads = 0;

    public static $MAX_NAME_LEN = 250;
    /**
     * LogBookCycle constructor.
     */
    public function __construct()
    {
        /**
         * For new creation usage
         */
        $this->setUpdatedAt();
        $this->setCreatedAt();
        $this->setTokenExpiration(new \DateTime('+7 days'));
        $this->setUploadToken(RandomString::generateRandomString(50));

        /**
         * Other stuff
         */
        $this->tests = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getDownloads(): int
    {
        return $this->downloads;
    }

    /**
     * @param int $downloads
     */
    public function setDownloads(int $downloads): void
    {
        $this->downloads = $downloads;
    }

    /**
     *
     */
    public function increaseDownloads(): void
    {
        $this->setDownloads($this->getDownloads()+1);
    }

    /**
     * @PreFlush
     */
    public function updateTimes(): void
    {
        if ($this->isForDelete() === true) {
            return;
        }
        $testsTimeSum = 0;
        $min_time = new \DateTime('+100 years');
        $max_time = new \DateTime('-100 years');
        $tests = $this->getTests();
        if (\is_object($tests) && $tests->count() > 0) {
            foreach ($tests as $test) {
                if ($test->isDisabled() !== true) {
                    /** @var LogBookTest $test */
                    $max_time = max($max_time, $test->getTimeEnd());
                    $min_time = min($min_time, $test->getTimeStart());
                    $testsTimeSum += $test->getTimeRun();
                }
            }
        } else {
            $min_time = new \DateTime();
            $max_time = new \DateTime();
        }
        $this->setTimeStart($min_time);
        $this->setTimeEnd($max_time);
        $this->setPeriod($this->getTimeEnd()->getTimestamp() - $this->getTimeStart()->getTimestamp());
        $this->setTestsTimeSum($testsTimeSum);
    }

    /**
     * Update Pass/Fail/Error/Warning and Rates
     * @PreFlush
     */
    public function updatePassRate(): void
    {
        if ($this->isForDelete() === true) {
            return;
        }
        $passCount = $failCount = $errorCount = $warningCount = $unknown = $naCount = $disabledCount = $forDeleteCount = 0;
        $tests = $this->getTests();
        $allCount = $tests->count();
        if (\is_object($tests) && $allCount > 0) {
            foreach ($tests as $test) {
                /** @var LogBookTest $test */
                if ($test->isDisabled() !== true) {
                    $verdictStr = $test->getVerdict();
                    if (strcasecmp($verdictStr, 'PASS') === 0) {
                        $passCount++;
                    } else if (strcasecmp($verdictStr, 'FAIL') === 0) {
                        $failCount++;
                    } else if(strcasecmp($verdictStr, 'ERROR') === 0) {
                        $errorCount++;
                    } else if(strcasecmp($verdictStr, 'WARNING') === 0) {
                        $warningCount++;
                    } else if(strcasecmp($verdictStr, 'TEST_NA') === 0) {
                        $naCount++;
                    }
                } else {
                    if ($test->isForDelete() === false) {
                        $disabledCount++;
                    } else {
                        $forDeleteCount++;
                    }
                }
            }
        }

        $this->setTestsPass($passCount);
        $this->setTestsFail($failCount);
        $this->setTestsError($errorCount);
        $this->setTestsWarning($warningCount);
        $this->setTestsNa($naCount);
        $allCount -= ($disabledCount + $forDeleteCount);
        $unknown = $allCount - ($passCount + $failCount + $errorCount + $warningCount + $naCount);
        $this->setTestsUnknown($unknown);
        $this->setTestsCount($allCount);
        $this->setTestsDisabled($disabledCount);

        if ($allCount > 0) {
            $coefficient = 100 / $allCount;
            $this->setPassRate($this->getTestsPass() * $coefficient);
            $this->setFailRate($this->getTestsFail() * $coefficient);
            $this->setErrorRate($this->getTestsError() * $coefficient);
            $this->setWarningRate($this->getTestsWarning() * $coefficient);
            $this->setNaRate($this->getTestsNa() * $coefficient);
            $this->setUnknownRate($this->getTestsUnknown() * $coefficient);
        } else {
            $this->setPassRate(0);
            $this->setFailRate(0);
            $this->setErrorRate(0);
            $this->setWarningRate(0);
            $this->setNaRate(0);
            $this->setUnknownRate(0);
        }
    }

    /**
     * @return int
     */
    public function getTestsPass(): int
    {
        return $this->testsPass;
    }

    /**
     * @param int $testsPass
     */
    public function setTestsPass(int $testsPass): void
    {
        $this->testsPass = $testsPass;
    }

    /**
     * @return int
     */
    public function getTestsFail(): int
    {
        return $this->testsFail;
    }

    /**
     * @param int $testsFail
     */
    public function setTestsFail(int $testsFail): void
    {
        $this->testsFail = $testsFail;
    }

    /**
     * @return int
     */
    public function getTestsError(): int
    {
        return $this->testsError;
    }

    /**
     * @param int $testsError
     */
    public function setTestsError(int $testsError): void
    {
        $this->testsError = $testsError;
    }

    /**
     * @return int
     */
    public function getTestsWarning(): int
    {
        return $this->testsWarning;
    }

    /**
     * @param int $testsWarning
     */
    public function setTestsWarning(int $testsWarning): void
    {
        $this->testsWarning = $testsWarning;
    }

    /**
     * @return int
     */
    public function getTestsNa(): int
    {
        return $this->testsNa;
    }

    /**
     * @param int $testsNa
     */
    public function setTestsNa(int $testsNa): void
    {
        $this->testsNa = $testsNa;
    }

    /**
     * @return int
     */
    public function getTestsUnknown(): int
    {
        return $this->testsUnknown;
    }

    /**
     * @param int $testsUnknown
     */
    public function setTestsUnknown(int $testsUnknown): void
    {
        $this->testsUnknown = $testsUnknown;
    }

    /**
     * @return bool
     */
    public function isDirty(): bool
    {
        return $this->dirty;
    }

    /**
     * @param bool $dirty
     */
    public function setDirty(bool $dirty): void
    {
        $this->dirty = $dirty;
    }

    /**
     * @return int
     */
    public function getTestsCount(): int
    {
        return $this->testsCount;
    }

    /**
     * @param int $testsCount
     */
    public function setTestsCount(int $testsCount): void
    {
        $this->testsCount = $testsCount;
    }

    /**
     * @return int
     */
    public function getTestsDisabled(): int
    {
        return $this->testsDisabled;
    }

    /**
     * @param int $testsDisabled
     */
    public function setTestsDisabled(int $testsDisabled): void
    {
        $this->testsDisabled = $testsDisabled;
    }

    /**
     * @return \DateTime
     */
    public function getTokenExpiration(): \DateTime
    {
        return $this->tokenExpiration;
    }

    /**
     * @param \DateTime $tokenExpiration
     */
    public function setTokenExpiration(\DateTime $tokenExpiration): void
    {
        $this->tokenExpiration = $tokenExpiration;
    }

    /**
     * @PreFlush
     */
    public function unsetDirty(): void
    {
        $this->setDirty(false);
    }

    /**
     * @return string
     */
    public function getUploadToken(): string
    {
        return $this->uploadToken;
    }

    /**
     * @param string $uploadToken
     */
    public function setUploadToken(string $uploadToken): void
    {
        $this->uploadToken = $uploadToken;
    }

    /**
     * @return LogBookTarget
     */
    public function getDut(): ?LogBookTarget
    {
        return $this->dut;
    }

    /**
     * @param LogBookTarget $dut
     */
    public function setDut(LogBookTarget $dut = null): void
    {
        $this->dut = $dut;
    }

    /**
     * @return mixed
     */
    public function getTargetUploader(): ?LogBookTarget
    {
        return $this->targetUploader;
    }

    /**
     * @param LogBookTarget $targetUploader
     */
    public function setTargetUploader(LogBookTarget $targetUploader = null): void
    {
        $this->targetUploader = $targetUploader;
    }

    /**
     * @return mixed
     */
    public function getController(): ?LogBookTarget
    {
        return $this->controller;
    }

    /**
     * @param mixed $controller
     */
    public function setController($controller): void
    {
        $this->controller = $controller;
    }

    /**
     * @return LogBookBuild
     */
    public function getBuild(): ?LogBookBuild
    {
        return $this->build;
    }

    /**
     * @param mixed $build
     */
    public function setBuild($build): void
    {
        $this->build = $build;
    }

    /**
     * @return int
     */
    public function getTestsTimeSum(): int
    {
        return $this->testsTimeSum;
    }

    /**
     * @param int $testsTimeSum
     */
    public function setTestsTimeSum(int $testsTimeSum): void
    {
        $this->testsTimeSum = $testsTimeSum;
    }

    /**
     * @return \DateTime
     */
    public function getTimeStart(): \DateTime
    {
        return $this->timeStart;
    }

    /**
     * @param \DateTime $timeStart
     */
    public function setTimeStart(\DateTime $timeStart): void
    {
        $this->timeStart = $timeStart;
    }

    /**
     * @return \DateTime
     */
    public function getTimeEnd(): \DateTime
    {
        return $this->timeEnd;
    }

    /**
     * @param \DateTime $timeEnd
     */
    public function setTimeEnd(\DateTime $timeEnd): void
    {
        $this->timeEnd = $timeEnd;
    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @param int $period
     */
    public function setPeriod(int $period): void
    {
        if ($period < 0) {
            $this->period = 0;
        } else {
            $this->period = $period;
        }
    }

    /**
     * @return float
     */
    public function getPassRate(): float
    {
        return $this->passRate;
    }

    /**
     * @param float $passRate
     * @param int $precision
     */
    public function setPassRate(float $passRate, $precision=2): void
    {
        $this->passRate = round($passRate, $precision);
    }

    /**
     * @return float
     */
    public function getFailRate(): float
    {
        return $this->failRate;
    }

    /**
     * @param float $failRate
     * @param int $precision
     */
    public function setFailRate(float $failRate, $precision=2): void
    {
        $this->failRate = round($failRate, $precision);
    }

    /**
     * @return float
     */
    public function getErrorRate(): float
    {
        return $this->errorRate;
    }

    /**
     * @param float $errorRate
     * @param int $precision
     */
    public function setErrorRate(float $errorRate, $precision=2): void
    {
        $this->errorRate = round($errorRate, $precision);
    }

    /**
     * @return float
     */
    public function getWarningRate(): float
    {
        return $this->warningRate;
    }

    /**
     * @param float $warningRate
     * @param int $precision
     */
    public function setWarningRate(float $warningRate, $precision=2): void
    {
        $this->warningRate = round($warningRate, $precision);
    }

    /**
     * @return float
     */
    public function getNaRate(): float
    {
        return $this->naRate;
    }

    /**
     * @param float $naRate
     * @param int $precision
     */
    public function setNaRate(float $naRate, $precision=2): void
    {
        $this->naRate = round($naRate, $precision);
    }

    /**
     * @return float
     */
    public function getUnknownRate(): float
    {
        return $this->unknownRate;
    }

    /**
     * @param float $unknownRate
     * @param int $precision
     */
    public function setUnknownRate(float $unknownRate, $precision=2): void
    {
        $this->unknownRate = round($unknownRate, $precision);
    }

    /**
     * @return Collection|LogBookTest[]
     */
    public function getTests(): Collection
    {
        return $this->tests;
    }

    /**
     * @param Collection|LogBookTest[] $tests
     */
    public function setTests(Collection $tests): void
    {
        $this->tests = $tests;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public static function validateName($newName): string
    {
        if (mb_strlen($newName) > self::$MAX_NAME_LEN) {
            $newName = mb_substr($newName, 0, self::$MAX_NAME_LEN);
        }
        return $newName;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = self::validateName($name);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return LogBookSetup
     */
    public function getSetup(): LogBookSetup
    {
        return $this->setup;
    }

    /**
     * @param LogBookSetup $setup
     */
    public function setSetup(LogBookSetup $setup): void
    {
        $this->setup = $setup;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt() : \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @PrePersist
     */
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt() : \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @PreFlush
     * @PrePersist
     */
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     */
    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    /**
     * @return bool
     */
    public function isForDelete(): bool
    {
        return $this->forDelete;
    }

    /**
     * @param bool $forDelete
     */
    public function setForDelete(bool $forDelete): void
    {
        $this->forDelete = $forDelete;
        if ($this->forDelete === true) {
            $this->setDisabled(true);
        } else {
            $this->setDisabled(false);
        }
    }
}
