<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Event\User;

use eZ\Publish\API\Repository\Events\User\DeleteUserGroupEvent as DeleteUserGroupEventInterface;
use eZ\Publish\API\Repository\Values\User\UserGroup;
use Symfony\Contracts\EventDispatcher\Event;

final class DeleteUserGroupEvent extends Event implements DeleteUserGroupEventInterface
{
    /** @var \eZ\Publish\API\Repository\Values\User\UserGroup */
    private $userGroup;

    /** @var array */
    private $locations;

    public function __construct(
        array $locations,
        UserGroup $userGroup
    ) {
        $this->userGroup = $userGroup;
        $this->locations = $locations;
    }

    public function getUserGroup(): UserGroup
    {
        return $this->userGroup;
    }

    public function getLocations(): array
    {
        return $this->locations;
    }
}
