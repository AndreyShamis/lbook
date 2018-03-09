<?php

namespace App\Tests\Utils;

use App\Utils\RandomString;
use PHPUnit\Framework\TestCase;

class RandomStringTest extends TestCase
{
    protected function findSpecial($value): bool
    {
        $chars = str_split(RandomString::$_special);
        $specialFound = false;
        foreach ($chars as $char){
            if(strpos($value, $char) !== false){
                $specialFound = true;
                break;
            }
        }
        return $specialFound;
    }

    public function testGenerateRandomString()
    {
        $length = 1200;
        $value = RandomString::generateRandomString($length);
        $this->assertEquals($length, strlen($value));

        $this->assertTrue($this->findSpecial($value) === false);
    }

    public function testGenerateRandomStringWithSpecial()
    {
        $length = 1200;
        $value = RandomString::generateRandomString($length, true);
        $this->assertEquals($length, strlen($value));

        $this->assertTrue($this->findSpecial($value));
    }

    public function testGenerateRandomStringShuffle()
    {
        $length = 1200;
        $value = RandomString::generateRandomStringShuffle($length);
        $this->assertEquals($length, strlen($value));

        $this->assertTrue($this->findSpecial($value) === false);
    }

    public function testGenerateRandomStringShuffleWithSpecial()
    {
        $length = 1200;
        $value = RandomString::generateRandomStringShuffle($length, true);
        $this->assertEquals($length, strlen($value));

        $this->assertTrue($this->findSpecial($value));
    }

    public function testGenerateRandomStringRange()
    {
        $length = 120;
        $value1 = RandomString::generateRandomStringRange($length);
        $value2 = RandomString::generateRandomStringRange($length);
        $this->assertEquals($length, strlen($value1));
        $this->assertEquals($length, strlen($value2));
        $this->assertTrue($value1 != $value2);
    }

    public function testGenerateRandomStringSha1()
    {
        $length = 120;
        $value1 = RandomString::generateRandomStringSha1($length);
        $value2 = RandomString::generateRandomStringSha1($length);
        $this->assertEquals($length, strlen($value1));
        $this->assertEquals($length, strlen($value2));
        $this->assertTrue($value1 != $value2);
    }

    public function testGenerateRandomStringMd5()
    {
        $length = 120;
        $value1 = RandomString::generateRandomStringMd5($length);
        $value2 = RandomString::generateRandomStringMd5($length);
        $this->assertEquals($length, strlen($value1));
        $this->assertEquals($length, strlen($value2));
        $this->assertTrue($value1 != $value2);
    }
}
