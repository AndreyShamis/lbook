<?php

namespace App\Entity;

use DateTime;
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
    protected $name = "";

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LogBookTest", mappedBy="cycle", cascade={"all"}, fetch="EXTRA_LAZY")
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
     * @var float
     *
     * @ORM\Column(name="pass_rate", type="float")
     */
    protected $passRate = 0;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     * //@Assert\DateTime()
     */
    protected $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     * //@Assert\DateTime()
     */
    protected $updatedAt;

    /**
     * @var DateTime Took MIN time from tests
     *
     * @ORM\Column(name="time_start", type="datetime", options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $timeStart;

    /**
     * @var DateTime Took MAX time from tests
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
     * @return DateTime
     */
    public function getTimeStart(): DateTime
    {
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
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @param int $period
     */
    public function setPeriod(int $period): void
    {
        $this->period = $period;
    }


    /**
     * @PreFlush
     */
    public function updateTimes(){
        $testsTimeSum = 0;
        $min_time = new \DateTime('+100 years');
        $max_time = new \DateTime('-100 years');
        foreach ($this->getTests() as $test){
            /** @var LogBookTest $test */
            $max_time = max($max_time, $test->getTimeEnd());
            $min_time = min($min_time, $test->getTimeStart());
            $testsTimeSum += $test->getTimeRun();
        }
        $this->setTimeStart($min_time);
        $this->setTimeEnd($max_time);
        $this->setPeriod($this->getTimeEnd()->getTimestamp() -$this->getTimeStart()->getTimestamp());
        $this->setTestsTimeSum($testsTimeSum);
    }

    /**
     * @PreFlush
     */
    public function updatePassRate(){
        $passCount = 0;
        $allCount = count($this->getTests());
        foreach ($this->getTests() as $test){
            /** @var LogBookTest $test */
            if($test->getVerdict() == "PASS"){
                $passCount++;
            }
        }
        if($allCount > 0){
            $this->setPassRate($passCount*100/$allCount);
        }
        else{
            $this->setPassRate(100);
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
     * @return mixed
     */
    public function getTests()
    {
        return $this->tests;
    }

    /**
     * @param mixed $tests
     */
    public function setTests($tests): void
    {
        $this->tests = $tests;
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
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return mixed
     */
    public function getSetup()
    {
        return $this->setup;
    }

    /**
     * @param mixed $setup
     */
    public function setSetup( $setup): void
    {
        $this->setup = $setup;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
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
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @PreFlush
     */
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTime();
    }



}
