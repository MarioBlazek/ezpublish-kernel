<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\Role;

use eZ\Publish\API\Repository\Events\Role\UpdateRoleEvent as UpdateRoleEventInterface;
use eZ\Publish\API\Repository\Values\User\Role;
use eZ\Publish\API\Repository\Values\User\RoleUpdateStruct;
use Symfony\Contracts\EventDispatcher\Event;

final class UpdateRoleEvent extends Event implements UpdateRoleEventInterface
{
    /** @var \eZ\Publish\API\Repository\Values\User\Role */
    private $role;

    /** @var \eZ\Publish\API\Repository\Values\User\RoleUpdateStruct */
    private $roleUpdateStruct;

    /** @var \eZ\Publish\API\Repository\Values\User\Role */
    private $updatedRole;

    public function __construct(
        Role $updatedRole,
        Role $role,
        RoleUpdateStruct $roleUpdateStruct
    ) {
        $this->role = $role;
        $this->roleUpdateStruct = $roleUpdateStruct;
        $this->updatedRole = $updatedRole;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getRoleUpdateStruct(): RoleUpdateStruct
    {
        return $this->roleUpdateStruct;
    }

    public function getUpdatedRole(): Role
    {
        return $this->updatedRole;
    }
}
