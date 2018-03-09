<?php

namespace App\Tests;

use App\Entity\LogBookTest;
use DateTime;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class LogBookTestTest extends TestCase
{
    /**
     * @param DateTime $timeStart
     * @param DateTime $timeEnd
     * @param bool $positive
     * @throws AssertionFailedError
     */
    protected function verify(DateTime &$timeStart, DateTime &$timeEnd, bool $positive = true)
    {
        $test = new LogBookTest();
        $diff = $timeEnd->getTimestamp() - $timeStart->getTimestamp();
        $test->setTimeStart($timeStart);
        $test->setTimeEnd($timeEnd);
        $test->calculateRunTime();
        $runTime = $test->getTimeRun();
        if($positive){
            $this->assertTrue($diff === $runTime);
        }
        else{
            $this->assertTrue($diff === ($runTime*-1));
        }
    }

    public function testRunTimeCalculationPositiveDays()
    {
        for($x=0; $x < 100; $x++){
            $timeStart = new DateTime('+' . rand(1, 10) . ' days');
            $timeEnd = new DateTime('+' . rand(10, 100) . ' days');
            $this->verify($timeStart, $timeEnd);
        }
    }

    public function testRunTimeCalculationPositiveHours()
    {
        for($x=0; $x < 1000; $x++){
            $timeStart = new DateTime('+' . rand(1, 1000) . ' hours');
            $timeEnd = new DateTime('+' . rand(1000, 10000) . ' hours');
            $this->verify($timeStart, $timeEnd);
        }
    }

    public function testRunTimeCalculationPositiveMinutes()
    {
        for($x=0; $x < 1000; $x++){
            $timeStart = new DateTime('+' . rand(1, 10000) . ' minutes');
            $timeEnd = new DateTime('+' . rand(10000, 100000) . ' minutes');
            $this->verify($timeStart, $timeEnd);
        }
    }

    public function testRunTimeCalculationPositiveSeconds()
    {
        for($x=0; $x < 1000; $x++){
            $timeStart = new DateTime('+' . rand(1, 100000) . ' seconds');
            $timeEnd = new DateTime('+' . rand(100000, 1000000) . ' seconds');
            $this->verify($timeStart, $timeEnd);
        }
    }

    public function testRunTimeCalculationNegative()
    {
        for($x=0; $x < 100; $x++){
            $timeStart = new DateTime('+' . rand(10, 100) . ' days');
            $timeEnd = new DateTime('+' . rand(0, 10) . ' days');
            $this->verify($timeStart, $timeEnd, false);
        }
    }

    public function testRunTimeCalculationBoth()
    {
        for($x=0; $x < 300; $x++){
            $timeStart = new DateTime('+' . rand(1, 100) . ' days');
            $timeEnd = new DateTime('+' . rand(50, 100) . ' days');
            $diff = $timeEnd->getTimestamp() - $timeStart->getTimestamp();
            $this->verify($timeStart, $timeEnd, $diff < 0 ? false : true);
        }
    }
}
