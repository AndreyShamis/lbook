<?php
/**
 * User: Andrey Shamis
 * Date: 09/03/18
 * Time: 08:09
 */

namespace App\Utils;


/**
 * Class RandomString
 * Is some place used rand : according to http://php.net/manual/en/function.rand.php
 *           "7.1.0 rand() has been made an alias of mt_rand()."
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
    public static function generateRandomString($length = 20, $useSpecial = false): String
    {
        if($length < 1){
            $length = 1;
        }
        if($useSpecial){
            $chars = RandomString::$chars . RandomString::$special;
        }
        else{
            $chars = RandomString::$chars;
        }

        $charsSize = strlen($chars);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $chars[rand(0, $charsSize - 1)];
        }
        return $randomString;
    }

    /**
     * @param int $length
     * @param bool $useSpecial
     * @return String
     */
    public static function generateRandomStringShuffle($length = 20, $useSpecial = false): String
    {
        if($useSpecial){
            $chars = RandomString::$chars . RandomString::$special;
        }
        else{
            $chars = RandomString::$chars;
        }
        return substr(str_shuffle(str_repeat($x=$chars, ceil($length/strlen($x)) )),1, $length);
    }

    /**
     * @param int $length
     * @return String
     */
    public static function generateRandomStringRange($length = 20): String
    {
        $keys = array_merge(range(0,9), range('a', 'z'));
        $key = "";
        $charsSize = count($keys);
        for($i=0; $i < $length; $i++) {
            $key .= $keys[rand(0, $charsSize - 1)];
        }
        return $key;
    }

    /**
     * @param int $length
     * @return String
     */
    public static function generateRandomStringSha1($length = 20): String
    {
        $tmp_ret = "";
        while(strlen($tmp_ret) < $length){
            $tmp_ret .= sha1(rand());
        }

        return substr($tmp_ret, 0, $length);
    }

    /**
     * @param int $length
     * @return String
     */
    public static function generateRandomStringMd5($length = 20): String
    {
        $tmp_ret = "";
        while(strlen($tmp_ret) < $length){
            $tmp_ret .= md5(rand() . time());
        }

        return substr($tmp_ret, 0, $length);
    }
}