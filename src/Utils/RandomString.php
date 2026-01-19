<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 09/03/18
 * Time: 08:09
 */

namespace App\Utils;

/**
 * Class RandomString
 * @package App\Utils
 */
final class RandomString
{
    public static $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public static $special = '~!@#$%^&*(){}[],./?';

    /**
     * @param int $length
     * @param bool $useSpecial
     * @return String
     */
    public static function generateRandomString($length = 20, $useSpecial = false): string
    {
        if ($length < 1) {
            $length = 1;
        }
        if ($useSpecial) {
            $chars = self::$chars . self::$special;
        } else {
            $chars = self::$chars;
        }

        $charsSize = \strlen($chars);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            try {
                $randomString .= $chars[random_int(0, $charsSize - 1)];
            } catch (\Exception $e) {
                $randomString .= $i;
            }
        }
        return $randomString;
    }

    /**
     * @param int $length
     * @param bool $useSpecial
     * @return String
     */
    public static function generateRandomStringShuffle($length = 20, $useSpecial = false): string
    {
        if ($useSpecial) {
            $chars = self::$chars . self::$special;
        } else {
            $chars = self::$chars;
        }
        return substr(str_shuffle(str_repeat($x=$chars, ceil($length/\strlen($x)) )),1, $length);
    }

    /**
     * @param int $length
     * @return String
     */
    public static function generateRandomStringRange($length = 20): string
    {
        $keys = array_merge(range(0,9), range('a', 'z'));
        $key = '';
        $charsSize = \count($keys);
        for ($i=0; $i < $length; $i++) {
            try {
                $key .= $keys[random_int(0, $charsSize - 1)];
            } catch (\Exception $e) {
                $key .= $i;
            }
        }
        return $key;
    }

    /**
     * @param int $length
     * @return String
     */
    public static function generateRandomStringSha1($length = 20): string
    {
        $tmp_ret = '';
        while (\strlen($tmp_ret) < $length) {
            $tmp_ret .= sha1(mt_rand());
        }

        return substr($tmp_ret, 0, $length);
    }

    /**
     * @param int $length
     * @return String
     */
    public static function generateRandomStringMd5($length = 20): string
    {
        $tmp_ret = '';
        while (\strlen($tmp_ret) < $length) {
            $tmp_ret .= md5(mt_rand() . time());
        }

        return substr($tmp_ret, 0, $length);
    }
}