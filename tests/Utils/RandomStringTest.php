<?php

namespace App\Tests\Utils;

use App\Utils\RandomString;
use PHPUnit\Framework\TestCase;

class RandomStringTest extends TestCase
{
    protected function findSpecial($value): bool
    {
        $chars = str_split(RandomString::$special);
        $specialFound = false;
        foreach ($chars as $char) {
            if (strpos($value, $char) !== false) {
                $specialFound = true;
                break;
            }
        }
        return $specialFound;
    }

    public function testGenerateRandomString(): void
    {
        $length = 1200;
        $value1 = RandomString::generateRandomString($length);
        $this->assertEquals($length, \strlen($value1));
        $this->assertFalse($this->findSpecial($value1));

        $value2 = RandomString::generateRandomString($length);
        $this->assertEquals($length, \strlen($value2));
        $this->assertFalse($this->findSpecial($value2));

        $this->assertNotEquals($value1, $value2);
    }

    public function testGenerateRandomStringWithSpecial(): void
    {
        $length = 1200;
        $value1 = RandomString::generateRandomString($length, true);
        $this->assertEquals($length, \strlen($value1));
        $this->assertTrue($this->findSpecial($value1));

        $value2 = RandomString::generateRandomString($length, true);
        $this->assertEquals($length, \strlen($value2));
        $this->assertTrue($this->findSpecial($value2));

        $this->assertNotEquals($value1, $value2);
    }

    public function testGenerateRandomStringShuffle(): void
    {
        $length = 1200;
        $value1 = RandomString::generateRandomStringShuffle($length);
        $this->assertEquals($length, \strlen($value1));
        $this->assertFalse($this->findSpecial($value1));

        $value2 = RandomString::generateRandomStringShuffle($length);
        $this->assertEquals($length, \strlen($value2));
        $this->assertFalse($this->findSpecial($value2));

        $this->assertNotEquals($value1, $value2);
    }

    public function testGenerateRandomStringShuffleWithSpecial(): void
    {
        $length = 1200;
        $value1 = RandomString::generateRandomStringShuffle($length, true);
        $this->assertEquals($length, \strlen($value1));
        $this->assertTrue($this->findSpecial($value1));

        $value2 = RandomString::generateRandomStringShuffle($length, true);
        $this->assertEquals($length, \strlen($value2));
        $this->assertTrue($this->findSpecial($value2));

        $this->assertNotEquals($value1, $value2);
    }

    public function testGenerateRandomStringRange(): void
    {
        $length = 120;
        $value1 = RandomString::generateRandomStringRange($length);
        $value2 = RandomString::generateRandomStringRange($length);
        $this->assertEquals($length, \strlen($value1));
        $this->assertEquals($length, \strlen($value2));

        $this->assertNotEquals($value1, $value2);
    }

    public function testGenerateRandomStringSha1(): void
    {
        $length = 120;
        $value1 = RandomString::generateRandomStringSha1($length);
        $value2 = RandomString::generateRandomStringSha1($length);
        $this->assertEquals($length, \strlen($value1));
        $this->assertEquals($length, \strlen($value2));

        $this->assertNotEquals($value1, $value2);
    }

    public function testGenerateRandomStringMd5(): void
    {
        $length = 120;
        $value1 = RandomString::generateRandomStringMd5($length);
        $value2 = RandomString::generateRandomStringMd5($length);
        $this->assertEquals($length, \strlen($value1));
        $this->assertEquals($length, \strlen($value2));

        $this->assertNotEquals($value1, $value2);
    }
}
