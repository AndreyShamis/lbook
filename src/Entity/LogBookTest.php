<?php

namespace App\Entity;

use App\Twig\AppExtension;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\PreFlush;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookTestRepository")
 * @ORM\Table(name="lbook_tests", indexes={
 *     @Index(name="i_test_key", columns={"test_key"}),
 *     @Index(name="i_disabled", columns={"disabled"}),
 *     @Index(name="i_cycle_execution_order", columns={"cycle", "execution_order"}),
 *     @Index(name="i_cycle_disabled", columns={"cycle", "disabled"}),
 *     @Index(name="fulltext_name", columns={"name"}, flags={"fulltext"}),
 *     @Index(name="i_executionOrder", columns={"execution_order"})})
 * // , uniqueConstraints={@ORM\UniqueConstraint(name="test_uniq_cycle_execution_order", columns={"execution_order", "cycle"})}
 * @ORM\HasLifecycleCallbacks()
 */
class LogBookTest
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    protected $id;

    public static $MAX_NAME_LEN = 255;
    public static $MAX_FAIL_DESC_LEN = 245;
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name = '';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_start", type="datetime")
     * //@Assert\DateTime()
     */
    protected $timeStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_end", type="datetime")
     * //@Assert\DateTime()
     */
    protected $timeEnd;

    /**
     * @var integer
     *
     * @ORM\Column(name="time_run", type="integer", options={"unsigned"=true})
     */
    protected $timeRun = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookVerdict", cascade={"persist"})
     * @ORM\JoinColumn(name="verdict", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $verdict;

    /**
     * @var integer
     *
     * @ORM\Column(name="execution_order", type="integer", options={"unsigned"=true})
     */
    protected $executionOrder = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="dut_up_time_start", type="smallint", options={"unsigned"=true})
     */
    protected $dutUpTimeStart = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="dut_up_time_end", type="smallint", options={"unsigned"=true})
     */
    protected $dutUpTimeEnd = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookCycle", inversedBy="tests", cascade={"persist"})
     * @ORM\JoinColumn(name="cycle", fieldName="id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $cycle;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookMessage", mappedBy="test", fetch="EXTRA_LAZY", orphanRemoval=true)
     * @ORM\JoinColumn(name="logs", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"chain" = "ASC"})
     */
    protected $logs;

    /**
     * @var string
     * @ORM\Column(name="log_file", type="string", length=1500)
     * //@//Assert\NotBlank(message="Please, provide log file as a DEBUG or INFO format file.")
     * @Assert\File(mimeTypes={"text/plain", "application/octet-stream"}, groups = {"create"})
     */
    private $logFile = '';

    /**
     * @var integer
     * @ORM\Column(name="log_file_size", type="integer", length=11, options={"unsigned"=true})
     */
    private $logFileSize = 0;

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
     * @var array
     * @ORM\Column(type="array", nullable=true)
     * //, columnDefinition="LONGTEXT DEFAULT NULL"
     */
    protected $meta_data = [];

    protected $temp_meta_data = [];

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SuiteExecution", inversedBy="tests")
     */
    private $suite_execution;

    /**
     * @ORM\Column(name="test_key", type="string", length=25, options={"default"=""})
     */
    private $testKey = '';

    /**
     * @ORM\Column(name="fail_description", type="string", length=250, options={"default"=""})
     */
    protected $failDescription = '';

    /**
     * @ORM\ManyToOne(targetEntity=LogBookTestInfo::class, inversedBy="logBookTests")
     */
    private $testInfo;

    /**
     * @ORM\ManyToOne(targetEntity=LogBookTestType::class)
     */
    private $testType;


    protected $failDescriptionParsed = false;

    protected $rate = 0;

    /**
     * @ORM\OneToOne(targetEntity=LogBookTestMD::class, cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $newMetaData;

    public function setRate(float $r) {
        $this->rate = round($r, 2);
    }

    public function getRate(): float{
        return $this->rate;
    }

    /**
     * @return bool
     */
    public function isFailDescriptionParsed(): bool
    {
        return $this->failDescriptionParsed;
    }

    /**
     * @param bool $failDescriptionParsed
     */
    public function setFailDescriptionParsed(bool $failDescriptionParsed): void
    {
        $this->failDescriptionParsed = $failDescriptionParsed;
    }

    public function getMDTestType(): string
    {
        $ret = 'TEST';
        if ($this->getTestType() === null) {
            $key = 'TEST_TYPE_SHOW_OPT';
            $md = $this->getMetaData();
            if (array_key_exists($key, $md)) {
                $ret = $md[$key];
                if ($ret === 'PRE_TEST_FLOW') {
                    $ret = 'PRE_CYCLE';
                }
                if ($ret === 'POST_TEST_FLOW') {
                    $ret = 'POST_CYCLE';
                }
            }
        } else {
            $ret = $this->getTestType()->getName();
        }

        return $ret;

    }
    /**
     * LogBookTest constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->timeStart = new \DateTime();
        $this->timeEnd = new \DateTime();
        $this->logs = new ArrayCollection();
        $this->meta_data = [];
        $this->temp_meta_data = [];
        $this->failDescription = '';
    }

    /**
     * @return array
     */
    public function getOldMetaData(): array
    {
        return $this->meta_data;
    }

    /**
     * @return array
     */
    public function getMetaData(): array
    {
        if ($this->meta_data === null) {
            $this->meta_data = array();
        }
        if ($this->meta_data === [] && $this->newMetaData !== null && $this->newMetaData !== []){
            return $this->getNewMetaData()->getValue();
        }
        return $this->meta_data;
    }

    /**
     * @return array
     */
    public function getTempMetaData(): array
    {
        if ($this->temp_meta_data === null) {
            $this->temp_meta_data = array();
        }
        return $this->temp_meta_data;
    }

    public function getFromMetaData(string $key, string $default=null): string
    {
        $ret_val = '';
        try {
            $metadata = $this->getMetaData();
            if (array_key_exists($key, $metadata)) {
                $ret_val = $metadata[$key];
            } else {
                if ($default !== null) {
                    $ret_val = $default;
                }
            }
        } catch (\Throwable $ex) {

        }
        return $ret_val;
    }

    public function parseFailDescription(): string
    {
        try {
            $ret_val = '';
            $ver = '';
            if ($this->getVerdict() !== null) {
                $ver = $this->getVerdict()->getName();
            }
            if ($ver !== 'PASS' && $ver !== 'UNKNOWN') {
                $logs = $this->getLogs();
                foreach ($logs as $log) {
                    $errors = '';
                    if ($log->getMsgType()->getName() === 'FAIL' && strpos($log->getMessage(), 'FAIL ') === 0) {
                        $errors = $log->getMessage();
                    } elseif ($log->getMsgType()->getName() === 'ERROR' && strpos($log->getMessage(), 'ERROR ') === 0) {
                        $errors = $log->getMessage();
                    } elseif ($log->getMsgType()->getName() === 'UNKNOWN' && strpos($log->getMessage(), 'FAIL ') === 0) {
                        $errors = $log->getMessage();
                    } elseif ($log->getMsgType()->getName() === 'TEST_NA' && strpos($log->getMessage(), 'TEST_NA ') === 0) {
                        $errors = $log->getMessage();
                    }
                    if ($errors !== null && $errors != '') {
                        $ret_val = AppExtension::cleanAutotestFinalMessage($errors);
                    }

                }

            }

            if ($ret_val === null || $ret_val === '') {
                $this->setFailDescription(' ');
            } else {
                $this->setFailDescription($ret_val);
            }
            return $ret_val;
        } catch (\Throwable $ex) {}
        return '';
    }

    public static function validateFailDescription($newFailDescription): string
    {
        if (mb_strlen($newFailDescription) > self::$MAX_FAIL_DESC_LEN) {
            $newFailDescription = mb_substr($newFailDescription, 0, self::$MAX_FAIL_DESC_LEN);
        }
        return $newFailDescription;
    }


    /**
     * @param string $newValue
     */
    public function setFailDescription(string $newValue):void
    {
        $this->failDescription = LogBookTest::validateFailDescription($newValue);
    }

    /**
     * @return string
     */
    public function getFailDescription(bool $forceParse=false): string
    {
        if (!$forceParse){
            // In cycle show we check twice what is the fail description,
            // in second time we will get saved value
            if ($this->failDescription !== null && $this->failDescription !== '') {
                return $this->failDescription;
            }

        }

        try {
            $this->setFailDescriptionParsed(true);
            return $this->parseFailDescription();
        } catch (\Throwable $ex) {}
        return 'Failed To Parse';
    }

    public function getSuiteName(): string
    {
        $suite = $this->getSuiteExecution();
        if ($suite !== null) {
            return $suite->getName();
        }
        return '';
    }

    public function getChip(): string
    {
        return $this->getFromMetaData('CHIP', '');
    }

    public function getPlatform(): string
    {
        return $this->getFromMetaData('PLATFORM', '');
    }

    /**
     * @param array $meta_data
     */
    public function addTempMetaData(array $meta_data): void
    {
        $this->temp_meta_data = array_merge($this->temp_meta_data, $meta_data);
    }

    /**
     * @param array $meta_data
     */
    public function addMetaData(array $meta_data): void
    {
//        foreach ($meta_data as $key => $val) {
//            if ($key === 'TEST_CASE_SHOW' && mb_strlen($val) > 2) {
//                $this->setTestKey($val);
//                break;
//            }
//        }
        $this->meta_data = array_merge($this->meta_data, $meta_data);
    }

    /**
     * @param array $meta_data
     */
    public function setTempMetaData(array $meta_data): void
    {
        if ($this->temp_meta_data === null) {
            $this->temp_meta_data = [];
        }
        $this->addTempMetaData($meta_data);

    }

    /**
     * @param array $meta_data
     */
    public function setMetaData(array $meta_data): void
    {
        if ($this->meta_data === null) {
            $this->meta_data = [];
        }
        $this->addMetaData($meta_data);

    }
    /**
     * @param string $key
     */
    public function resetMetaData(string $key): void
    {
        if ($key === '*') {
            $this->meta_data = [];
        } else {
            unset($this->meta_data[$key]);
        }

    }
    /**
     * @return int
     */
    public function getLogFileSize(): int
    {
        return $this->logFileSize;
    }

    /**
     * @param int $logFileSize
     */
    public function setLogFileSize(int $logFileSize): void
    {
        $this->logFileSize = $logFileSize;
    }

    /**
     * @return null|string
     */
    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    /**
     * @param string $logFile
     * @return $this
     */
    public function setLogFile(string $logFile): self
    {
        $this->logFile = $logFile;

        return $this;
    }

    /**
     * @param UploadedFile $file
     * @return $this
     */
    public function setUploadedFile(UploadedFile $file): self
    {
        $this->setLogFile($file->getPath());

        return $this;
    }

    /**
     * @return Collection|LogBookMessage[]
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    /**
     * @param Collection $logs
     */
    public function setLogs(Collection $logs): void
    {
        $this->logs = $logs;
    }

    /**
     * @PreFlush
     */
    public function calculateRunTime(): void
    {
        $run_time = abs($this->getTimeEnd()->getTimestamp() - $this->getTimeStart()->getTimestamp());
        $this->setTimeRun($run_time);
    }

    /**
     * @return LogBookCycle
     */
    public function getCycle(): ?LogBookCycle
    {
        return $this->cycle;
    }

    /**
     * @param LogBookCycle $cycle
     */
    public function setCycle(LogBookCycle $cycle): void
    {
        $this->cycle = $cycle;
    }

    /**
     * @return LogBookSetup|null
     */
    public function getSetup(): ?LogBookSetup
    {
        if ($this->getCycle() !== null) {
            return $this->getCycle()->getSetup();
        }
        return null;
    }

    /**
     * @return LogBookVerdict
     */
    public function getVerdict(): ?LogBookVerdict
    {
        return $this->verdict;
    }

    /**
     * @return bool
     */
    public function isPass(): bool
    {
        try {
            $verdict = $this->getVerdictStringLower();
            if ($verdict === 'pass' || $verdict === 'success') {
                return true;
            }
        } catch (\Throwable $ex) {}
        return false;
    }

    /**
     * @return bool
     */
    public function isFail(): bool
    {
        try {
            $verdict = $this->getVerdictStringLower();
            if ($verdict === 'fail' || $verdict === 'failed' || $verdict === 'failure') {
                return true;
            }
        } catch (\Throwable $ex) {}
        return false;
    }

    public function getVerdictStringLower(): string
    {
        $ret = '';
        try {
            $ret = strtolower($this->getVerdictString());
        } catch (\Throwable $ex) {}
        return $ret;
    }

    public function getVerdictString(): string
    {
        $ret = '';
        try {
            if ($this->getVerdict() !== null) {
                $ret = $this->getVerdict()->getName();
            } else {
                $ret = 'UNKNOWN';
            }
        } catch (\Throwable $ex) {}
        return $ret;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        try {
            if ($this->getVerdictStringLower() === 'error') {
                return true;
            }
        } catch (\Throwable $ex) {}
        return false;
    }

    /**
     * @param LogBookVerdict $verdict
     */
    public function setVerdict(LogBookVerdict $verdict): void
    {
        $this->verdict = $verdict;
    }

    /**
     * @return int
     */
    public function getExecutionOrder(): int
    {
        return $this->executionOrder;
    }

    /**
     * @param int $executionOrder
     */
    public function setExecutionOrder(int $executionOrder): void
    {
        $this->executionOrder = $executionOrder;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
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
    public function getTimeRun(): int
    {
        return $this->timeRun;
    }

    /**
     * @param int $timeRun
     */
    public function setTimeRun(int $timeRun): void
    {
        $this->timeRun = $timeRun;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return int
     */
    public function getDutUpTimeStart(): int
    {
        return $this->dutUpTimeStart;
    }

    /**
     * @param int $dutUpTimeStart
     */
    public function setDutUpTimeStart(int $dutUpTimeStart): void
    {
        $this->dutUpTimeStart = $dutUpTimeStart;
    }

    /**
     * @return int
     */
    public function getDutUpTimeEnd(): int
    {
        return $this->dutUpTimeEnd;
    }

    /**
     * @param int $dutUpTimeEnd
     */
    public function setDutUpTimeEnd(int $dutUpTimeEnd): void
    {
        $this->dutUpTimeEnd = $dutUpTimeEnd;
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
        $this->getCycle()->setDirty(true);
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

    /**
     * @return string
     */
    public function getLogFilesPath(): string
    {
        return $this->getCycle()->getLogFilesPath() . '/' . $this->getLogFile();
    }

    /**
     * @return SuiteExecution|null
     */
    public function getSuiteExecution(): ?SuiteExecution
    {
        return $this->suite_execution;
    }

    /**
     * @param SuiteExecution|null $suite_execution
     * @return LogBookTest
     */
    public function setSuiteExecution(?SuiteExecution $suite_execution): self
    {
        $this->suite_execution = $suite_execution;

        return $this;
    }

    public function toArray(): array
    {
        $ret = array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'verdict' => $this->getVerdict()->getName(),
            'meta_data' => $this->getMetaData(),
            'suite_execution' => $this->getSuiteExecution(),
            'cycle' => $this->getCycle()->getId(),
            'setup' => $this->getSetup()->getId(),
            'time_start' => $this->getTimeStart()->getTimestamp(),
            'time_end' => $this->getTimeEnd()->getTimestamp(),
            'time_run' => $this->getTimeRun(),
        );
        if ($this->getTestInfo() !== null && $this->getTestInfo()->getPath() !== null) {
            $ret['meta_data']['CONTROL_FILE_SHOW_OPT'] = $this->getTestInfo()->getPath();
        }
        if ($this->getTestType() !== null) {
            $ret['meta_data']['TEST_TYPE_SHOW_OPT'] = $this->getTestType()->getName();
        }  else {
            if (!array_key_exists('TEST_TYPE_SHOW_OPT', $ret['meta_data'])){
                $ret['meta_data']['TEST_TYPE_SHOW_OPT'] = 'TEST';
            }
        }
        return $ret;

    }

    /**
     * @return string
     */
    public function getTestKey(): string
    {
        return $this->testKey;
    }

    /**
     * @param string $testKey
     * @return LogBookTest
     */
    public function setTestKey(string $testKey): self
    {
        $this->testKey = $testKey;

        return $this;
    }

    public function getTestInfo(): ?LogBookTestInfo
    {
        return $this->testInfo;
    }

    public function setTestInfo(?LogBookTestInfo $testInfo): self
    {
        $this->testInfo = $testInfo;

        return $this;
    }

    public function getTestType(): ?LogBookTestType
    {
        return $this->testType;
    }

    public function setTestType(?LogBookTestType $testType): self
    {
        $this->testType = $testType;

        return $this;
    }

    public function getNewMetaData(): ?LogBookTestMD
    {
        return $this->newMetaData;
    }

    public function setNewMetaData(LogBookTestMD $newMetaData): self
    {
        $this->newMetaData = $newMetaData;

        // set the owning side of the relation if necessary
        if ($newMetaData->getTest() !== $this) {
            $newMetaData->setTest($this);
        }

        return $this;
    }
}
