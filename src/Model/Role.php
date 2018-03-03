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
    const ROLE_SUPER_ADMIN = '0';
    const ROLE_ADMIN = '1';
    const ROLE_MANAGER = '2';
    const ROLE_USER = '3';


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
     * @return array<integer>
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
     * @return array<integer>
     */
    public static function getPreferredRoles()
    {
        return [
            self::ROLE_USER,
        ];
    }
}