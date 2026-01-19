<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 18/02/18
 * Time: 08:15
 */

namespace App\Model;

abstract class OsType
{
    public const OS_UNKNOWN = 0;
    public const OS_LINUX = 1;
    public const OS_WINDOWS = 2;
    public const OS_ANDROID = 3;
    public const OS_FREEBSD = 4;

    /** @var array user friendly named type */
    protected static $typeName = [
        self::OS_LINUX    => 'Linux',
        self::OS_WINDOWS => 'Windows',
        self::OS_ANDROID => 'Android',
        self::OS_FREEBSD  => 'FreeBSD',
        self::OS_UNKNOWN  => 'Unknown',
    ];

    /**
     * @param  string $typeShortName
     * @return string
     */
    public static function getTypeName($typeShortName): string
    {
        if (!isset(static::$typeName[$typeShortName])) {
            return static::$typeName[static::OS_UNKNOWN];
        }
        return static::$typeName[$typeShortName];
    }

    /**
     * @return array<integer>
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::OS_UNKNOWN,
            self::OS_LINUX,
            self::OS_WINDOWS,
            self::OS_ANDROID,
            self::OS_FREEBSD,
        ];
    }

    /**
     * @return array<integer>
     */
    public static function getPreferredTypes(): array
    {
        return [
            self::OS_LINUX,
        ];
    }
}