<?php
/**
 * Created by PhpStorm.
 * User: Andrey Shamis
 * Date: 03/03/18
 * Time: 14:55
 */

namespace App\Model;


abstract class Role
{
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_MANAGER = 'ROLE_MANAGER';
    const ROLE_USER = 'ROLE_USER';


    /** @var array user friendly named Roles */
    protected static $roles = [
        self::ROLE_SUPER_ADMIN => 'ROLE_SUPER_ADMIN',
        self::ROLE_ADMIN => 'ROLE_ADMIN',
        self::ROLE_MANAGER => 'ROLE_MANAGER',
        self::ROLE_USER  => 'ROLE_USER',
    ];

    /**
     * @param  string {
     * @return string
     */
    public static function getRoleName($roleName)
    {
        if (!isset(static::$roles[$roleName])) {
            return "Unknown type ($roleName)";
        }
        return static::$roles[$roleName];
    }

    /**
     * @return array<string>
     */
    public static function getAvailableRoles()
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_USER,
        ];
    }

    /**
     * @return array<string>
     */
    public static function getPreferredRoles()
    {
        return [
            self::ROLE_USER,
        ];
    }
}