<?php

namespace App\Entity;

use App\Utils\RandomString;
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
     * @ORM\ManyToOne(targetEntity="App\Entity\LogBookBuild", cascade={"persist"})
     * @ORM\JoinColumn(name="build", fieldName="id", referencedColumnName="id")
     */
    protected $build;

    /**
     * @var float
     *
     * @ORM\Column(name="pass_rate", type="float")
     */
    protected $passRate = 0;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     * //@Assert\DateTime()
     */
    protected $createdAt;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true, options={"default"="CURRENT_TIMESTAMP"})
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
    protected $uploadToken = "";

    /**
     * @var DateTime Time till token can be used
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

    function __construct()
    {
        $this->setUpdatedAt();
        $this->setCreatedAt();
        $this->setTokenExpiration(new \DateTime('+7 days'));
        $this->setUploadToken(RandomString::generateRandomString(50));
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
    public function setTestsPass(int $testsPass)
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
    public function setTestsFail(int $testsFail)
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
    public function setTestsError(int $testsError)
    {
        $this->testsError = $testsError;
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
    public function setTestsCount(int $testsCount)
    {
        $this->testsCount = $testsCount;
    }

    /**
     * @return DateTime
     */
    public function getTokenExpiration()
    {
        return $this->tokenExpiration;
    }

    /**
     * @param DateTime $tokenExpiration
     */
    public function setTokenExpiration(DateTime $tokenExpiration): void
    {
        $this->tokenExpiration = $tokenExpiration;
    }

    /**
     * @PreFlush
     */
    public function unsetDirty(){
        $this->setDirty(false);
    }

    /**
     * @PreFlush
     */
    public function updateTimes(){
        $testsTimeSum = 0;
        $min_time = new \DateTime('+100 years');
        $max_time = new \DateTime('-100 years');
        $tests = $this->getTests();
        if(is_object($tests)){
            foreach ($tests as $test){
                /** @var LogBookTest $test */
                $max_time = max($max_time, $test->getTimeEnd());
                $min_time = min($min_time, $test->getTimeStart());
                $testsTimeSum += $test->getTimeRun();
            }
        }
        else{
            $min_time = new \DateTime();
            $max_time = new \DateTime();
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
        $failCount = 0;
        $errorCount = 0;
        $tests = $this->getTests();
        $allCount = 0;
        if(is_object($tests)){
            $allCount = count($tests);
            foreach ($tests as $test){
                /** @var LogBookTest $test */
                if(strcasecmp($test->getVerdict(), "PASS") == 0){
                    $passCount++;
                }
                else if(strcasecmp($test->getVerdict(), "FAIL") == 0){
                    $failCount++;
                }
                else if(strcasecmp($test->getVerdict(), "ERROR") == 0){
                    $errorCount++;
                }
            }
        }
        $this->setTestsPass($passCount);
        $this->setTestsFail($failCount);
        $this->setTestsError($errorCount);
        $this->setTestsCount($allCount);
        if($allCount > 0){
            $this->setPassRate($this->getTestsPass()*100/$this->getTestsCount());
        }
        else{
            $this->setPassRate(100);
        }
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
     * @return mixed
     */
    public function getDut()
    {
        return $this->dut;
    }

    /**
     * @param mixed $dut
     */
    public function setDut($dut): void
    {
        $this->dut = $dut;
    }

    /**
     * @return mixed
     */
    public function getTargetUploader()
    {
        return $this->targetUploader;
    }

    /**
     * @param mixed $targetUploader
     */
    public function setTargetUploader($targetUploader): void
    {
        $this->targetUploader = $targetUploader;
    }

    /**
     * @return mixed
     */
    public function getController()
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
    public function getBuild()
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
     * @return DateTime
     */
    public function getTimeStart()
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
    public function getTimeEnd()
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
    public function getCreatedAt() : DateTime
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
    public function getUpdatedAt() : DateTime
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



}
