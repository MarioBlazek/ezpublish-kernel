<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository\Events\Trash;

use eZ\Publish\SPI\Repository\Event\BeforeEvent;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\TrashItem;

interface BeforeRecoverEvent extends BeforeEvent
{
    public function getTrashItem(): TrashItem;

    public function getNewParentLocation(): ?Location;

    public function getLocation(): Location;

    public function setLocation(?Location $location): void;

    public function hasLocation(): bool;
}
