<?php


namespace App\Utils;


final class LogBookCommon
{

    public static function get(array $array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * @param string $str
     * @return int
     */
    public static function stringDigitsToInt (string $str) : int
    {
        preg_match_all('/\d+/', $str, $matches);
        return intval(implode($matches[0], ''));
    }

    /**
     * @param $char
     * @return int
     */
    public static function toNumber($char): int
    {
        if ($char) {
            return abs(ord(strtolower($char)) - 96);
        } else {
            return 0;
        }
    }

    /**
     * @param string $input
     * @return int
     */
    public static function stringToInt(string $input): int
    {
        $length = strlen($input);
        $digits = array();
        for ($i=0; $i<$length; $i++) {
            if (is_numeric($input[$i])) {
                $digits[$i] = intval($input[$i]);
            } else {
                $digits[$i] = LogBookCommon::toNumber($input[$i]);
            }
        }
        $totalSum = 0;
        $totalProd = 1;
        foreach($digits as $val) {
            $totalSum += intval($val);
            if (intval($val) > 0) {
                $totalProd *= intval($val);
            }
        }
        if ($totalSum === 0) {
            $totalSum = 1;
        }
        return $totalSum - ($totalProd % $totalSum);
    }
}