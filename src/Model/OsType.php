<?php
/**
 * User: Andrey Shamis
 * Date: 18/02/18
 * Time: 08:15
 */

namespace App\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class OsType
{
    const OS_UNKNOWN = 0;
    const OS_LINUX = 1;
    const OS_WINDOWS = 2;
    const OS_ANDROID = 3;
    const OS_FREEBSD = 4;

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
            return "Unknown type ($typeShortName)";
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