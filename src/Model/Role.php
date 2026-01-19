<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 03/03/18
 * Time: 14:55
 */

namespace App\Model;

abstract class Role
{
    protected const ROLE_SUPER_ADMIN = '0';
    protected const ROLE_ADMIN = '1';
    protected const ROLE_MANAGER = '2';
    protected const ROLE_USER = '3';
    protected const ROLE_FILTER_CREATOR = '4';

    /** @var array user friendly named Roles */
    protected static $roles = [
        self::ROLE_SUPER_ADMIN => 'ROLE_SUPER_ADMIN',
        self::ROLE_ADMIN => 'ROLE_ADMIN',
        self::ROLE_MANAGER => 'ROLE_MANAGER',
        self::ROLE_USER  => 'ROLE_USER',
        self::ROLE_FILTER_CREATOR  => 'ROLE_FILTER_CREATOR',
    ];

    /**
     * @param  string {
     * @return string
     */
    public static function getRoleName($roleName): string
    {
        if (!isset(static::$roles[$roleName])) {
            return "Unknown type ($roleName)";
        }
        return static::$roles[$roleName];
    }

    /**
     * @return array<integer>
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_FILTER_CREATOR,
            self::ROLE_USER,
        ];
    }

    /**
     * @return array<integer>
     */
    public static function getPreferredRoles(): array
    {
        return [
            self::ROLE_USER,
        ];
    }
}