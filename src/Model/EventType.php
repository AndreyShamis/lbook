<?php
/**
 * User: Andrey Shamis
 * Date: 29/12/18
 * Time: 14:02
 */

namespace App\Model;


use PhpParser\Node\Expr\Cast\Int_;

abstract class EventType
{
    public const UNDEFINED = 0;
    public const DELETE_CYCLE = 1;
    public const DELETE_SETUP = 2;
    public const FINISH = 3;
    public const CLEAR_LOG_DEBUG = 4;
    public const CLEAR_LOG_INFO = 5;
    public const CLEAR_LOG_ALL = 6;

    /** @var array friendly named Event Types */
    protected static $event_types_list = [
        self::UNDEFINED => 'UNDEFINED',
        self::DELETE_CYCLE => 'DELETE_CYCLE',
        self::DELETE_SETUP => 'DELETE_SETUP',
        self::FINISH => 'FINISH',
        self::CLEAR_LOG_DEBUG => 'CLEAR_LOG_DEBUG',
        self::CLEAR_LOG_INFO => 'CLEAR_LOG_INFO',
        self::CLEAR_LOG_ALL => 'CLEAR_LOG_ALL',
    ];

    /**
     * @param  int {
     * @return string
     */
    public static function getTypeName(int $value_int): string
    {
        if (!isset(static::$event_types_list[$value_int])) {
            return "Unknown type ($value_int)";
        }
        return static::$event_types_list[$value_int];
    }

    /**
     * @return array<integer>
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::UNDEFINED,
            self::DELETE_CYCLE,
            self::DELETE_SETUP,
            self::FINISH,
            self::CLEAR_LOG_DEBUG,
            self::CLEAR_LOG_INFO,
            self::CLEAR_LOG_ALL,
        ];
    }

    /**
     * @return array<integer>
     */
    public static function getPreferredTypes(): array
    {
        return [
            self::UNDEFINED,
        ];
    }
}