<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PreFlush;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookTestRepository")
 * @ORM\Table(name="lbook_tests", uniqueConstraints={@ORM\UniqueConstraint(name="test_uniq_cycle_execution_order", columns={"execution_order", "cycle"})})
 * @ORM\HasLifecycleCallbacks()
 */
class LogBookTest
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
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookMessage", mappedBy="test", fetch="EXTRA_LAZY")
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
     * LogBookTest constructor.
     */
    public function __construct()
    {
        $this->timeStart = new \DateTime();
        $this->timeEnd = new \DateTime();
        $this->logs = new ArrayCollection();
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
    public function getCycle(): LogBookCycle
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
     * @return LogBookVerdict
     */
    public function getVerdict(): ?LogBookVerdict
    {
        return $this->verdict;
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

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
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
        if ($this->disabled !== $disabled) {
            $this->disabled = $disabled;
            $this->getCycle()->setDirty(true);
        }
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
