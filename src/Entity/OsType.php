<?php
/**
 * User: Andrey Shamis
 * Date: 18/02/18
 * Time: 08:15
 */

namespace App\Entity;

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

    public function __construct($id = 0)
    {
        $this->os = $this::getTypeName($id);
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="os", type="smallint", nullable=true)
     */
    protected $os = "";

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

    /**
     * @return int
     */
    public function getOs(): int
    {
        return $this->os;
    }

    /**
     * @param int $os
     */
    public function setOs(int $os): void
    {
        $this->os = $os;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this::getTypeName($this->os);
    }
}