<?php
/**
 * User: Andrey Shamis
 * Date: 29/12/18
 * Time: 13:59
 */

namespace App\Model;


abstract class EventStatus
{
    public const CREATED = 1;
    public const PROGRESS = 2;
    public const FINISH = 3;

    /** @var array friendly named Status */
    protected static $status_list = [
        self::CREATED => 'CREATED',
        self::PROGRESS => 'PROGRESS',
        self::FINISH => 'FINISH',
    ];

    /**
     * @param  string {
     * @return string
     */
    public static function getStatusName(int $status_int): string
    {
        if (!isset(static::$status_list[$status_int])) {
            return "Unknown type ($status_int)";
        }
        return static::$status_list[$status_int];
    }

    /**
     * @return array<integer>
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::CREATED,
            self::PROGRESS,
            self::FINISH,
        ];
    }

    /**
     * @return array<integer>
     */
    public static function getPreferredStatuses(): array
    {
        return [
            self::CREATED,
        ];
    }
}