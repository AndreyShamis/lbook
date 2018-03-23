<?php

namespace App\Tests;

use App\Entity\LogBookTest;
use DateTime;
use PHPUnit\Framework\TestCase;

class LogBookTestTest extends TestCase
{
    /**
     * @param DateTime $timeStart
     * @param DateTime $timeEnd
     * @param bool $positive
     */
    protected function verify(DateTime $timeStart, DateTime $timeEnd, bool $positive = true): void
    {
        $test = new LogBookTest();
        $diff = $timeEnd->getTimestamp() - $timeStart->getTimestamp();
        $test->setTimeStart($timeStart);
        $test->setTimeEnd($timeEnd);
        $test->calculateRunTime();
        $runTime = $test->getTimeRun();
        if ($positive) {
            $this->assertSame($diff, $runTime);
        } else {
            $this->assertSame($diff, $runTime*-1);
        }
    }

    protected function rand($min = 0, $max = 0): int
    {
        try {
            return random_int($min, $max);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function testRunTimeCalculationPositiveDays(): void
    {
        for ($x=0; $x < 100; $x++) {
            $timeStart = new DateTime('+' . $this->rand(1, 10) . ' days');
            $timeEnd = new DateTime('+' . $this->rand(10, 100) . ' days');
            $this->verify($timeStart, $timeEnd);
        }
    }

    public function testRunTimeCalculationPositiveHours(): void
    {
        for ($x=0; $x < 1000; $x++) {
            $timeStart = new DateTime('+' . $this->rand(1, 1000) . ' hours');
            $timeEnd = new DateTime('+' . $this->rand(1000, 10000) . ' hours');
            $this->verify($timeStart, $timeEnd);
        }
    }

    public function testRunTimeCalculationPositiveMinutes(): void
    {
        for ($x=0; $x < 1000; $x++) {
            $timeStart = new DateTime('+' . $this->rand(1, 10000) . ' minutes');
            $timeEnd = new DateTime('+' . $this->rand(10000, 100000) . ' minutes');
            $this->verify($timeStart, $timeEnd);
        }
    }

    public function testRunTimeCalculationPositiveSeconds(): void
    {
        for ($x=0; $x < 1000; $x++) {
            $timeStart = new DateTime('+' . $this->rand(1, 100000) . ' seconds');
            $timeEnd = new DateTime('+' . $this->rand(100000, 1000000) . ' seconds');
            $this->verify($timeStart, $timeEnd);
        }
    }

    public function testRunTimeCalculationNegative(): void
    {
        for ($x=0; $x < 100; $x++) {
            $timeStart = new DateTime('+' . $this->rand(10, 100) . ' days');
            $timeEnd = new DateTime('+' . $this->rand(0, 10) . ' days');
            $this->verify($timeStart, $timeEnd, false);
        }
    }

    public function testRunTimeCalculationBoth(): void
    {
        for ($x=0; $x < 300; $x++) {
            $timeStart = new DateTime('+' . $this->rand(1, 100) . ' days');
            $timeEnd = new DateTime('+' . $this->rand(50, 100) . ' days');
            $diff = $timeEnd->getTimestamp() - $timeStart->getTimestamp();
            $this->verify($timeStart, $timeEnd, $diff >= 0);
        }
    }
}
