<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PreFlush;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LogBookTestRepository")
 * @ORM\Table(name="lbook_tests", uniqueConstraints={@ORM\UniqueConstraint(name="uniq_cycle_execution_order", columns={"execution_order", "cycle"})})
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
    protected $name = "";

    /**
     * @var DateTime
     *
     * @ORM\Column(name="time_start", type="datetime")
     * //@Assert\DateTime()
     */
    protected $timeStart;

    /**
     * @var DateTime
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
     * @ORM\JoinColumn(name="cycle", fieldName="id", referencedColumnName="id")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $cycle;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookMessage", mappedBy="test", cascade={"all"}, fetch="EXTRA_LAZY", )
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
    private $logFile = "";
    /**
     * @var integer
     * @ORM\Column(name="log_file_size", type="integer", length=11)
     */
    private $logFileSize = 0;

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


    public function getLogFile()
    {
        return $this->logFile;
    }

    public function setLogFile(string $logFile)
    {
        $this->logFile = $logFile;

        return $this;
    }

    public function setUploadedFile(UploadedFile $file)
    {
        $this->setLogFile($file->getPath());

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param mixed $logs
     */
    public function setLogs($logs): void
    {
        $this->logs = $logs;
    }


    /**
     * @PreFlush
     */
    public function calculateRunTime(){
        $this->setTimeRun($this->getTimeEnd()->getTimestamp() -$this->getTimeStart()->getTimestamp());
    }

    /**
     * @return mixed
     */
    public function getCycle()
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
     * @return mixed
     */
    public function getVerdict()
    {
//        if($this->verdict === null){
//            $this->verdict = new LogBookVerdict();
//        }
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
     * @return DateTime
     */
    public function getTimeStart(): DateTime
    {
        if($this->timeStart === null){
            $this->timeStart = new DateTime();
        }
        return $this->timeStart;
    }

    /**
     * @param DateTime $timeStart
     */
    public function setTimeStart(DateTime $timeStart): void
    {
        $this->timeStart = $timeStart;
    }

    /**
     * @return DateTime
     */
    public function getTimeEnd(): DateTime
    {
        if($this->timeEnd === null){
            $this->timeEnd = new DateTime();
        }
        return $this->timeEnd;
    }

    /**
     * @param DateTime $timeEnd
     */
    public function setTimeEnd(DateTime $timeEnd): void
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


}
