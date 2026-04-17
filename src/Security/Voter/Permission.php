<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\Security\Voter;

/**
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * @package <https://github.com/Agbokoudjo/>
 */
interface Permission
{
    public const PERMISSION_VIEW = 'VIEW';
    public const PERMISSION_EDIT = 'EDIT';
    public const PERMISSION_HISTORY = 'HISTORY';
    public const PERMISSION_CREATE = 'CREATE';
    public const PERMISSION_DELETE = 'DELETE';
    public const PERMISSION_UNDELETE = 'UNDELETE';
    public const PERMISSION_LIST = 'LIST';
    public const PERMISSION_EXPORT = 'EXPORT';
    public const PERMISSION_OPERATOR = 'OPERATOR';
    public const PERMISSION_MASTER = 'MASTER';
    public const PERMISSION_OWNER = 'OWNER';

    /**
     * @var array
     */
    public const ADMIN_PERMISSIONS = [
        self::PERMISSION_CREATE,
        self::PERMISSION_LIST,
        self::PERMISSION_EXPORT,
        self::PERMISSION_OPERATOR,
        self::PERMISSION_MASTER
    ];

    /**
     * @var array
     */
    public const OBJECT_PERMISSIONS = [
        self::PERMISSION_VIEW,
        self::PERMISSION_EDIT,
        self::PERMISSION_HISTORY,
        self::PERMISSION_DELETE,
        self::PERMISSION_UNDELETE,
        self::PERMISSION_OPERATOR,
        self::PERMISSION_MASTER,
        self::PERMISSION_OWNER
    ];

    public const OBJECT_PERMISSION_CRUD = [
        self::PERMISSION_CREATE,
        self::PERMISSION_VIEW,
        self::PERMISSION_EDIT,
        self::PERMISSION_DELETE,
    ];

    public const ADMIN_USER_PERMISSION = [
        self::PERMISSION_CREATE,
        self::PERMISSION_VIEW,
        self::PERMISSION_EDIT,
        self::PERMISSION_DELETE,
        self::PERMISSION_UNDELETE,
        self::PERMISSION_OPERATOR,
        self::PERMISSION_MASTER,
        self::PERMISSION_OWNER,
        self::PERMISSION_HISTORY,
        self::PERMISSION_LIST,
    ];
}
